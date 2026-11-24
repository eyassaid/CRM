import http from '@/api/http';
import { toFormData } from '@/utils/formData';

const resource = '/projects';

export default {
    listForClient: (clientId, params = {}) => http.get(`/clients/${clientId}/projects`, { params }).then((r) => r.data),
    get: (id) => http.get(`${resource}/${id}`).then((r) => r.data),
    // The project form carries an image (and invoice items when it completes the project): multipart.
    create: (clientId, fields) => http.post(`/clients/${clientId}/projects`, toFormData(fields)).then((r) => r.data),
    updateForm: (id, fields) => http.post(`${resource}/${id}`, toFormData(fields, 'PUT')).then((r) => r.data),
    // JSON updates without files, e.g. { approved: true } or { user_ids: [...] } (an empty list must still be sent).
    update: (id, payload) => http.put(`${resource}/${id}`, payload).then((r) => r.data),
    remove: (id) => http.delete(`${resource}/${id}`),
};
