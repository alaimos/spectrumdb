import forms  from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'selector',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/livewire/flux-pro/stubs/**/*.blade.php',
        './vendor/livewire/flux/stubs/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
            // colors: {
            //     zinc: colors.gray,
            //     accent: {
            //         DEFAULT: 'var(--color-accent)',
            //         content: 'var(--color-accent-content)',
            //         foreground: 'var(--color-accent-foreground)',
            //     },
            // },
        },
    },

    plugins: [forms],
};
