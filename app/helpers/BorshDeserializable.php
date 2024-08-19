<?php
trait BorshDeserializable
{
    /**
     * Create a new instance of this object.
     *
     * Note: must override when the default constructor requires parameters!
     *
     * @return static
     */
    public static function borshConstructor()
    {
        return new static();
    }

    /**
     * Magic setter to dynamically set properties.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value)
    {
        if (!$this->isPrivateProperty($name)) {
            $this->fields[$name] = $value;
        }

        if ($this->isPrivateProperty($name)) {
            $reflectionClass = new ReflectionClass($this);
            $property = $reflectionClass->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($this, $value);
        }
    }

    /**
     * Magic isset to check if dynamically set property is set.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->fields[$name]) || ($this->isPrivateProperty($name) && (new ReflectionClass($this))->getProperty($name)->isInitialized($this));
    }

    /**
     * Magic unset to unset dynamically set property.
     *
     * @param string $name
     */
    public function __unset(string $name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        } elseif ($this->isPrivateProperty($name)) {
            $reflectionClass = new ReflectionClass($this);
            $property = $reflectionClass->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($this, null);
        }
    }

    /**
     * Determine if a property is considered private.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isPrivateProperty(string $name): bool
    {
        $reflectionClass = new ReflectionClass($this);
        return $reflectionClass->hasProperty($name) && $reflectionClass->getProperty($name)->isPrivate();
    }
}
