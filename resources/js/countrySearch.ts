export interface SearchableCountry {
    code: string;
    name: string;
    name_en?: string;
}

export function fold(value: string): string {
    return value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('es').trim();
}

/**
 * 0 when the Spanish name, English name, or ISO code starts with the query.
 * 1 when the query appears later in one of those fields.
 * null when nothing matches. An empty query matches everything at rank 0.
 */
export function countryRank(country: SearchableCountry, query: string): number | null {
    const needle = fold(query);

    if (!needle) {
        return 0;
    }

    const fields = [fold(country.name), fold(country.name_en ?? ''), country.code.toLowerCase()];

    if (fields.some((field) => field.startsWith(needle))) {
        return 0;
    }

    if (fields.some((field) => field.includes(needle))) {
        return 1;
    }

    return null;
}

export function countryMatches(country: SearchableCountry, query: string): boolean {
    return countryRank(country, query) !== null;
}

export function filterCountries<T extends SearchableCountry>(countries: T[], query: string): T[] {
    return countries
        .map((country, index) => ({ country, index, rank: countryRank(country, query) }))
        .filter((item): item is { country: T; index: number; rank: number } => item.rank !== null)
        .sort((left, right) => left.rank - right.rank || left.index - right.index)
        .map((item) => item.country);
}
