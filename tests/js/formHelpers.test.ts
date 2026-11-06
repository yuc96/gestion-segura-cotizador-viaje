import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { countryMatches } from '../../resources/js/countrySearch.ts';
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
