<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class InsuredSnapshot
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $identification,
        public string $email,
        public string $birthDate,
    ) {}

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
