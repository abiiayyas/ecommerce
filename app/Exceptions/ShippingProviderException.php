<?php

namespace App\Exceptions;

use App\Enums\ShippingProviderDriver;
use RuntimeException;
use Throwable;

class ShippingProviderException extends RuntimeException
{
    public function __construct(
        public readonly ShippingProviderDriver $provider,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
