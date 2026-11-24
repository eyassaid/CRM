<script setup>
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import BaseErrorBanner from '@/components/common/BaseErrorBanner.vue';
import BasePagination from '@/components/common/BasePagination.vue';
import { useForm } from '@/composables/useForm';
import AppLayout from '@/layouts/AppLayout.vue';
import { useSectionStore } from '@/stores/sections';

const sectionStore = useSectionStore();
const { items: sections, meta, loading, error } = storeToRefs(sectionStore);

const page = ref(1);
// Row actions (delete); their failures show above the list, next to load failures.
const action = useForm();

const editing = ref(null);
const form = useForm({ name: '' });

// A failed load is shown from the store's `error`.
async function load(target = page.value) {
    page.value = target;
    await sectionStore.fetchAll({ page: target }).catch(() => {});
}

function edit(section) {
    editing.value = section;
    form.reset({ name: section.name });
}

function resetForm() {
    editing.value = null;
    form.reset();
}

async function submit() {
    const ok = await form.submit((fields) =>
        editing.value ? sectionStore.update(editing.value.id, fields) : sectionStore.create(fields),
    );

    if (ok) {
        resetForm();
        await load();
    }
}

async function remove(section) {
    if (!confirm(`Delete section "${section.name}"? Its teams are deleted too.`)) return;

    if (await action.submit(() => sectionStore.remove(section.id))) {
        await load();
    }
}

onMounted(() => load());
</script>

<template>
    <AppLayout>
        <header class="mb-8">
            <h1 class="page-title">Sections</h1>
            <p class="mt-1 text-ink-muted">The firm's sections and the teams inside each one.</p>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-0 lg:col-span-2">
                <BaseErrorBanner class="mx-6 mt-4" :message="error?.message || action.message.value" />

                <div class="overflow-x-auto px-3 pt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Updated</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="section in sections" :key="section.id">
                                <td>{{ section.id }}</td>
                                <td>
                                    <RouterLink :to="{ name: 'section-detail', params: { id: section.id } }" class="font-medium text-ink hover:text-cobalt-dark hover:underline">
                                        {{ section.name }}
                                    </RouterLink>
                                </td>
                                <td class="whitespace-nowrap text-ink-muted">{{ new Date(section.updated_at).toLocaleString() }}</td>
                                <td class="space-x-4 whitespace-nowrap text-end rtl:space-x-reverse">
                                    <button type="button" class="link" @click="edit(section)">Edit</button>
                                    <button type="button" class="link-danger" @click="remove(section)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!loading && !sections.length">
                                <td colspan="4" class="py-6 text-ink-muted">No sections yet. Create one with the form, then add its teams.</td>
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
                    {{ editing ? `Edit section #${editing.id}` : 'New section' }}
                </h2>
                <BaseErrorBanner :message="form.message.value" />

                <div>
                    <label for="name" class="label">Name</label>
                    <input id="name" v-model="form.fields.name" type="text" class="input" />
                    <p v-if="form.errors.value.name" class="field-error">{{ form.errors.value.name[0] }}</p>
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
