import { defineStore } from 'pinia';
import { api } from '../api';

export const useCountryStore = defineStore('countries', {
    state: () => ({
        items: [],
        source: null,
        notice: null,
        loading: false,
        error: null,
    }),
    actions: {
        async load(force = false) {
            if (this.items.length && !force) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const payload = await api('/countries');
                this.items = payload.data;
                this.source = payload.meta?.source ?? null;
                this.notice = payload.meta?.notice ?? null;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    },
});
