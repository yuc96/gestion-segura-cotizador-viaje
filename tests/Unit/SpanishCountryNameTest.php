<?php

use App\Domain\CountrySearch;
use App\Domain\SpanishCountryName;

it('names japan in spanish from the bundled map and from icu', function () {
    expect(SpanishCountryName::resolve('JP', 'Japan'))->toBe('Japón')
        ->and(SpanishCountryName::fromIcu('JP'))->toBe('Japón')
        ->and(SpanishCountryName::resolve('es', 'Spain'))->toBe('España');
});

it('matches japon, japón, japan and jp to the same country', function (string $query) {
    expect(CountrySearch::matches('Japón', 'Japan', 'JP', $query))->toBeTrue();
})->with(['Japon', 'Japón', 'japan', 'JP', ' jp ']);

it('does not match an unrelated query', function () {
    expect(CountrySearch::matches('Japón', 'Japan', 'JP', 'Ecuador'))->toBeFalse();
});
