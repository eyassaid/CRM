<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ResponseLog from '@/components/common/ResponseLog.vue';
import { useSidebar } from '@/composables/useSidebar';
import { teamInk } from '@/constants/teamLines';
import { userRoleLabel } from '@/constants/userRole';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';

const auth = useAuthStore();
const router = useRouter();

// Outline icons (24×24, stroked) for the rail; collapsed, the rail shows only these.
const ICONS = {
    work: 'M4 8h16v11H4zM9 8V5h6v3M4 13h16',
    clients: 'M4 20V6l8-3v17M12 9h8v11M3 20h18M7 9h2M7 13h2M15 13h2M15 16h2',
    notifications: 'M6 16v-5a6 6 0 1 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0',
    reports: 'M5 20v-9M12 20V5M19 20v-6M3 20h18',
    users: 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 6.5M18 14a6 6 0 0 1 3.5 6',
    sections: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
    logout: 'M9 4H5v16h4M14 8l4 4-4 4M18 12H9',
    collapse: 'M15 6l-6 6 6 6',
};

// Only pages the user can open: Sections are for admins, Users and Reports for admins and team leaders.
const links = computed(() => [
    { to: { name: 'dashboard' }, label: 'My work', icon: ICONS.work },
    { to: { name: 'clients' }, label: 'Clients', icon: ICONS.clients },
    { to: { name: 'notifications' }, label: 'Notifications', icon: ICONS.notifications, badge: true },
    auth.canManageUsers && { to: { name: 'reports' }, label: 'Reports', icon: ICONS.reports },
    auth.canManageUsers && { to: { name: 'users' }, label: 'Users', icon: ICONS.users },
    auth.isAdmin && { to: { name: 'sections' }, label: 'Sections', icon: ICONS.sections },
].filter(Boolean));

// Large screens: the rail collapses to icons (remembered per browser). Below lg it is a top bar with a menu instead.
const { collapsed, toggle } = useSidebar();

// The rail lists a handful of lines; admins belong to every team, so the rest are summed up.
const RAIL_LINES = 6;
const lines = computed(() => (auth.teams ?? []).slice(0, RAIL_LINES));
const moreLines = computed(() => Math.max((auth.teams?.length ?? 0) - RAIL_LINES, 0));

const initials = computed(() =>
    (auth.user?.name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0].toUpperCase()).join(''),
);

// The API response panel is a development aid, not part of the product.
const showLog = import.meta.env.DEV;

// Below the lg breakpoint the rail folds into a top bar with a menu.
const menuOpen = ref(false);

const notifications = useNotificationStore();

// The badge and the lines are optional; a 401 is handled by the http interceptor.
onMounted(() => {
    notifications.fetchUnreadCount().catch(() => {});
    auth.fetchTeams().catch(() => {});
});

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen bg-porcelain lg:flex">
        <aside class="bg-enamel-950 text-enamel-200 lg:sticky lg:top-0 lg:flex lg:h-screen lg:shrink-0 lg:flex-col lg:transition-[width] lg:duration-200"
            :class="collapsed ? 'lg:w-[4.5rem]' : 'lg:w-64'">
            <div class="flex h-16 items-center justify-between px-5 lg:h-auto lg:pb-8 lg:pt-6"
                :class="{ 'lg:justify-center lg:px-0': collapsed }">
                <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-3 text-white">
                    <svg viewBox="0 0 32 32" class="h-8 w-8 shrink-0" aria-hidden="true">
                        <path d="M2 23 L11 16 H21 L30 9" fill="none" stroke="#D21F2B" stroke-width="3" stroke-linejoin="round" />
                        <path d="M2 9 L11 16 H21 L30 23" fill="none" stroke="#1E5BFF" stroke-width="3" stroke-linejoin="round" />
                        <circle cx="16" cy="16" r="5.5" fill="#0B1230" stroke="#fff" stroke-width="2.5" />
                    </svg>
                    <span class="font-sign text-xl font-bold uppercase tracking-widest" :class="{ 'lg:sr-only': collapsed }">CRM</span>
                </RouterLink>
                <button type="button" class="rounded p-2 text-enamel-200 hover:bg-enamel-800 hover:text-white lg:hidden"
                    :aria-expanded="menuOpen" aria-controls="app-nav" @click="menuOpen = !menuOpen">
                    <span class="sr-only">Menu</span>
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" aria-hidden="true">
                        <path v-if="menuOpen" d="M6 6l12 12M18 6L6 18" />
                        <path v-else d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>

            <div id="app-nav" class="flex-1 flex-col overflow-y-auto overflow-x-hidden px-3 pb-4 lg:flex" :class="menuOpen ? 'flex' : 'hidden'">
                <nav aria-label="Main">
                    <RouterLink v-for="link in links" :key="link.label" :to="link.to"
                        class="group relative flex items-center gap-3 rounded px-3 py-2 font-sign text-base font-semibold uppercase tracking-wider text-enamel-200 transition-colors duration-150 hover:bg-enamel-800 hover:text-white"
                        :class="{ 'lg:justify-center lg:px-0 lg:[&.is-current]:bg-enamel-800': collapsed }"
                        :title="collapsed ? link.label : undefined"
                        exact-active-class="is-current !text-white" @click="menuOpen = false">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="link.icon" />
                        </svg>
                        <span class="whitespace-nowrap border-b-[3px] border-transparent pb-0.5 transition-colors duration-150 group-[.is-current]:border-white"
                            :class="{ 'lg:sr-only': collapsed }">
                            {{ link.label }}
                        </span>
                        <span v-if="link.badge && notifications.unread"
                            class="ms-auto min-w-6 rounded-full bg-scarlet px-2 text-center font-sans text-xs leading-5 text-white"
                            :class="{ 'lg:absolute lg:end-1 lg:top-0.5 lg:min-w-4 lg:px-1 lg:text-[10px] lg:leading-4': collapsed }">
                            {{ notifications.unread }}
                            <span class="sr-only">unread</span>
                        </span>
                    </RouterLink>
                </nav>

                <section v-if="lines.length" class="mt-8" :class="{ 'lg:hidden': collapsed }" aria-labelledby="rail-lines">
                    <h2 id="rail-lines" class="px-3 font-sign text-xs font-semibold uppercase tracking-widest text-enamel-300">
                        Your lines
                    </h2>
                    <ul class="mt-2 space-y-1">
                        <li v-for="team in lines" :key="team.id" class="flex items-center gap-3 px-3 py-1.5 text-sm">
                            <span class="h-1.5 w-6 shrink-0 rounded-full" :style="{ backgroundColor: teamInk(team.id) }"
                                aria-hidden="true" />
                            <span class="truncate">{{ team.name }}</span>
                        </li>
                        <li v-if="moreLines" class="px-3 py-1.5 text-sm text-enamel-300">and {{ moreLines }} more</li>
                    </ul>
                </section>

                <div class="mt-8 border-t border-enamel-800 pt-4 lg:mt-auto">
                    <div v-if="auth.user" class="flex items-center gap-3 px-3" :class="{ 'lg:justify-center lg:px-0': collapsed }"
                        :title="collapsed ? `${auth.user.name} (${userRoleLabel(auth.user.role)})` : undefined">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-amber font-sign text-sm font-bold text-enamel-950"
                            aria-hidden="true">
                            {{ initials }}
                        </span>
                        <div class="min-w-0" :class="{ 'lg:sr-only': collapsed }">
                            <p class="truncate text-sm font-semibold text-white">{{ auth.user.name }}</p>
                            <p class="text-xs text-enamel-300">{{ userRoleLabel(auth.user.role) }}</p>
                        </div>
                    </div>
                    <button type="button"
                        class="mt-3 flex w-full items-center gap-3 rounded px-3 py-2 text-start font-sign text-sm font-semibold uppercase tracking-wider text-enamel-300 transition-colors duration-150 hover:bg-enamel-800 hover:text-white"
                        :class="{ 'lg:justify-center lg:px-0': collapsed }" :title="collapsed ? 'Log out' : undefined"
                        @click="logout">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 rtl:-scale-x-100" fill="none" stroke="currentColor"
                            stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="ICONS.logout" />
                        </svg>
                        <span :class="{ 'lg:sr-only': collapsed }">Log out</span>
                    </button>
                    <button type="button"
                        class="mt-1 hidden w-full items-center gap-3 rounded px-3 py-2 text-start font-sign text-sm font-semibold uppercase tracking-wider text-enamel-300 transition-colors duration-150 hover:bg-enamel-800 hover:text-white lg:flex"
                        :class="{ 'lg:justify-center lg:px-0': collapsed }" :title="collapsed ? 'Expand sidebar' : undefined"
                        :aria-expanded="!collapsed" @click="toggle">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 transition-transform duration-200"
                            :class="collapsed ? 'rotate-180 rtl:rotate-0' : 'rtl:rotate-180'" fill="none" stroke="currentColor"
                            stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="ICONS.collapse" />
                        </svg>
                        <span :class="{ 'sr-only': collapsed }">{{ collapsed ? 'Expand sidebar' : 'Collapse' }}</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- In development the API response panel floats over the bottom of the page: leave room for it. -->
        <main class="mx-auto w-full min-w-0 max-w-7xl px-4 py-8 sm:px-8 lg:py-10" :class="{ 'pb-24 lg:pb-24': showLog }">
            <slot />
        </main>

        <ResponseLog v-if="showLog" />
    </div>
</template>
