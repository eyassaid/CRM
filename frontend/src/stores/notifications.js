import { ref } from 'vue';
import { defineStore } from 'pinia';
import notificationService from '@/api/services/notificationService';

export const useNotificationStore = defineStore('notifications', () => {
    // State
    const items = ref([]);
    const meta = ref(null);
    // Unread count for the navbar badge, shared by AppLayout and the notification pages.
    const unread = ref(0);
    const loading = ref(false);
    const error = ref(null);

    // Actions
    // params: { page } and/or { unread: 1 }.
    async function fetchAll(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const res = await notificationService.list(params);
            items.value = res.data;
            meta.value = res.meta ?? null;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // The badge count: the total of the unread-only list. Does not touch `loading` or `items`.
    async function fetchUnreadCount() {
        const res = await notificationService.list({ unread: 1 });
        unread.value = res.meta?.total ?? 0;
    }

    async function mark(id, read) {
        const res = await notificationService.update(id, { read });
        const i = items.value.findIndex((n) => n.id === id);
        if (i !== -1) items.value[i] = res.data;
        await fetchUnreadCount();
        return res.data;
    }

    function $reset() {
        items.value = [];
        meta.value = null;
        unread.value = 0;
        error.value = null;
    }

    return { items, meta, unread, loading, error, fetchAll, fetchUnreadCount, mark, $reset };
});
