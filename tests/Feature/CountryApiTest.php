<?php

use App\Models\Country;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('serves the bundled snapshot without calling the api when no key is configured', function () {
    config(['services.restcountries.key' => '']);

    $response = $this->getJson('/api/countries');

    $response->assertOk()
        ->assertJsonPath('meta.source', 'snapshot')
        ->assertJsonFragment(['code' => 'EC', 'name' => 'Ecuador', 'surcharge_percent' => 0])
        ->assertJsonFragment(['code' => 'ES', 'name' => 'España', 'surcharge_percent' => 20]);

    expect($response->json('data'))->toHaveCount(250);

    Http::assertNothingSent();
});

it('stores a usable v5 catalog and follows pagination', function () {
    config(['services.restcountries.key' => 'test-key']);

    Http::fake([
        '*' => Http::sequence()
            ->push([
                'data' => [
                    'objects' => [countryRow('ES', 'España', 'Europe', 'Southern Europe')],
                    'meta' => ['more' => true, 'count' => 1],
                ],
            ])
            ->push([
                'data' => [
                    'objects' => [countryRow('EC', 'Ecuador', 'Americas', 'South America')],
                    'meta' => ['more' => false, 'count' => 1],
                ],
            ]),
    ]);

    $this->getJson('/api/countries')
        ->assertOk()
        ->assertJsonPath('meta.source', 'api')
        ->assertJsonPath('meta.notice', null)
        ->assertJsonFragment(['code' => 'ES', 'surcharge_percent' => 20])
        ->assertJsonFragment(['code' => 'EC', 'surcharge_percent' => 0]);

    expect(Country::query()->count())->toBe(2);
    Http::assertSentCount(2);
});

it('falls back to the snapshot when the api times out, fails or returns an unexpected body', function (string $scenario) {
    config(['services.restcountries.key' => 'test-key']);

    Http::fake(match ($scenario) {
        'timeout' => fn () => throw new ConnectionException('timed out'),
        'http error' => ['*' => Http::response(['message' => 'unavailable'], 503)],
        'deprecated body' => ['*' => Http::response([
            'success' => false,
            'data' => null,
            'errors' => [['message' => 'deprecated']],
        ])],
        'demo payload' => ['*' => Http::response([
            'data' => [
                '_demo' => ['message' => 'demo'],
                'objects' => [countryRow('CA', 'Canadá', 'Americas', 'North America')],
                'meta' => ['more' => false, 'count' => 1],
            ],
        ])],
        'not json' => ['*' => Http::response('upstream exploded', 200, ['Content-Type' => 'text/plain'])],
    });

    $response = $this->getJson('/api/countries');

    $response->assertOk()->assertJsonPath('meta.source', 'snapshot');
    expect($response->json('data'))->toHaveCount(250);
    expect(Country::query()->count())->toBe(0);
})->with([
    'timeout',
    'http error',
    'deprecated body',
    'demo payload',
    'not json',
]);

it('prefers the database cache when the api is down and countries were synced before', function () {
    config(['services.restcountries.key' => 'test-key']);
    Country::factory()->create([
        'code' => 'EC',
        'name' => 'Ecuador',
        'region' => 'Americas',
        'subregion' => 'South America',
        'pricing_region' => 'South America',
        'flag_emoji' => '🇪🇨',
    ]);
    Http::fake(fn () => throw new ConnectionException('down'));

    $this->getJson('/api/countries')
        ->assertOk()
        ->assertJsonPath('meta.source', 'cache')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'EC');
});

/**
 * @return array<string, mixed>
 */
function countryRow(string $code, string $name, string $region, string $subregion): array
{
    return [
        'names' => [
            'common' => $name,
            'translations' => ['spa' => ['common' => $name]],
        ],
        'codes' => ['alpha_2' => $code],
        'region' => $region,
        'subregion' => $subregion,
        'flag' => ['emoji' => '🏳️'],
    ];
}
