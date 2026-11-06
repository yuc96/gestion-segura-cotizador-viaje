<?php

declare(strict_types=1);

namespace App\Services\Countries;

use App\Domain\Countries\CountryData;
use App\Domain\Countries\CountryProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class RestCountriesClient
{
    public function __construct(private CountryResponseNormalizer $normalizer) {}

    /**
     * @return list<CountryData>
     */
    public function fetchAll(): array
    {
        $key = (string) config('services.restcountries.key');

        if ($key === '') {
            throw new CountryProviderException('No hay una API key de REST Countries configurada.', 'unconfigured');
        }

        $collected = [];
        $offset = 0;

        for ($page = 0; $page < 10; $page++) {
            $payload = $this->requestPage($key, $offset);
            $batch = $this->normalizer->normalize($payload);

            foreach ($batch as $country) {
                $collected[$country->code] = $country;
            }

            $more = (bool) ($payload['data']['meta']['more'] ?? false);

            if (! $more) {
                break;
            }

            $count = (int) ($payload['data']['meta']['count'] ?? count($batch));

            if ($count < 1) {
                break;
            }

            $offset += $count;
        }

        return array_values($collected);
    }

    /**
     * @return array<mixed>
     */
    private function requestPage(string $key, int $offset): array
    {
        $timeout = max(1, (int) config('services.restcountries.timeout', 8));
        $baseUrl = rtrim((string) config('services.restcountries.base_url'), '/');

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'GestionSeguraCotizador/1.0'])
                ->connectTimeout(min(5, $timeout))
                ->timeout($timeout)
                ->retry(2, 250, fn (Throwable $exception): bool => $exception instanceof ConnectionException, throw: false)
                ->get($baseUrl.'/countries/v5', [
                    'response_fields' => 'names.common,names.translations.spa,codes.alpha_2,region,subregion,flag.emoji',
                    'limit' => 100,
                    'offset' => $offset,
                ]);
        } catch (ConnectionException $exception) {
            throw new CountryProviderException(
                'Tiempo de espera agotado al consultar países.',
                'timeout',
                $exception,
            );
        }

        if ($response->failed()) {
            throw new CountryProviderException(
                'La API de países respondió HTTP '.$response->status().'.',
                'http_error',
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new CountryProviderException('La API de países devolvió un cuerpo que no es JSON.', 'unexpected');
        }

        return $payload;
    }
}
