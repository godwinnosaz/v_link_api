<?php

/**
 * Buffer class for handling binary data manipulation
 *
 * @category Utility
 * @package  SolanaPhpSdk
 */
class Buffer implements Countable
{
    const TYPE_STRING = 'string';
    const TYPE_BYTE = 'byte';
    const TYPE_SHORT = 'short';
    const TYPE_INT = 'int';
    const TYPE_LONG = 'long';
    const TYPE_FLOAT = 'float';

    const FORMAT_CHAR_SIGNED = 'c';
    const FORMAT_CHAR_UNSIGNED = 'C';
    const FORMAT_SHORT_16_SIGNED = 's';
    const FORMAT_SHORT_16_UNSIGNED = 'v';
    const FORMAT_LONG_32_SIGNED = 'l';
    const FORMAT_LONG_32_UNSIGNED = 'V';
    const FORMAT_LONG_LONG_64_SIGNED = 'q';
    const FORMAT_LONG_LONG_64_UNSIGNED = 'P';
    const FORMAT_FLOAT = 'e';

    /**
     * @var array<int>
     */
    protected array $data;

    /**
     * @var bool is this a signed or unsigned value?
     */
    protected ?bool $signed = null;

    /**
     * @var ?string $datatype
     */
    protected ?string $datatype = null;

    /**
     * @param mixed $value
     */
    public function __construct($value = null, ?string $datatype = null, ?bool $signed = null)
    {
        $this->datatype = $datatype;
        $this->signed = $signed;

        $isString = is_string($value);
        $isNumeric = is_numeric($value);

        if ($isString || $isNumeric) {
            $this->datatype = $datatype;
            $this->signed = $signed;

            $this->data = $isString
                ? array_values(unpack("C*", $value))
                : array_values(unpack("C*", pack($this->computedFormat(), $value)));
        } elseif (is_array($value)) {
            $this->data = $value;
        } elseif ($value instanceof PublicKey) {
            $this->data = $value->toBytes();
        } elseif ($value instanceof Buffer) {
            $this->data = $value->toArray();
            $this->datatype = $value->datatype;
            $this->signed = $value->signed;
        } elseif ($value == null) {
            $this->data = [];
        } elseif (method_exists($value, 'toArray')) {
            $this->data = $value->toArray();
        } else {
            throw new InputValidationException('Unsupported $value for Buffer: ' . get_class($value));
        }
    }

    public static function concat(array $buffers): static
    {
        $data = [];
        foreach ($buffers as $buffer) {
            $data = array_merge($data, $buffer->toArray());
        }

        return new static($data);
    }

    public static function fromArray(array $array): static
    {
        return new static($array);
    }

    public static function from($value = null, ?string $format = null, ?bool $signed = null): Buffer
    {
        return new static($value, $format, $signed);
    }

    public static function fromBase58(string $value): Buffer
    {
        $value = PublicKey::base58()->decode($value);

        return new static($value);
    }

    public function pad($len, int $val = 0): Buffer
    {
        $this->data = array_pad($this->data, $len, $val);

        return $this;
    }

    public function push($source): Buffer
    {
        $sourceAsBuffer = Buffer::from($source);

        array_push($this->data, ...$sourceAsBuffer->toArray());

        return $this;
    }

    public function slice(int $offset, ?int $length = null, ?string $format = null, ?bool $signed = null): Buffer
    {
        return static::from(array_slice($this->data, $offset, $length), $format, $signed);
    }

    public function splice(int $offset, ?int $length = null): Buffer
    {
        return static::from(array_splice($this->data, $offset, $length));
    }

    public function shift(): ?int
    {
        return array_shift($this->data);
    }

    public function fixed(int $size): Buffer
    {
        $fixedSizeData = SplFixedArray::fromArray($this->data);
        $fixedSizeData->setSize($size);
        $this->data = $fixedSizeData->toArray();

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function toBase58String(): string
    {
        return PublicKey::base58()->encode($this->toString());
    }

    #[\ReturnTypeWillChange]
    public  function count(): int
    {
        return count($this->toArray());
    }

    public function __toString()
    {
        return pack('C*', ...$this->toArray());
    }

    public function value(?int $length = null)
    {
        if ($length) {
            $this->fixed($length);
        }

        if ($this->datatype === self::TYPE_STRING) {
            return ord(pack("C*", ...$this->toArray()));
        } else {
            return unpack($this->computedFormat(), pack("C*", ...$this->toArray()))[1];
        }
    }

    protected function computedFormat(): string
    {
        if (! $this->datatype) {
            throw new InputValidationException('Trying to calculate format of unspecified buffer. Please specify a datatype.');
        }

        switch ($this->datatype) {
            case self::TYPE_STRING: return self::FORMAT_CHAR_UNSIGNED;
            case self::TYPE_BYTE: return $this->signed ? self::FORMAT_CHAR_SIGNED : self::FORMAT_CHAR_UNSIGNED;
            case self::TYPE_SHORT: return $this->signed ? self::FORMAT_SHORT_16_SIGNED : self::FORMAT_SHORT_16_UNSIGNED;
            case self::TYPE_INT: return $this->signed ? self::FORMAT_LONG_32_SIGNED : self::FORMAT_LONG_32_UNSIGNED;
            case self::TYPE_LONG: return $this->signed ? self::FORMAT_LONG_LONG_64_SIGNED : self::FORMAT_LONG_LONG_64_UNSIGNED;
            case self::TYPE_FLOAT: return self::FORMAT_FLOAT;
            default: throw new InputValidationException("Unsupported datatype.");
        }
    }

    public static function alloc(int $size): Buffer
    {
        return new static(array_fill(0, $size, 0));
    }
}