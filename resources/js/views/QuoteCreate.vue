<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
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

const countryQuery = ref('');
const localErrors = reactive({});

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

const visibleCountries = computed(() => {
    const query = countryQuery.value.trim().toLocaleLowerCase('es');
    const items = countries.items.filter((country) => {
        if (!query) {
            return true;
        }

        return country.name.toLocaleLowerCase('es').includes(query)
            || country.code.toLowerCase().includes(query);
    });

    const selected = countries.items.find((country) => country.code === form.destination_code);

    if (selected && !items.some((country) => country.code === selected.code)) {
        return [selected, ...items];
    }

    return items;
});

const selectedCountry = computed(() => countries.items.find((country) => country.code === form.destination_code) ?? null);

const catalogNotice = computed(() => {
    if (!countries.source || countries.source === 'api') {
        return '';
    }

    return 'Países cargados desde el catálogo local.';
});

function errorFor(key) {
    return localErrors[key] || firstError(quotes.fieldErrors[key]);
}

function touch(key) {
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
        await router.push({ name: 'quote.show', params: { id: quote.id } });
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
                    <Field label="Fecha de nacimiento" hint="Debe tener entre 18 y 85 años al salir." :error="errorFor('birth_date')">
                        <input v-model="form.birth_date" class="field-input" type="date" :aria-invalid="Boolean(errorFor('birth_date'))" @input="touch('birth_date')">
                    </Field>
                </div>

                <h2 class="mt-8 font-serif text-2xl text-navy-900">Viaje</h2>
                <p v-if="catalogNotice" class="mt-3 text-sm text-muted">{{ catalogNotice }}</p>
                <p v-if="countries.error" class="mt-3 rounded-2xl bg-red-50 px-4 py-3 text-sm text-danger">
                    {{ countries.error }}
                    <button type="button" class="ml-2 font-semibold underline" @click="countries.load(true)">Reintentar</button>
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <Field label="Buscar país" hint="Filtra la lista. No cambia la prima.">
                        <input v-model="countryQuery" class="field-input" placeholder="Ecuador, España, Japón…" :disabled="countries.loading">
                    </Field>
                    <Field label="País de destino" :error="errorFor('destination_code')">
                        <select v-model="form.destination_code" class="field-input" :disabled="countries.loading || countries.items.length === 0" :aria-invalid="Boolean(errorFor('destination_code'))" @change="touch('destination_code')">
                            <option value="">{{ countries.loading ? 'Cargando países…' : 'Selecciona un país' }}</option>
                            <option v-for="country in visibleCountries" :key="country.code" :value="country.code">
                                {{ country.flag_emoji }} {{ country.name }}
                            </option>
                        </select>
                    </Field>
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
                <p v-if="visibleCountries.length === 0 && !countries.loading" class="mt-3 text-sm text-muted">Ningún país coincide con la búsqueda.</p>
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
