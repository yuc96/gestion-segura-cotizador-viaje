<script setup lang="ts">
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import BirthDateField from '../components/BirthDateField.vue';
import CountryCombobox from '../components/CountryCombobox.vue';
import Field from '../components/Field.vue';
import { firstError } from '../api';
import { useCountryStore } from '../stores/countries';
import { usePricingStore } from '../stores/pricing';
import { useQuoteStore } from '../stores/quotes';
import { validateQuoteForm } from '../validation/quoteForm';

const router = useRouter();
const countries = useCountryStore();
const pricing = usePricingStore();
const quotes = useQuoteStore();

const localErrors = reactive<Record<string, string>>({});

const form = reactive({
    first_name: '',
    last_name: '',
    identification: '',
    email: '',
    birth_date: '',
    destination_code: '',
    departure_date: '',
    return_date: '',
});

const catalogNotice = computed(() => {
    if (!countries.source || countries.source === 'api') {
        return '';
    }

    return 'Países cargados desde el catálogo local.';
});

function errorFor(key: string): string {
    return localErrors[key] || firstError(quotes.fieldErrors[key]);
}

function touch(key: string) {
    delete localErrors[key];

    if (quotes.fieldErrors[key]) {
        const next = { ...quotes.fieldErrors };
        delete next[key];
        quotes.fieldErrors = next;
    }
}

async function submit() {
    Object.keys(localErrors).forEach((key) => delete localErrors[key]);
    const errors = validateQuoteForm(form);
    Object.assign(localErrors, errors);

    if (Object.keys(errors).length > 0) {
        return;
    }

    try {
        const quote = await quotes.create({ ...form });
        await router.push({ name: 'quote.show', params: { id: String(quote.id) } });
    } catch {
        // El store conserva el mensaje y los errores de campo.
    }
}

onMounted(() => {
    countries.load();
    pricing.load();
});
</script>

<template>
    <div class="split split-quote">
        <section class="page">
            <header>
                <p class="eyebrow">Nueva cotización</p>
                <h1 class="page-title">Seguro para el viaje</h1>
                <p class="lede">
                    Ingresa los datos del asegurado y del viaje. La prima la calcula el servidor: USD 3 por día, más el recargo de la región.
                </p>
            </header>

            <form class="card" @submit.prevent="submit">
                <h2 class="section-title">Asegurado</h2>
                <div class="fields fields-2">
                    <Field label="Nombres" :error="errorFor('first_name')">
                        <input v-model="form.first_name" class="field-input" autocomplete="given-name" :aria-invalid="Boolean(errorFor('first_name'))" @input="touch('first_name')">
                    </Field>
                    <Field label="Apellidos" :error="errorFor('last_name')">
                        <input v-model="form.last_name" class="field-input" autocomplete="family-name" :aria-invalid="Boolean(errorFor('last_name'))" @input="touch('last_name')">
                    </Field>
                    <Field label="Identificación" hint="Cédula o pasaporte, sin espacios." :error="errorFor('identification')">
                        <input v-model="form.identification" class="field-input" autocomplete="off" :aria-invalid="Boolean(errorFor('identification'))" @input="touch('identification')">
                    </Field>
                    <Field label="Correo electrónico" :error="errorFor('email')">
                        <input v-model="form.email" class="field-input" type="email" autocomplete="email" :aria-invalid="Boolean(errorFor('email'))" @input="touch('email')">
                    </Field>
                    <div class="fields-span">
                        <span class="field-name">Fecha de nacimiento</span>
                        <BirthDateField v-model="form.birth_date" :invalid="Boolean(errorFor('birth_date'))" @update:model-value="touch('birth_date')" />
                        <p class="field-hint">DD/MM/AAAA. Entre 18 y 85 años en la fecha de salida.</p>
                        <p v-if="errorFor('birth_date')" class="field-error" role="alert">{{ errorFor('birth_date') }}</p>
                    </div>
                </div>

                <h2 class="section-title">Viaje</h2>
                <p v-if="catalogNotice" class="hint">{{ catalogNotice }}</p>
                <p v-if="countries.error" class="alert">
                    {{ countries.error }}
                    <button type="button" class="ml-2 font-semibold underline" @click="countries.load(true)">Reintentar</button>
                </p>

                <div class="fields fields-2">
                    <div class="fields-span">
                        <label id="destination-label" for="destination-country" class="field-name">País de destino</label>
                        <CountryCombobox
                            v-model="form.destination_code"
                            input-id="destination-country"
                            label-id="destination-label"
                            :countries="countries.items"
                            :loading="countries.loading"
                            :invalid="Boolean(errorFor('destination_code'))"
                            @update:model-value="touch('destination_code')"
                        />
                        <p v-if="errorFor('destination_code')" class="field-error" role="alert">{{ errorFor('destination_code') }}</p>
                    </div>
                    <Field label="Fecha de salida" :error="errorFor('departure_date')">
                        <input v-model="form.departure_date" class="field-input" type="date" :aria-invalid="Boolean(errorFor('departure_date'))" @input="touch('departure_date')">
                    </Field>
                    <Field label="Fecha de regreso" :error="errorFor('return_date')">
                        <input v-model="form.return_date" class="field-input" type="date" :aria-invalid="Boolean(errorFor('return_date'))" @input="touch('return_date')">
                    </Field>
                </div>

                <p v-if="quotes.error && Object.keys(quotes.fieldErrors).length === 0" class="form-error" role="alert">{{ quotes.error }}</p>

                <div class="actions is-fill">
                    <button class="btn btn-primary" type="submit" :disabled="quotes.saving">
                        {{ quotes.saving ? 'Calculando…' : 'Calcular cotización' }}
                    </button>
                    <span class="muted text-sm">El total aparece en la siguiente pantalla.</span>
                </div>
            </form>
        </section>

        <aside class="card-dark is-sticky">
            <p class="eyebrow">Tarifa publicada</p>
            <h2 class="aside-title">USD 3 por día</h2>
            <p class="hint muted">El recargo se aplica sobre la tarifa base, según la región del destino.</p>

            <p v-if="pricing.loading" class="hint muted">Cargando la tabla…</p>
            <p v-else-if="pricing.error" class="hint is-warn">La tabla no se pudo cargar. La cotización igual se calcula en el servidor.</p>
            <ul v-else-if="pricing.tariff" class="tariff">
                <li v-for="region in pricing.tariff.regions" :key="region.code">
                    <span>{{ region.label }}</span>
                    <span>{{ region.surcharge_percent }}%</span>
                </li>
            </ul>
            <p class="fine">
                Los días se cuentan desde la salida hasta el regreso, ambos inclusive. Un viaje del 1 al 10 dura 10 días. Centroamérica y el Caribe se tarifican con Norteamérica. Antártida y regiones no listadas usan el recargo de otras regiones.
            </p>
        </aside>
    </div>
</template>
