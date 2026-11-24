<script setup>
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import BaseSearchInput from '@/components/common/BaseSearchInput.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import { teamInk } from '@/constants/teamLines';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useClientStore } from '@/stores/clients';

const auth = useAuthStore();
const clientStore = useClientStore();
const { items: clients, meta, loading, error } = storeToRefs(clientStore);

const page = ref(1);
// Filters the list by name or phone number (on the server, so it covers every page).
const search = ref('');
// Row actions (delete); their failures show above the list, next to load failures.
const action = useForm();

// The client being edited, or null while the form creates a new one.
const editing = ref(null);
const form = useForm({ name: '', phone_number: '', team_id: '', image: null });
// Teams the user may assign a client to (their own teams, or all teams for an admin).
const teams = computed(() => auth.teams ?? []);
// Changing the key re-renders the file input, which clears the chosen file.
const fileKey = ref(0);

// Mirrors ClientPolicy::update: admins, the team's leader, and the member who brought the client.
function canChange(client) {
    return auth.isAdmin || client.team?.leader_id === auth.user?.id || client.created_by === auth.user?.id;
}

// A user with a single team does not need to pick one.
function defaultTeamId() {
    return teams.value.length === 1 ? teams.value[0].id : '';
}

// A failed load is shown from the store's `error`.
async function load(target = page.value) {
    page.value = target;
    await clientStore.fetchAll({ page: target, search: search.value.trim() || undefined }).catch(() => {});
}

function edit(client) {
    editing.value = client;
    form.reset({ name: client.name, phone_number: client.phone_number, team_id: client.team_id ?? '' });
    fileKey.value++;
}

function resetForm() {
    editing.value = null;
    form.reset({ team_id: defaultTeamId() });
    fileKey.value++;
}

async function submit() {
    const ok = await form.submit((fields) =>
        editing.value ? clientStore.update(editing.value.id, fields) : clientStore.create(fields),
    );

    if (ok) {
        resetForm();
        await load();
    }
}

async function remove(client) {
    if (!confirm(`Delete client "${client.name}"?`)) return;

    if (await action.submit(() => clientStore.remove(client.id))) {
        await load();
    }
}

onMounted(async () => {
    // A failure to load the team picker shows in the form.
    await form.submit(() => auth.fetchTeams({ refresh: true }));
    form.fields.team_id = defaultTeamId();
    await load();
});
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">Clients</h1>
            <p class="mt-1 text-ink-muted">
                Clients of your teams. The member who brings a client owns it and all of its projects.
            </p>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="card p-0 lg:col-span-2" aria-label="Clients">
                <div class="px-6 pt-4">
                    <BaseSearchInput v-model="search" label="Search clients" placeholder="Search by name or phone"
                        @search="load(1)" />
                </div>
                <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || action.message.value" />

                <div class="overflow-x-auto px-3 pt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Line</th>
                                <th>Phone</th>
                                <th class="text-end">Projects</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in clients" :key="client.id" :class="{ 'bg-cobalt-soft': editing?.id === client.id }">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img v-if="client.image" :src="client.image" alt=""
                                            class="h-9 w-9 shrink-0 rounded object-cover" />
                                        <span v-else class="grid h-9 w-9 shrink-0 place-items-center rounded border-2 bg-white font-sign font-semibold text-ink"
                                            :style="{ borderColor: teamInk(client.team_id) }" aria-hidden="true">
                                            {{ client.name.charAt(0).toUpperCase() }}
                                        </span>
                                        <RouterLink :to="{ name: 'client-detail', params: { id: client.id } }"
                                            class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                            {{ client.name }}
                                        </RouterLink>
                                    </div>
                                </td>
                                <td><TeamLine :team="client.team" /></td>
                                <td class="whitespace-nowrap" dir="ltr">{{ client.phone_number }}</td>
                                <td class="text-end">{{ client.projects_count }}</td>
                                <td class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse">
                                    <template v-if="canChange(client)">
                                        <button type="button" class="link" @click="edit(client)">Edit</button>
                                        <button type="button" class="link-danger" @click="remove(client)">Delete</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="loading && !clients.length">
                                <td colspan="5"><span class="block h-9 animate-pulse rounded bg-porcelain" /></td>
                            </tr>
                            <tr v-if="!loading && !clients.length && !error">
                                <td colspan="5" class="py-6 text-ink-muted">
                                    <template v-if="search.trim()">No clients match “{{ search.trim() }}”.</template>
                                    <template v-else>No clients yet. Add the first one with the form; it joins your team's line.</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                    <BasePagination :meta="meta" @change="load" />
                </div>
            </section>

            <form class="card space-y-5 self-start lg:sticky lg:top-10" @submit.prevent="submit">
                <h2 class="section-title">
                    {{ editing ? `Edit client #${editing.id}` : 'New client' }}
                </h2>
                <BaseErrorBanner :message="form.message.value" />

                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" v-model="form.fields.name" type="text" class="input" />
                    <p v-if="form.errors.value.name" class="field-error">{{ form.errors.value.name[0] }}</p>
                </div>

                <div>
                    <label for="phone_number" class="label">Phone number</label>
                    <input id="phone_number" v-model="form.fields.phone_number" type="tel" class="input" dir="ltr" />
                    <p v-if="form.errors.value.phone_number" class="field-error">
                        {{ form.errors.value.phone_number[0] }}
                    </p>
                </div>

                <div>
                    <label for="team_id" class="label">Team</label>
                    <select id="team_id" v-model.number="form.fields.team_id" class="input">
                        <option value="">Choose a team…</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                    <p v-if="!teams.length" class="mt-1 text-sm text-ink-muted">
                        You are not in any team yet, so you cannot add clients.
                    </p>
                    <p v-if="form.errors.value.team_id" class="field-error">{{ form.errors.value.team_id[0] }}</p>
                </div>

                <div>
                    <label for="image" class="label">Image</label>
                    <input id="image" :key="fileKey" type="file"
                        class="file-input"
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
