<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import StatusBadge from '../components/StatusBadge.vue';
import { formatDate, formatDateTime, formatUsd } from '../format';
import { useQuoteStore } from '../stores/quotes';

const quotes = useQuoteStore();

const filters = reactive({
    search: '',
    status: '',
    departure_from: '',
    departure_to: '',
    page: 1,
    per_page: 10,
});

function load() {
    quotes.list({ ...filters });
}

function search() {
    filters.page = 1;
    load();
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.departure_from = '';
    filters.departure_to = '';
    filters.page = 1;
    load();
}

function goTo(page) {
    filters.page = page;
    load();
}

onMounted(load);
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gold-600">Consulta</p>
            <h1 class="mt-2 font-serif text-4xl text-navy-950">Cotizaciones y contrataciones</h1>
        </div>
        <RouterLink to="/" class="btn btn-primary">Nueva cotización</RouterLink>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-line bg-card p-4 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="search">
        <label class="lg:col-span-2">
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Buscar</span>
            <input v-model="filters.search" class="field-input" placeholder="Nombre, cédula o destino">
        </label>
        <label>
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Estado</span>
            <select v-model="filters.status" class="field-input">
                <option value="">Todos</option>
                <option value="Cotizado">Cotizado</option>
                <option value="Contratado">Contratado</option>
            </select>
        </label>
        <label>
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Salida desde</span>
            <input v-model="filters.departure_from" class="field-input" type="date">
        </label>
        <label>
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted">Salida hasta</span>
            <input v-model="filters.departure_to" class="field-input" type="date">
        </label>
        <div class="flex items-end gap-2">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <button class="btn btn-ghost" type="button" @click="clearFilters">Limpiar</button>
        </div>
    </form>

    <p v-if="quotes.loading" class="mt-6 text-muted">Cargando registros…</p>
    <p v-else-if="quotes.error" class="mt-6 rounded-2xl bg-red-50 px-4 py-3 text-sm text-danger">
        {{ quotes.error }}
        <button type="button" class="ml-2 font-semibold underline" @click="load">Reintentar</button>
    </p>
    <p v-else-if="quotes.items.length === 0" class="mt-6 rounded-3xl border border-dashed border-line bg-card px-6 py-10 text-center text-muted">
        No hay cotizaciones con esos filtros.
    </p>

    <div v-else class="mt-6">
        <p class="mb-3 text-sm text-muted">{{ quotes.meta?.total ?? quotes.items.length }} registros</p>

        <div class="hidden overflow-hidden rounded-3xl border border-line bg-card md:block">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Cliente</th>
                        <th class="px-4 py-3 font-semibold">Identificación</th>
                        <th class="px-4 py-3 font-semibold">Destino</th>
                        <th class="px-4 py-3 font-semibold">Salida</th>
                        <th class="px-4 py-3 font-semibold">Regreso</th>
                        <th class="px-4 py-3 font-semibold">Valor</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Creación</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="quote in quotes.items" :key="quote.id" class="border-t border-line">
                        <td class="px-4 py-3">
                            <RouterLink class="font-semibold text-navy-900" :to="{ name: 'quote.show', params: { id: quote.id } }">
                                {{ quote.insured.full_name }}
                            </RouterLink>
                        </td>
                        <td class="px-4 py-3">{{ quote.insured.identification }}</td>
                        <td class="px-4 py-3">{{ quote.destination.flag_emoji }} {{ quote.destination.name }}</td>
                        <td class="px-4 py-3">{{ formatDate(quote.departure_date) }}</td>
                        <td class="px-4 py-3">{{ formatDate(quote.return_date) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ formatUsd(quote.pricing.total_amount) }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="quote.status" /></td>
                        <td class="px-4 py-3">{{ formatDateTime(quote.created_at) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            <RouterLink
                v-for="quote in quotes.items"
                :key="quote.id"
                class="rounded-3xl border border-line bg-card p-4 text-inherit no-underline"
                :to="{ name: 'quote.show', params: { id: quote.id } }"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ quote.insured.full_name }}</p>
                        <p class="text-sm text-muted">{{ quote.insured.identification }}</p>
                    </div>
                    <StatusBadge :status="quote.status" />
                </div>
                <p class="mt-3">{{ quote.destination.flag_emoji }} {{ quote.destination.name }}</p>
                <p class="text-sm text-muted">{{ formatDate(quote.departure_date) }} → {{ formatDate(quote.return_date) }}</p>
                <p class="mt-2 font-semibold">{{ formatUsd(quote.pricing.total_amount) }}</p>
                <p class="text-xs text-muted">{{ formatDateTime(quote.created_at) }}</p>
            </RouterLink>
        </div>

        <div v-if="quotes.meta && quotes.meta.last_page > 1" class="mt-4 flex items-center justify-between gap-3">
            <button class="btn btn-ghost" type="button" :disabled="quotes.meta.current_page <= 1" @click="goTo(quotes.meta.current_page - 1)">Anterior</button>
            <span class="text-sm text-muted">Página {{ quotes.meta.current_page }} de {{ quotes.meta.last_page }}</span>
            <button class="btn btn-ghost" type="button" :disabled="quotes.meta.current_page >= quotes.meta.last_page" @click="goTo(quotes.meta.current_page + 1)">Siguiente</button>
        </div>
    </div>
</template>
