<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import StatusBadge from '../components/StatusBadge.vue';
import { formatDate, formatDateTime, formatUsd } from '../format';
import { useQuoteStore } from '../stores/quotes';

const props = defineProps({
    id: { type: String, required: true },
});

const quotes = useQuoteStore();
const confirming = ref(false);

async function load() {
    confirming.value = false;
    await quotes.fetchOne(props.id);
}

async function contract() {
    try {
        await quotes.contract(props.id);
        confirming.value = false;
    } catch {
        // El mensaje queda en el store.
    }
}

onMounted(load);
watch(() => props.id, load);
</script>

<template>
    <p v-if="quotes.loading" class="text-muted">Cargando la cotización…</p>

    <div v-else-if="quotes.error && !quotes.current" class="rounded-3xl border border-line bg-card p-8">
        <h1 class="font-serif text-3xl text-navy-950">No encontramos esa cotización</h1>
        <p class="mt-2 text-muted">{{ quotes.error }}</p>
        <RouterLink to="/" class="btn btn-primary mt-6">Nueva cotización</RouterLink>
    </div>

    <article v-else-if="quotes.current" class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.7fr)]">
        <section class="rounded-3xl border border-line bg-card p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gold-600">{{ quotes.current.number }}</p>
                <StatusBadge :status="quotes.current.status" />
            </div>
            <h1 class="mt-3 font-serif text-4xl text-navy-950">
                {{ quotes.current.destination.flag_emoji }} {{ quotes.current.destination.name }}
            </h1>
            <p class="mt-2 text-muted">
                {{ quotes.current.insured.full_name }} · {{ quotes.current.insured.identification }}
            </p>

            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Salida</dt>
                    <dd class="text-lg font-semibold">{{ formatDate(quotes.current.departure_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Regreso</dt>
                    <dd class="text-lg font-semibold">{{ formatDate(quotes.current.return_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Días</dt>
                    <dd class="text-lg font-semibold">{{ quotes.current.trip_days }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Región tarifaria</dt>
                    <dd class="text-lg font-semibold">{{ quotes.current.destination.pricing_region_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Correo</dt>
                    <dd>{{ quotes.current.insured.email }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Nacimiento</dt>
                    <dd>{{ formatDate(quotes.current.insured.birth_date) }}</dd>
                </div>
            </dl>
        </section>

        <aside class="h-fit rounded-3xl border border-line bg-card p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold-600">Prima</p>
            <p class="mt-2 font-serif text-4xl text-navy-950">{{ formatUsd(quotes.current.pricing.total_amount) }}</p>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt>Base ({{ quotes.current.trip_days }} × {{ formatUsd(quotes.current.pricing.daily_rate) }})</dt>
                    <dd>{{ formatUsd(quotes.current.pricing.base_amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Recargo {{ quotes.current.pricing.surcharge_percent }}%</dt>
                    <dd>{{ formatUsd(quotes.current.pricing.surcharge_amount) }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-muted">Creada {{ formatDateTime(quotes.current.created_at) }}</p>
            <p v-if="quotes.current.contracted_at" class="text-xs text-ok">Contratada {{ formatDateTime(quotes.current.contracted_at) }}</p>

            <div class="mt-6 flex flex-col gap-3">
                <a class="btn btn-ghost" :href="`/api/quotes/${quotes.current.id}/pdf`">Descargar PDF</a>
                <button
                    v-if="quotes.current.status === 'Cotizado' && !confirming"
                    class="btn btn-gold"
                    type="button"
                    @click="confirming = true"
                >
                    Contratar seguro
                </button>
            </div>

            <div v-if="confirming" class="mt-4 rounded-2xl bg-paper p-4">
                <p class="text-sm">Se registrará el seguro con el total ya cotizado. No hay pasarela de pago.</p>
                <p v-if="quotes.error" class="mt-2 text-sm text-danger">{{ quotes.error }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="button" :disabled="quotes.saving" @click="contract">
                        {{ quotes.saving ? 'Guardando…' : 'Confirmar contratación' }}
                    </button>
                    <button class="btn btn-ghost" type="button" @click="confirming = false">Cancelar</button>
                </div>
            </div>
        </aside>
    </article>
</template>
