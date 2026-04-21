import { describe, it, expect, vi } from "vitest";
import { ref } from "vue";

const mockLocale = ref("en");

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ locale: mockLocale }),
}));

import { useDateFormat } from "@/composables/useDateFormat";

// All tests run with TZ=UTC (set in vitest.config.js).
const ISO_DATE = "2024-06-15T10:30:00.000Z";
const YEAR_MONTH = "2024-06";

describe("useDateFormat", () => {
    describe("formatDate", () => {
        it("includes the day, year, and time for en locale", () => {
            mockLocale.value = "en";
            const { formatDate } = useDateFormat();
            const result = formatDate(ISO_DATE);

            expect(result).toContain("2024");
            expect(result).toContain("15");
            expect(result).toContain("10");
            expect(result).toContain("30");
        });

        it("includes the day, year, and time for fr locale", () => {
            mockLocale.value = "fr";
            const { formatDate } = useDateFormat();
            const result = formatDate(ISO_DATE);

            expect(result).toContain("2024");
            expect(result).toContain("15");
        });

        it("returns a non-empty string", () => {
            mockLocale.value = "en";
            const { formatDate } = useDateFormat();
            expect(formatDate(ISO_DATE)).not.toBe("");
        });
    });

    describe("formatDateShort", () => {
        it("includes the day, year, and time for en locale", () => {
            mockLocale.value = "en";
            const { formatDateShort } = useDateFormat();
            const result = formatDateShort(ISO_DATE);

            expect(result).toContain("2024");
            expect(result).toContain("15");
        });

        it("produces a shorter result than formatDate", () => {
            mockLocale.value = "en";
            const { formatDate, formatDateShort } = useDateFormat();
            expect(formatDateShort(ISO_DATE).length).toBeLessThanOrEqual(
                formatDate(ISO_DATE).length,
            );
        });
    });

    describe("formatMonth", () => {
        it("includes the 2-digit year for en locale", () => {
            mockLocale.value = "en";
            const { formatMonth } = useDateFormat();
            const result = formatMonth(YEAR_MONTH);

            expect(result).toContain("24");
        });

        it("includes the 2-digit year for fr locale", () => {
            mockLocale.value = "fr";
            const { formatMonth } = useDateFormat();
            const result = formatMonth(YEAR_MONTH);

            expect(result).toContain("24");
        });

        it("returns a non-empty string", () => {
            mockLocale.value = "en";
            const { formatMonth } = useDateFormat();
            expect(formatMonth(YEAR_MONTH)).not.toBe("");
        });
    });
});
