<?php

class VerificationMethodStruct
{
    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['fragment', 'string'],
                ['flags', 'u16'],
                ['methodType', 'u8'],
                ['keyData', 'bytes']
            ],
        ],
    ];

    // Implement any methods required by Borsh\BorshObject here
}
