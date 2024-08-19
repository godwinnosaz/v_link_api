<?php

class CompiledInstruction
{
    public int $programIdIndex;
    
    /**
     * Array of indexes.
     *
     * @var array<int>
     */
    public array $accounts;
    
    public Buffer $data;

    public function __construct(
        int $programIdIndex,
        array $accounts,
        $data
    )
    {
        $this->programIdIndex = $programIdIndex;
        $this->accounts = $accounts;
        $this->data = Buffer::from($data);
    }
}
