<?php 
trait BorshSerializable
{
    /**
     * Magic getter to retrieve dynamically set properties.
     * Note, changed from dynamic properties make use of an array due to dynamic properties being deprecated.
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        // Check if the property exists in the dynamic properties
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }

        // Check if the property exists as a private property
        if ($this->isPrivateProperty($name)) {
            // Use reflection to access the private property
            $reflectionClass = new ReflectionClass($this);
            $property = $reflectionClass->getProperty($name);
            $property->setAccessible(true);
            return $property->getValue($this);
        }

        // Property not found
        return null;
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
        // Get the class name (whatever class is implementing this trait)
        $className = static::class;

        // Create a ReflectionClass instance for the class
        $reflectionClass = new ReflectionClass($className);

        // Check if the property is declared in the class and is private
        return $reflectionClass->hasProperty($name) && $reflectionClass->getProperty($name)->isPrivate();
    }
}
