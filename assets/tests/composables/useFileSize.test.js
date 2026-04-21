import { describe, it, expect, vi } from "vitest";
import { ref } from "vue";

const mockLocale = ref("en");

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ locale: mockLocale }),
}));

import { useFileSize } from "@/composables/useFileSize";

describe("useFileSize — formatSize", () => {
    describe("en locale", () => {
        it("formats bytes", () => {
            mockLocale.value = "en";
            const { formatSize } = useFileSize();
            expect(formatSize(512)).toBe("512 B");
        });

        it("formats kilobytes", () => {
            mockLocale.value = "en";
            const { formatSize } = useFileSize();
            expect(formatSize(1024)).toBe("1.0 KB");
        });

        it("formats megabytes", () => {
            mockLocale.value = "en";
            const { formatSize } = useFileSize();
            expect(formatSize(1024 * 1024)).toBe("1.0 MB");
        });

        it("formats gigabytes", () => {
            mockLocale.value = "en";
            const { formatSize } = useFileSize();
            expect(formatSize(1024 * 1024 * 1024)).toBe("1.00 GB");
        });

        it("formats values just below each boundary", () => {
            mockLocale.value = "en";
            const { formatSize } = useFileSize();
            expect(formatSize(1023)).toBe("1023 B");
            expect(formatSize(1024 * 1024 - 1)).toContain("KB");
        });
    });

    describe("fr locale", () => {
        it("uses French units (o, Ko, Mo, Go)", () => {
            mockLocale.value = "fr";
            const { formatSize } = useFileSize();
            expect(formatSize(512)).toBe("512 o");
            expect(formatSize(1024)).toBe("1.0 Ko");
            expect(formatSize(1024 * 1024)).toBe("1.0 Mo");
            expect(formatSize(1024 * 1024 * 1024)).toBe("1.00 Go");
        });
    });

    describe("unknown locale", () => {
        it("falls back to en units", () => {
            mockLocale.value = "xx";
            const { formatSize } = useFileSize();
            expect(formatSize(512)).toBe("512 B");
            expect(formatSize(1024 * 1024)).toBe("1.0 MB");
        });
    });
});
