// Every team rides one line colour, fixed by its id, wherever its clients and projects appear.
export const LINE_INKS = ['#D21F2B', '#1E5BFF', '#F2B100', '#1F9D55', '#7B3FA0', '#0E8C8C', '#E8590C', '#8D5A2B'];

export function teamInk(teamId) {
    if (!teamId) return '#9AA6C7';

    return LINE_INKS[(teamId - 1) % LINE_INKS.length];
}
