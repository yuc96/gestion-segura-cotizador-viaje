<script setup lang="ts">
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <label class="field">
        <span class="field-name">{{ label }}</span>
        <slot />
        <span v-if="error" class="field-error" role="alert">{{ error }}</span>
        <span v-else-if="hint" class="field-hint">{{ hint }}</span>
    </label>
</template>
