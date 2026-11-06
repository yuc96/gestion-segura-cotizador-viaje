import { defineStore } from 'pinia';
import { api } from '../api';

export const useQuoteStore = defineStore('quotes', {
    state: () => ({
        current: null,
        items: [],
        meta: null,
        loading: false,
        saving: false,
        error: null,
        fieldErrors: {},
    }),
    actions: {
        async create(form) {
            this.saving = true;
            this.error = null;
            this.fieldErrors = {};

            try {
                const payload = await api('/quotes', { method: 'POST', body: form });
                this.current = payload.data;

                return payload.data;
            } catch (error) {
                this.error = error.message;
                this.fieldErrors = error.payload?.errors ?? {};
                throw error;
            } finally {
                this.saving = false;
            }
        },
        async fetchOne(id) {
            this.loading = true;
            this.error = null;

            try {
                const payload = await api(`/quotes/${id}`);
                this.current = payload.data;
            } catch (error) {
                this.current = null;
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        async contract(id) {
            this.saving = true;
            this.error = null;

            try {
                const payload = await api(`/quotes/${id}/contract`, { method: 'POST' });
                this.current = payload.data;

                return payload.data;
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.saving = false;
            }
        },
        async list(filters) {
            this.loading = true;
            this.error = null;

            try {
                const payload = await api('/quotes', { query: filters });
                this.items = payload.data;
                this.meta = payload.meta;
            } catch (error) {
                this.items = [];
                this.meta = null;
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    },
});
