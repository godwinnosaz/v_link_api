<?php

class Accounts
{
    protected const TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
    private static $address;
    private static $tlvData;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['mint', 'pubKey'],
                ['owner', 'pubKey'],
                ['amount', 'u64'],
                ['delegateOption', 'u32'],
                ['delegate', 'pubKey'],
                ['state', 'u8'],
                ['isNativeOption', 'u8'],
                ['isNative', 'u8'],
                ['delegatedAmount', 'u64'],
                ['closeAuthorityOption', 'u32'],
                ['closeAuthority', 'pubKey']
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }

    /**
     * @throws AccountNotFoundException
     */
    public static function getAccount(
        Connection $connection,
        PublicKey $accountPublicKeyOnbject,
        Commitment $commitment = null,
        $programId = null
    ): Account
    {
        if ($programId === null) {
            $programId = new PublicKey(self::TOKEN_PROGRAM_ID);
        }

        try {
            $info = $connection->getAccountInfo($accountPublicKeyOnbject, $commitment);
            self::$address = $accountPublicKeyOnbject;
            self::$tlvData = $info['data'];
            $base64Data = $info['data']['0'];
            $base64String = base64_decode($base64Data);
            $uint8Array = array_values(unpack('C*', $base64String));
            return self::fromBuffer($uint8Array);
        } catch (AccountNotFoundException $e) {
            throw new AccountNotFoundException();
        }
    }
}
