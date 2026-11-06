import { defineStore } from 'pinia';
import { api } from '../api';
import type { Tariff } from '../types';

export const usePricingStore = defineStore('pricing', {
    state: () => ({
        tariff: null as Tariff | null,
        loading: false,
        error: null as string | null,
    }),
    actions: {
        async load() {
            if (this.tariff) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const payload = await api<{ data: Tariff }>('/pricing');
                this.tariff = payload.data;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'No se pudo cargar la tarifa.';
            } finally {
                this.loading = false;
            }
        },
    },
});
