import http from '@/api/http';

export default {
    login: (credentials) => http.post('/auth/login', credentials).then((r) => r.data),
    logout: () => http.post('/auth/logout'),
    me: () => http.get('/me').then((r) => r.data),
    // The user's teams (every team for an admin). Not paginated.
    teams: () => http.get('/me/teams').then((r) => r.data),
};
