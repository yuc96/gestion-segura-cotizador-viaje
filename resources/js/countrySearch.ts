export interface SearchableCountry {
    code: string;
    name: string;
    name_en?: string;
}

export function fold(value: string): string {
    return value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('es').trim();
}

export function countryMatches(country: SearchableCountry, query: string): boolean {
    const needle = fold(query);

    if (!needle) {
        return true;
    }

    return fold(country.name).includes(needle)
        || fold(country.name_en ?? '').includes(needle)
        || country.code.toLowerCase().includes(needle);
}
