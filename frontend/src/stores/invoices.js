import { ref } from 'vue';
import { defineStore } from 'pinia';
import invoiceService from '@/api/services/invoiceService';

export const useInvoiceStore = defineStore('invoices', () => {
    // State: one project's invoices; `projectId` says whose.
    const items = ref([]);
    const projectId = ref(null);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    async function fetchForProject(id, params = {}) {
        loading.value = true;
        error.value = null;
        if (projectId.value !== id) {
            items.value = [];
            meta.value = null;
        }
        projectId.value = id;
        try {
            const res = await invoiceService.listForProject(id, params);
            items.value = res.data;
            meta.value = res.meta ?? null;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function create(forProjectId, fields) {
        const res = await invoiceService.create(forProjectId, fields);
        if (projectId.value === forProjectId) items.value.unshift(res.data);
        return res.data;
    }

    async function update(id, fields) {
        const res = await invoiceService.update(id, fields);
        const i = items.value.findIndex((invoice) => invoice.id === id);
        if (i !== -1) items.value[i] = res.data;
        return res.data;
    }

    async function remove(id) {
        await invoiceService.remove(id);
        items.value = items.value.filter((invoice) => invoice.id !== id);
    }

    // The invoice PDF (a Blob), rendered by the API from the invoice's items.
    function downloadPdf(id) {
        return invoiceService.downloadPdf(id);
    }

    function $reset() {
        items.value = [];
        projectId.value = null;
        meta.value = null;
        error.value = null;
    }

    return { items, projectId, meta, loading, error, fetchForProject, create, update, remove, downloadPdf, $reset };
});
