<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import InvoiceItemsField from '@/components/invoices/InvoiceItemsField.vue';
import ProjectRoute from '@/components/projects/ProjectRoute.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import { PROJECT_STATUSES } from '@/constants/projectStatus';
import { teamInk } from '@/constants/teamLines';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useClientStore } from '@/stores/clients';
import { useProjectStore } from '@/stores/projects';

const props = defineProps({ id: { type: String, required: true } });

const auth = useAuthStore();
const clientStore = useClientStore();
const projectStore = useProjectStore();
const { current: client, error: clientError } = storeToRefs(clientStore);
const { items: projects, meta, loading, error } = storeToRefs(projectStore);

const page = ref(1);
// Row actions (approve, delete); their failures show above the list, next to load failures.
const action = useForm();

// Admins and the team's leader approve requests and delete projects (ClientPolicy::assignProjects).
// The member who brought the client owns it: they work on all its projects, and the projects they create wait for
// approval. Helpers only see and edit the projects they were assigned to.
const canManage = computed(() => auth.isAdmin || (!!client.value?.team && client.value.team.leader_id === auth.user?.id));
const isOwner = computed(() => !!client.value && client.value.created_by === auth.user?.id);

const editing = ref(null);
const form = useForm({ name: '', status: 'not_started', image: null });
const fileKey = ref(0);

// Marking a project done creates its invoice from these lines, so at least one is needed (ProjectRequest).
// New projects cannot start as done.
const items = ref([]);
const statuses = computed(() => (editing.value ? PROJECT_STATUSES : PROJECT_STATUSES.filter((status) => status.value !== 'done')));
const completes = computed(() => !!editing.value && editing.value.status !== 'done' && form.fields.status === 'done');

watch(completes, (value) => {
    items.value = value ? [{ name: '', cost: '' }] : [];
});

function itemFields() {
    if (!completes.value) return {};

    return Object.fromEntries(items.value.flatMap((item, index) => [
        [`items[${index}][name]`, item.name],
        [`items[${index}][cost]`, item.cost],
    ]));
}

// Failed loads are shown from the stores' `error`.
async function loadProjects(target = page.value) {
    page.value = target;
    await projectStore.fetchForClient(props.id, { page: target }).catch(() => {});
}

function edit(project) {
    editing.value = project;
    form.reset({ name: project.name, status: project.status });
    fileKey.value++;
}

function resetForm() {
    editing.value = null;
    form.reset();
    fileKey.value++;
}

async function submit() {
    const ok = await form.submit((fields) =>
        editing.value
            ? projectStore.updateForm(editing.value.id, { ...fields, ...itemFields() })
            : projectStore.create(props.id, fields),
    );

    if (ok) {
        resetForm();
        await loadProjects();
    }
}

async function approve(project) {
    if (await action.submit(() => projectStore.approve(project.id))) {
        await loadProjects();
    }
}

async function remove(project) {
    const verb = canManage.value ? 'Delete project' : 'Withdraw the request for';
    if (!confirm(`${verb} "${project.name}"?`)) return;

    if (await action.submit(() => projectStore.remove(project.id))) {
        await loadProjects();
    }
}

onMounted(() => Promise.all([clientStore.fetchOne(props.id).catch(() => {}), loadProjects()]));
</script>

<template>
    <AppLayout>
        <nav aria-label="Breadcrumb" class="mb-4 font-sign text-sm font-semibold uppercase tracking-wider">
            <RouterLink :to="{ name: 'clients' }" class="text-ink-muted hover:text-ink">Clients</RouterLink>
            <span v-if="client" class="mx-2 text-enamel-300" aria-hidden="true">/</span>
            <span v-if="client" class="text-ink" aria-current="page">{{ client.name }}</span>
        </nav>

        <BaseErrorBanner :message="clientError?.message" />

        <header v-if="client" class="mb-8 flex items-center gap-5">
            <img v-if="client.image" :src="client.image" alt="" class="h-16 w-16 rounded object-cover" />
            <div>
                <h1 class="page-title">{{ client.name }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-ink-muted">
                    <TeamLine :team="client.team" class="text-ink" />
                    <span dir="ltr">{{ client.phone_number }}</span>
                    <span class="tabular">#{{ client.id }}</span>
                </p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-0 lg:col-span-2" aria-labelledby="projects-title">
                <div class="border-b border-rule px-6 py-4">
                    <h2 id="projects-title" class="section-title">Projects</h2>
                </div>
                <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || action.message.value" />

                <div class="overflow-x-auto px-3">
                    <table class="table">
                        <thead class="max-sm:hidden">
                            <tr>
                                <th>Project</th>
                                <th>Route</th>
                                <th>Helpers</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="project in projects" :key="project.id"
                                class="max-sm:flex max-sm:flex-wrap max-sm:items-center max-sm:gap-x-4 max-sm:border-b max-sm:border-rule/60 max-sm:py-2 max-sm:last:border-0"
                                :class="{ 'bg-cobalt-soft': editing?.id === project.id }">
                                <td class="max-sm:w-full max-sm:border-0 max-sm:pb-0">
                                    <div class="flex items-center gap-3">
                                        <img v-if="project.image" :src="project.image" alt=""
                                            class="h-9 w-9 shrink-0 rounded object-cover" />
                                        <RouterLink :to="{ name: 'project-detail', params: { id: project.id } }"
                                            class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                            {{ project.name }}
                                        </RouterLink>
                                    </div>
                                </td>
                                <td class="max-sm:border-0">
                                    <ProjectRoute :status="project.status" :approved="!!project.approved_at"
                                        :ink="teamInk(client?.team_id)" />
                                </td>
                                <td class="text-ink-muted max-sm:w-full max-sm:border-0 max-sm:py-0 max-sm:text-sm">
                                    {{ project.assignees?.map((person) => person.name).join(', ') || '—' }}
                                </td>
                                <td class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse max-sm:border-0">
                                    <button v-if="canManage && !project.approved_at" type="button" class="btn px-3 py-1"
                                        @click="approve(project)">Approve</button>
                                    <button type="button" class="link" @click="edit(project)">Edit</button>
                                    <!-- The owner can withdraw a request until it is approved (ProjectPolicy::delete). -->
                                    <button v-if="canManage || (isOwner && !project.approved_at)" type="button"
                                        class="link-danger"
                                        @click="remove(project)">
                                        {{ canManage ? 'Delete' : 'Withdraw' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="loading && !projects.length">
                                <td colspan="4"><span class="block h-9 animate-pulse rounded bg-porcelain" /></td>
                            </tr>
                            <tr v-if="!loading && !projects.length && !error">
                                <td colspan="4" class="py-6 text-ink-muted">
                                    No projects yet.
                                    <template v-if="canManage">Start one with the form.</template>
                                    <template v-else-if="isOwner">Request one with the form; your team leader approves it.</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                    <BasePagination :meta="meta" @change="loadProjects" />
                </div>
            </section>

            <!-- Helpers edit their projects here but cannot create one for a client they do not own. -->
            <form v-if="editing || canManage || isOwner" class="card space-y-5 self-start lg:sticky lg:top-10"
                @submit.prevent="submit">
                <div>
                    <h2 class="section-title">
                        {{ editing ? `Edit project #${editing.id}` : canManage ? 'New project' : 'Request a project' }}
                    </h2>
                    <p v-if="!editing && !canManage" class="mt-1 text-sm text-ink-muted">
                        The team leader approves it before invoices can be added.
                    </p>
                </div>
                <BaseErrorBanner :message="form.message.value" />

                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" v-model="form.fields.name" type="text" class="input" />
                    <p v-if="form.errors.value.name" class="field-error">{{ form.errors.value.name[0] }}</p>
                </div>

                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" v-model="form.fields.status" class="input">
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.value.status" class="field-error">{{ form.errors.value.status[0] }}</p>
                </div>

                <InvoiceItemsField v-if="completes" v-model="items" :errors="form.errors.value">
                    The invoice is created from these when the project is saved as done.
                </InvoiceItemsField>

                <div>
                    <label for="image" class="label">Image</label>
                    <input id="image" :key="fileKey" type="file" class="file-input"
                        @change="form.fields.image = $event.target.files[0] ?? null" />
                    <p class="mt-1 text-sm text-ink-muted">PNG, JPG, JPEG or WEBP.</p>
                    <p v-if="form.errors.value.image" class="field-error">{{ form.errors.value.image[0] }}</p>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit" class="btn" :disabled="form.processing.value">
                        {{ editing ? 'Update' : 'Create' }}
                    </button>
                    <button v-if="editing" type="button" class="btn-secondary" @click="resetForm">Cancel</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
