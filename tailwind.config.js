import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: ["./assets/**/*.{js,vue}", "./templates/**/*.twig"],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", "ui-sans-serif", "system-ui", "sans-serif"],
            },
            colors: {
                bg: "rgb(var(--color-bg) / <alpha-value>)",
                surface: {
                    DEFAULT: "rgb(var(--color-surface) / <alpha-value>)",
                    2: "rgb(var(--color-surface-2) / <alpha-value>)",
                    3: "rgb(var(--color-surface-3) / <alpha-value>)",
                },
            },
            textColor: {
                primary: "rgb(var(--color-text-primary)   / <alpha-value>)",
                secondary: "rgb(var(--color-text-secondary) / <alpha-value>)",
                muted: "rgb(var(--color-text-muted)     / <alpha-value>)",
                subtle: "rgb(var(--color-text-subtle)    / <alpha-value>)",
            },
            borderColor: {
                base: "rgb(var(--color-border)        / <alpha-value>)",
                strong: "rgb(var(--color-border-strong) / <alpha-value>)",
                subtle: "rgb(var(--color-border-subtle) / <alpha-value>)",
            },
        },
    },
    plugins: [forms],
};
