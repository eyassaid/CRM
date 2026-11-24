<script setup>
import { teamInk } from '@/constants/teamLines';

// A team's line: its colour bar and name. `team` needs `id` and `name`.
defineProps({ team: { type: Object, default: null } });
</script>

<template>
    <span v-if="team" class="inline-flex items-center gap-2 whitespace-nowrap">
        <span class="h-1.5 w-5 shrink-0 rounded-full" :style="{ backgroundColor: teamInk(team.id) }" aria-hidden="true" />
        <span>{{ team.name }}</span>
    </span>
    <span v-else class="text-ink-muted">No team</span>
</template>
