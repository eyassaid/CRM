<script setup>
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import BaseSearchInput from '@/components/common/BaseSearchInput.vue';
import TeamLine from '@/components/teams/TeamLine.vue';
import { useForm } from '@/composables/useForm';
import { ASSIGNABLE_ROLES, userRoleLabel } from '@/constants/userRole';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/users';
import { withoutEmpty } from '@/utils/formData';

const auth = useAuthStore();
const userStore = useUserStore();
const { items: users, meta, loading, error } = storeToRefs(userStore);

const page = ref(1);
// Filters the list by name or email (on the server, so it covers every page).
const search = ref('');
// Row actions (delete); their failures show above the list, next to load failures.
const action = useForm();

// A new user joins one of these teams: any team for admins (optional), a team they lead for leaders (required).
const teams = computed(() => (auth.isAdmin ? auth.teams ?? [] : auth.ledTeams));

const editing = ref(null);
// Admins pick employee or team leader; an admin's own role is not editable here. Leaders always add employees.
const canChooseRole = computed(() => auth.isAdmin && editing.value?.role !== 'admin');
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', team_id: '', role: 'employee' });

// A failed load is shown from the store's `error`.
async function load(target = page.value) {
    page.value = target;
    await userStore.fetchAll({ page: target, search: search.value.trim() || undefined }).catch(() => {});
}

function edit(user) {
    editing.value = user;
    form.reset({ name: user.name, email: user.email, role: user.role });
}

function resetForm() {
    editing.value = null;
    form.reset();
}

async function submit() {
    const ok = await form.submit((values) => {
        // On update, a blank password means "keep the current one", so it is not sent; the team is only set on create.
        const fields = { ...values, role: canChooseRole.value ? values.role : '' };

        return editing.value
            ? userStore.update(editing.value.id, withoutEmpty({ ...fields, team_id: '' }))
            : userStore.create(withoutEmpty(fields));
    });

    if (ok) {
        resetForm();
        await load();
    }
}

async function remove(user) {
    if (!confirm(`Delete user "${user.name}"?`)) return;

    if (await action.submit(() => userStore.remove(user.id))) {
        await load();
    }
}

// A failure to load the team picker shows above the list (auth store `error`).
onMounted(() => Promise.all([load(), auth.fetchTeams({ refresh: true }).catch(() => {})]));
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">Users</h1>
            <p class="mt-1 text-ink-muted">
                {{ auth.isAdmin ? 'Everyone in the firm. Admins add employees and team leaders.' : 'People on the teams you lead. Add employees to your teams.' }}
            </p>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-0 lg:col-span-2">
                <div class="px-6 pt-4">
                    <BaseSearchInput v-model="search" label="Search users" placeholder="Search by name or email"
                        @search="load(1)" />
                </div>
                <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || auth.error?.message || action.message.value" />

                <div class="overflow-x-auto px-3 pt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Teams</th>
                                <th v-if="auth.isAdmin"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td class="font-medium">{{ user.name }}</td>
                                <td class="text-ink-muted">{{ user.email }}</td>
                                <td class="whitespace-nowrap">{{ userRoleLabel(user.role) }}</td>
                                <td>
                                    <ul v-if="user.teams?.length" class="space-y-1">
                                        <li v-for="team in user.teams" :key="team.id" :title="team.section_name">
                                            <TeamLine :team="team" />
                                        </li>
                                    </ul>
                                    <span v-else class="text-ink-muted">—</span>
                                </td>
                                <td v-if="auth.isAdmin" class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse">
                                    <button type="button" class="link" @click="edit(user)">Edit</button>
                                    <button type="button" class="link-danger" @click="remove(user)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!loading && !users.length && !error">
                                <td colspan="6" class="py-6 text-ink-muted">
                                    <template v-if="search.trim()">No users match “{{ search.trim() }}”.</template>
                                    <template v-else>No users yet. Add the first one with the form.</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="meta?.last_page > 1" class="px-6 pb-4">
                    <BasePagination :meta="meta" @change="load" />
                </div>
            </div>

            <form class="card space-y-5 self-start lg:sticky lg:top-10" @submit.prevent="submit">
                <h2 class="section-title">
                    {{ editing ? `Edit user #${editing.id}` : auth.isAdmin ? 'New user' : 'New employee' }}
                </h2>
                <BaseErrorBanner :message="form.message.value" />

                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" v-model="form.fields.name" type="text" class="input" />
                    <p v-if="form.errors.value.name" class="field-error">{{ form.errors.value.name[0] }}</p>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" v-model="form.fields.email" type="email" class="input" />
                    <p v-if="form.errors.value.email" class="field-error">{{ form.errors.value.email[0] }}</p>
                </div>

                <div>
                    <label for="password" class="label">
                        Password <span v-if="editing" class="normal-case tracking-normal">(leave blank to keep)</span>
                    </label>
                    <input id="password" v-model="form.fields.password" type="password" autocomplete="new-password"
                        class="input" />
                    <p v-if="form.errors.value.password" class="field-error">{{ form.errors.value.password[0] }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" v-model="form.fields.password_confirmation" type="password"
                        autocomplete="new-password" class="input" />
                </div>

                <div v-if="canChooseRole">
                    <label for="role" class="label">Role</label>
                    <select id="role" v-model="form.fields.role" class="input">
                        <option v-for="role in ASSIGNABLE_ROLES" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                    <p class="mt-1 text-sm text-ink-muted">Only team leaders can lead a team.</p>
                    <p v-if="form.errors.value.role" class="field-error">{{ form.errors.value.role[0] }}</p>
                </div>

                <div v-if="!editing">
                    <label for="team_id" class="label">Team</label>
                    <select id="team_id" v-model="form.fields.team_id" class="input">
                        <option value="">{{ auth.isAdmin ? 'No team' : 'Choose a team…' }}</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                    <p class="mt-1 text-sm text-ink-muted">The employee joins this team.</p>
                    <p v-if="form.errors.value.team_id" class="field-error">{{ form.errors.value.team_id[0] }}</p>
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
