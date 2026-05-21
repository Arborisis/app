import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        './resources/js/**/*.ts',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', 'system-ui', ...defaultTheme.fontFamily.sans],
                display: ['Cormorant', 'Georgia', 'serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            zIndex: {
                'dropdown': '10',
                'sticky': '20',
                'fixed-nav': '1100',
                'agent': '1150',
                'drawer': '40',
                'modal': '50',
                'popover': '60',
                'toast': '70',
                'tooltip': '80',
                'banner': '90',
                'map': '800',
                'map-max': '800',
            },
            colors: {
                arbor: {
                    night:    '#0B1220',
                    deep:     '#111827',
                    deeper:   '#0d1117',
                    forest:   '#07110D',
                    ink:      '#0A0D0B',
                    canopy:   '#102018',
                    moss:     '#4A6741',
                    'moss-light': '#5a7d4f',
                    emerald:  '#34D399',
                    'emerald-dark': '#10B981',
                    sage:     '#8FA68E',
                    cream:    '#F3F0E7',
                    'cream-dark': '#E8E4D9',
                    mineral:  '#D8D0BD',
                    mist:     '#F2EFE7',
                    bark:     '#6B4F3A',
                    clay:     '#A66F4E',
                    lichen:   '#D7B46A',
                    firefly:  '#8FE6C1',
                    'cyan-trace': '#78D6D6',
                    glass:    'rgba(255,255,255,0.05)',
                    'glass-border': 'rgba(255,255,255,0.1)',
                    amber:    '#D4A574',
                    'amber-glow': '#C9842B',
                    copper:   '#B87333',
                    charcoal: '#1a1f2e',
                    fog:      '#2a3142',
                },
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'hero-glow': 'radial-gradient(ellipse 80% 50% at 50% -20%, rgba(74, 103, 65, 0.3), transparent)',
                'studio-gradient': 'linear-gradient(180deg, #0B1220 0%, #111827 50%, #0d1117 100%)',
                'atlas-depth': 'radial-gradient(circle at 20% 10%, rgba(215, 180, 106, 0.12), transparent 28%), radial-gradient(circle at 78% 0%, rgba(120, 214, 214, 0.08), transparent 26%), linear-gradient(180deg, #07110D 0%, #0A0D0B 52%, #102018 100%)',
                'mist-paper': 'linear-gradient(135deg, rgba(242, 239, 231, 0.08), rgba(216, 208, 189, 0.025))',
            },
            boxShadow: {
                'organic': '0 24px 80px rgba(0, 0, 0, 0.35)',
                'lichen': '0 16px 48px rgba(215, 180, 106, 0.14)',
                'firefly': '0 0 32px rgba(143, 230, 193, 0.16)',
            },
            animation: {
                'fade-in': 'fadeIn 0.8s ease-out forwards',
                'slide-up': 'slideUp 0.6s ease-out forwards',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'spin-slow': 'spin 12s linear infinite',
                'wave': 'wave 1.2s ease-in-out infinite',
                'glow-pulse': 'glowPulse 3s ease-in-out infinite',
                'reel-spin': 'reelSpin 8s linear infinite',
                'breath': 'breath 4.8s ease-in-out infinite',
                'sound-ripple': 'soundRipple 1.8s ease-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                wave: {
                    '0%, 100%': { transform: 'scaleY(0.3)' },
                    '50%': { transform: 'scaleY(1)' },
                },
                glowPulse: {
                    '0%, 100%': { opacity: '0.4', transform: 'scale(1)' },
                    '50%': { opacity: '0.8', transform: 'scale(1.05)' },
                },
                reelSpin: {
                    '0%': { transform: 'rotate(0deg)' },
                    '100%': { transform: 'rotate(360deg)' },
                },
                breath: {
                    '0%, 100%': { opacity: '0.68', transform: 'scale(1)' },
                    '50%': { opacity: '1', transform: 'scale(1.025)' },
                },
                soundRipple: {
                    '0%': { opacity: '0.35', transform: 'scale(0.82)' },
                    '100%': { opacity: '0', transform: 'scale(1.45)' },
                },
            },
        },
    },

    plugins: [forms],
};
