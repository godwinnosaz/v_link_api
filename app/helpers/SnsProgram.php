<?php

class SnsProgram extends Program
{
    public mixed $config;
    public PublicKey $centralStateSNSRecords;

    public const SYSVAR_RENT_PUBKEY = 'SysvarRent111111111111111111111111111111111';

    /**
     * @throws InputValidationException
     */
    public function __construct(SolanaRpcClient $client, $config = null)
    {
        parent::__construct($client);
        if ($config) {
            $this->config = $config;
        } else {
            $this->config = $this->loadConstants();
        }
        $sns_records_id = new PublicKey($this->config['BONFIDA_SNS_RECORDS_ID']);

        $this->centralStateSNSRecords = PublicKey::findProgramAddressSync(
            [$sns_records_id],
            $sns_records_id
        )[0];

        return $this;
    }

    // Assume Instructions, Utils, Bindings traits are defined elsewhere
}
