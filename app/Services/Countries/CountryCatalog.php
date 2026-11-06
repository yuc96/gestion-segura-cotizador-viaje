<?php

declare(strict_types=1);

namespace App\Services\Countries;

use App\Domain\Countries\CatalogResult;
use App\Domain\Countries\CountryData;
use App\Domain\Countries\CountryProviderException;
use App\Models\Country;
use App\Support\StructuredLogger;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class CountryCatalog
{
    private const CACHE_KEY = 'countries.catalog.v2';

    public function __construct(
        private CountriesGraphqlClient $client,
        private BundledCountryCatalog $bundled,
        private StructuredLogger $logger,
    ) {}

    public function get(): CatalogResult
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && isset($cached['countries'], $cached['source']) && is_array($cached['countries'])) {
            return new CatalogResult(
                countries: $this->sort($this->hydrate($cached['countries'])),
                source: (string) $cached['source'],
                notice: $cached['notice'] ?? null,
            );
        }

        try {
            $countries = $this->sort($this->localize($this->client->fetchAll()));
            $this->persist($countries);
            $result = new CatalogResult($countries, 'api', null);
            $this->remember($result, (int) config('services.countries.cache_ttl_minutes', 720));

            return $result;
        } catch (CountryProviderException $exception) {
            $this->logger->warning('countries.catalog.fallback', [
                'reason' => $exception->reason,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->warning('countries.catalog.fallback', [
                'reason' => 'unexpected',
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback('Error inesperado al consultar la API de países.');
        }
    }

    public function find(string $code): ?CountryData
    {
        $code = strtoupper($code);

        foreach ($this->get()->countries as $country) {
            if ($country->code === $code) {
                return $country;
            }
        }

        return null;
    }

    private function fallback(string $notice): CatalogResult
    {
        $stored = Country::query()->orderBy('name')->get();

        if ($stored->isNotEmpty()) {
            $result = new CatalogResult(
                countries: $this->sort($stored->map(fn (Country $country): CountryData => $country->toData())->all()),
                source: 'cache',
                notice: $notice,
            );
            $this->remember($result, (int) config('services.countries.failure_ttl_minutes', 10));

            return $result;
        }

        $result = new CatalogResult(
            countries: $this->sort($this->bundled->all()),
            source: 'snapshot',
            notice: $notice,
        );
        $this->remember($result, (int) config('services.countries.failure_ttl_minutes', 10));

        return $result;
    }

    /**
     * @param  list<CountryData>  $countries
     */
    private function persist(array $countries): void
    {
        $now = now();
        $rows = array_map(fn (CountryData $country): array => [
            'code' => $country->code,
            'name' => $country->name,
            'region' => $country->region,
            'subregion' => $country->subregion,
            'pricing_region' => $country->pricingRegion->value,
            'flag_emoji' => $country->flagEmoji,
            'created_at' => $now,
            'updated_at' => $now,
        ], $countries);

        foreach (array_chunk($rows, 100) as $chunk) {
            Country::query()->upsert(
                $chunk,
                ['code'],
                ['name', 'region', 'subregion', 'pricing_region', 'flag_emoji', 'updated_at'],
            );
        }
    }

    private function remember(CatalogResult $result, int $minutes): void
    {
        Cache::put(self::CACHE_KEY, [
            'source' => $result->source,
            'notice' => $result->notice,
            'countries' => array_map(
                fn (CountryData $country): array => $country->toCacheArray(),
                $result->countries,
            ),
        ], now()->addMinutes(max($minutes, 1)));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<CountryData>
     */
    private function hydrate(array $rows): array
    {
        $countries = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['code'], $row['name'], $row['region'], $row['pricing_region'])) {
                continue;
            }

            $countries[] = CountryData::fromCacheArray($row);
        }

        return $countries;
    }

    /**
     * @param  list<CountryData>  $countries
     * @return list<CountryData>
     */
    /**
     * @param  list<CountryData>  $countries
     * @return list<CountryData>
     */
    private function localize(array $countries): array
    {
        $names = [];

        foreach ($this->bundled->all() as $country) {
            $names[$country->code] = $country->name;
        }

        return array_map(function (CountryData $country) use ($names): CountryData {
            if (! isset($names[$country->code]) || $names[$country->code] === $country->name) {
                return $country;
            }

            return new CountryData(
                code: $country->code,
                name: $names[$country->code],
                region: $country->region,
                subregion: $country->subregion,
                pricingRegion: $country->pricingRegion,
                flagEmoji: $country->flagEmoji,
            );
        }, $countries);
    }

    /**
     * @param  list<CountryData>  $countries
     * @return list<CountryData>
     */
    private function sort(array $countries): array
    {
        if (class_exists(\Collator::class)) {
            $collator = new \Collator('es');
            usort($countries, fn (CountryData $left, CountryData $right): int => $collator->compare($left->name, $right->name));

            return $countries;
        }

        usort($countries, fn (CountryData $left, CountryData $right): int => strcasecmp($left->name, $right->name));

        return $countries;
    }
}
