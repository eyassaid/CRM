<script setup>
import { computed } from 'vue';

// Editable invoice lines ([{ name, cost }], at least one) with their total. `errors` are the form's 422 errors
// (`items`, `items.0.name`, ...). The default slot holds a hint shown under the legend.
const items = defineModel({ type: Array, required: true });

defineProps({ errors: { type: Object, default: () => ({}) } });

const total = computed(() => items.value.reduce((sum, item) => sum + (Number(item.cost) || 0), 0));

function change(index, key, value) {
    items.value = items.value.map((item, i) => (i === index ? { ...item, [key]: value } : item));
}

function add() {
    items.value = [...items.value, { name: '', cost: '' }];
}

function removeAt(index) {
    items.value = items.value.filter((_, i) => i !== index);
}
</script>

<template>
    <fieldset class="space-y-3 rounded border border-rule bg-porcelain p-4">
        <legend class="label px-1">Invoice items</legend>
        <p v-if="$slots.default" class="text-sm text-ink-muted"><slot /></p>
        <div v-for="(item, index) in items" :key="index">
            <div class="flex items-start gap-2">
                <input :value="item.name" type="text" class="input mt-0" placeholder="Item"
                    :aria-label="`Item ${index + 1} name`" @input="change(index, 'name', $event.target.value)" />
                <input :value="item.cost" type="number" min="0" step="0.01" class="input mt-0 w-28"
                    placeholder="Cost" :aria-label="`Item ${index + 1} cost`"
                    @input="change(index, 'cost', $event.target.value)" />
                <button type="button" class="link-danger mt-2" :disabled="items.length === 1"
                    @click="removeAt(index)">Remove</button>
            </div>
            <p v-if="errors[`items.${index}.name`]" class="field-error">{{ errors[`items.${index}.name`][0] }}</p>
            <p v-if="errors[`items.${index}.cost`]" class="field-error">{{ errors[`items.${index}.cost`][0] }}</p>
        </div>
        <p v-if="errors.items" class="field-error">{{ errors.items[0] }}</p>
        <div class="flex items-center justify-between border-t border-rule pt-3 text-sm">
            <button type="button" class="link" @click="add">Add item</button>
            <span class="tabular font-semibold text-ink">Total {{ total.toFixed(2) }}</span>
        </div>
    </fieldset>
</template>
