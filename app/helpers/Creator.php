<?php

class Creator
{
    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['address', 'pubkeyAsString'],
                ['verified', 'u8'],
                ['share', 'u8'],
            ],
        ],
    ];

    // Assuming BorshDeserializable methods are implemented here
    public static function deserialize(array $buffer): self
    {
        // Implementation for deserialization
    }

    public function toArray(): array
    {
        // Implementation to convert to array
    }
}
