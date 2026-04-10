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
            fontSize: {
                "2xs": ["0.625rem", { lineHeight: "0.875rem" }],
            },
            colors: {
                bg: "rgb(var(--color-bg) / <alpha-value>)",
                surface: {
                    DEFAULT: "rgb(var(--color-surface) / <alpha-value>)",
                    2: "rgb(var(--color-surface-2) / <alpha-value>)",
                    3: "rgb(var(--color-surface-3) / <alpha-value>)",
                },
                badge: {
                    danger: {
                        bg: "rgb(var(--color-badge-danger-bg)  / <alpha-value>)",
                        text: "rgb(var(--color-badge-danger-text)  / <alpha-value>)",
                    },
                    warning: {
                        bg: "rgb(var(--color-badge-warning-bg) / <alpha-value>)",
                        text: "rgb(var(--color-badge-warning-text) / <alpha-value>)",
                    },
                    primary: {
                        bg: "rgb(var(--color-badge-primary-bg) / <alpha-value>)",
                        text: "rgb(var(--color-badge-primary-text) / <alpha-value>)",
                    },
                    success: {
                        bg: "rgb(var(--color-badge-success-bg) / <alpha-value>)",
                        text: "rgb(var(--color-badge-success-text) / <alpha-value>)",
                    },
                    info: {
                        bg: "rgb(var(--color-badge-info-bg)    / <alpha-value>)",
                        text: "rgb(var(--color-badge-info-text)    / <alpha-value>)",
                    },
                },
            },
            textColor: {
                primary: "rgb(var(--color-text-primary)    / <alpha-value>)",
                secondary: "rgb(var(--color-text-secondary)  / <alpha-value>)",
                muted: "rgb(var(--color-text-muted)      / <alpha-value>)",
                subtle: "rgb(var(--color-text-subtle)     / <alpha-value>)",
                link: "rgb(var(--color-text-link)       / <alpha-value>)",
                "link-hover":
                    "rgb(var(--color-text-link-hover) / <alpha-value>)",
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
