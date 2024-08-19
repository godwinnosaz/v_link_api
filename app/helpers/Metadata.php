<?php

class Metadata
{
    public const SCHEMA = [
        Creator::class => Creator::SCHEMA[Creator::class],
        MetadataData::class => MetadataData::SCHEMA[MetadataData::class],
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['key', 'u8'],
                ['updateAuthority', 'pubkeyAsString'],
                ['mint', 'pubkeyAsString'],
                ['data', MetadataData::class],
                ['primarySaleHappened', 'u8'], // bool
                ['isMutable', 'u8'], // bool
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        // Implementation for deserialization
        // Assuming Borsh::deserialize is available and works as expected
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }
}
