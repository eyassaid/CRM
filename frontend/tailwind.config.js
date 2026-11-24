import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: ['./index.html', './src/**/*.{vue,js}'],

    theme: {
        extend: {
            colors: {
                // The side rail and primary actions.
                enamel: {
                    950: '#0B1230',
                    900: '#121B40',
                    800: '#1C2752',
                    700: '#2A376A',
                    300: '#9AA6C7',
                    200: '#C3CBE0',
                },
                // The work area: ground, panels' rules, text.
                porcelain: '#F5F6F8',
                rule: '#D9DEE7',
                ink: { DEFAULT: '#141B34', muted: '#55607A' },
                // Team line inks (see src/constants/teamLines.js) and state colours.
                scarlet: { DEFAULT: '#D21F2B', dark: '#A8141F', soft: '#FDECEC' },
                cobalt: { DEFAULT: '#1E5BFF', dark: '#1747C8', soft: '#EAF0FF' },
                amber: { DEFAULT: '#F2B100', dark: '#8A5A00', soft: '#FFF5D6' },
                green: { DEFAULT: '#1F9D55', dark: '#14713C', soft: '#E6F5EC' },
                plum: { DEFAULT: '#7B3FA0' },
                teal: { DEFAULT: '#0E8C8C' },
            },
            fontFamily: {
                sans: ['Barlow', 'Noto Kufi Arabic', ...defaultTheme.fontFamily.sans],
                // Signage voice: labels, table heads, headings.
                sign: ['Barlow Semi Condensed', 'Noto Kufi Arabic', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
