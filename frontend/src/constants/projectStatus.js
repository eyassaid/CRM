// Mirrors App\Enums\ProjectStatus on the backend.
export const PROJECT_STATUSES = [
    { value: 'not_started', label: 'Not started', badge: 'bg-porcelain text-ink-muted ring-1 ring-inset ring-rule' },
    { value: 'working_on_it', label: 'Working on it', badge: 'bg-enamel-950 text-white' },
    { value: 'cancelled', label: 'Cancelled', badge: 'bg-white text-ink-muted line-through ring-1 ring-inset ring-rule' },
    { value: 'done', label: 'Done', badge: 'bg-enamel-200 text-enamel-950' },
];

export function projectStatus(value) {
    return PROJECT_STATUSES.find((status) => status.value === value) ?? PROJECT_STATUSES[0];
}
