import { ref, watch } from 'vue';

// Whether the side rail is collapsed to icons (large screens only). Shared across pages, since every view mounts its
// own AppLayout, and remembered in this browser. Storage can be unavailable (private mode), so failures are ignored.
const KEY = 'sidebar-collapsed';

function stored() {
    try {
        return localStorage.getItem(KEY) === '1';
    } catch {
        return false;
    }
}

const collapsed = ref(stored());

watch(collapsed, (value) => {
    try {
        localStorage.setItem(KEY, value ? '1' : '0');
    } catch {
        // Not remembered; the rail still toggles.
    }
});

export function useSidebar() {
    return {
        collapsed,
        toggle: () => {
            collapsed.value = !collapsed.value;
        },
    };
}
