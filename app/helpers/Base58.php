<?php
class Base58
{
    /**
     * @var ServiceInterface
     * @since v1.1.0
     */
    protected $service;

    /**
     * Constructor
     *
     * @param string           $alphabet optional
     * @param ServiceInterface $service  optional
     */
    public function __construct(
        $alphabet = null,
        $service = null
    ) {
        // Handle null alphabet
        if (is_null($alphabet)) {
            $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        }

        // Type validation
        if (!is_string($alphabet)) {
            throw new \InvalidArgumentException('Argument $alphabet must be a string.');
        }

        // The alphabet must contain 58 characters
        if (strlen($alphabet) !== 58) {
            throw new \InvalidArgumentException('Argument $alphabet must contain 58 characters.');
        }

        // Provide a default service if one isn't injected
        if ($service === null) {
            // Check for GMP support first
            if (function_exists('gmp_init')) {
                $service = new GMPService($alphabet);
            } elseif (function_exists('bcmul')) {
                $service = new BCMathService($alphabet);
            } else {
                throw new \Exception('Please install the BC Math or GMP extension.');
            }
        }

        $this->service = $service;
    }

    /**
     * Encode a string into base58.
     *
     * @param  string $string The string you wish to encode.
     * @since v1.0.0
     * @return string The Base58 encoded string.
     */
    public function encode($string)
    {
        return $this->service->encode($string);
    }

    /**
     * Decode base58 into a PHP string.
     *
     * @param  string $base58 The base58 encoded string.
     * @since v1.0.0
     * @return string Returns the decoded string.
     */
    public function decode($base58)
    {
        return $this->service->decode($base58);
    }
}
