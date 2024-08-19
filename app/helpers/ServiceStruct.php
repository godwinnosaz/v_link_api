<?php

class ServiceStruct
{
    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['fragment', 'string'],
                ['serviceType', 'string'],
                ['serviceEndpoint', 'string']
            ],
        ],
    ];

    // Implement any methods required by Borsh\BorshObject here
}
