<script setup>
import { computed } from 'vue';
import { projectStatus } from '@/constants/projectStatus';

// A project's lifecycle drawn as a line with stations, in its team's ink.
// A request waits at the first station until the leader approves it; a cancelled project's line is suspended.
const props = defineProps({
    status: { type: String, required: true },
    approved: { type: Boolean, default: true },
    ink: { type: String, default: '#1E5BFF' },
    large: { type: Boolean, default: false },
    // Gives the small route's label a fixed width so routes line up in a list.
    aligned: { type: Boolean, default: false },
});

const STATIONS = ['Requested', 'Approved', 'Working', 'Done'];
const STATUS_STATION = { not_started: 1, working_on_it: 2, done: 3 };

const cancelled = computed(() => props.status === 'cancelled');
const reached = computed(() => (cancelled.value ? -1 : props.approved ? STATUS_STATION[props.status] ?? 1 : 0));
const label = computed(() => (props.approved || cancelled.value ? projectStatus(props.status).label : 'Awaiting approval'));
const fill = computed(() => `${(Math.max(reached.value, 0) / (STATIONS.length - 1)) * 100}%`);
</script>

<template>
    <div :class="large ? 'w-full' : 'inline-flex items-center gap-3'" role="img" :aria-label="`Status: ${label}`">
        <p v-if="large" class="mb-4 text-ink-muted" aria-hidden="true">
            Status
            <strong class="ms-1 font-semibold" :class="cancelled ? 'text-ink-muted' : 'text-ink'">{{ label }}</strong>
        </p>
        <div class="relative flex items-center justify-between" :class="large ? 'mx-3 h-5' : 'h-3 w-24 shrink-0'">
            <!-- A cancelled project's line is suspended: drawn as dashes instead of a solid track. -->
            <span class="absolute inset-x-0 top-1/2 -translate-y-1/2" :class="[
                large ? 'h-1.5' : 'h-1',
                cancelled ? 'h-0.5 bg-[repeating-linear-gradient(to_right,theme(colors.enamel.300)_0_6px,transparent_6px_10px)]' : 'rounded-full bg-rule',
            ]" />
            <span v-if="!cancelled"
                class="absolute start-0 top-1/2 -translate-y-1/2 rounded-full transition-[width] duration-200 ease-out motion-reduce:transition-none"
                :class="large ? 'h-1.5' : 'h-1'" :style="{ width: fill, backgroundColor: ink }" />
            <span v-for="(station, index) in STATIONS" :key="station" class="relative rounded-full border-2 bg-white"
                :class="[
                    large ? (index === reached ? 'h-5 w-5 border-4' : 'h-4 w-4') : (index === reached ? 'h-3 w-3' : 'h-2 w-2 border'),
                    cancelled ? 'border-enamel-300' : index > reached ? 'border-rule' : '',
                ]"
                :style="!cancelled && index <= reached ? { borderColor: ink, backgroundColor: index < reached ? ink : '#fff' } : {}" />
        </div>

        <div v-if="large" class="mt-2 flex justify-between font-sign text-xs font-semibold uppercase tracking-wider" aria-hidden="true">
            <span v-for="(station, index) in STATIONS" :key="station"
                :class="!cancelled && index === reached ? 'text-ink' : 'text-ink-muted'">
                {{ station }}
            </span>
        </div>
        <span v-else class="whitespace-nowrap text-sm" :class="[{ 'w-32': aligned }, cancelled ? 'text-ink-muted line-through' : 'text-ink']"
            aria-hidden="true">
            {{ label }}
        </span>
    </div>
</template>
