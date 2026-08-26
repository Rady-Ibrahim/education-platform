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
                card: '0 1px 3px rgba(11, 28, 44, 0.06), 0 4px 16px -6px rgba(11, 28, 44, 0.10)',
                'card-hover': '0 2px 6px rgba(11, 28, 44, 0.08), 0 12px 32px -8px rgba(11, 28, 44, 0.18)',
                glow: '0 0 0 3px rgba(20, 85, 102, 0.15)',
                'glow-accent': '0 0 0 3px rgba(226, 160, 8, 0.20)',
            },
            backgroundImage: {
                'app-wash':
                    'radial-gradient(ellipse 80% 50% at 100% -10%, rgba(31, 132, 153, 0.14), transparent 55%), radial-gradient(ellipse 60% 40% at 0% 100%, rgba(226, 160, 8, 0.08), transparent 50%), linear-gradient(180deg, #F4F7F8 0%, #EEF2F4 100%)',
                'hero-wash':
                    'radial-gradient(circle at 15% 20%, rgba(74, 160, 179, 0.35), transparent 42%), radial-gradient(circle at 85% 10%, rgba(226, 160, 8, 0.18), transparent 35%), linear-gradient(145deg, #0A2430 0%, #134554 48%, #0B1C2C 100%)',
                'sidebar-wash':
                    'linear-gradient(180deg, #ffffff 0%, #F8FBFC 100%)',
            },
            animation: {
                'fade-up': 'fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fadeIn 0.4s ease both',
                'pulse-soft': 'pulseSoft 2.5s ease-in-out infinite',
            },
            keyframes: {
                fadeUp: {
                    from: { opacity: '0', transform: 'translateY(12px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                fadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
            },
            transitionTimingFunction: {
                'spring': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },
            borderRadius: {
                '2.5xl': '1.125rem',
                '3.5xl': '1.625rem',
            },
        },
    },

    plugins: [forms],
};
