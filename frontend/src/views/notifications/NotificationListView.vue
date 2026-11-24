<script setup>
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import { useForm } from '@/composables/useForm';
import AppLayout from '@/layouts/AppLayout.vue';
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();
const { items: notifications, meta, unread, loading, error } = storeToRefs(notificationStore);

const page = ref(1);
// Marking read / unread; its failures show above the list, next to load failures.
const action = useForm();

// A failed load is shown from the store's `error`.
async function load(target = page.value) {
    page.value = target;
    await notificationStore.fetchAll({ page: target }).catch(() => {});
}

function mark(notification, read) {
    return action.submit(() => notificationStore.mark(notification.id, read));
}

onMounted(() => load());
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">Notifications</h1>
            <p class="mt-1 text-ink-muted">
                <template v-if="unread">{{ unread }} unread</template>
                <template v-else>All caught up</template>
            </p>
        </header>

        <section class="card p-0" aria-label="Notifications">
            <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || action.message.value" />

            <ul class="divide-y divide-rule/60">
                <li v-for="notification in notifications" :key="notification.id"
                    class="flex flex-wrap items-start gap-4 px-6 py-4 sm:flex-nowrap"
                    :class="{ 'bg-porcelain/60': notification.read_at }">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full border-2"
                        :class="notification.read_at ? 'border-enamel-300 bg-white' : 'border-scarlet bg-scarlet'"
                        aria-hidden="true" />
                    <div class="min-w-0 flex-1">
                        <p :class="notification.read_at ? 'text-ink-muted' : 'font-semibold text-ink'">
                            <span v-if="!notification.read_at" class="sr-only">Unread: </span>
                            {{ notification.details.message }}
                        </p>
                        <p class="text-sm text-ink-muted">
                            <template v-if="notification.type === 'ProjectCompleted'">
                                {{ notification.details.client_name }}
                                <template v-if="notification.details.team_name">
                                    · {{ notification.details.team_name }}
                                </template>
                                · Invoice total <span class="tabular">{{ notification.details.invoice_total.toFixed(2) }}</span> ·
                            </template>
                            {{ new Date(notification.created_at).toLocaleString() }}
                        </p>
                    </div>
                    <div class="flex gap-4 whitespace-nowrap ps-6 sm:ps-0">
                        <RouterLink v-if="notification.details.project_id"
                            :to="{ name: 'project-detail', params: { id: notification.details.project_id } }" class="link"
                            @click="!notification.read_at && mark(notification, true)">
                            View project
                        </RouterLink>
                        <button type="button" class="link" @click="mark(notification, !notification.read_at)">
                            {{ notification.read_at ? 'Mark unread' : 'Mark read' }}
                        </button>
                    </div>
                </li>
                <li v-if="loading && !notifications.length" class="px-6 py-4">
                    <span class="block h-10 animate-pulse rounded bg-porcelain" />
                </li>
                <li v-if="!loading && !notifications.length && !error" class="px-6 py-6 text-ink-muted">
                    No notifications yet. Completed projects on your teams show up here.
                </li>
            </ul>

            <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                <BasePagination :meta="meta" @change="load" />
            </div>
        </section>
    </AppLayout>
</template>
