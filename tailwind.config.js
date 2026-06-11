import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    safelist: [
        // สีการ์ด/ไอคอนที่ประกอบชื่อคลาสตอน runtime
        { pattern: /(bg|text)-(brand|violet|sky|emerald|teal|amber|rose)-(100|400|500|600)/, variants: ['dark'] },
        { pattern: /(bg|text)-(brand|violet|sky|emerald|teal|amber|rose)-500\/15/, variants: ['dark'] },
    ],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './node_modules/flowbite/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans Thai"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                    400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                    800: '#3730a3', 900: '#312e81', 950: '#1e1b4b',
                },
            },
            boxShadow: {
                soft: '0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06)',
            },
        },
    },
    plugins: [forms, flowbite],
};
