<script setup>
import { ref } from 'vue';
import { apiLog, clearLog } from '@/api/log';

// Collapsed by default so it does not cover the page.
const open = ref(false);
const expanded = ref(null);

function statusClass(status) {
    if (typeof status !== 'number' || status >= 500) return 'bg-scarlet-soft text-scarlet-dark';
    if (status >= 400) return 'bg-amber-soft text-amber-dark';
    return 'bg-green-soft text-green-dark';
}

function toggle(id) {
    expanded.value = expanded.value === id ? null : id;
}

function format(data) {
    if (data === '' || data === undefined) return '(empty body)';
    return typeof data === 'string' ? data : JSON.stringify(data, null, 2);
}
</script>

<template>
    <div class="fixed bottom-4 end-4 z-50 w-[calc(100%-2rem)] max-w-xl rounded border border-rule bg-white shadow-[0_8px_24px_rgba(11,18,48,0.16)]">
        <div class="flex items-center justify-between border-b border-rule px-4 py-2">
            <button type="button" class="flex items-center gap-2 font-sign text-sm font-semibold uppercase tracking-wider text-ink"
                :aria-expanded="open" @click="open = !open">
                API responses ({{ apiLog.entries.length }})
                <svg viewBox="0 0 16 16" class="h-4 w-4 transition-transform duration-150" :class="{ '-rotate-90 rtl:rotate-90': !open }"
                    fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 6l4 4 4-4" />
                </svg>
            </button>
            <button v-if="open && apiLog.entries.length" type="button" class="text-xs text-ink-muted hover:text-ink"
                @click="clearLog">
                Clear
            </button>
        </div>

        <ul v-if="open" class="max-h-[50vh] divide-y divide-rule/60 overflow-y-auto">
            <li v-if="!apiLog.entries.length" class="px-4 py-3 text-sm text-ink-muted">No requests yet.</li>

            <li v-for="entry in apiLog.entries" :key="entry.id">
                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-start text-xs hover:bg-porcelain"
                    @click="toggle(entry.id)">
                    <span class="rounded px-1.5 py-0.5 font-mono font-semibold" :class="statusClass(entry.status)">
                        {{ entry.status }}
                    </span>
                    <span class="font-mono font-semibold text-ink">{{ entry.method }}</span>
                    <span class="truncate font-mono text-ink-muted">{{ entry.url }}</span>
                    <span class="ms-auto shrink-0 text-ink-muted">{{ entry.at.toLocaleTimeString() }}</span>
                </button>
                <pre v-if="expanded === entry.id"
                    class="max-h-72 overflow-auto bg-enamel-950 px-4 py-3 text-xs text-enamel-200" dir="ltr">{{ format(entry.data) }}</pre>
            </li>
        </ul>
    </div>
</template>
