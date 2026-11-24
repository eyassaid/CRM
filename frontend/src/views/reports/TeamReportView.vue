<script setup>
import { computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import ProjectRoute from '@/components/projects/ProjectRoute.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { PROJECT_STATUSES } from '@/constants/projectStatus';
import { teamInk } from '@/constants/teamLines';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportStore } from '@/stores/reports';

const props = defineProps({ id: { type: String, required: true } });

const reportStore = useReportStore();
const { current: team, error } = storeToRefs(reportStore);

// The leader is listed with the members; the leader may not be in the members list.
const people = computed(() => {
    if (!team.value) return [];

    const members = team.value.members ?? [];
    const leader = team.value.leader;
    const rows = members.map((member) => ({ ...member, isLeader: member.id === leader?.id }));

    if (leader && !rows.some((row) => row.isLeader)) {
        rows.push({ ...leader, isLeader: true });
    }

    return rows.sort((a, b) => b.clients_count - a.clients_count);
});

// Projects by status as segments of one bar, all in the team's own ink: done is solid, work in progress is lighter,
// not started is the bare track and cancelled is a suspended (dashed) stretch.
function statusFill(status) {
    const ink = teamInk(team.value?.id);

    return {
        done: ink,
        working_on_it: `color-mix(in srgb, ${ink} 55%, white)`,
        not_started: '#D9DEE7',
        cancelled: 'repeating-linear-gradient(to right, #9AA6C7 0 6px, transparent 6px 10px)',
    }[status];
}

const segments = computed(() => PROJECT_STATUSES
    .map((status) => ({ ...status, count: team.value?.projects_by_status?.[status.value] ?? 0 }))
    .filter((segment) => segment.count > 0));

// A failed load is shown from the store's `error`.
onMounted(() => reportStore.fetchOne(props.id).catch(() => {}));
</script>

<template>
    <AppLayout>
        <nav aria-label="Breadcrumb" class="mb-4 font-sign text-sm font-semibold uppercase tracking-wider">
            <RouterLink :to="{ name: 'reports' }" class="text-ink-muted hover:text-ink">Reports</RouterLink>
            <template v-if="team">
                <span class="mx-2 text-enamel-300" aria-hidden="true">/</span>
                <span class="text-ink" aria-current="page">{{ team.name }}</span>
            </template>
        </nav>

        <BaseErrorBanner :message="error?.message" />

        <section v-if="team" class="card mb-6" aria-labelledby="team-title">
            <h1 id="team-title" class="page-title flex items-center gap-3">
                <span class="h-2 w-10 rounded-full" :style="{ backgroundColor: teamInk(team.id) }" aria-hidden="true" />
                {{ team.name }}
            </h1>
            <p class="tabular mt-1 text-ink-muted">
                {{ team.section_name }} · {{ team.clients_count }} clients · {{ team.projects_count }} projects
            </p>

            <div class="mt-6">
                <div v-if="segments.length" class="flex h-3 gap-1 overflow-hidden rounded-full" role="img"
                    :aria-label="segments.map((segment) => `${segment.label}: ${segment.count}`).join(', ')">
                    <span v-for="segment in segments" :key="segment.value" class="h-full"
                        :style="{ flexGrow: segment.count, background: statusFill(segment.value) }" />
                </div>
                <span v-else class="block h-3 rounded-full bg-porcelain" aria-hidden="true" />
                <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <li v-for="status in PROJECT_STATUSES" :key="status.value" class="flex items-center gap-2">
                        <span class="h-1.5 w-5 rounded-full" :style="{ background: statusFill(status.value) }" aria-hidden="true" />
                        {{ status.label }}
                        <span class="tabular font-semibold">{{ team.projects_by_status?.[status.value] ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <div v-if="team" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-0 self-start" aria-labelledby="people-title">
                <div class="border-b border-rule px-6 py-4">
                    <h2 id="people-title" class="section-title">Clients brought</h2>
                </div>
                <ul class="divide-y divide-rule/60">
                    <li v-for="person in people" :key="person.id" class="flex items-center gap-3 px-6 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-ink">
                                {{ person.name }}
                                <span v-if="person.isLeader" class="text-xs font-normal text-ink-muted">(leader)</span>
                            </p>
                            <p class="truncate text-sm text-ink-muted">{{ person.email }}</p>
                        </div>
                        <span class="tabular font-sign text-2xl font-semibold text-ink">{{ person.clients_count }}</span>
                    </li>
                    <li v-if="!people.length" class="px-6 py-6 text-ink-muted">This team has no members yet.</li>
                </ul>
            </section>

            <section class="card p-0 lg:col-span-2" aria-labelledby="clients-title">
                <div class="border-b border-rule px-6 py-4">
                    <h2 id="clients-title" class="section-title">Clients and projects</h2>
                </div>
                <div class="overflow-x-auto px-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Projects</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in team.clients" :key="client.id">
                                <td class="align-top">
                                    <RouterLink :to="{ name: 'client-detail', params: { id: client.id } }"
                                        class="whitespace-nowrap font-medium text-ink hover:text-cobalt-dark hover:underline">
                                        {{ client.name }}
                                    </RouterLink>
                                </td>
                                <td class="whitespace-nowrap align-top text-ink-muted" dir="ltr">{{ client.phone_number }}</td>
                                <td>
                                    <ul v-if="client.projects?.length" class="space-y-2">
                                        <li v-for="project in client.projects" :key="project.id"
                                            class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                                            <RouterLink :to="{ name: 'project-detail', params: { id: project.id } }" class="link">
                                                {{ project.name }}
                                            </RouterLink>
                                            <ProjectRoute :status="project.status" :approved="!!project.approved_at"
                                                :ink="teamInk(team.id)" />
                                        </li>
                                    </ul>
                                    <span v-else class="text-ink-muted">—</span>
                                </td>
                            </tr>
                            <tr v-if="!team.clients?.length">
                                <td colspan="3" class="py-6 text-ink-muted">This team has no clients yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
