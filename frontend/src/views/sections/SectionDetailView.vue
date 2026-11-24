<script setup>
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useSectionStore } from '@/stores/sections';
import { useTeamStore } from '@/stores/teams';
import { useUserStore } from '@/stores/users';

const props = defineProps({ id: { type: String, required: true } });

const auth = useAuthStore();
const sectionStore = useSectionStore();
const teamStore = useTeamStore();
const userStore = useUserStore();
const { current: section, error: sectionError } = storeToRefs(sectionStore);
const { items: teams, loading, error } = storeToRefs(teamStore);
// Leader and member choices need every user, which only admins can list (team leaders get just their
// own members). Others type a user id instead.
const { items: users } = storeToRefs(userStore);
// Only users with the team leader role can lead a team (TeamRequest).
const leaders = computed(() => users.value.filter((user) => user.role === 'team_leader'));
const usersForbidden = ref(false);

// Row actions (delete); their failures show above the list, next to load failures.
const action = useForm();

const editing = ref(null);
const form = useForm({ name: '', leader_id: '', members_ids: [] });

// Failed loads are shown from the stores' `error`.
function loadTeams() {
    return teamStore.fetchForSection(props.id).catch(() => {});
}

async function loadUsers() {
    if (!auth.isAdmin) {
        usersForbidden.value = true;
        return;
    }

    await userStore.fetchAll().catch(() => {
        usersForbidden.value = true;
    });
}

function edit(team) {
    editing.value = team;
    form.reset({
        name: team.name,
        leader_id: team.leader_id ?? '',
        members_ids: team.members?.map((member) => member.id) ?? [],
    });
}

function resetForm() {
    editing.value = null;
    form.reset();
}

async function submit() {
    const ok = await form.submit((fields) => {
        // TeamRequest validates section_id from the body, even on the nested route.
        const payload = { ...fields, section_id: Number(props.id) };
        // Without the users list there is no members picker, so leave the current members alone.
        if (usersForbidden.value) delete payload.members_ids;

        return editing.value ? teamStore.update(editing.value.id, payload) : teamStore.create(props.id, payload);
    });

    if (ok) {
        resetForm();
        // The side rail lists the user's teams; reload them on the next page.
        auth.forgetTeams();
        await loadTeams();
    }
}

async function remove(team) {
    if (!confirm(`Delete team "${team.name}"?`)) return;

    if (await action.submit(() => teamStore.remove(team.id))) {
        auth.forgetTeams();
        await loadTeams();
    }
}

onMounted(() => Promise.all([sectionStore.fetchOne(props.id).catch(() => {}), loadTeams(), loadUsers()]));
</script>

<template>
    <AppLayout>
        <nav aria-label="Breadcrumb" class="mb-4 font-sign text-sm font-semibold uppercase tracking-wider">
            <RouterLink :to="{ name: 'sections' }" class="text-ink-muted hover:text-ink">Sections</RouterLink>
            <template v-if="section">
                <span class="mx-2 text-enamel-300" aria-hidden="true">/</span>
                <span class="text-ink" aria-current="page">{{ section.name }}</span>
            </template>
        </nav>

        <BaseErrorBanner :message="sectionError?.message" />

        <header v-if="section" class="mb-8">
            <h1 class="page-title">{{ section.name }}</h1>
            <p class="tabular mt-1 text-ink-muted">Section #{{ section.id }}</p>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-0 lg:col-span-2">
                <div class="border-b border-rule px-6 py-4">
                    <h2 class="section-title">Teams</h2>
                </div>
                <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || action.message.value" />

                <div class="overflow-x-auto px-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Leader</th>
                                <th>Members</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="team in teams" :key="team.id">
                                <td>{{ team.id }}</td>
                                <td class="font-medium"><TeamLine :team="team" /></td>
                                <td>{{ team.leader_name }} <span class="text-ink-muted">#{{ team.leader_id }}</span></td>
                                <td class="text-ink-muted">{{ team.members?.map((member) => member.name).join(', ') || '—' }}</td>
                                <td class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse">
                                    <button type="button" class="link" @click="edit(team)">Edit</button>
                                    <!-- The backend refuses (409) to delete a team that still has members. -->
                                    <button type="button" class="link-danger"
                                        :disabled="team.members?.length > 0"
                                        :title="team.members?.length ? 'Remove the members first' : ''"
                                        @click="remove(team)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!loading && !teams.length">
                                <td colspan="5" class="py-6 text-ink-muted">No teams in this section yet. Create one with the form.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <form class="card space-y-5 self-start lg:sticky lg:top-10" @submit.prevent="submit">
                <h2 class="section-title">
                    {{ editing ? `Edit team #${editing.id}` : 'New team' }}
                </h2>
                <BaseErrorBanner :message="form.message.value" />

                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" v-model="form.fields.name" type="text" class="input" />
                    <p v-if="form.errors.value.name" class="field-error">{{ form.errors.value.name[0] }}</p>
                </div>

                <div>
                    <label for="leader_id" class="label">Leader</label>
                    <input v-if="usersForbidden" id="leader_id" v-model.number="form.fields.leader_id" type="number"
                        placeholder="User id" class="input" />
                    <select v-else id="leader_id" v-model.number="form.fields.leader_id" class="input">
                        <option value="">Choose a user…</option>
                        <option v-for="user in leaders" :key="user.id" :value="user.id">
                            {{ user.name }} (#{{ user.id }})
                        </option>
                    </select>
                    <p v-if="usersForbidden" class="mt-1 text-sm text-ink-muted">
                        Listing users needs an admin, so enter the leader's user id.
                    </p>
                    <p v-if="form.errors.value.leader_id" class="field-error">{{ form.errors.value.leader_id[0] }}</p>
                    <p v-if="form.errors.value.section_id" class="field-error">{{ form.errors.value.section_id[0] }}</p>
                </div>

                <div v-if="!usersForbidden">
                    <label for="members_ids" class="label">Members</label>
                    <select id="members_ids" v-model="form.fields.members_ids" multiple class="input h-32">
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }} (#{{ user.id }})
                        </option>
                    </select>
                    <p class="mt-1 text-sm text-ink-muted">Members see the team's clients. Ctrl/Cmd-click to pick several.</p>
                    <p v-if="form.errors.value.members_ids" class="field-error">{{ form.errors.value.members_ids[0] }}</p>
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
