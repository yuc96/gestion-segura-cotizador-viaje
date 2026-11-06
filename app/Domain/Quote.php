<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\QuoteAlreadyContracted;
use DateTimeImmutable;

final readonly class Quote
{
    public function __construct(
        public ?int $id,
        public InsuredSnapshot $insured,
        public Country $destination,
        public TripPeriod $trip,
        public Premium $premium,
        public QuoteStatus $status,
        public ?DateTimeImmutable $contractedAt,
        public ?DateTimeImmutable $createdAt,
    ) {}

    public function number(): string
    {
        return sprintf('GS-%06d', (int) $this->id);
    }

    public function contract(DateTimeImmutable $at): self
    {
        if ($this->status === QuoteStatus::Contracted) {
            throw new QuoteAlreadyContracted('Esta cotización ya fue contratada.');
        }

        return new self(
            id: $this->id,
            insured: $this->insured,
            destination: $this->destination,
            trip: $this->trip,
            premium: $this->premium,
            status: QuoteStatus::Contracted,
            contractedAt: $at,
            createdAt: $this->createdAt,
        );
    }
}
