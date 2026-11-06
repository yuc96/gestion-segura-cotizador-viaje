<?php

declare(strict_types=1);

namespace App\Services\Countries;

use App\Domain\Countries\CountryData;
use App\Domain\Countries\CountryProviderException;
use App\Support\StructuredLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class CountriesGraphqlClient
{
    private const QUERY = <<<'GQL'
    query Countries {
      countries {
        code
        name
        emoji
        continent { name }
      }
    }
    GQL;

    public function __construct(
        private CountryResponseNormalizer $normalizer,
        private StructuredLogger $logger,
    ) {}

    /**
     * @return list<CountryData>
     */
    public function fetchAll(): array
    {
        $timeout = max(1, (int) config('services.countries.timeout', 8));
        $url = (string) config('services.countries.url');
        $started = microtime(true);

        $this->logger->info('countries.api.request', [
            'url' => $url,
            'timeout_seconds' => $timeout,
        ]);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['User-Agent' => 'GestionSeguraCotizador/1.0'])
                ->connectTimeout(min(5, $timeout))
                ->timeout($timeout)
                ->retry(2, 250, fn (Throwable $exception): bool => $exception instanceof ConnectionException, throw: false)
                ->post($url, ['query' => self::QUERY]);
        } catch (ConnectionException $exception) {
            $this->logger->warning('countries.api.failure', [
                'url' => $url,
                'reason' => 'timeout',
                'duration_ms' => $this->elapsed($started),
                'message' => $exception->getMessage(),
            ]);

            throw new CountryProviderException(
                'Tiempo de espera agotado al consultar países.',
                'timeout',
                $exception,
            );
        }

        if ($response->failed()) {
            $this->logger->warning('countries.api.failure', [
                'url' => $url,
                'reason' => 'http_error',
                'status' => $response->status(),
                'duration_ms' => $this->elapsed($started),
            ]);

            throw new CountryProviderException(
                'La API de países respondió HTTP '.$response->status().'.',
                'http_error',
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $this->logger->warning('countries.api.failure', [
                'url' => $url,
                'reason' => 'unexpected',
                'status' => $response->status(),
                'duration_ms' => $this->elapsed($started),
            ]);

            throw new CountryProviderException('La API de países devolvió un cuerpo que no es JSON.', 'unexpected');
        }

        try {
            $countries = $this->normalizer->normalize($payload);
        } catch (CountryProviderException $exception) {
            $this->logger->warning('countries.api.failure', [
                'url' => $url,
                'reason' => $exception->reason,
                'status' => $response->status(),
                'duration_ms' => $this->elapsed($started),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->logger->info('countries.api.response', [
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => $this->elapsed($started),
            'country_count' => count($countries),
        ]);

        return $countries;
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
