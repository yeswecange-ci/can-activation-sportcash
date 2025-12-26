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
                'can-gold': {
                    DEFAULT: '#D4AF37',
                    50: '#FAF7ED',
                    100: '#F5EFDB',
                    200: '#EBDFB7',
                    300: '#E1CF93',
                    400: '#D7BF6F',
                    500: '#D4AF37',
                    600: '#B8962B',
                    700: '#8A711F',
                    800: '#5C4C14',
                    900: '#2E260A',
                },
                'can-green': {
                    DEFAULT: '#006B3F',
                    50: '#E6F5ED',
                    100: '#CCEBDB',
                    200: '#99D7B7',
                    300: '#66C393',
                    400: '#33AF6F',
                    500: '#006B3F',
                    600: '#005632',
                    700: '#004026',
                    800: '#002B19',
                    900: '#00150D',
                },
                'can-red': {
                    DEFAULT: '#DC2626',
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    200: '#FECACA',
                    300: '#FCA5A5',
                    400: '#F87171',
                    500: '#DC2626',
                    600: '#B91C1C',
                    700: '#991B1B',
                    800: '#7F1D1D',
                    900: '#5F1515',
                },
                'can-black': '#1A1A1A',
            },
        },
    },

    plugins: [forms],
};
