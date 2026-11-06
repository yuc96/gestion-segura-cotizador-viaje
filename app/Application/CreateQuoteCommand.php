<?php

declare(strict_types=1);

namespace App\Application;

final readonly class CreateQuoteCommand
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $identification,
        public string $email,
        public string $birthDate,
        public string $destinationCode,
        public string $departureDate,
        public string $returnDate,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            firstName: (string) $payload['first_name'],
            lastName: (string) $payload['last_name'],
            identification: (string) $payload['identification'],
            email: (string) $payload['email'],
            birthDate: (string) $payload['birth_date'],
            destinationCode: (string) $payload['destination_code'],
            departureDate: (string) $payload['departure_date'],
            returnDate: (string) $payload['return_date'],
        );
    }
}
