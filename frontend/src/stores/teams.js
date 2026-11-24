import { ref } from 'vue';
import { defineStore } from 'pinia';
import teamService from '@/api/services/teamService';

export const useTeamStore = defineStore('teams', () => {
    // State: `items` are one section's teams (`sectionId` says whose); `current` is one team with its people.
    const items = ref([]);
    const sectionId = ref(null);
    const current = ref(null);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    async function fetchForSection(id, params = {}) {
        loading.value = true;
        error.value = null;
        if (sectionId.value !== id) {
            items.value = [];
            meta.value = null;
        }
        sectionId.value = id;
        try {
            const res = await teamService.listForSection(id, params);
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
            const res = await teamService.get(id);
            current.value = res.data;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function create(forSectionId, payload) {
        const res = await teamService.create(forSectionId, payload);
        if (sectionId.value === forSectionId) items.value.unshift(res.data);
        return res.data;
    }

    async function update(id, payload) {
        const res = await teamService.update(id, payload);
        const i = items.value.findIndex((t) => t.id === id);
        if (i !== -1) items.value[i] = res.data;
        if (current.value?.id === id) current.value = res.data;
        return res.data;
    }

    async function remove(id) {
        await teamService.remove(id);
        items.value = items.value.filter((t) => t.id !== id);
    }

    function $reset() {
        items.value = [];
        sectionId.value = null;
        current.value = null;
        meta.value = null;
        error.value = null;
    }

    return { items, sectionId, current, meta, loading, error, fetchForSection, fetchOne, create, update, remove, $reset };
});
