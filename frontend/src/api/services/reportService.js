import http from '@/api/http';

const resource = '/reports/teams';

export default {
    // Every team with its counts (admins only).
    teams: (params = {}) => http.get(resource, { params }).then((r) => r.data),
    // One team's report (admins and the team's leader).
    team: (id) => http.get(`${resource}/${id}`).then((r) => r.data),
};
