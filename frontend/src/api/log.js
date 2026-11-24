import { reactive } from 'vue';

// Every API response (success or error) is recorded here so the ResponseLog panel
// can show exactly what each controller returned.
export const apiLog = reactive({ entries: [] });

let nextId = 1;

export function record(entry) {
    apiLog.entries.unshift({ id: nextId++, at: new Date(), ...entry });
    apiLog.entries.splice(30);
}

export function clearLog() {
    apiLog.entries.splice(0);
}
