import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'misdinar': {
                    'dark': '#071f5e',
                    'primary': '#0B2C8A',
                    'light': '#1a3fa0',
                    '50': '#f0f4ff',
                    '100': '#dce7ff',
                    '200': '#c1d5ff',
                    '300': '#96bbff',
                    '400': '#6497ff',
                    '500': '#3d75ff',
                    '600': '#1a3fa0',
                    '700': '#0B2C8A',
                    '800': '#071f5e',
                    '900': '#051742',
                },
            },
        },
    },

    plugins: [forms],
};
