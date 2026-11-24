<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>

<template>
    <component :is="auth.isAuthenticated ? AppLayout : GuestLayout">
        <h1 class="page-title">Page not found</h1>
        <p class="mt-1 text-ink-muted">There is nothing at this address.</p>
        <RouterLink :to="{ name: auth.isAuthenticated ? 'dashboard' : 'login' }" class="link mt-6 inline-block">
            {{ auth.isAuthenticated ? 'Back to my work' : 'Log in' }}
        </RouterLink>
    </component>
</template>
