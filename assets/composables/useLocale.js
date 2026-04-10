import { useI18n } from "vue-i18n";

export const SUPPORTED_LOCALES = [
    { code: "fr", label: "Français" },
    { code: "en", label: "English" },
    { code: "es", label: "Español" },
    { code: "de", label: "Deutsch" },
];

export function useLocale() {
    const { locale } = useI18n();

    async function setLocale(code) {
        await fetch("/locale", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ locale: code }),
        });
        locale.value = code;
        localStorage.setItem("nimbus-locale", code);
    }

    return { locale, setLocale, SUPPORTED_LOCALES };
}
