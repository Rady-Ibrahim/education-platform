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
                    50: '#F2F8F9',
                    100: '#DCEEF1',
                    200: '#B8DCE3',
                    300: '#84C2CE',
                    400: '#4AA0B3',
                    500: '#1F8499',
                    600: '#176A7C',
                    700: '#145566',
                    800: '#134554',
                    900: '#123A47',
                    950: '#0A2430',
                },
                ink: {
                    DEFAULT: '#0B1C2C',
                    soft: '#243447',
                    muted: '#5B6B7C',
                },
                sand: {
                    50: '#F7F5F1',
                    100: '#EFEBE3',
                },
                accent: {
                    DEFAULT: '#E2A008',
                    soft: '#F6E7B8',
                    dark: '#B47D06',
                },
            },
            fontFamily: {
                sans: ['Cairo', 'IBM Plex Sans Arabic', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 10px 40px -20px rgba(11, 28, 44, 0.18)',
                panel: '0 1px 2px rgba(11, 28, 44, 0.04), 0 8px 24px -12px rgba(11, 28, 44, 0.12)',
            },
            backgroundImage: {
                'app-wash':
                    'radial-gradient(ellipse 80% 50% at 100% -10%, rgba(31, 132, 153, 0.14), transparent 55%), radial-gradient(ellipse 60% 40% at 0% 100%, rgba(226, 160, 8, 0.08), transparent 50%), linear-gradient(180deg, #F4F7F8 0%, #EEF2F4 100%)',
                'hero-wash':
                    'radial-gradient(circle at 15% 20%, rgba(74, 160, 179, 0.35), transparent 42%), radial-gradient(circle at 85% 10%, rgba(226, 160, 8, 0.18), transparent 35%), linear-gradient(145deg, #0A2430 0%, #134554 48%, #0B1C2C 100%)',
            },
        },
    },

    plugins: [forms],
};
