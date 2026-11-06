<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { filterCountries } from '../countrySearch';
import type { Country } from '../types';

const props = defineProps<{
    modelValue: string;
    countries: Country[];
    invalid?: boolean;
    loading?: boolean;
    inputId?: string;
    labelId?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const root = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);
const open = ref(false);
const filtering = ref(false);
const query = ref('');
const active = ref(0);

const selected = computed(() => props.countries.find((country) => country.code === props.modelValue) ?? null);

const matches = computed(() => filterCountries(props.countries, filtering.value ? query.value : ''));

const inputValue = computed(() => {
    if (open.value && filtering.value) {
        return query.value;
    }

    return selected.value?.name ?? '';
});

const activeId = computed(() => {
    const country = matches.value[active.value];

    return open.value && country ? `country-opt-${country.code}` : undefined;
});

watch(matches, () => {
    if (active.value > matches.value.length - 1) {
        active.value = 0;
    }
});

function openList() {
    open.value = true;
    filtering.value = false;
    query.value = '';
    const selectedIndex = props.countries.findIndex((country) => country.code === props.modelValue);
    active.value = selectedIndex >= 0 ? selectedIndex : 0;
    void nextTick(() => {
        input.value?.select();
        scrollActiveIntoView();
    });
}

function closeList() {
    open.value = false;
    filtering.value = false;
    query.value = '';
}

function choose(country: Country) {
    emit('update:modelValue', country.code);
    closeList();
    input.value?.focus();
}

function onFocus() {
    if (!open.value) {
        openList();
    }
}

function onInput(event: Event) {
    query.value = (event.target as HTMLInputElement).value;
    filtering.value = true;
    open.value = true;
    active.value = 0;
    void nextTick(scrollActiveIntoView);
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();

        if (!open.value) {
            openList();

            return;
        }

        active.value = Math.min(active.value + 1, Math.max(matches.value.length - 1, 0));
        scrollActiveIntoView();
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();

        if (!open.value) {
            openList();

            return;
        }

        active.value = Math.max(active.value - 1, 0);
        scrollActiveIntoView();
    }

    if (event.key === 'Home' && open.value) {
        event.preventDefault();
        active.value = 0;
        scrollActiveIntoView();
    }

    if (event.key === 'End' && open.value) {
        event.preventDefault();
        active.value = Math.max(matches.value.length - 1, 0);
        scrollActiveIntoView();
    }

    if (event.key === 'Enter' && open.value) {
        event.preventDefault();
        const country = matches.value[active.value];

        if (country) {
            choose(country);
        }
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeList();
    }

    if (event.key === 'Tab') {
        closeList();
    }
}

function onToggle(event: MouseEvent) {
    event.preventDefault();

    if (open.value) {
        closeList();
        input.value?.focus();

        return;
    }

    input.value?.focus();
    openList();
}

function scrollActiveIntoView() {
    const id = activeId.value;

    if (!id) {
        return;
    }

    document.getElementById(id)?.scrollIntoView({ block: 'nearest' });
}

function onDocumentPointer(event: MouseEvent) {
    if (!root.value?.contains(event.target as Node)) {
        closeList();
    }
}

onMounted(() => document.addEventListener('mousedown', onDocumentPointer));
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentPointer));
</script>

<template>
    <div ref="root" class="relative w-full">
        <div class="relative">
            <input
                :id="inputId ?? 'destination-country'"
                ref="input"
                class="field-input w-full pr-12"
                role="combobox"
                aria-autocomplete="list"
                aria-haspopup="listbox"
                aria-controls="country-listbox"
                :aria-expanded="open"
                :aria-activedescendant="activeId"
                :aria-labelledby="labelId"
                :aria-describedby="selected ? 'country-selection' : undefined"
                :aria-invalid="invalid || undefined"
                :value="inputValue"
                :disabled="loading && countries.length === 0"
                :placeholder="selected && !open ? selected.name : 'Selecciona un país'"
                autocomplete="off"
                spellcheck="false"
                @focus="onFocus"
                @click="onFocus"
                @input="onInput"
                @keydown="onKeydown"
            >
            <button
                type="button"
                class="absolute inset-y-1 right-1 inline-flex w-10 items-center justify-center rounded-lg text-navy-900"
                :aria-label="open ? 'Cerrar la lista de países' : 'Abrir la lista de países'"
                :aria-expanded="open"
                aria-controls="country-listbox"
                tabindex="-1"
                @mousedown="onToggle"
            >
                <svg viewBox="0 0 20 20" class="h-5 w-5" aria-hidden="true" fill="currentColor">
                    <path d="M5.2 7.5 10 12.3l4.8-4.8 1.2 1.2L10 14.7 4 8.7l1.2-1.2Z" />
                </svg>
            </button>
        </div>

        <ul
            v-if="open"
            id="country-listbox"
            ref="list"
            role="listbox"
            :aria-labelledby="labelId"
            class="absolute z-30 mt-1 max-h-[min(18rem,50vh)] w-full overflow-auto rounded-xl border border-line bg-white py-1 shadow-lg"
        >
            <li v-if="loading" class="px-3 py-3 text-sm text-muted">Cargando países…</li>
            <li v-else-if="matches.length === 0" class="px-3 py-3 text-sm text-muted">Ningún país coincide.</li>
            <li
                v-for="(country, index) in matches"
                v-else
                :id="`country-opt-${country.code}`"
                :key="country.code"
                role="option"
                class="country-option"
                :class="index === active ? 'bg-gold-100' : 'hover:bg-paper'"
                :aria-selected="country.code === modelValue"
                @mouseenter="active = index"
                @mousedown.prevent="choose(country)"
            >
                <span class="country-option-name">
                    <span aria-hidden="true">{{ country.flag_emoji }}</span>
                    {{ country.name }}
                </span>
                <span class="country-option-meta">{{ country.pricing_region_label }} · {{ country.surcharge_percent }}%</span>
            </li>
        </ul>

        <p v-if="selected" id="country-selection" class="mt-2 text-sm text-navy-900">
            <span aria-hidden="true">{{ selected.flag_emoji }}</span>
            {{ selected.name }} · {{ selected.pricing_region_label }} · recargo {{ selected.surcharge_percent }}%.
        </p>
    </div>
</template>
