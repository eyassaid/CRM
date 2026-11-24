<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { PROJECT_STATUSES } from '@/constants/projectStatus';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useReportStore } from '@/stores/reports';

const auth = useAuthStore();
const reportStore = useReportStore();
const isAdmin = computed(() => auth.isAdmin);

// Admins get every team with its counts (paginated); a team leader gets the teams they lead, each linking to its report.
const source = computed(() => (isAdmin.value ? reportStore : auth));
const teams = computed(() => (isAdmin.value ? reportStore.items : auth.ledTeams));
const meta = computed(() => (isAdmin.value ? reportStore.meta : null));
const loading = computed(() => source.value.loading);
const error = computed(() => source.value.error);

const page = ref(1);

// A failed load is shown from the store's `error`.
async function load(target = page.value) {
    page.value = target;
    const request = isAdmin.value ? reportStore.fetchAll({ page: target }) : auth.fetchTeams({ refresh: true });
    await request.catch(() => {});
}

onMounted(() => load());
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">Reports</h1>
            <p class="mt-1 text-ink-muted">
                {{ isAdmin ? 'Clients and projects on every line.' : 'Teams you lead. Open one to see the clients each member brought.' }}
            </p>
        </header>

        <section class="card p-0" aria-label="Teams">
            <BaseErrorBanner class="mx-6 mt-4" :message="error?.message" />

            <div v-if="isAdmin" class="overflow-x-auto px-3 pt-2">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Section</th>
                            <th>Leader</th>
                            <th class="text-end">Clients</th>
                            <th class="text-end">Projects</th>
                            <th v-for="status in PROJECT_STATUSES" :key="status.value" class="text-end">{{ status.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="team in teams" :key="team.id">
                            <td>
                                <RouterLink :to="{ name: 'team-report', params: { id: team.id } }"
                                    class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                    <TeamLine :team="team" />
                                </RouterLink>
                            </td>
                            <td class="text-ink-muted">{{ team.section_name }}</td>
                            <td>{{ team.leader_name ?? '—' }}</td>
                            <td class="text-end">{{ team.clients_count }}</td>
                            <td class="text-end font-semibold">{{ team.projects_count }}</td>
                            <td v-for="status in PROJECT_STATUSES" :key="status.value" class="text-end"
                                :class="{ 'text-ink-muted': !team.projects_by_status?.[status.value] }">
                                {{ team.projects_by_status?.[status.value] ?? 0 }}
                            </td>
                        </tr>
                        <tr v-if="loading && !teams.length">
                            <td :colspan="5 + PROJECT_STATUSES.length"><span class="block h-9 animate-pulse rounded bg-porcelain" /></td>
                        </tr>
                        <tr v-if="!loading && !teams.length && !error">
                            <td :colspan="5 + PROJECT_STATUSES.length" class="py-6 text-ink-muted">
                                No teams yet. Create sections and teams under Sections.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <ul v-else class="divide-y divide-rule/60">
                <li v-for="team in teams" :key="team.id">
                    <RouterLink :to="{ name: 'team-report', params: { id: team.id } }"
                        class="flex items-center justify-between px-6 py-4 font-medium text-ink transition-colors duration-150 hover:bg-porcelain">
                        <TeamLine :team="team" />
                        <span class="link">Open report</span>
                    </RouterLink>
                </li>
                <li v-if="loading && !teams.length" class="px-6 py-4">
                    <span class="block h-9 animate-pulse rounded bg-porcelain" />
                </li>
                <li v-if="!loading && !teams.length && !error" class="px-6 py-6 text-ink-muted">
                    You do not lead any team.
                </li>
            </ul>

            <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                <BasePagination :meta="meta" @change="load" />
            </div>
        </section>
    </AppLayout>
</template>
