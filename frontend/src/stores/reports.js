import { ref } from 'vue';
import { defineStore } from 'pinia';
import reportService from '@/api/services/reportService';

export const useReportStore = defineStore('reports', () => {
    // State: `items` are every team with its counts (admins); `current` is one team's report.
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
            const res = await reportService.teams(params);
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
            const res = await reportService.team(id);
            current.value = res.data;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    function $reset() {
        items.value = [];
        current.value = null;
        meta.value = null;
        error.value = null;
    }

    return { items, current, meta, loading, error, fetchAll, fetchOne, $reset };
});
