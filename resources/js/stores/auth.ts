import { defineStore } from 'pinia';
import { api, ApiError, ensureCsrfCookie, http } from '../api';
import type { AuthUser } from '../types';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as AuthUser | null,
        ready: false,
        loading: false,
        error: null as string | null,
        fieldErrors: {} as Record<string, string[]>,
    }),
    actions: {
        async fetchUser() {
            try {
                const payload = await api<{ data: AuthUser | null }>('/user');
                this.user = payload.data;
            } catch {
                this.user = null;
            } finally {
                this.ready = true;
            }
        },
        async login(email: string, password: string) {
            this.loading = true;
            this.error = null;
            this.fieldErrors = {};

            try {
                await ensureCsrfCookie();
                const payload = await http<{ data: AuthUser }>('/login', {
                    method: 'POST',
                    body: { email, password },
                });
                this.user = payload.data;
                this.ready = true;

                return payload.data;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'No se pudo iniciar sesión.';
                this.fieldErrors = error instanceof ApiError ? error.payload?.errors ?? {} : {};
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            await ensureCsrfCookie();
            await http('/logout', { method: 'POST' });
            this.user = null;
        },
    },
});
