import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import routes from './routes';

const APP_NAME = import.meta.env.VITE_APP_NAME ?? 'CRM';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior: (to, from, saved) => saved ?? { top: 0 },
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    // Load the current user once (e.g. after a page refresh) so the layout can show it.
    if (auth.isAuthenticated) {
        try {
            await auth.fetchUser();
        } catch {
            // An expired token: the http interceptor has cleared the session.
            if (to.meta.requiresAuth) return { name: 'login', query: { redirect: to.fullPath } };
        }
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ${APP_NAME}` : APP_NAME;
});

export default router;
