import { useI18n } from "vue-i18n";

export function useDateFormat() {
    const { locale } = useI18n();

    function formatDate(isoString) {
        return new Intl.DateTimeFormat(locale.value, {
            day: "numeric",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }).format(new Date(isoString));
    }

    function formatDateShort(isoString) {
        return new Intl.DateTimeFormat(locale.value, {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }).format(new Date(isoString));
    }

    function formatMonth(yyyyMm) {
        const [y, m] = yyyyMm.split("-");
        return new Intl.DateTimeFormat(locale.value, {
            month: "short",
            year: "2-digit",
        }).format(new Date(+y, +m - 1));
    }

    return { formatDate, formatDateShort, formatMonth };
}
