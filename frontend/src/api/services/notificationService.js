import http from '@/api/http';

const resource = '/notifications';

export default {
    // { unread: 1 } lists only unread notifications.
    list: (params = {}) => http.get(resource, { params }).then((r) => r.data),
    update: (id, payload) => http.put(`${resource}/${id}`, payload).then((r) => r.data),
};
