import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { countryMatches, filterCountries } from '../../resources/js/countrySearch.ts';
import { displayFromIso, isoFromDisplay, maskDayMonthYear } from '../../resources/js/birthDate.ts';

const japan = { code: 'JP', name: 'Japón', name_en: 'Japan' };

describe('country search', () => {
    for (const query of ['Japon', 'Japón', 'japan', 'JP']) {
        it(`matches ${query}`, () => {
            assert.equal(countryMatches(japan, query), true);
        });
    }

    it('rejects an unrelated name', () => {
        assert.equal(countryMatches(japan, 'Ecuador'), false);
    });

    it('lists prefix matches before partial names', () => {
        const countries = [
            { code: 'AB', name: 'Abjasia', name_en: 'Abkhazia' },
            { code: 'JP', name: 'Japón', name_en: 'Japan' },
            { code: 'JM', name: 'Jamaica', name_en: 'Jamaica' },
            { code: 'PA', name: 'Panamá', name_en: 'Panama' },
            { code: 'ES', name: 'España', name_en: 'Spain' },
        ];

        assert.deepEqual(
            filterCountries(countries, 'ja').map((country) => country.code),
            ['JP', 'JM', 'AB'],
        );
        assert.deepEqual(
            filterCountries(countries, 'pon').map((country) => country.code),
            ['JP'],
        );
        assert.deepEqual(
            filterCountries(countries, 'pa').map((country) => country.code),
            ['PA', 'JP', 'ES'],
        );
        assert.deepEqual(
            filterCountries(countries, '').map((country) => country.code),
            ['AB', 'JP', 'JM', 'PA', 'ES'],
        );
    });
});

describe('birth date mask', () => {
    it('inserts slashes while typing', () => {
        assert.equal(maskDayMonthYear('1'), '1');
        assert.equal(maskDayMonthYear('1205'), '12/05');
        assert.equal(maskDayMonthYear('12051990'), '12/05/1990');
    });

    it('converts a complete date to iso and back', () => {
        assert.equal(isoFromDisplay('12/04/1990'), '1990-04-12');
        assert.equal(displayFromIso('1990-04-12'), '12/04/1990');
        assert.equal(isoFromDisplay('31/02/1990'), null);
    });
});
