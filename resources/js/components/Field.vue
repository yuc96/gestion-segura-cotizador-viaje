<script setup lang="ts">
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <label class="block">
        <span class="mb-1.5 block text-sm font-semibold text-navy-950">{{ label }}</span>
        <slot />
        <span v-if="error" class="mt-1 block text-sm text-danger" role="alert">{{ error }}</span>
        <span v-else-if="hint" class="mt-1 block text-xs text-muted">{{ hint }}</span>
    </label>
</template>
