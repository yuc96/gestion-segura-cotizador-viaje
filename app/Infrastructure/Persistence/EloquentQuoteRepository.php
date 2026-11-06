<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Port\QuoteRepository;
use App\Application\QuoteCriteria;
use App\Application\QuotePage;
use App\Domain\Country;
use App\Domain\InsuredSnapshot;
use App\Domain\Money;
use App\Domain\Premium;
use App\Domain\Quote;
use App\Domain\TripPeriod;
use App\Models\Insured;
use App\Models\Quote as QuoteRecord;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentQuoteRepository implements QuoteRepository
{
    public function save(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote): Quote {
            if ($quote->id === null) {
                $insured = Insured::query()->create([
                    'first_name' => $quote->insured->firstName,
                    'last_name' => $quote->insured->lastName,
                    'identification' => $quote->insured->identification,
                    'email' => $quote->insured->email,
                    'birth_date' => $quote->insured->birthDate,
                ]);

                $record = QuoteRecord::query()->create([
                    'insured_id' => $insured->id,
                    'destination_code' => $quote->destination->code,
                    'destination_name' => $quote->destination->name,
                    'destination_region' => $quote->destination->region,
                    'destination_subregion' => $quote->destination->subregion,
                    'pricing_region' => $quote->destination->pricingRegion,
                    'destination_flag' => $quote->destination->flagEmoji,
                    'departure_date' => $quote->trip->departure->format('Y-m-d'),
                    'return_date' => $quote->trip->return->format('Y-m-d'),
                    'trip_days' => $quote->premium->days,
                    'daily_rate_cents' => $quote->premium->dailyRate->cents,
                    'base_amount_cents' => $quote->premium->base->cents,
                    'surcharge_percent' => $quote->premium->surchargePercent,
                    'surcharge_amount_cents' => $quote->premium->surcharge->cents,
                    'total_amount_cents' => $quote->premium->total->cents,
                    'currency' => 'USD',
                    'status' => $quote->status,
                ]);

                return $this->map($record->load('insured'));
            }

            $record = QuoteRecord::query()->findOrFail($quote->id);
            $record->status = $quote->status;
            $record->contracted_at = $quote->contractedAt;
            $record->save();

            return $this->map($record->load('insured'));
        });
    }

    public function find(int $id): ?Quote
    {
        $record = QuoteRecord::query()->with('insured')->find($id);

        return $record === null ? null : $this->map($record);
    }

    public function paginate(QuoteCriteria $criteria): QuotePage
    {
        $search = str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $criteria->search));

        $paginator = QuoteRecord::query()
            ->with('insured')
            ->when(filled($criteria->status), fn ($query) => $query->where('status', $criteria->status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like): void {
                    $query->where('destination_name', 'like', $like)
                        ->orWhere('destination_code', 'like', $like)
                        ->orWhereHas('insured', function ($insured) use ($like): void {
                            $insured->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('identification', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->when(filled($criteria->departureFrom), fn ($query) => $query->whereDate('departure_date', '>=', $criteria->departureFrom))
            ->when(filled($criteria->departureTo), fn ($query) => $query->whereDate('departure_date', '<=', $criteria->departureTo))
            ->latest('id')
            ->paginate(perPage: $criteria->perPage, page: $criteria->page);

        return new QuotePage(
            items: array_map(fn (QuoteRecord $record): Quote => $this->map($record), $paginator->items()),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }

    private function map(QuoteRecord $record): Quote
    {
        $departure = DateTimeImmutable::createFromInterface($record->departure_date->toDateTimeImmutable())->setTime(0, 0);
        $return = DateTimeImmutable::createFromInterface($record->return_date->toDateTimeImmutable())->setTime(0, 0);

        return new Quote(
            id: $record->id,
            insured: new InsuredSnapshot(
                firstName: $record->insured->first_name,
                lastName: $record->insured->last_name,
                identification: $record->insured->identification,
                email: $record->insured->email,
                birthDate: $record->insured->birth_date->toDateString(),
            ),
            destination: new Country(
                code: $record->destination_code,
                name: $record->destination_name,
                region: $record->destination_region,
                subregion: $record->destination_subregion,
                pricingRegion: $record->pricing_region,
                flagEmoji: (string) $record->destination_flag,
            ),
            trip: new TripPeriod($departure, $return, $record->trip_days),
            premium: new Premium(
                days: $record->trip_days,
                dailyRate: new Money($record->daily_rate_cents),
                base: new Money($record->base_amount_cents),
                surchargePercent: $record->surcharge_percent,
                surcharge: new Money($record->surcharge_amount_cents),
                total: new Money($record->total_amount_cents),
                region: $record->pricing_region,
            ),
            status: $record->status,
            contractedAt: $record->contracted_at?->toDateTimeImmutable(),
            createdAt: $record->created_at?->toDateTimeImmutable(),
        );
    }
}
