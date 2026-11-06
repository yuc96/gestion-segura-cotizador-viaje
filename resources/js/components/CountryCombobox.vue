<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { countryMatches } from '../countrySearch';
import type { Country } from '../types';

const props = defineProps<{
    modelValue: string;
    countries: Country[];
    invalid?: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const root = ref<HTMLElement | null>(null);
const open = ref(false);
const query = ref('');
const active = ref(0);

const selected = computed(() => props.countries.find((country) => country.code === props.modelValue) ?? null);

const matches = computed(() => props.countries.filter((country) => countryMatches(country, query.value)));

watch(selected, (country) => {
    if (country && !open.value) {
        query.value = country.name;
    }
}, { immediate: true });

watch(query, () => {
    active.value = 0;
});

function choose(country: Country) {
    emit('update:modelValue', country.code);
    query.value = country.name;
    open.value = false;
}

function onInput(event: Event) {
    query.value = (event.target as HTMLInputElement).value;
    open.value = true;

    if (selected.value && query.value.trim() !== selected.value.name) {
        emit('update:modelValue', '');
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;
        active.value = Math.min(active.value + 1, Math.max(matches.value.length - 1, 0));
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = Math.max(active.value - 1, 0);
    }

    if (event.key === 'Enter' && open.value && matches.value[active.value]) {
        event.preventDefault();
        choose(matches.value[active.value]);
    }

    if (event.key === 'Escape') {
        open.value = false;
    }
}

function onDocumentClick(event: MouseEvent) {
    if (!root.value?.contains(event.target as Node)) {
        open.value = false;

        if (selected.value) {
            query.value = selected.value.name;
        }
    }
}

onMounted(() => document.addEventListener('mousedown', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentClick));
</script>

<template>
    <div ref="root" class="relative">
        <input
            class="field-input"
            role="combobox"
            aria-autocomplete="list"
            aria-controls="country-options"
            :aria-expanded="open"
            :value="query"
            :disabled="loading"
            placeholder="Japón, Japon, JP…"
            :aria-invalid="invalid"
            autocomplete="off"
            @focus="open = true"
            @input="onInput"
            @keydown="onKeydown"
        >
        <ul
            v-if="open"
            id="country-options"
            role="listbox"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-line bg-white py-1 shadow-lg"
        >
            <li v-if="loading" class="px-3 py-2 text-sm text-muted">Cargando países…</li>
            <li v-else-if="matches.length === 0" class="px-3 py-2 text-sm text-muted">Ningún país coincide con la búsqueda.</li>
            <li v-for="(country, index) in matches" v-else :key="country.code" role="presentation">
                <button
                    type="button"
                    role="option"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm"
                    :class="index === active ? 'bg-gold-100' : 'hover:bg-paper'"
                    :aria-selected="country.code === modelValue"
                    @mouseenter="active = index"
                    @click="choose(country)"
                >
                    <span>{{ country.flag_emoji }} {{ country.name }}</span>
                    <span class="text-xs text-muted">{{ country.code }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>
