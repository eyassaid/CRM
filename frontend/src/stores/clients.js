import { ref } from 'vue';
import { defineStore } from 'pinia';
import clientService from '@/api/services/clientService';

export const useClientStore = defineStore('clients', () => {
    // State
    const items = ref([]);
    const current = ref(null);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    async function fetchAll(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const res = await clientService.list(params);
            items.value = res.data;
            meta.value = res.meta ?? null;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOne(id) {
        loading.value = true;
        error.value = null;
        current.value = null;
        try {
            const res = await clientService.get(id);
            current.value = res.data;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function create(fields) {
        const res = await clientService.create(fields);
        items.value.unshift(res.data);
        return res.data;
    }

    async function update(id, fields) {
        const res = await clientService.update(id, fields);
        const i = items.value.findIndex((c) => c.id === id);
        if (i !== -1) items.value[i] = res.data;
        if (current.value?.id === id) current.value = res.data;
        return res.data;
    }

    async function remove(id) {
        await clientService.remove(id);
        items.value = items.value.filter((c) => c.id !== id);
    }

    function $reset() {
        items.value = [];
        current.value = null;
        meta.value = null;
        error.value = null;
    }

    return { items, current, meta, loading, error, fetchAll, fetchOne, create, update, remove, $reset };
});
