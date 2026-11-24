import { ref } from 'vue';
import { defineStore } from 'pinia';
import userService from '@/api/services/userService';

export const useUserStore = defineStore('users', () => {
    // State
    const items = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    async function fetchAll(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const res = await userService.list(params);
            items.value = res.data;
            meta.value = res.meta ?? null;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function create(payload) {
        const res = await userService.create(payload);
        items.value.unshift(res.data);
        return res.data;
    }

    async function update(id, payload) {
        const res = await userService.update(id, payload);
        const i = items.value.findIndex((u) => u.id === id);
        if (i !== -1) items.value[i] = res.data;
        return res.data;
    }

    async function remove(id) {
        await userService.remove(id);
        items.value = items.value.filter((u) => u.id !== id);
    }

    function $reset() {
        items.value = [];
        meta.value = null;
        error.value = null;
    }

    return { items, meta, loading, error, fetchAll, create, update, remove, $reset };
});
