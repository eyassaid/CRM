// Mirrors App\Enums\UserRole on the backend.
export const USER_ROLES = [
    { value: 'admin', label: 'Admin' },
    { value: 'team_leader', label: 'Team leader' },
    { value: 'employee', label: 'Employee' },
];

// Roles an admin can give when creating or editing a user (see UserRequest).
export const ASSIGNABLE_ROLES = USER_ROLES.filter((role) => role.value !== 'admin');

export function userRoleLabel(value) {
    return USER_ROLES.find((role) => role.value === value)?.label ?? value;
}
