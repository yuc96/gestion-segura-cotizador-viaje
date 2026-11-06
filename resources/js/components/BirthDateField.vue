<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { MONTHS_ES, daysInMonth, displayFromIso, isoFromDisplay, maskDayMonthYear } from '../birthDate';

const props = defineProps<{
    modelValue: string;
    invalid?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const root = ref<HTMLElement | null>(null);
const text = ref(displayFromIso(props.modelValue));
const open = ref(false);
const today = new Date();
const viewYear = ref(props.modelValue ? Number(props.modelValue.slice(0, 4)) : today.getFullYear() - 30);
const viewMonth = ref(props.modelValue ? Number(props.modelValue.slice(5, 7)) - 1 : 0);

watch(() => props.modelValue, (iso) => {
    const display = displayFromIso(iso);

    if (display && display !== text.value) {
        text.value = display;
        viewYear.value = Number(iso.slice(0, 4));
        viewMonth.value = Number(iso.slice(5, 7)) - 1;
    }
});

const formatError = computed(() => {
    if (text.value.length < 10) {
        return '';
    }

    return isoFromDisplay(text.value) ? '' : 'Esa fecha no existe. Usa día, mes y año.';
});

const weeks = computed(() => {
    const count = daysInMonth(viewYear.value, viewMonth.value);
    const first = new Date(viewYear.value, viewMonth.value, 1).getDay();
    const offset = (first + 6) % 7;
    const cells: Array<number | null> = [...Array(offset).fill(null), ...Array.from({ length: count }, (_, index) => index + 1)];

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
});

function onInput(event: Event) {
    text.value = maskDayMonthYear((event.target as HTMLInputElement).value);
    const iso = isoFromDisplay(text.value);
    emit('update:modelValue', iso ?? '');

    if (iso) {
        viewYear.value = Number(iso.slice(0, 4));
        viewMonth.value = Number(iso.slice(5, 7)) - 1;
    }
}

function pick(day: number) {
    const iso = isoFromDisplay(
        `${String(day).padStart(2, '0')}/${String(viewMonth.value + 1).padStart(2, '0')}/${String(viewYear.value).padStart(4, '0')}`,
    );

    if (!iso) {
        return;
    }

    text.value = displayFromIso(iso);
    emit('update:modelValue', iso);
    open.value = false;
}

function shiftMonth(step: number) {
    const next = new Date(viewYear.value, viewMonth.value + step, 1);
    viewYear.value = next.getFullYear();
    viewMonth.value = next.getMonth();
}

function onDocumentClick(event: MouseEvent) {
    if (!root.value?.contains(event.target as Node)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentClick));
</script>

<template>
    <div ref="root" class="relative">
        <div class="flex gap-2">
            <input
                class="field-input"
                inputmode="numeric"
                autocomplete="bday"
                placeholder="DD/MM/AAAA"
                maxlength="10"
                :value="text"
                :aria-invalid="invalid || Boolean(formatError)"
                @input="onInput"
            >
            <button class="btn btn-ghost shrink-0 px-3" type="button" aria-label="Abrir calendario" @click="open = !open">
                Calendario
            </button>
        </div>
        <p v-if="formatError" class="mt-1 text-sm text-danger">{{ formatError }}</p>
        <div v-if="open" class="absolute z-20 mt-2 w-full rounded-2xl border border-line bg-white p-3 shadow-lg sm:w-80">
            <div class="grid grid-cols-2 gap-2">
                <label class="text-xs font-semibold text-muted">
                    Mes
                    <select v-model.number="viewMonth" class="field-input mt-1">
                        <option v-for="(month, index) in MONTHS_ES" :key="month" :value="index">{{ month }}</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-muted">
                    Año
                    <input v-model.number="viewYear" class="field-input mt-1" type="number" min="1900" :max="today.getFullYear()" inputmode="numeric">
                </label>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm">
                <button class="btn btn-ghost px-2 py-1" type="button" @click="shiftMonth(-1)">Anterior</button>
                <span class="font-semibold capitalize">{{ MONTHS_ES[viewMonth] }} {{ viewYear }}</span>
                <button class="btn btn-ghost px-2 py-1" type="button" @click="shiftMonth(1)">Siguiente</button>
            </div>
            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-xs text-muted">
                <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sa</span><span>Do</span>
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                <button
                    v-for="(day, index) in weeks"
                    :key="index"
                    type="button"
                    class="h-8 rounded-md text-sm"
                    :class="day ? 'hover:bg-gold-100' : 'pointer-events-none'"
                    :disabled="!day"
                    @click="day && pick(day)"
                >
                    {{ day ?? '' }}
                </button>
            </div>
        </div>
    </div>
</template>
