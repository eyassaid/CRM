<script setup>
import { onBeforeUnmount, useId } from 'vue';

// A search box for a list. `v-model` holds the text; `search` fires once the user stops typing for 300 ms,
// or right away on Enter.
const model = defineModel({ type: String, default: '' });

defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['search']);
const id = useId();

let timer = null;

function input(value) {
    model.value = value;
    clearTimeout(timer);
    timer = setTimeout(() => emit('search'), 300);
}

function searchNow() {
    clearTimeout(timer);
    emit('search');
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div role="search" class="relative">
        <label class="sr-only" :for="id">{{ label }}</label>
        <svg viewBox="0 0 24 24" class="pointer-events-none absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" />
            <path d="M16 16l4.5 4.5" />
        </svg>
        <input :id="id" :value="model" type="search" class="input mt-0 ps-10" :placeholder="placeholder"
            autocomplete="off" @input="input($event.target.value)" @keydown.enter.prevent="searchNow" />
    </div>
</template>
