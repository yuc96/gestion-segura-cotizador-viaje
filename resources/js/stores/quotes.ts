import { defineStore } from 'pinia';
import { api, ApiError } from '../api';
import type { PageMeta, Quote, QuoteForm } from '../types';

interface QuoteResponse {
    data: Quote;
}

interface QuoteListResponse {
    data: Quote[];
    meta: PageMeta;
}

export const useQuoteStore = defineStore('quotes', {
    state: () => ({
        current: null as Quote | null,
        items: [] as Quote[],
        meta: null as PageMeta | null,
        loading: false,
        saving: false,
        error: null as string | null,
        fieldErrors: {} as Record<string, string[]>,
    }),
    actions: {
        async create(form: QuoteForm) {
            this.saving = true;
            this.error = null;
            this.fieldErrors = {};

            try {
                const payload = await api<QuoteResponse>('/quotes', { method: 'POST', body: form });
                this.current = payload.data;

                return payload.data;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'No se pudo cotizar.';
                this.fieldErrors = error instanceof ApiError ? error.payload?.errors ?? {} : {};
                throw error;
            } finally {
                this.saving = false;
            }
        },
        async fetchOne(id: string | number) {
            this.loading = true;
            this.error = null;

            try {
                const payload = await api<QuoteResponse>(`/quotes/${id}`);
                this.current = payload.data;
            } catch (error) {
                this.current = null;
                this.error = error instanceof Error ? error.message : 'No se encontró la cotización.';
            } finally {
                this.loading = false;
            }
        },
        async contract(id: string | number) {
            this.saving = true;
            this.error = null;

            try {
                const payload = await api<QuoteResponse>(`/quotes/${id}/contract`, { method: 'POST' });
                this.current = payload.data;

                return payload.data;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'No se pudo contratar.';
                throw error;
            } finally {
                this.saving = false;
            }
        },
        async list(filters: Record<string, string | number | null | undefined>) {
            this.loading = true;
            this.error = null;

            try {
                const payload = await api<QuoteListResponse>('/quotes', { query: filters });
                this.items = payload.data;
                this.meta = payload.meta;
            } catch (error) {
                this.items = [];
                this.meta = null;
                this.error = error instanceof Error ? error.message : 'No se pudo cargar el listado.';
            } finally {
                this.loading = false;
            }
        },
    },
});
