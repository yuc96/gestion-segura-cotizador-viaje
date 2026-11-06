export interface AuthUser {
    name: string;
    email: string;
}

export interface Country {
    code: string;
    name: string;
    region: string;
    subregion: string | null;
    pricing_region: string;
    pricing_region_label: string;
    surcharge_percent: number;
    flag_emoji: string;
}

export interface TariffRegion {
    code: string;
    label: string;
    surcharge_percent: number;
}

export interface Tariff {
    currency: string;
    daily_rate: string;
    max_days: number;
    regions: TariffRegion[];
}

export interface Quote {
    id: number;
    number: string;
    status: 'Cotizado' | 'Contratado';
    insured: {
        first_name: string;
        last_name: string;
        full_name: string;
        identification: string;
        email: string;
        birth_date: string | null;
    };
    destination: {
        code: string;
        name: string;
        region: string;
        subregion: string | null;
        pricing_region: string;
        pricing_region_label: string;
        flag_emoji: string | null;
    };
    departure_date: string;
    return_date: string;
    trip_days: number;
    pricing: {
        currency: string;
        daily_rate: string;
        base_amount: string;
        surcharge_percent: number;
        surcharge_amount: string;
        total_amount: string;
    };
    contracted_at: string | null;
    created_at: string | null;
}

export interface PageMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface QuoteForm {
    first_name: string;
    last_name: string;
    identification: string;
    email: string;
    birth_date: string;
    destination_code: string;
    departure_date: string;
    return_date: string;
}
