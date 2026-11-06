import { defineStore } from 'pinia';
import { api } from '../api';
import type { Country } from '../types';

interface CountryResponse {
    data: Country[];
    meta?: {
        source?: string | null;
        notice?: string | null;
    };
}

export const useCountryStore = defineStore('countries', {
    state: () => ({
        items: [] as Country[],
        source: null as string | null,
        notice: null as string | null,
        loading: false,
        error: null as string | null,
    }),
    actions: {
        async load(force = false) {
            if (this.items.length && !force) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const payload = await api<CountryResponse>('/countries');
                this.items = payload.data;
                this.source = payload.meta?.source ?? null;
                this.notice = payload.meta?.notice ?? null;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'No se pudieron cargar los países.';
            } finally {
                this.loading = false;
            }
        },
    },
});
