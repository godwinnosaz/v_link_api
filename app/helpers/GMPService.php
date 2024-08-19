<?php

class GMPService implements ServiceInterface
{
    /**
     * @var string
     * @since v1.1.0
     */
    protected $alphabet;

    /**
     * @var int
     * @since v1.1.0
     */
    protected $base;

    /**
     * Constructor
     *
     * @param string $alphabet optional
     * @since v1.1.0
     */
    public function __construct($alphabet = null)
    {
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

        $this->alphabet = $alphabet;
        $this->base = strlen($alphabet);
    }

    /**
     * Encode a string into base58.
     *
     * @param  string $string The string you wish to encode.
     * @since Release v1.1.0
     * @return string The Base58 encoded string.
     */
    public function encode($string)
    {
        // Type validation
        if (!is_string($string)) {
            throw new \InvalidArgumentException('Argument $string must be a string.');
        }

        // If the string is empty, then the encoded string is obviously empty
        if (strlen($string) === 0) {
            return '';
        }

        // Convert the string into hexadecimal
        $hex = unpack('H*', $string);
        $hex = reset($hex);
        $decimal = gmp_init($hex, 16);

        // Convert from base10 to base58
        $output = '';
        while (gmp_cmp($decimal, $this->base) >= 0) {
            list($decimal, $mod) = gmp_div_qr($decimal, $this->base);
            $output .= $this->alphabet[gmp_intval($mod)];
        }

        // Append the remainder, if any
        if (gmp_cmp($decimal, 0) > 0) {
            $output .= $this->alphabet[gmp_intval($decimal)];
        }

        // Reverse the encoded data
        $output = strrev($output);

        // Add leading zeros
        $bytes = str_split($string);
        foreach ($bytes as $byte) {
            if ($byte === "\x00") {
                $output = $this->alphabet[0] . $output;
                continue;
            }
            break;
        }

        return (string) $output;
    }

    /**
     * Decode base58 into a PHP string.
     *
     * @param  string $base58 The base58 encoded string.
     * @since Release v1.1.0
     * @return string Returns the decoded string.
     */
    public function decode($base58)
    {
        // Type validation
        if (!is_string($base58)) {
            throw new \InvalidArgumentException('Argument $base58 must be a string.');
        }

        // If the string is empty, then the decoded string is obviously empty
        if (strlen($base58) === 0) {
            return '';
        }

        $indexes = array_flip(str_split($this->alphabet));
        $chars = str_split($base58);

        // Check for invalid characters in the base58 string
        foreach ($chars as $char) {
            if (!isset($indexes[$char])) {
                throw new \InvalidArgumentException('Argument $base58 contains invalid characters.');
            }
        }

        // Convert from base58 to base10
        $decimal = gmp_init($indexes[$chars[0]], 10);

        for ($i = 1, $l = count($chars); $i < $l; $i++) {
            $decimal = gmp_mul($decimal, $this->base);
            $decimal = gmp_add($decimal, $indexes[$chars[$i]]);
        }

        // Convert from base10 to base256 (8-bit byte array)
        $output = '';
        while (gmp_cmp($decimal, 0) > 0) {
            list($decimal, $byte) = gmp_div_qr($decimal, 256);
            $output = pack('C', gmp_intval($byte)) . $output;
        }

        // Add leading zeros
        foreach ($chars as $char) {
            if ($indexes[$char] === 0) {
                $output = "\x00" . $output;
                continue;
            }
            break;
        }

        return $output;
    }
}
