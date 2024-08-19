<?php

class Mint
{
    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['mintAuthorityOption', 'u32'],
                ['mintAuthority', 'pubKey'],
                ['supply', 'u64'],
                ['decimals', 'u8'],
                ['isInitialized', 'u8'],
                ['freezeAuthorityOption', 'u32'],
                ['freezeAuthority', 'pubKey']
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }
}
