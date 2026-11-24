import axios from 'axios';
import router from '@/router';
import { useAuthStore } from '@/stores/auth';
import { record } from './log';

// The one axios instance. In development Vite proxies /api to Laravel (see vite.config.js); in production nginx does.
// .env is not copied into the Docker build, so the production bundle falls back to /api.
const http = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? '/api',
    headers: { Accept: 'application/json' },
    timeout: 15000,
});

// Attach the Passport token to every request.
http.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

// The method the controller sees: a POST carrying `_method=PUT` reaches the update action.
function describe(config = {}) {
    const spoofed = config.data instanceof FormData ? config.data.get('_method') : null;
    const method = (config.method ?? 'get').toUpperCase();

    return {
        method: spoofed ? `${spoofed} (via POST)` : method,
        url: (config.baseURL ?? '') + (config.url ?? ''),
    };
}

function errorMessage(status, data) {
    if (data?.message) return data.message;
    if (typeof data === 'string' && data) return data;

    return status ? 'Something went wrong.' : 'Network error, please try again.';
}

// Record every response for the ResponseLog panel, and reject every failed request in one shape:
// { status, message, errors } (errors holds Laravel's 422 field errors).
http.interceptors.response.use(
    (response) => {
        const data = response.data instanceof Blob
            ? `(${response.data.type || 'file'}, ${response.data.size} bytes)`
            : response.data;
        record({ ...describe(response.config), status: response.status, data });

        return response;
    },
    async (error) => {
        const status = error.response?.status ?? 0;
        let data = error.response?.data;

        // Downloads (responseType 'blob') receive their JSON error body as a Blob.
        if (data instanceof Blob) {
            data = await data.text().then(JSON.parse).catch(() => undefined);
        }

        record({ ...describe(error.config), status: status || 'network error', data: data ?? error.message });

        // A 401 means the token is missing or expired: drop the session and go back to the login page.
        // Before the first navigation has resolved there is no current route; the router guard redirects then.
        if (status === 401) {
            useAuthStore().clearSession();

            const current = router.currentRoute.value;
            if (current.matched.length && current.name !== 'login') {
                router.push({ name: 'login', query: { redirect: current.fullPath } });
            }
        }

        return Promise.reject({ status, message: errorMessage(status, data), errors: data?.errors ?? {} });
    },
);

export default http;
