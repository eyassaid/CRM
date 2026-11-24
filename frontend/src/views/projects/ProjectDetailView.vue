<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import InvoiceItemsField from '@/components/invoices/InvoiceItemsField.vue';
import ProjectRoute from '@/components/projects/ProjectRoute.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import { teamInk } from '@/constants/teamLines';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useInvoiceStore } from '@/stores/invoices';
import { useProjectStore } from '@/stores/projects';
import { useTeamStore } from '@/stores/teams';

const props = defineProps({ id: { type: String, required: true } });

const auth = useAuthStore();
const router = useRouter();
const projectStore = useProjectStore();
const teamStore = useTeamStore();
const invoiceStore = useInvoiceStore();
const { current: project, error: projectError } = storeToRefs(projectStore);
const { current: team, error: teamError } = storeToRefs(teamStore);
const { items: invoices, meta, loading, error } = storeToRefs(invoiceStore);

const page = ref(1);
// Invoice row actions (download, delete); their failures show above the list, next to load failures.
const action = useForm();
// Adds an invoice, or edits the items of `editingInvoice`. The PDF is rendered by the API on download.
const invoiceForm = useForm({ items: [{ name: '', cost: '' }] });
const editingInvoice = ref(null);

// People: anyone on the client's team can be picked, by an admin or the team's leader (ClientPolicy::assignProjects).
// `assign` also covers approving and withdrawing the project.
const assign = useForm();
const assigneeIds = ref([]);
const canAssign = computed(() => auth.isAdmin || (!!team.value && team.value.leader_id === auth.user?.id));
const teamPeople = computed(() => {
    if (!team.value) return [];

    const members = team.value.members ?? [];
    const leader = team.value.leader;

    return leader && !members.some((member) => member.id === leader.id) ? [leader, ...members] : members;
});

function syncAssignees() {
    assigneeIds.value = project.value?.assignees?.map((person) => person.id) ?? [];
}

// Failed loads are shown from the stores' `error`.
async function loadProject() {
    await projectStore.fetchOne(props.id).catch(() => {});
    syncAssignees();

    const teamId = project.value?.client?.team_id;
    if (teamId) {
        await teamStore.fetchOne(teamId).catch(() => {});
    }
}

async function saveAssignees() {
    if (await assign.submit(() => projectStore.assign(props.id, assigneeIds.value))) {
        syncAssignees();
    }
}

// A project a member requested waits for the team leader (or an admin) before it gets invoices.
function approve() {
    return assign.submit(() => projectStore.approve(props.id));
}

// The client's owner can withdraw their request until it is approved (ProjectPolicy::delete).
const isOwner = computed(() => project.value?.client?.created_by === auth.user?.id);

async function withdraw() {
    if (!confirm(`Withdraw the request for "${project.value.name}"?`)) return;

    const clientId = project.value.client.id;
    if (await assign.submit(() => projectStore.remove(props.id))) {
        router.push({ name: 'client-detail', params: { id: clientId } });
    }
}

async function loadInvoices(target = page.value) {
    page.value = target;
    await invoiceStore.fetchForProject(props.id, { page: target }).catch(() => {});
}

function editInvoice(invoice) {
    editingInvoice.value = invoice;
    invoiceForm.reset({ items: invoice.items.map((item) => ({ ...item })) });
}

function resetInvoiceForm() {
    editingInvoice.value = null;
    invoiceForm.reset();
}

async function submitInvoice() {
    const ok = await invoiceForm.submit((fields) =>
        editingInvoice.value
            ? invoiceStore.update(editingInvoice.value.id, fields)
            : invoiceStore.create(props.id, fields),
    );

    if (ok) {
        resetInvoiceForm();
        await loadInvoices();
    }
}

// The PDF comes through the API (it needs the Bearer token), then is handed to the browser as a file.
async function download(invoice) {
    await action.submit(async () => {
        const blob = await invoiceStore.downloadPdf(invoice.id);
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `invoice-${invoice.id}.pdf`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(url));
    });
}

const itemSummary = (invoice) => invoice.items.map((item) => item.name).join(', ');

async function remove(invoice) {
    if (!confirm(`Delete invoice #${invoice.id}?`)) return;

    if (await action.submit(() => invoiceStore.remove(invoice.id))) {
        if (editingInvoice.value?.id === invoice.id) resetInvoiceForm();
        await loadInvoices();
    }
}

onMounted(() => Promise.all([loadProject(), loadInvoices()]));
</script>

<template>
    <AppLayout>
        <nav aria-label="Breadcrumb" class="mb-4 font-sign text-sm font-semibold uppercase tracking-wider">
            <RouterLink :to="{ name: 'clients' }" class="text-ink-muted hover:text-ink">Clients</RouterLink>
            <template v-if="project?.client">
                <span class="mx-2 text-enamel-300" aria-hidden="true">/</span>
                <RouterLink :to="{ name: 'client-detail', params: { id: project.client.id } }" class="text-ink-muted hover:text-ink">
                    {{ project.client.name }}
                </RouterLink>
            </template>
            <template v-if="project">
                <span class="mx-2 text-enamel-300" aria-hidden="true">/</span>
                <span class="text-ink" aria-current="page">{{ project.name }}</span>
            </template>
        </nav>

        <BaseErrorBanner :message="projectError?.message" />

        <section v-if="project" class="card mb-6" aria-labelledby="project-title">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 id="project-title" class="page-title">{{ project.name }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-ink-muted">
                        <TeamLine v-if="team" :team="team" class="text-ink" />
                        <span class="tabular">#{{ project.id }}</span>
                    </p>
                </div>
                <div v-if="!project.approved_at" class="flex items-center gap-4">
                    <span class="font-sign text-sm font-semibold uppercase tracking-wider text-enamel-700">Awaiting approval</span>
                    <button v-if="canAssign" type="button" class="btn" :disabled="assign.processing.value" @click="approve">
                        Approve
                    </button>
                    <button v-else-if="isOwner" type="button" class="link-danger" :disabled="assign.processing.value"
                        @click="withdraw">
                        Withdraw request
                    </button>
                </div>
            </div>

            <div class="mt-8 max-w-2xl">
                <ProjectRoute large :status="project.status" :approved="!!project.approved_at"
                    :ink="teamInk(project.client?.team_id)" />
            </div>
        </section>

        <section v-if="project" class="card mb-6" aria-labelledby="helpers-title">
            <!-- The client's owner always works on its projects; the team leader adds helpers. -->
            <h2 id="helpers-title" class="section-title mb-4">Helpers</h2>
            <BaseErrorBanner :message="teamError?.message || assign.message.value" />

            <form v-if="canAssign" class="space-y-4" @submit.prevent="saveAssignees">
                <div class="flex flex-wrap gap-2">
                    <label v-for="person in teamPeople" :key="person.id"
                        class="flex cursor-pointer items-center gap-2 rounded border border-rule px-3 py-2 text-sm transition-colors duration-150 hover:bg-porcelain has-[:checked]:border-cobalt has-[:checked]:bg-cobalt-soft">
                        <input v-model="assigneeIds" type="checkbox" :value="person.id"
                            class="rounded border-rule text-cobalt focus:ring-cobalt" />
                        {{ person.name }}
                        <span v-if="person.id === team.leader_id" class="text-xs text-ink-muted">(leader)</span>
                    </label>
                    <p v-if="!teamPeople.length" class="text-sm text-ink-muted">The client's team has no members.</p>
                </div>
                <p v-if="assign.errors.value.user_ids" class="field-error">{{ assign.errors.value.user_ids[0] }}</p>
                <button type="submit" class="btn" :disabled="assign.processing.value">Save people</button>
            </form>

            <template v-else>
                <ul v-if="project.assignees?.length" class="flex flex-wrap gap-2 text-sm">
                    <li v-for="person in project.assignees" :key="person.id" class="rounded border border-rule px-3 py-1">
                        {{ person.name }}
                    </li>
                </ul>
                <p v-else class="text-sm text-ink-muted">No helpers. The team leader adds them.</p>
            </template>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-0 lg:col-span-2" aria-labelledby="invoices-title">
                <div class="border-b border-rule px-6 py-4">
                    <h2 id="invoices-title" class="section-title">Invoices</h2>
                </div>
                <div class="px-6 pt-4 empty:hidden">
                    <BaseErrorBanner :message="error?.message || action.message.value" />
                </div>

                <div class="overflow-x-auto px-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Items</th>
                                <th class="text-end">Total</th>
                                <th>Created by</th>
                                <th>Created</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id">
                                <td>{{ invoice.id }}</td>
                                <td class="max-w-xs truncate" :title="itemSummary(invoice)">{{ itemSummary(invoice) }}</td>
                                <td class="text-end font-medium tabular">{{ invoice.total.toFixed(2) }}</td>
                                <td class="whitespace-nowrap">{{ invoice.creator?.name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-ink-muted">{{ new Date(invoice.created_at).toLocaleString() }}</td>
                                <td class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse">
                                    <button type="button" class="link" :disabled="action.processing.value"
                                        @click="download(invoice)">Download PDF</button>
                                    <button type="button" class="link" @click="editInvoice(invoice)">Edit</button>
                                    <button type="button" class="link-danger" @click="remove(invoice)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="loading && !invoices.length">
                                <td colspan="6"><span class="block h-9 animate-pulse rounded bg-porcelain" /></td>
                            </tr>
                            <tr v-if="!loading && !invoices.length && !error">
                                <td colspan="6" class="py-6 text-ink-muted">
                                    No invoices yet. Marking the project done generates one from its items.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                    <BasePagination :meta="meta" @change="loadInvoices" />
                </div>
            </section>

            <form class="card space-y-5 self-start lg:sticky lg:top-10" @submit.prevent="submitInvoice">
                <h2 class="section-title">{{ editingInvoice ? `Edit invoice #${editingInvoice.id}` : 'New invoice' }}</h2>
                <BaseErrorBanner :message="invoiceForm.message.value" />
                <p v-if="project && !project.approved_at" class="text-sm text-ink-muted">
                    Invoices can be added once the project is approved.
                </p>

                <InvoiceItemsField v-model="invoiceForm.fields.items" :errors="invoiceForm.errors.value">
                    The PDF is generated from these lines when it is downloaded.
                </InvoiceItemsField>

                <div class="flex gap-3">
                    <button type="submit" class="btn"
                        :disabled="invoiceForm.processing.value || (!editingInvoice && !project?.approved_at)">
                        {{ editingInvoice ? 'Update' : 'Add invoice' }}
                    </button>
                    <button v-if="editingInvoice" type="button" class="btn-secondary" @click="resetInvoiceForm">Cancel</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
