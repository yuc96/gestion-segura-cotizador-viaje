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

it('ranks a name that starts with the query ahead of a partial name', function () {
    expect(CountrySearch::relevance('Jamaica', 'Jamaica', 'JM', 'ja'))->toBe(0)
        ->and(CountrySearch::relevance('Japón', 'Japan', 'JP', 'ja'))->toBe(0)
        ->and(CountrySearch::relevance('Japón', 'Japan', 'JP', 'pon'))->toBe(1)
        ->and(CountrySearch::relevance('Panamá', 'Panama', 'PA', 'pa'))->toBe(0)
        ->and(CountrySearch::relevance('Japón', 'Japan', 'JP', 'ecuador'))->toBeNull();
});
