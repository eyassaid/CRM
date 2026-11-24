<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useForm } from '@/composables/useForm';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

// Laravel returns 422 with { message, errors } for validation, and a bare { message } for bad credentials.
const form = useForm({ email: '', password: '' });

// Back to the page that sent the user here (the router guard and the 401 handler add ?redirect=), if it is ours.
function destination() {
    const redirect = route.query.redirect;

    return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
        ? redirect
        : { name: 'dashboard' };
}

async function submit() {
    if (await form.submit(auth.login)) {
        router.push(destination());
    }
}
</script>

<template>
    <GuestLayout>
        <h1 class="page-title">Log in</h1>
        <p class="mb-8 mt-1 text-ink-muted">Use the account your admin or team leader created for you.</p>

        <p v-if="form.message.value" class="mb-6 rounded border border-scarlet/30 bg-scarlet-soft px-3 py-2 text-sm text-scarlet-dark" role="alert">
            {{ form.message.value }}
        </p>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" v-model="form.fields.email" type="email" required autocomplete="username" class="input" />
                <p v-if="form.errors.value.email" class="field-error">{{ form.errors.value.email[0] }}</p>
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input id="password" v-model="form.fields.password" type="password" required autocomplete="current-password"
                    class="input" />
                <p v-if="form.errors.value.password" class="field-error">{{ form.errors.value.password[0] }}</p>
            </div>

            <button type="submit" :disabled="form.processing.value" class="btn w-full py-3">
                {{ form.processing.value ? 'Logging in…' : 'Log in' }}
            </button>
        </form>
    </GuestLayout>
</template>
