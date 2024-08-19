<?php

interface HasSecretKey
{
    public function getSecretKey(): Buffer;
}
