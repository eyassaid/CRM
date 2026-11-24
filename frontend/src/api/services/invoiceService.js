import http from '@/api/http';

const resource = '/invoices';

// Invoices are stored as items ({ items: [{ name, cost }] }); the PDF is rendered by the API on download.
export default {
    listForProject: (projectId, params = {}) => http.get(`/projects/${projectId}/invoices`, { params }).then((r) => r.data),
    create: (projectId, fields) => http.post(`/projects/${projectId}/invoices`, fields).then((r) => r.data),
    update: (id, fields) => http.put(`${resource}/${id}`, fields).then((r) => r.data),
    remove: (id) => http.delete(`${resource}/${id}`),
    // Resolves to the PDF as a Blob.
    downloadPdf: (id) => http.get(`${resource}/${id}/pdf`, { responseType: 'blob' }).then((r) => r.data),
};
