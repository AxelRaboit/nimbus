import { computed } from "vue";
import { useI18n } from "vue-i18n";

export function useDateFormat() {
    const { locale } = useI18n();

    function formatDate(isoString) {
        return computed(() =>
            new Intl.DateTimeFormat(locale.value, {
                day: "numeric",
                month: "long",
                year: "numeric",
            }).format(new Date(isoString)),
        );
    }

    return { formatDate };
}
