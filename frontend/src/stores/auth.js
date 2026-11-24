import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import authService from '@/api/services/authService';

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref(null);
    const token = ref(localStorage.getItem('token'));
    // The user's teams (GET /me/teams), shown as lines in the side rail and used by team pickers.
    const teams = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Getters
    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const isTeamLeader = computed(() => user.value?.role === 'team_leader');
    // Mirrors UserPolicy: admins and team leaders can list and add users.
    const canManageUsers = computed(() => isAdmin.value || isTeamLeader.value);
    // The teams the user leads (used for reports and for the users a leader can add).
    const ledTeams = computed(() => (teams.value ?? []).filter((team) => team.leader_id === user.value?.id));

    // Actions
    function setToken(value) {
        token.value = value;

        if (value) {
            localStorage.setItem('token', value);
        } else {
            localStorage.removeItem('token');
        }
    }

    async function login(credentials) {
        const res = await authService.login(credentials);
        setToken(res.data.access_token);
        user.value = res.data.user;
    }

    // Loads the current user once (e.g. after a page refresh).
    async function fetchUser() {
        if (!token.value || user.value) return;

        const res = await authService.me();
        user.value = res.data;
    }

    // Loaded once and kept; pass { refresh: true } where the list must be current (e.g. a team picker).
    async function fetchTeams({ refresh = false } = {}) {
        if (teams.value && !refresh) return;

        loading.value = true;
        error.value = null;
        try {
            const res = await authService.teams();
            teams.value = res.data;
        } catch (e) {
            error.value = e;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // After teams change, the next fetchTeams() loads them again.
    function forgetTeams() {
        teams.value = null;
    }

    // Drops the session without calling the API (the token is already invalid, e.g. after a 401).
    function clearSession() {
        setToken(null);
        user.value = null;
        teams.value = null;
        error.value = null;
    }

    async function logout() {
        try {
            await authService.logout();
        } finally {
            clearSession();
        }
    }

    return {
        user, token, teams, loading, error,
        isAuthenticated, isAdmin, isTeamLeader, canManageUsers, ledTeams,
        login, fetchUser, fetchTeams, forgetTeams, clearSession, logout,
    };
});
