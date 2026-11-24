<script setup>
// Works with the `meta` block Laravel adds to paginated resource collections.
defineProps({ meta: { type: Object, default: null } });
defineEmits(['change']);
</script>

<template>
    <div v-if="meta && meta.last_page > 1" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted">
        <span class="tabular">Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span>
        <div class="flex gap-2">
            <button type="button" class="btn-secondary" :disabled="meta.current_page <= 1"
                @click="$emit('change', meta.current_page - 1)">
                Previous
            </button>
            <button type="button" class="btn-secondary" :disabled="meta.current_page >= meta.last_page"
                @click="$emit('change', meta.current_page + 1)">
                Next
            </button>
        </div>
    </div>
</template>
