import { ref, watch } from "vue";

const STORAGE_KEY = "nimbus-theme";

function getInitial() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === "dark" || stored === "light") return stored;
    return window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
}

function apply(t) {
    const el = document.documentElement;
    el.classList.add("theme-transitioning");
    el.classList.toggle("dark", t === "dark");
    window.setTimeout(() => el.classList.remove("theme-transitioning"), 300);
}

// Singleton — shared across all composable calls
const theme = ref(getInitial());
apply(theme.value);

export function useTheme() {
    watch(theme, (t) => {
        apply(t);
        localStorage.setItem(STORAGE_KEY, t);
    });

    function toggle() {
        theme.value = theme.value === "dark" ? "light" : "dark";
    }

    return { theme, toggle };
}
