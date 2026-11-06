<?php

declare(strict_types=1);

namespace App\Actions\Quotes;

use App\Domain\Pricing\TravelInsurancePricer;
use App\Domain\Pricing\TripDuration;
use App\Domain\Quotes\QuoteStatus;
use App\Exceptions\UnknownDestination;
use App\Models\Insured;
use App\Models\Quote;
use App\Services\Countries\CountryCatalog;
use App\Support\StructuredLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CreateQuote
{
    public function __construct(
        private CountryCatalog $countries,
        private TripDuration $duration,
        private TravelInsurancePricer $pricer,
        private StructuredLogger $logger,
    ) {}

    public function handle(CreateQuoteData $data): Quote
    {
        $country = $this->countries->find($data->destinationCode);

        if ($country === null) {
            throw new UnknownDestination($data->destinationCode);
        }

        $departure = CarbonImmutable::parse($data->departureDate)->startOfDay();
        $return = CarbonImmutable::parse($data->returnDate)->startOfDay();
        $days = $this->duration->inclusiveDays($departure, $return);
        $price = $this->pricer->price($days, $country->pricingRegion);

        $quote = DB::transaction(function () use ($data, $country, $departure, $return, $price): Quote {
            $insured = Insured::query()->create([
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'identification' => $data->identification,
                'email' => $data->email,
                'birth_date' => $data->birthDate,
            ]);

            return Quote::query()->create([
                'insured_id' => $insured->id,
                'destination_code' => $country->code,
                'destination_name' => $country->name,
                'destination_region' => $country->region,
                'destination_subregion' => $country->subregion,
                'pricing_region' => $country->pricingRegion,
                'destination_flag' => $country->flagEmoji,
                'departure_date' => $departure->toDateString(),
                'return_date' => $return->toDateString(),
                'trip_days' => $price->days,
                'daily_rate_cents' => $price->dailyRate->cents,
                'base_amount_cents' => $price->base->cents,
                'surcharge_percent' => $price->surchargePercent,
                'surcharge_amount_cents' => $price->surcharge->cents,
                'total_amount_cents' => $price->total->cents,
                'currency' => 'USD',
                'status' => QuoteStatus::Quoted,
            ])->load('insured');
        });

        $this->logger->info('quote.created', [
            'quote_id' => $quote->id,
            'destination_code' => $quote->destination_code,
            'trip_days' => $quote->trip_days,
            'total_cents' => $quote->total_amount_cents,
            'status' => $quote->status->value,
        ]);

        return $quote;
    }
}
