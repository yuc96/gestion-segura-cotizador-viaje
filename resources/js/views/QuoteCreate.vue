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

const selectedCountry = computed(() => countries.items.find((country) => country.code === form.destination_code) ?? null);

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
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1.6fr)_minmax(18rem,0.8fr)]">
        <section>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gold-600">Nueva cotización</p>
            <h1 class="mt-2 font-serif text-4xl text-navy-950">Seguro para el viaje</h1>
            <p class="mt-3 max-w-2xl text-lg text-muted">
                Ingresa los datos del asegurado y del viaje. La prima la calcula el servidor: USD 3 por día, más el recargo de la región.
            </p>

            <form class="mt-6 rounded-3xl border border-line bg-card p-5 shadow-sm sm:p-7" @submit.prevent="submit">
                <h2 class="font-serif text-2xl text-navy-900">Asegurado</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
                    <div class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-semibold text-navy-950">Fecha de nacimiento</span>
                        <BirthDateField v-model="form.birth_date" :invalid="Boolean(errorFor('birth_date'))" @update:model-value="touch('birth_date')" />
                        <p class="mt-1 text-xs text-muted">DD/MM/AAAA. Entre 18 y 85 años en la fecha de salida.</p>
                        <p v-if="errorFor('birth_date')" class="mt-1 text-sm text-danger" role="alert">{{ errorFor('birth_date') }}</p>
                    </div>
                </div>

                <h2 class="mt-8 font-serif text-2xl text-navy-900">Viaje</h2>
                <p v-if="catalogNotice" class="mt-3 text-sm text-muted">{{ catalogNotice }}</p>
                <p v-if="countries.error" class="mt-3 rounded-2xl bg-red-50 px-4 py-3 text-sm text-danger">
                    {{ countries.error }}
                    <button type="button" class="ml-2 font-semibold underline" @click="countries.load(true)">Reintentar</button>
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-semibold text-navy-950">País de destino</span>
                        <CountryCombobox
                            v-model="form.destination_code"
                            :countries="countries.items"
                            :loading="countries.loading"
                            :invalid="Boolean(errorFor('destination_code'))"
                            @update:model-value="touch('destination_code')"
                        />
                        <p class="mt-1 text-xs text-muted">Escribe en español o inglés, con o sin tilde. También vale el código ISO.</p>
                        <p v-if="errorFor('destination_code')" class="mt-1 text-sm text-danger" role="alert">{{ errorFor('destination_code') }}</p>
                    </div>
                    <Field label="Fecha de salida" :error="errorFor('departure_date')">
                        <input v-model="form.departure_date" class="field-input" type="date" :aria-invalid="Boolean(errorFor('departure_date'))" @input="touch('departure_date')">
                    </Field>
                    <Field label="Fecha de regreso" :error="errorFor('return_date')">
                        <input v-model="form.return_date" class="field-input" type="date" :aria-invalid="Boolean(errorFor('return_date'))" @input="touch('return_date')">
                    </Field>
                </div>

                <p v-if="selectedCountry" class="mt-4 text-sm text-muted">
                    {{ selectedCountry.flag_emoji }} {{ selectedCountry.name }} · {{ selectedCountry.pricing_region_label }} · recargo {{ selectedCountry.surcharge_percent }}%.
                </p>
                <p v-if="quotes.error && Object.keys(quotes.fieldErrors).length === 0" class="mt-4 text-sm text-danger" role="alert">{{ quotes.error }}</p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button class="btn btn-primary" type="submit" :disabled="quotes.saving">
                        {{ quotes.saving ? 'Calculando…' : 'Calcular cotización' }}
                    </button>
                    <span class="text-sm text-muted">El total aparece en la siguiente pantalla.</span>
                </div>
            </form>
        </section>

        <aside class="h-fit rounded-3xl border border-line bg-navy-950 p-6 text-white lg:sticky lg:top-6">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold-500">Tarifa publicada</p>
            <h2 class="mt-2 font-serif text-3xl">USD 3 por día</h2>
            <p class="mt-2 text-sm text-white/75">El recargo se aplica sobre la tarifa base, según la región del destino.</p>

            <p v-if="pricing.loading" class="mt-6 text-sm text-white/70">Cargando la tabla…</p>
            <p v-else-if="pricing.error" class="mt-6 text-sm text-gold-100">La tabla no se pudo cargar. La cotización igual se calcula en el servidor.</p>
            <ul v-else-if="pricing.tariff" class="mt-6 divide-y divide-white/10">
                <li v-for="region in pricing.tariff.regions" :key="region.code" class="flex items-center justify-between py-2.5 text-sm">
                    <span>{{ region.label }}</span>
                    <span class="font-semibold text-gold-100">{{ region.surcharge_percent }}%</span>
                </li>
            </ul>
            <p class="mt-6 text-xs leading-5 text-white/60">
                Los días se cuentan desde la salida hasta el regreso, ambos inclusive. Un viaje del 1 al 10 dura 10 días. Centroamérica y el Caribe se tarifican con Norteamérica. Antártida y regiones no listadas usan el recargo de otras regiones.
            </p>
        </aside>
    </div>
</template>
