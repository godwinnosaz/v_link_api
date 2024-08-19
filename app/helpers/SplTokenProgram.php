<?php




class SplTokenProgram extends Program
{
    public const TOKEN_PROGRAM_ID = '125hdQiyhxbEmEyE24Yway5HdqKSStJSpDhMxLuMNhwr';
    public const NATIVE_MINT = 'So11111111111111111111111111111111111111112';
    public const ASSOCIATED_TOKEN_PROGRAM_ID = 'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL';
    public const TOKEN_2022_PROGRAM_ID ='TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb';
    public const TOKEN_2022_MINT = '9pan9bMn5HatX4EJdBwg9VgCa7Uz5HL8N1m5D3NdXejP';

    use SPLTokenActions;
    use SPLTokenInstructions;

    /**
     * @param string $pubKey
     * @return mixed
     */
    public function getTokenAccountsByOwner(string $pubKey)
    {
        return $this->client->call('getTokenAccountsByOwner', [
            $pubKey,
            [
                'programId' => self::TOKEN_PROGRAM_ID,
            ],
            [
                'encoding' => 'jsonParsed',
            ],
        ]);
    }

    /**
     * @param PublicKey $mint
     * @param PublicKey $owner
     * @param bool $allowOwnerOffCurve
     * @param PublicKey|null $programId
     * @param PublicKey|null $associatedTokenProgramId
     * @return PublicKey
     * @throws TokenOwnerOffCurveError
     * @throws InputValidationException
     */
    public function getAssociatedTokenAddressSync(
        PublicKey $mint,
        PublicKey $owner,
        bool $allowOwnerOffCurve = false,
        PublicKey $programId = new PublicKey(self::TOKEN_PROGRAM_ID),
        PublicKey $associatedTokenProgramId = new PublicKey(self::ASSOCIATED_TOKEN_PROGRAM_ID)
    ): PublicKey {
        if (!$allowOwnerOffCurve && !PublicKey::isOnCurve($owner->toBinaryString())) {
            throw new TokenOwnerOffCurveError();
        }

        $address = PublicKey::findProgramAddressSync(
            [$owner->toBuffer(), $programId->toBuffer(), $mint->toBuffer()],
            $associatedTokenProgramId
        );

        return $address[0];
    }
}
