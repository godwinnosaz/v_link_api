<?php

class TodoException extends Exception
{
    public function __construct($message = "Barrier issues X!", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message . " Contact Brainiac Og", $code, $previous);
    }
}
