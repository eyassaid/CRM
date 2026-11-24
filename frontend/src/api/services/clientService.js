import http from '@/api/http';
import { toFormData } from '@/utils/formData';

const resource = '/clients';

// Clients carry an image, so create and update are sent as multipart form data.
export default {
    list: (params = {}) => http.get(resource, { params }).then((r) => r.data),
    get: (id) => http.get(`${resource}/${id}`).then((r) => r.data),
    create: (fields) => http.post(resource, toFormData(fields)).then((r) => r.data),
    update: (id, fields) => http.post(`${resource}/${id}`, toFormData(fields, 'PUT')).then((r) => r.data),
    remove: (id) => http.delete(`${resource}/${id}`),
};
