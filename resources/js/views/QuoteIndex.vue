<script setup lang="ts">
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

function goTo(page: number) {
    filters.page = page;
    load();
}

onMounted(load);
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Consulta</p>
                <h1 class="page-title">Cotizaciones</h1>
                <p class="lede lede-sm">Listado interno. La cotización del cliente sigue siendo pública.</p>
            </div>
            <RouterLink to="/" class="btn btn-primary">Nueva cotización</RouterLink>
        </div>

        <form class="card filter-grid" @submit.prevent="search">
            <label class="filter-search">
                <span class="filter-label">Buscar</span>
                <input v-model="filters.search" class="field-input" placeholder="Nombre, cédula o destino">
            </label>
            <label>
                <span class="filter-label">Estado</span>
                <select v-model="filters.status" class="field-input">
                    <option value="">Todos</option>
                    <option value="Cotizado">Cotizado</option>
                    <option value="Contratado">Contratado</option>
                </select>
            </label>
            <label>
                <span class="filter-label">Salida desde</span>
                <input v-model="filters.departure_from" class="field-input" type="date">
            </label>
            <label>
                <span class="filter-label">Salida hasta</span>
                <input v-model="filters.departure_to" class="field-input" type="date">
            </label>
            <div class="actions is-fill filter-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <button class="btn btn-ghost" type="button" @click="clearFilters">Limpiar</button>
            </div>
        </form>

        <p v-if="quotes.loading" class="muted">Cargando registros…</p>
        <p v-else-if="quotes.error" class="alert">
            {{ quotes.error }}
            <button type="button" class="ml-2 font-semibold underline" @click="load">Reintentar</button>
        </p>
        <p v-else-if="quotes.items.length === 0" class="card empty">
            No hay cotizaciones con esos filtros.
        </p>

        <div v-else class="stack">
            <p class="meta">{{ quotes.meta?.total ?? quotes.items.length }} registros</p>

            <div class="card card-flush table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Identificación</th>
                            <th>Destino</th>
                            <th>Salida</th>
                            <th>Regreso</th>
                            <th>Valor</th>
                            <th>Estado</th>
                            <th>Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="quote in quotes.items" :key="quote.id">
                            <td>
                                <RouterLink class="font-semibold text-navy-900" :to="{ name: 'quote.show', params: { id: String(quote.id) } }">
                                    {{ quote.insured.full_name }}
                                </RouterLink>
                            </td>
                            <td>{{ quote.insured.identification }}</td>
                            <td>{{ quote.destination.flag_emoji }} {{ quote.destination.name }}</td>
                            <td>{{ formatDate(quote.departure_date) }}</td>
                            <td>{{ formatDate(quote.return_date) }}</td>
                            <td class="font-semibold">{{ formatUsd(quote.pricing.total_amount) }}</td>
                            <td><StatusBadge :status="quote.status" /></td>
                            <td>{{ formatDateTime(quote.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cards-only stack">
                <RouterLink
                    v-for="quote in quotes.items"
                    :key="quote.id"
                    class="card card-link"
                    :to="{ name: 'quote.show', params: { id: String(quote.id) } }"
                >
                    <div class="card-body">
                        <div class="card-topline">
                            <div>
                                <p class="font-semibold">{{ quote.insured.full_name }}</p>
                                <p class="field-hint">{{ quote.insured.identification }}</p>
                            </div>
                            <StatusBadge :status="quote.status" />
                        </div>
                        <p>{{ quote.destination.flag_emoji }} {{ quote.destination.name }}</p>
                        <p class="muted text-sm">{{ formatDate(quote.departure_date) }} → {{ formatDate(quote.return_date) }}</p>
                        <p class="font-semibold">{{ formatUsd(quote.pricing.total_amount) }}</p>
                        <p class="fine">{{ formatDateTime(quote.created_at) }}</p>
                    </div>
                </RouterLink>
            </div>

            <div v-if="quotes.meta && quotes.meta.last_page > 1" class="pager">
                <button class="btn btn-ghost" type="button" :disabled="quotes.meta.current_page <= 1" @click="goTo(quotes.meta.current_page - 1)">Anterior</button>
                <span>Página {{ quotes.meta.current_page }} de {{ quotes.meta.last_page }}</span>
                <button class="btn btn-ghost" type="button" :disabled="quotes.meta.current_page >= quotes.meta.last_page" @click="goTo(quotes.meta.current_page + 1)">Siguiente</button>
            </div>
        </div>
    </div>
</template>
