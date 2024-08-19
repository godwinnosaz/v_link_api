<?php

class SolanaRpcClient
{
    public const LOCAL_ENDPOINT = 'http://localhost:8899';
    public const DEVNET_ENDPOINT = 'https://api.devnet.solana.com';
    public const TESTNET_ENDPOINT = 'https://api.testnet.solana.com';
    public const MAINNET_ENDPOINT = 'https://api.mainnet-beta.solana.com';

    public const ERROR_CODE_PARSE_ERROR = -32700;
    public const ERROR_CODE_INVALID_REQUEST = -32600;
    public const ERROR_CODE_METHOD_NOT_FOUND = -32601;
    public const ERROR_CODE_INVALID_PARAMETERS = -32602;
    public const ERROR_CODE_INTERNAL_ERROR = -32603;

    protected string $endpoint;
    protected int $randomKey;

    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint ?: self::DEVNET_ENDPOINT;
        $this->randomKey = random_int(0, 99999999);
    }

    public function call(string $method, array $params = [], array $headers = []): mixed
    {
        $body = json_encode($this->buildRpc($method, $params));
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $resp_object = json_decode($response, true);

        $this->validateResponse($resp_object, $method);

        return $resp_object['result'] ?? null;
    }

    public function buildRpc(string $method, array $params): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->randomKey,
            'method' => $method,
            'params' => $params,
        ];
    }

    protected function validateResponse(array $body, string $method): void
    {
        if ($body == null) {
            throw new Exception('Invalid JSON response');
        }

        if (isset($body['params']['error']) || isset($body['error'])) {
            $error = $body['params']['error'] ?: $body['error'];
            if ($error['code'] === self::ERROR_CODE_METHOD_NOT_FOUND) {
                throw new Exception("API Error: Method $method not found.");
            } else {
                throw new Exception($error['message']);
            }
        }

        if ($body['id'] !== $this->randomKey) {
            throw new Exception('Invalid response ID');
        }
    }

    public function getRandomKey(): int
    {
        return $this->randomKey;
    }
}
