import { defineStore } from 'pinia';
import { api } from '../api';

export const usePricingStore = defineStore('pricing', {
    state: () => ({
        tariff: null,
        loading: false,
        error: null,
    }),
    actions: {
        async load() {
            if (this.tariff) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const payload = await api('/pricing');
                this.tariff = payload.data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    },
});
