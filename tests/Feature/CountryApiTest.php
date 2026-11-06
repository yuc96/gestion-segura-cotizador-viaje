<?php

use App\Models\Country;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('loads the graphql catalog, translates known names and stores them', function () {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'countries' => [
                    graphqlCountry('ES', 'Spain', 'Europe'),
                    graphqlCountry('EC', 'Ecuador', 'South America'),
                    ['name' => 'sin código'],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/api/countries');

    $response->assertOk()
        ->assertJsonPath('meta.source', 'api')
        ->assertJsonPath('meta.notice', null)
        ->assertJsonFragment(['code' => 'ES', 'name' => 'España', 'surcharge_percent' => 20])
        ->assertJsonFragment(['code' => 'EC', 'name' => 'Ecuador', 'surcharge_percent' => 0]);

    expect(Country::query()->count())->toBe(2);
    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains((string) $request['query'], 'countries');
    });
});

it('falls back when the countries api times out, fails or returns an unexpected body', function (string $scenario) {
    Http::fake(match ($scenario) {
        'timeout' => fn () => throw new ConnectionException('timed out'),
        'http error' => ['*' => Http::response(['message' => 'unavailable'], 503)],
        'graphql error' => ['*' => Http::response([
            'errors' => [['message' => 'Cannot query field']],
            'data' => null,
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
    'graphql error',
    'not json',
]);

it('writes a json log when the countries api fails', function () {
    $path = storage_path('logs/structured-country-test.log');
    @unlink($path);
    config(['logging.channels.json.handler_with.stream' => $path]);
    Log::forgetChannel('json');

    Http::fake(['*' => Http::response(['errors' => [['message' => 'nope']]], 200)]);

    $this->getJson('/api/countries')->assertOk()->assertJsonPath('meta.source', 'snapshot');

    $raw = (string) file_get_contents($path);
    expect($raw)->toContain('"countries.api.failure"')
        ->and($raw)->toContain('"countries.catalog.fallback"')
        ->and($raw)->toContain('"reason":"unexpected"');

    @unlink($path);
});

it('prefers the database cache when the api is down and countries were synced before', function () {
    Country::factory()->create([
        'code' => 'EC',
        'name' => 'Ecuador',
        'region' => 'South America',
        'subregion' => null,
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
function graphqlCountry(string $code, string $name, string $continent): array
{
    return [
        'code' => $code,
        'name' => $name,
        'emoji' => '🏳️',
        'continent' => ['name' => $continent],
    ];
}
