<?php

class DidData
{
    public $keyData;

    public const SCHEMA = [
        VerificationMethodStruct::class => VerificationMethodStruct::SCHEMA[VerificationMethodStruct::class],
        ServiceStruct::class => ServiceStruct::SCHEMA[ServiceStruct::class],
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['offset', 'u64'],
                ['version', 'u8'],
                ['bump', 'u8'],
                ['nonce', 'u64'],
                ['initialVerificationMethod', 'string'],
                ['flags', 'u16'],
                ['methodType', 'u8'],
                ['keyData', ['u8']],
                ['verificationMethods', [VerificationMethodStruct::class]],
                ['services', [ServiceStruct::class]],
                ['nativeControllers', ['pubKey']],
                ['otherControllers', ['string']],
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
