<?php

class ReverseInstructionAccount
{
    use BorshObject;

    private int $tag;
    private string $name;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['tag', 'u8'],
                ['name', 'string'],
            ],
        ],
    ];

    public function __construct(string $name)
    {
        $this->tag = 12;
        $this->name = $name;
    }

    /**
     * @throws InputValidationException
     */
    public function getInstruction(
        PublicKey $programId,
        PublicKey $namingServiceProgram,
        PublicKey $rootDomain,
        PublicKey $reverseLookup,
        PublicKey $systemProgram,
        PublicKey $centralState,
        PublicKey $feePayer,
        PublicKey $rentSysvar,
        ?PublicKey $parentName = null,
        ?PublicKey $parentNameOwner = null
    ): TransactionInstruction {
        $data = Buffer::from($this->serialize());
        $keys = [
            new AccountMeta($programId, false, false),
            new AccountMeta($namingServiceProgram, false, false),
            new AccountMeta($rootDomain, false, false),
            new AccountMeta($reverseLookup, false, true),
            new AccountMeta($systemProgram, false, false),
            new AccountMeta($centralState, false, false),
            new AccountMeta($feePayer, true, true),
            new AccountMeta($rentSysvar, false, false),
        ];

        if ($parentName !== null) {
            $keys[] = ['pubkey' => $parentName, 'isSigner' => false, 'isWritable' => true];
        }

        if ($parentNameOwner !== null) {
            $keys[] = ['pubkey' => $parentNameOwner, 'isSigner' => true, 'isWritable' => true];
        }

        return new TransactionInstruction(
            $programId,
            $keys,
            $data
        );
    }

    public static function deserialize(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }

    public function serialize(): array
    {
        return Borsh::serialize(self::SCHEMA, $this);
    }
}
