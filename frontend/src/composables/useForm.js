import { reactive, ref } from 'vue';

// Fields, Laravel errors and a busy flag for one form, or for one kind of row action (delete, approve...)
// when called without fields. `submit` resolves to true when the action succeeded; when it fails it records
// the message and the 422 field errors for the template to show, and resolves to false.
export function useForm(initial = {}) {
    const fields = reactive(structuredClone(initial));
    const errors = ref({});
    const message = ref('');
    const processing = ref(false);

    async function submit(action) {
        processing.value = true;
        clear();

        try {
            await action({ ...fields });
            return true;
        } catch (error) {
            // Requests reject with { status, message, errors } (see api/http.js).
            errors.value = error.errors ?? {};
            message.value = error.message ?? 'Something went wrong.';
            return false;
        } finally {
            processing.value = false;
        }
    }

    function clear() {
        errors.value = {};
        message.value = '';
    }

    // Back to the initial values, with `values` on top (e.g. a record being edited).
    function reset(values = {}) {
        Object.assign(fields, structuredClone(initial), values);
        clear();
    }

    return { fields, errors, message, processing, submit, reset, clear };
}
