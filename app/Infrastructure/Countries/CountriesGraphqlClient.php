<?php

declare(strict_types=1);

namespace App\Infrastructure\Countries;

use App\Application\Port\ActivityLog;
use App\Domain\Country;
use App\Domain\Exception\CountryProviderException;
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
        private ActivityLog $log,
    ) {}

    /**
     * @return list<Country>
     */
    public function fetchAll(): array
    {
        $timeout = max(1, (int) config('services.countries.timeout', 8));
        $url = (string) config('services.countries.url');
        $started = microtime(true);

        $this->log->info('countries.api.request', [
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
            $this->log->warning('countries.api.failure', [
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
            $this->log->warning('countries.api.failure', [
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
            $this->log->warning('countries.api.failure', [
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
            $this->log->warning('countries.api.failure', [
                'url' => $url,
                'reason' => $exception->reason,
                'status' => $response->status(),
                'duration_ms' => $this->elapsed($started),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->log->info('countries.api.response', [
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
