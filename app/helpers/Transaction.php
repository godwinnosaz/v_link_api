<?php

class Transaction
{
    const DEFAULT_SIGNATURE = [
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
    ];

    const SIGNATURE_LENGTH = 64;
    const PACKET_DATA_SIZE = 1280 - 40 - 8;

    public array $signatures;
    public ?string $recentBlockhash;
    public ?NonceInformation $nonceInformation;
    public ?PublicKey $feePayer;
    public array $instructions = [];

    public function __construct(
        ?string $recentBlockhash = null,
        ?NonceInformation $nonceInformation = null,
        ?PublicKey $feePayer = null,
        ?array $signatures = []
    )
    {
        $this->recentBlockhash = $recentBlockhash;
        $this->nonceInformation = $nonceInformation;
        $this->feePayer = $feePayer;
        $this->signatures = $signatures;
    }

    public function signature(): ?string
    {
        if (sizeof($this->signatures)) {
            return $this->signatures[0]->signature;
        }

        return null;
    }

    public function add(...$items): Transaction
    {
        foreach ($items as $item) {
            if ($item instanceof TransactionInstruction) {
                $this->instructions[] = $item;
            } elseif ($item instanceof Transaction) {
                array_push($this->instructions, ...$item->instructions);
            } else {
                throw new InputValidationException("Invalid parameter to add(). Only Transaction and TransactionInstruction are allowed.");
            }
        }

        return $this;
    }

    public function compileMessage(): Message
    {
        $nonceInfo = $this->nonceInformation;

        if ($nonceInfo && sizeof($this->instructions) && $this->instructions[0] !== $nonceInfo->nonceInstruction) {
            $this->recentBlockhash = $nonceInfo->nonce;
            array_unshift($this->instructions, $nonceInfo->nonceInstruction);
        }

        $recentBlockhash = $this->recentBlockhash;
        if (!$recentBlockhash) {
            throw new InputValidationException('Transaction recentBlockhash required.');
        } elseif (!sizeof($this->instructions)) {
            throw new InputValidationException('No instructions provided.');
        }

        if ($this->feePayer) {
            $feePayer = $this->feePayer;
        } elseif (sizeof($this->signatures) && $this->signatures[0]->getPublicKey()) {
            $feePayer = $this->signatures[0]->getPublicKey();
        } else {
            throw new InputValidationException('Transaction fee payer required.');
        }

        $programIds = [];
        $accountMetas = [];

        foreach ($this->instructions as $i => $instruction) {
            if (!$instruction->programId) {
                throw new InputValidationException("Transaction instruction index {$i} has undefined program id.");
            }

            array_push($accountMetas, ...$instruction->keys);

            $programId = $instruction->programId->toBase58();
            if (!in_array($programId, $programIds)) {
                array_push($programIds, $programId);
            }
        }

        foreach ($programIds as $programId) {
            array_push($accountMetas, new AccountMeta(
                new PublicKey($programId),
                false,
                false
            ));
        }

        usort($accountMetas, function (AccountMeta $x, AccountMeta $y) {
            if ($x->isSigner !== $y->isSigner) {
                return $x->isSigner ? -1 : 1;
            }

            if ($x->isWritable !== $y->isWritable) {
                return $x->isWritable ? -1 : 1;
            }

            return 0;
        });

        $uniqueMetas = [];
        foreach ($accountMetas as $accountMeta) {
            $eachPublicKey = $accountMeta->getPublicKey();
            $uniqueIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $eachPublicKey);

            if ($uniqueIndex > -1) {
                $uniqueMetas[$uniqueIndex]->isWritable = $uniqueMetas[$uniqueIndex]->isWritable || $accountMeta->isWritable;
            } else {
                array_push($uniqueMetas, $accountMeta);
            }
        }

        $feePayerIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $feePayer);
        if ($feePayerIndex > -1) {
            list($payerMeta) = array_splice($uniqueMetas, $feePayerIndex, 1);
            $payerMeta->isSigner = true;
            $payerMeta->isWritable = true;
            array_unshift($uniqueMetas, $payerMeta);
        } else {
            array_unshift($uniqueMetas, new AccountMeta($feePayer, true, true));
        }

        foreach ($this->signatures as $signature) {
            $uniqueIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $signature);
            if ($uniqueIndex > -1) {
                $uniqueMetas[$uniqueIndex]->isSigner = true;
            } else {
                throw new InputValidationException("Unknown signer: {$signature->getPublicKey()->toBase58()}");
            }
        }

        $numRequiredSignatures = 0;
        $numReadonlySignedAccounts = 0;
        $numReadonlyUnsignedAccounts = 0;

        $signedKeys = [];
        $unsignedKeys = [];

        foreach ($uniqueMetas as $accountMeta) {
            if ($accountMeta->isSigner) {
                array_push($signedKeys, $accountMeta->getPublicKey()->toBase58());
                $numRequiredSignatures++;
                if (!$accountMeta->isWritable) {
                    $numReadonlySignedAccounts++;
                }
            } else {
                array_push($unsignedKeys, $accountMeta->getPublicKey()->toBase58());
                if (!$accountMeta->isWritable) {
                    $numReadonlyUnsignedAccounts++;
                }
            }
        }

        if (!$this->signatures) {
            $this->signatures = array_map(function ($signedKey) {
                return new SignaturePubkeyPair(new PublicKey($signedKey), null);
            }, $signedKeys);
        }

        $accountKeys = array_merge($signedKeys, $unsignedKeys);

        $instructions = array_map(function (TransactionInstruction $instruction) use ($accountKeys) {
            $programIdIndex = array_search($instruction->programId->toBase58(), $accountKeys);
            $encodedData = $instruction->data;
            $accounts = array_map(function (AccountMeta $meta) use ($accountKeys) {
                return array_search($meta->getPublicKey()->toBase58(), $accountKeys);
            }, $instruction->keys);
            return new CompiledInstruction(
                $programIdIndex,
                $accounts,
                $encodedData
            );
        }, $this->instructions);

        return new Message(
            new MessageHeader(
                $numRequiredSignatures,
                $numReadonlySignedAccounts,
                $numReadonlyUnsignedAccounts
            ),
            $accountKeys,
            $recentBlockhash,
            $instructions
        );
    }

    public function serializeMessage(): string
    {
        return $this->compileMessage()->serialize();
    }

    public function setSigners(...$signers)
    {
        $uniqueSigners = $this->arrayUnique($signers);

        $this->signatures = array_map(function (PublicKey $signer) {
            return new SignaturePubkeyPair($signer, null);
        }, $uniqueSigners);
    }

    public function addSigner(Keypair $signer)
    {
        $message = $this->compileMessage();
        $signData = $message->serialize();
        $signature = sodium_crypto_sign_detached($signData, $this->toSecretKey($signer));
        $this->_addSignature($signer->getPublicKey(), $signature);
    }

    public function sign(...$signers): void
    {
        $this->partialSign(...$signers);
    }

    public function partialSign(...$signers): void
    {
        $uniqueSigners = $this->arrayUnique($signers);

        $this->signatures = array_map(function ($signer) {
            return new SignaturePubkeyPair($this->toPublicKey($signer), null);
        }, $uniqueSigners);

        $message = $this->compileMessage();
        $signData = $message->serialize();

        foreach ($uniqueSigners as $signer) {
            if ($signer instanceof Keypair) {
                $signature = sodium_crypto_sign_detached($signData, $this->toSecretKey($signer));
                if (strlen($signature) !== self::SIGNATURE_LENGTH) {
                    throw new InputValidationException('Signature has invalid length.');
                }
                $this->_addSignature($signer->getPublicKey(), $signature);
            }
        }
    }

    public function addSignature(PublicKey $pubkey, string $signature): void
    {
        if (strlen($signature) !== self::SIGNATURE_LENGTH) {
            throw new InputValidationException('Signature has invalid length.');
        }

        $index = array_search($pubkey->toBase58(), array_map(function ($signature) {
            return $signature->getPublicKey()->toBase58();
        }, $this->signatures));

        if ($index !== false) {
            $this->signatures[$index] = new SignaturePubkeyPair($pubkey, $signature);
        } else {
            throw new InputValidationException("Unknown signer: {$pubkey->toBase58()}");
        }
    }

    public function verifySignatures(string $signData): bool
    {
        foreach ($this->signatures as $signature) {
            if (!$signature->verify($signData)) {
                return false;
            }
        }
        return true;
    }

    public function serialize(): string
    {
        $signatures = array_map(function (SignaturePubkeyPair $pair) {
            return $pair->signature;
        }, $this->signatures);

        $serializedMessage = $this->serializeMessage();

        return pack('N', sizeof($signatures)) . implode('', $signatures) . $serializedMessage;
    }

    public static function from(string $buffer): Transaction
    {
        $instance = new self();

        return self::populate($instance, $buffer);
    }

    public static function populate(Transaction $transaction, string $buffer): Transaction
    {
        $signatureCount = unpack('N', substr($buffer, 0, 4))[1];

        $offset = 4;
        $signatures = [];
        for ($i = 0; $i < $signatureCount; $i++) {
            $signature = substr($buffer, $offset, self::SIGNATURE_LENGTH);
            $signatures[] = $signature === self::DEFAULT_SIGNATURE ? null : $signature;
            $offset += self::SIGNATURE_LENGTH;
        }

        $message = Message::fromBinary(substr($buffer, $offset));

        $transaction->recentBlockhash = $message->recentBlockhash;
        $transaction->feePayer = $message->getAccountKeys()[0];
        $transaction->instructions = $message->instructions;
        $transaction->signatures = array_map(function ($signer, $publicKey) {
            return new SignaturePubkeyPair($publicKey, $signer);
        }, $signatures, $message->getAccountKeys());

        return $transaction;
    }

    private function arraySearchAccountMetaForPublicKey(array $accountMetas, PublicKey $publicKey): int
    {
        foreach ($accountMetas as $index => $meta) {
            if ($meta->getPublicKey()->equals($publicKey)) {
                return $index;
            }
        }
        return -1;
    }

    private function arrayUnique(array $signers): array
    {
        $uniqueSigners = [];
        foreach ($signers as $signer) {
            if (!in_array($signer, $uniqueSigners, true)) {
                $uniqueSigners[] = $signer;
            }
        }
        return $uniqueSigners;
    }

    private function _addSignature(PublicKey $pubkey, string $signature): void
    {
        $index = array_search($pubkey->toBase58(), array_map(function ($signature) {
            return $signature->getPublicKey()->toBase58();
        }, $this->signatures));

        if ($index !== false) {
            $this->signatures[$index] = new SignaturePubkeyPair($pubkey, $signature);
        } else {
            throw new InputValidationException("Unknown signer: {$pubkey->toBase58()}");
        }
    }

    private function toPublicKey($signer): PublicKey
    {
        if ($signer instanceof Keypair) {
            return $signer->getPublicKey();
        } elseif ($signer instanceof PublicKey) {
            return $signer;
        }

        throw new InputValidationException('Invalid signer.');
    }

    private function toSecretKey(Keypair $signer): string
    {
        return $signer->getSecretKey();
    }
}
