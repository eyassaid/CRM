// Builds multipart form data for requests that carry files.
// PHP only parses multipart bodies on POST, so updates are sent as POST with
// `_method=PUT` and Laravel routes them to the update action.
export function toFormData(fields, method = null) {
    const formData = new FormData();

    for (const [key, value] of Object.entries(fields)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
    }

    if (method) {
        formData.append('_method', method);
    }

    return formData;
}

// Drops empty fields so optional inputs (e.g. a blank password on update) are not sent.
export function withoutEmpty(fields) {
    return Object.fromEntries(Object.entries(fields).filter(([, value]) => value !== '' && value !== null));
}
