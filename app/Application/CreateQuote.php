<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\ActivityLog;
use App\Application\Port\CountryProvider;
use App\Application\Port\QuoteRepository;
use App\Domain\Exception\UnknownDestination;
use App\Domain\InsuredSnapshot;
use App\Domain\PremiumCalculator;
use App\Domain\Quote;
use App\Domain\QuoteStatus;
use App\Domain\TripPeriod;
use DateTimeImmutable;

final class CreateQuote
{
    public function __construct(
        private CountryProvider $countries,
        private PremiumCalculator $calculator,
        private QuoteRepository $quotes,
        private ActivityLog $log,
    ) {}

    public function handle(CreateQuoteCommand $command): Quote
    {
        $country = $this->countries->find($command->destinationCode);

        if ($country === null) {
            throw new UnknownDestination($command->destinationCode);
        }

        $trip = TripPeriod::inclusive(
            new DateTimeImmutable($command->departureDate),
            new DateTimeImmutable($command->returnDate),
        );
        $premium = $this->calculator->price($trip->days, $country->pricingRegion);

        $quote = $this->quotes->save(new Quote(
            id: null,
            insured: new InsuredSnapshot(
                firstName: $command->firstName,
                lastName: $command->lastName,
                identification: $command->identification,
                email: $command->email,
                birthDate: $command->birthDate,
            ),
            destination: $country,
            trip: $trip,
            premium: $premium,
            status: QuoteStatus::Quoted,
            contractedAt: null,
            createdAt: null,
        ));

        $this->log->info('quote.created', [
            'quote_id' => $quote->id,
            'destination_code' => $quote->destination->code,
            'trip_days' => $quote->trip->days,
            'total_cents' => $quote->premium->total->cents,
            'status' => $quote->status->value,
        ]);

        return $quote;
    }
}
