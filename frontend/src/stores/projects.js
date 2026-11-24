import { ref } from 'vue';
import { defineStore } from 'pinia';
import clientService from '@/api/services/clientService';
import projectService from '@/api/services/projectService';

export const useProjectStore = defineStore('projects', () => {
    // State
    // `items` are one client's projects; `clientId` says whose.
    const items = ref([]);
    const clientId = ref(null);
    const current = ref(null);
    const meta = ref(null);
    // The dashboard's projects across clients, each with its `client`.
    const recent = ref([]);
    const clientsTotal = ref(0);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    async function fetchForClient(id, params = {}) {
        loading.value = true;
        error.value = null;
        if (clientId.value !== id) {
            items.value = [];
            meta.value = null;
        }
        clientId.value = id;
        try {
            const res = await projectService.listForClient(id, params);
            items.value = res.data;
            meta.value = res.meta ?? null;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // There is no project index across clients, so this reads the projects of the first `clients` clients the
    // user can see (GET /clients/{id} loads the projects visible to them).
    async function fetchRecent({ clients = 8 } = {}) {
        loading.value = true;
        error.value = null;
        try {
            const list = await clientService.list();
            clientsTotal.value = list.meta?.total ?? list.data.length;

            const details = await Promise.all(list.data.slice(0, clients).map((client) => clientService.get(client.id)));
            recent.value = details.flatMap(({ data: client }) =>
                (client.projects ?? []).map((project) => ({ ...project, client })),
            );
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
            const res = await projectService.get(id);
            current.value = res.data;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    function replace(project) {
        const i = items.value.findIndex((p) => p.id === project.id);
        if (i !== -1) items.value[i] = project;

        // Dashboard rows keep the client they were listed with.
        const r = recent.value.findIndex((p) => p.id === project.id);
        if (r !== -1) recent.value[r] = { ...project, client: recent.value[r].client };

        if (current.value?.id === project.id) current.value = project;
    }

    async function create(forClientId, fields) {
        const res = await projectService.create(forClientId, fields);
        if (clientId.value === forClientId) items.value.unshift(res.data);
        return res.data;
    }

    // The project form: may carry an image and, when it completes the project, invoice items.
    async function updateForm(id, fields) {
        const res = await projectService.updateForm(id, fields);
        replace(res.data);
        return res.data;
    }

    async function update(id, payload) {
        const res = await projectService.update(id, payload);
        replace(res.data);
        return res.data;
    }

    // A project a member requested waits for the team leader (or an admin) before it gets invoices.
    const approve = (id) => update(id, { approved: true });

    const assign = (id, userIds) => update(id, { user_ids: userIds });

    async function remove(id) {
        await projectService.remove(id);
        items.value = items.value.filter((p) => p.id !== id);
        recent.value = recent.value.filter((p) => p.id !== id);
    }

    function $reset() {
        items.value = [];
        clientId.value = null;
        current.value = null;
        meta.value = null;
        recent.value = [];
        clientsTotal.value = 0;
        error.value = null;
    }

    return {
        items, clientId, current, meta, recent, clientsTotal, loading, error,
        fetchForClient, fetchRecent, fetchOne, create, updateForm, update, approve, assign, remove, $reset,
    };
});
