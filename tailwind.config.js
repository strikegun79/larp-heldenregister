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
                // Waldritter-Designsystem v1
                heading: ['"Cinzel Decorative"', 'Georgia', 'serif'],
                body: ['"EB Garamond"', 'Georgia', 'serif'],
            },
            // Waldritter-Designsystem v1: Farben verweisen auf CSS-Variablen
            // (resources/css/waldritter-v1.css). Hinweis: Deckkraft-Modifier
            // wie bg-wr-forest/50 funktionieren mit Hex-Variablen nicht.
            // wr-ink und wr-gold haben nur innerhalb von .wr-v1 die neuen Werte.
            colors: {
                wr: {
                    forest: 'var(--wr-forest)',
                    'forest-hover': 'var(--wr-forest-hover)',
                    moss: 'var(--wr-moss)',
                    parchment: 'var(--wr-parchment)',
                    'parchment-deep': 'var(--wr-parchment-deep)',
                    paper: 'var(--wr-paper)',
                    ink: 'var(--wr-ink)',
                    'ink-muted': 'var(--wr-ink-muted)',
                    gold: 'var(--wr-gold)',
                    leather: 'var(--wr-leather)',
                    danger: 'var(--wr-danger)',
                    border: 'var(--wr-border)',
                    focus: 'var(--wr-focus)',
                },
            },
            fontSize: {
                sm: ['1rem', '1.4rem']
            }
        },
    },

    plugins: [forms],
};
