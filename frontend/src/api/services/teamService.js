import http from '@/api/http';

const resource = '/teams';

export default {
    listForSection: (sectionId, params = {}) => http.get(`/sections/${sectionId}/teams`, { params }).then((r) => r.data),
    get: (id) => http.get(`${resource}/${id}`).then((r) => r.data),
    create: (sectionId, payload) => http.post(`/sections/${sectionId}/teams`, payload).then((r) => r.data),
    update: (id, payload) => http.put(`${resource}/${id}`, payload).then((r) => r.data),
    remove: (id) => http.delete(`${resource}/${id}`),
};
