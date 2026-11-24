<script setup>
import { computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import ProjectRoute from '@/components/projects/ProjectRoute.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import { teamInk } from '@/constants/teamLines';
import { userRoleLabel } from '@/constants/userRole';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import { useProjectStore } from '@/stores/projects';

const auth = useAuthStore();
const notificationStore = useNotificationStore();
const projectStore = useProjectStore();
const { items: notifications, loading: inboxLoading, error: inboxError } = storeToRefs(notificationStore);
const { recent: projects, clientsTotal, loading: projectsLoading, error: projectsError } = storeToRefs(projectStore);

const PROJECTS_SHOWN = 10;

// Approving a request from the inbox.
const action = useForm();

// Mirrors ClientPolicy::assignProjects: admins and the client's team leader approve requests.
function canApprove(project) {
    return auth.isAdmin || project.client.team?.leader_id === auth.user?.id;
}

// Leaders open their own teams' reports; admins open every team's (TeamPolicy::viewReport).
function canViewReport(team) {
    return auth.isAdmin || team.leader_id === auth.user?.id;
}

const unread = computed(() => notifications.value.slice(0, 5));

// Requests first, then work in progress; finished and cancelled projects sink to the end.
const ORDER = { working_on_it: 1, not_started: 2, done: 3, cancelled: 4 };
const requests = computed(() => projects.value.filter((project) => !project.approved_at && project.status !== 'cancelled'));
const onLines = computed(() => [...projects.value]
    .sort((a, b) => (a.approved_at ? ORDER[a.status] : 0) - (b.approved_at ? ORDER[b.status] : 0))
    .slice(0, PROJECTS_SHOWN));

const today = computed(() => new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }));

function approve(project) {
    return action.submit(() => projectStore.approve(project.id));
}

// Failed loads are shown from each store's `error`.
onMounted(() => Promise.all([
    notificationStore.fetchAll({ unread: 1 }).catch(() => {}),
    projectStore.fetchRecent().catch(() => {}),
    auth.fetchTeams().catch(() => {}),
]));
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">My work</h1>
            <p class="mt-1 text-ink-muted">
                {{ today }}
                <template v-if="auth.user"> · {{ auth.user.name }}, {{ userRoleLabel(auth.user.role) }}</template>
            </p>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <section class="card p-0" aria-labelledby="waiting-title">
                    <div class="flex items-center justify-between border-b border-rule px-6 py-4">
                        <h2 id="waiting-title" class="section-title">Waiting on you</h2>
                        <RouterLink :to="{ name: 'notifications' }" class="link">All notifications</RouterLink>
                    </div>
                    <div class="px-6 pt-4 empty:hidden">
                        <BaseErrorBanner :message="inboxError?.message" />
                        <BaseErrorBanner :message="action.message.value" />
                    </div>

                    <ul v-if="inboxLoading || projectsLoading" class="space-y-3 px-6 py-5" aria-label="Loading">
                        <li v-for="n in 3" :key="n" class="h-10 animate-pulse rounded bg-porcelain" />
                    </ul>
                    <ul v-else-if="requests.length || unread.length" class="divide-y divide-rule/60">
                        <li v-for="project in requests" :key="`project-${project.id}`"
                            class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-4">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 bg-white"
                                :style="{ borderColor: teamInk(project.client.team_id) }" aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <RouterLink :to="{ name: 'project-detail', params: { id: project.id } }"
                                    class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                    {{ project.name }}
                                </RouterLink>
                                <p class="text-sm text-ink-muted">
                                    Project request · {{ project.client.name }} · {{ project.client.team?.name }}
                                </p>
                            </div>
                            <button v-if="canApprove(project)" type="button" class="btn px-3 py-1"
                                :disabled="action.processing.value" @click="approve(project)">
                                Approve
                            </button>
                            <span v-else class="font-sign text-sm font-semibold uppercase tracking-wider text-enamel-700">
                                Awaiting approval
                            </span>
                        </li>
                        <li v-for="notification in unread" :key="notification.id" class="flex items-start gap-4 px-6 py-4">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-scarlet" aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink">{{ notification.details.message }}</p>
                                <p class="text-sm text-ink-muted">
                                    Unread
                                    <template v-if="notification.details.client_name"> · {{ notification.details.client_name }}</template>
                                    · {{ new Date(notification.created_at).toLocaleString() }}
                                </p>
                            </div>
                            <RouterLink v-if="notification.details.project_id"
                                :to="{ name: 'project-detail', params: { id: notification.details.project_id } }"
                                class="link whitespace-nowrap">
                                Open project
                            </RouterLink>
                        </li>
                    </ul>
                    <p v-else-if="!inboxError && !projectsError" class="px-6 py-6 text-ink-muted">
                        Nothing waits on you. Project requests and unread notifications show up here.
                    </p>
                </section>

                <section class="card p-0" aria-labelledby="projects-title">
                    <div class="flex items-center justify-between border-b border-rule px-6 py-4">
                        <h2 id="projects-title" class="section-title">On your lines</h2>
                        <RouterLink :to="{ name: 'clients' }" class="link">
                            All clients<template v-if="clientsTotal"> ({{ clientsTotal }})</template>
                        </RouterLink>
                    </div>
                    <BaseErrorBanner class="mx-6 mt-4" :message="projectsError?.message" />

                    <ul v-if="projectsLoading" class="space-y-3 px-6 py-5" aria-label="Loading">
                        <li v-for="n in 4" :key="n" class="h-10 animate-pulse rounded bg-porcelain" />
                    </ul>
                    <ul v-else-if="onLines.length" class="divide-y divide-rule/60">
                        <li v-for="project in onLines" :key="project.id"
                            class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-6 py-3">
                            <div class="min-w-0">
                                <RouterLink :to="{ name: 'project-detail', params: { id: project.id } }"
                                    class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                    {{ project.name }}
                                </RouterLink>
                                <p class="flex flex-wrap items-center gap-x-3 text-sm text-ink-muted">
                                    <RouterLink :to="{ name: 'client-detail', params: { id: project.client.id } }" class="hover:text-ink hover:underline">
                                        {{ project.client.name }}
                                    </RouterLink>
                                    <TeamLine :team="project.client.team" />
                                </p>
                            </div>
                            <ProjectRoute aligned :status="project.status" :approved="!!project.approved_at"
                                :ink="teamInk(project.client.team_id)" />
                        </li>
                    </ul>
                    <p v-else-if="!projectsError" class="px-6 py-6 text-ink-muted">
                        No projects on your lines yet. Open a client from
                        <RouterLink :to="{ name: 'clients' }" class="link">Clients</RouterLink> to start one.
                    </p>
                </section>
            </div>

            <section class="card self-start p-0" aria-labelledby="lines-title">
                <div class="border-b border-rule px-6 py-4">
                    <h2 id="lines-title" class="section-title">Your lines</h2>
                </div>
                <BaseErrorBanner class="mx-6 mt-4" :message="auth.error?.message" />
                <ul v-if="auth.teams?.length" class="divide-y divide-rule/60">
                    <li v-for="team in auth.teams" :key="team.id" class="flex items-center justify-between gap-3 px-6 py-3">
                        <TeamLine :team="team" class="text-ink" />
                        <RouterLink v-if="canViewReport(team)" :to="{ name: 'team-report', params: { id: team.id } }"
                            class="link whitespace-nowrap">
                            Report
                        </RouterLink>
                    </li>
                </ul>
                <p v-else-if="!auth.loading" class="px-6 py-6 text-ink-muted">
                    You are not on a team yet. A team leader or an admin adds you to one.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
