const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                ubuntu: ['Ubuntu', 'sans-serif'],
                primary: ['var(--font-primary)']
            },
            colors: {
                first: 'var(--color-primary)',
                second: 'var(--color-secondary)',
                background: 'var(--color-background)',
                light: 'var(--color-light)',
                dark: 'var(--color-dark)',
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
