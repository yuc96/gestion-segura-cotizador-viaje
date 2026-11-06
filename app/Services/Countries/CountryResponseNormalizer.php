<?php

declare(strict_types=1);

namespace App\Services\Countries;

use App\Domain\Countries\CountryData;
use App\Domain\Countries\CountryProviderException;
use App\Domain\Pricing\RegionResolver;

final class CountryResponseNormalizer
{
    public function __construct(private RegionResolver $regions) {}

    /**
     * Acepta el catálogo GraphQL (countries.trevorblades.com) y, si apareciera,
     * el sobre de REST Countries v5 o el arreglo legado de v3.
     *
     * @param  array<mixed>  $payload
     * @return list<CountryData>
     */
    public function normalize(array $payload): array
    {
        if (array_key_exists('success', $payload) && $payload['success'] === false) {
            throw new CountryProviderException('La API de países indicó un error.', 'unexpected');
        }

        if (isset($payload['data']['_demo'])) {
            throw new CountryProviderException(
                'La API devolvió el payload de demostración, no el catálogo real.',
                'unexpected',
            );
        }

        $rows = $this->rows($payload);
        $countries = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $country = $this->mapRow($row);

            if ($country !== null) {
                $countries[$country->code] = $country;
            }
        }

        if ($countries === []) {
            throw new CountryProviderException('La respuesta no contenía países utilizables.', 'unexpected');
        }

        return array_values($countries);
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<mixed>
     */
    private function rows(array $payload): array
    {
        if (isset($payload['errors']) && is_array($payload['errors']) && $payload['errors'] !== [] && ! isset($payload['data']['countries'])) {
            throw new CountryProviderException('La API de países indicó un error.', 'unexpected');
        }

        if (isset($payload['data']['countries']) && is_array($payload['data']['countries'])) {
            return array_values($payload['data']['countries']);
        }

        if (isset($payload['data']['objects']) && is_array($payload['data']['objects'])) {
            return array_values($payload['data']['objects']);
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        throw new CountryProviderException('La respuesta de países tiene una forma inesperada.', 'unexpected');
    }

    /**
     * @param  array<mixed>  $row
     */
    private function mapRow(array $row): ?CountryData
    {
        $code = strtoupper((string) ($row['code'] ?? $row['codes']['alpha_2'] ?? $row['cca2'] ?? ''));

        if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            return null;
        }

        $name = $this->name($row);

        if ($name === null) {
            return null;
        }

        $region = trim((string) ($row['continent']['name'] ?? $row['region'] ?? ''));
        $subregion = trim((string) ($row['subregion'] ?? ''));

        return new CountryData(
            code: $code,
            name: $name,
            region: $region,
            subregion: $subregion !== '' ? $subregion : null,
            pricingRegion: $this->regions->resolve($region, $subregion),
            flagEmoji: $this->flag($row),
        );
    }

    /**
     * @param  array<mixed>  $row
     */
    private function name(array $row): ?string
    {
        $candidates = [
            is_string($row['name'] ?? null) ? $row['name'] : null,
            $row['names']['translations']['spa']['common'] ?? null,
            $row['translations']['spa']['common'] ?? null,
            $row['name']['translations']['spa']['common'] ?? null,
            $row['names']['common'] ?? null,
            $row['name']['common'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $row
     */
    private function flag(array $row): string
    {
        $flag = $row['emoji'] ?? $row['flag'] ?? '';

        if (is_string($flag)) {
            return $flag;
        }

        if (is_array($flag) && is_string($flag['emoji'] ?? null)) {
            return $flag['emoji'];
        }

        return '';
    }
}
