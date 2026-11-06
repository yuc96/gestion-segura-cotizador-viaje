<script setup lang="ts">
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
    <p v-if="quotes.loading" class="muted">Cargando la cotización…</p>

    <div v-else-if="quotes.error && !quotes.current" class="card">
        <h1 class="page-title">No encontramos esa cotización</h1>
        <p class="subline">{{ quotes.error }}</p>
        <div class="actions">
            <RouterLink to="/" class="btn btn-primary">Nueva cotización</RouterLink>
        </div>
    </div>

    <article v-else-if="quotes.current" class="split split-show">
        <section class="card">
            <div class="cluster">
                <p class="eyebrow">{{ quotes.current.number }}</p>
                <StatusBadge :status="quotes.current.status" />
            </div>
            <h1 class="page-title">
                {{ quotes.current.destination.flag_emoji }} {{ quotes.current.destination.name }}
            </h1>
            <p class="subline">
                {{ quotes.current.insured.full_name }} · {{ quotes.current.insured.identification }}
            </p>

            <dl class="facts">
                <div>
                    <dt>Salida</dt>
                    <dd class="fact-value">{{ formatDate(quotes.current.departure_date) }}</dd>
                </div>
                <div>
                    <dt>Regreso</dt>
                    <dd class="fact-value">{{ formatDate(quotes.current.return_date) }}</dd>
                </div>
                <div>
                    <dt>Días</dt>
                    <dd class="fact-value">{{ quotes.current.trip_days }}</dd>
                </div>
                <div>
                    <dt>Región tarifaria</dt>
                    <dd class="fact-value">{{ quotes.current.destination.pricing_region_label }}</dd>
                </div>
                <div>
                    <dt>Correo</dt>
                    <dd>{{ quotes.current.insured.email }}</dd>
                </div>
                <div>
                    <dt>Nacimiento</dt>
                    <dd>{{ formatDate(quotes.current.insured.birth_date) }}</dd>
                </div>
            </dl>
        </section>

        <aside class="card split-aside is-sticky">
            <p class="eyebrow">Prima</p>
            <p class="page-title">{{ formatUsd(quotes.current.pricing.total_amount) }}</p>
            <dl class="price-lines">
                <div>
                    <dt>Base ({{ quotes.current.trip_days }} × {{ formatUsd(quotes.current.pricing.daily_rate) }})</dt>
                    <dd>{{ formatUsd(quotes.current.pricing.base_amount) }}</dd>
                </div>
                <div>
                    <dt>Recargo {{ quotes.current.pricing.surcharge_percent }}%</dt>
                    <dd>{{ formatUsd(quotes.current.pricing.surcharge_amount) }}</dd>
                </div>
            </dl>
            <div class="price-meta">
                <p class="fine">Creada {{ formatDateTime(quotes.current.created_at) }}</p>
                <p v-if="quotes.current.contracted_at" class="ok-line">Contratada {{ formatDateTime(quotes.current.contracted_at) }}</p>
                <p>El PDF usa la prima ya guardada. Contratar no cobra: solo cambia el estado.</p>
            </div>

            <div class="actions is-stack">
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

            <div v-if="confirming" class="confirm">
                <p>Se registrará el seguro con el total ya cotizado. No hay pasarela de pago.</p>
                <p v-if="quotes.error" class="form-error">{{ quotes.error }}</p>
                <div class="actions is-stack">
                    <button class="btn btn-primary" type="button" :disabled="quotes.saving" @click="contract">
                        {{ quotes.saving ? 'Guardando…' : 'Confirmar contratación' }}
                    </button>
                    <button class="btn btn-ghost" type="button" @click="confirming = false">Cancelar</button>
                </div>
            </div>
        </aside>
    </article>
</template>
