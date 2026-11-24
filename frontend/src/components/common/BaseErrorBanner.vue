<script setup>
defineProps({ message: { type: String, default: '' } });
</script>

<template>
    <p v-if="message" class="mb-4 rounded border border-scarlet/30 bg-scarlet-soft px-3 py-2 text-sm text-scarlet-dark" role="alert">{{ message }}</p>
</template>
