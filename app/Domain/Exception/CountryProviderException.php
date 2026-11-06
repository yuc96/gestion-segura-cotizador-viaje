<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;
use Throwable;

final class CountryProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
