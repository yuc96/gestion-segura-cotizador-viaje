<?php

declare(strict_types=1);

namespace App\Infrastructure\Countries;

use App\Application\CountryCatalogResult;
use App\Application\Port\ActivityLog;
use App\Application\Port\CountryProvider;
use App\Domain\Country;
use App\Domain\Exception\CountryProviderException;
use App\Models\Country as CountryRecord;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class GraphqlCountryProvider implements CountryProvider
{
    private const CACHE_KEY = 'countries.catalog.v2';

    public function __construct(
        private CountriesGraphqlClient $client,
        private BundledCountryCatalog $bundled,
        private ActivityLog $log,
    ) {}

    public function catalog(): CountryCatalogResult
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && isset($cached['countries'], $cached['source']) && is_array($cached['countries'])) {
            return new CountryCatalogResult(
                countries: $this->sort($this->hydrate($cached['countries'])),
                source: (string) $cached['source'],
                notice: $cached['notice'] ?? null,
            );
        }

        try {
            $countries = $this->sort($this->localize($this->client->fetchAll()));
            $this->persist($countries);
            $result = new CountryCatalogResult($countries, 'api', null);
            $this->remember($result, (int) config('services.countries.cache_ttl_minutes', 720));

            return $result;
        } catch (CountryProviderException $exception) {
            $this->log->warning('countries.catalog.fallback', [
                'reason' => $exception->reason,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($exception->getMessage());
        } catch (Throwable $exception) {
            $this->log->warning('countries.catalog.fallback', [
                'reason' => 'unexpected',
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback('Error inesperado al consultar la API de países.');
        }
    }

    public function find(string $code): ?Country
    {
        $code = strtoupper($code);

        foreach ($this->catalog()->countries as $country) {
            if ($country->code === $code) {
                return $country;
            }
        }

        return null;
    }

    private function fallback(string $notice): CountryCatalogResult
    {
        $stored = CountryRecord::query()->orderBy('name')->get();

        if ($stored->isNotEmpty()) {
            $result = new CountryCatalogResult(
                countries: $this->sort($stored->map(fn (CountryRecord $country): Country => $this->fromRecord($country))->all()),
                source: 'cache',
                notice: $notice,
            );
            $this->remember($result, (int) config('services.countries.failure_ttl_minutes', 10));

            return $result;
        }

        $result = new CountryCatalogResult(
            countries: $this->sort($this->bundled->all()),
            source: 'snapshot',
            notice: $notice,
        );
        $this->remember($result, (int) config('services.countries.failure_ttl_minutes', 10));

        return $result;
    }

    /**
     * @param  list<Country>  $countries
     */
    private function persist(array $countries): void
    {
        $now = now();
        $rows = array_map(fn (Country $country): array => [
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
            CountryRecord::query()->upsert(
                $chunk,
                ['code'],
                ['name', 'region', 'subregion', 'pricing_region', 'flag_emoji', 'updated_at'],
            );
        }
    }

    private function remember(CountryCatalogResult $result, int $minutes): void
    {
        Cache::put(self::CACHE_KEY, [
            'source' => $result->source,
            'notice' => $result->notice,
            'countries' => array_map(
                fn (Country $country): array => $country->toCacheArray(),
                $result->countries,
            ),
        ], now()->addMinutes(max($minutes, 1)));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<Country>
     */
    private function hydrate(array $rows): array
    {
        $countries = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['code'], $row['name'], $row['region'], $row['pricing_region'])) {
                continue;
            }

            $countries[] = Country::fromCacheArray($row);
        }

        return $countries;
    }

    /**
     * @param  list<Country>  $countries
     * @return list<Country>
     */
    private function localize(array $countries): array
    {
        $names = [];

        foreach ($this->bundled->all() as $country) {
            $names[$country->code] = $country->name;
        }

        return array_map(function (Country $country) use ($names): Country {
            if (! isset($names[$country->code]) || $names[$country->code] === $country->name) {
                return $country;
            }

            return new Country(
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
     * @param  list<Country>  $countries
     * @return list<Country>
     */
    private function sort(array $countries): array
    {
        if (class_exists(\Collator::class)) {
            $collator = new \Collator('es');
            usort($countries, fn (Country $left, Country $right): int => $collator->compare($left->name, $right->name));

            return $countries;
        }

        usort($countries, fn (Country $left, Country $right): int => strcasecmp($left->name, $right->name));

        return $countries;
    }

    private function fromRecord(CountryRecord $country): Country
    {
        return new Country(
            code: $country->code,
            name: $country->name,
            region: $country->region,
            subregion: $country->subregion,
            pricingRegion: $country->pricing_region,
            flagEmoji: (string) $country->flag_emoji,
        );
    }
}
