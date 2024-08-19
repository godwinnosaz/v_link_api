<?php

class SignaturePubkeyPair implements HasPublicKey
{
    protected PublicKey $publicKey;
    public ?string $signature;

    public function __construct(PublicKey $publicKey, ?string $signature = null)
    {
        $this->publicKey = $publicKey;
        $this->signature = $signature;
    }

    public function getPublicKey(): PublicKey
    {
        return $this->publicKey;
    }
}
