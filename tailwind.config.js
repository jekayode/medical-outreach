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
            colors: {
                brand: {
                    primary: '#F1592A',
                    hover: '#D94818',
                    active: '#B83712',
                    secondary: '#9DC83B',
                    'secondary-hover': '#8DB22E',
                    text: '#606060',
                    accent: '#FFAC93',
                    'accent-warm': '#FFA589',
                    ink: '#1E1E1E',
                    surface: '#FEF7F4',
                    'surface-border': '#F5D0C2',
                    'surface-muted': '#FFEDE8',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
