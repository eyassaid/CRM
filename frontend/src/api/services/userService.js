import http from '@/api/http';

const resource = '/users';

export default {
    list: (params = {}) => http.get(resource, { params }).then((r) => r.data),
    create: (payload) => http.post(resource, payload).then((r) => r.data),
    update: (id, payload) => http.put(`${resource}/${id}`, payload).then((r) => r.data),
    remove: (id) => http.delete(`${resource}/${id}`),
};
