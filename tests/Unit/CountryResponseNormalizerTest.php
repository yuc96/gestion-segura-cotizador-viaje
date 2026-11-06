<?php

use App\Domain\Exception\CountryProviderException;
use App\Domain\PricingRegion;
use App\Domain\RegionSurchargePolicy;
use App\Infrastructure\Countries\CountryResponseNormalizer;

function normalizer(): CountryResponseNormalizer
{
    return new CountryResponseNormalizer(new RegionSurchargePolicy);
}

it('reads the graphql catalog and skips a malformed row', function () {
    $countries = normalizer()->normalize([
        'data' => [
            'countries' => [
                [
                    'code' => 'es',
                    'name' => 'Spain',
                    'emoji' => '🇪🇸',
                    'continent' => ['name' => 'Europe'],
                ],
                ['name' => 'Sin código'],
            ],
        ],
    ]);

    expect($countries)->toHaveCount(1)
        ->and($countries[0]->code)->toBe('ES')
        ->and($countries[0]->name)->toBe('Spain')
        ->and($countries[0]->region)->toBe('Europe')
        ->and($countries[0]->pricingRegion)->toBe(PricingRegion::Europe)
        ->and($countries[0]->flagEmoji)->toBe('🇪🇸');
});

it('reads a v5 page and skips a malformed row', function () {
    $countries = normalizer()->normalize([
        'data' => [
            'objects' => [
                [
                    'names' => [
                        'common' => 'Spain',
                        'translations' => ['spa' => ['common' => 'España']],
                    ],
                    'codes' => ['alpha_2' => 'es'],
                    'region' => 'Europe',
                    'subregion' => 'Southern Europe',
                    'flag' => ['emoji' => '🇪🇸'],
                ],
                ['names' => ['common' => 'Sin código']],
            ],
            'meta' => ['more' => false],
        ],
    ]);

    expect($countries)->toHaveCount(1)
        ->and($countries[0]->code)->toBe('ES')
        ->and($countries[0]->name)->toBe('España')
        ->and($countries[0]->pricingRegion)->toBe(PricingRegion::Europe);
});

it('reads the legacy v3 list shape', function () {
    $countries = normalizer()->normalize([
        [
            'cca2' => 'EC',
            'region' => 'Americas',
            'subregion' => 'South America',
            'flag' => '🇪🇨',
            'name' => ['common' => 'Ecuador'],
            'translations' => ['spa' => ['common' => 'Ecuador']],
        ],
    ]);

    expect($countries[0]->code)->toBe('EC')
        ->and($countries[0]->pricingRegion)->toBe(PricingRegion::SouthAmerica)
        ->and($countries[0]->flagEmoji)->toBe('🇪🇨');
});

it('rejects an error envelope, a demo payload and an empty catalog', function (array $payload) {
    expect(fn () => normalizer()->normalize($payload))->toThrow(CountryProviderException::class);
})->with([
    'deprecation' => [[
        'success' => false,
        'data' => null,
        'errors' => [['message' => 'This API version has been deprecated.']],
    ]],
    'demo key' => [[
        'data' => [
            '_demo' => ['message' => 'demo'],
            'objects' => [[
                'names' => ['common' => 'Canada'],
                'codes' => ['alpha_2' => 'CA'],
                'region' => 'Americas',
                'subregion' => 'North America',
            ]],
        ],
    ]],
    'graphql errors' => [[
        'errors' => [['message' => 'Cannot query field']],
        'data' => null,
    ]],
    'empty objects' => [[
        'data' => ['objects' => []],
    ]],
    'unexpected object' => [[
        'data' => ['hello' => 'world'],
    ]],
]);
