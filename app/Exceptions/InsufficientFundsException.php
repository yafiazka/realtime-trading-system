<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    public function __construct(string $asset, string $required, string $available)
    {
        parent::__construct("Insufficient funds for {$asset}. Required: {$required}, Available: {$available}");
    }
}
