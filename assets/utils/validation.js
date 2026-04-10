/**
 * Email validation regex
 * Simple pattern: local@domain.extension
 */
export const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Validate email address
 */
export function isValidEmail(email) {
    return EMAIL_REGEX.test(email.trim());
}

/**
 * Units per locale. Locales not listed fall back to SI (GB/MB).
 */
const SIZE_UNITS = {
    fr: { mb: "Mo", gb: "Go" },
    es: { mb: "MB", gb: "GB" },
    de: { mb: "MB", gb: "GB" },
    en: { mb: "MB", gb: "GB" },
};

function getUnits(locale) {
    const lang = (locale ?? "en").split("-")[0].toLowerCase();
    return SIZE_UNITS[lang] ?? SIZE_UNITS.en;
}

/**
 * Format file size from MB to readable format.
 * @param {number} mb - Size in megabytes
 * @param {string} [locale] - Locale string (e.g. "fr", "en")
 * @returns {string} Formatted size (e.g. "500 Mo" or "10 Go")
 */
export function formatFileSize(mb, locale) {
    const num = Number(mb);
    const units = getUnits(locale);
    if (num >= 1000) {
        return `${(num / 1000).toFixed(1).replace(/\.0$/, "")} ${units.gb}`;
    }
    return `${Math.round(num)} ${units.mb}`;
}
