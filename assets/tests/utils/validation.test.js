import { describe, it, expect } from "vitest";
import {
    normalizeServerErrors,
    isValidEmail,
    formatFileSize,
} from "@/utils/validation";

describe("normalizeServerErrors", () => {
    it("strips brackets from a single key", () => {
        expect(normalizeServerErrors({ "[email]": "Invalid" })).toEqual({
            email: "Invalid",
        });
    });

    it("strips brackets from multiple keys", () => {
        expect(
            normalizeServerErrors({
                "[email]": "Invalid",
                "[name]": "Required",
            }),
        ).toEqual({
            email: "Invalid",
            name: "Required",
        });
    });

    it("returns empty object for empty input", () => {
        expect(normalizeServerErrors({})).toEqual({});
    });

    it("leaves keys without brackets unchanged", () => {
        expect(normalizeServerErrors({ name: "Required" })).toEqual({
            name: "Required",
        });
    });

    it("handles Symfony-style keys like [senderEmail]", () => {
        expect(normalizeServerErrors({ "[senderEmail]": "Bad email" })).toEqual(
            {
                senderEmail: "Bad email",
            },
        );
    });
});

describe("isValidEmail", () => {
    it("accepts a standard email", () => {
        expect(isValidEmail("user@example.com")).toBe(true);
    });

    it("accepts email with plus alias", () => {
        expect(isValidEmail("user+tag@example.com")).toBe(true);
    });

    it("accepts email with subdomain", () => {
        expect(isValidEmail("user@mail.example.com")).toBe(true);
    });

    it("trims surrounding whitespace before validating", () => {
        expect(isValidEmail("  user@example.com  ")).toBe(true);
    });

    it("rejects email without @", () => {
        expect(isValidEmail("userexample.com")).toBe(false);
    });

    it("rejects email without domain extension", () => {
        expect(isValidEmail("user@")).toBe(false);
    });

    it("rejects empty string", () => {
        expect(isValidEmail("")).toBe(false);
    });

    it("rejects whitespace-only string", () => {
        expect(isValidEmail("   ")).toBe(false);
    });
});

describe("formatFileSize", () => {
    it("formats MB under 1000 with en locale", () => {
        expect(formatFileSize(500, "en")).toBe("500 MB");
    });

    it("formats exactly 1000 MB as 1 GB", () => {
        expect(formatFileSize(1000, "en")).toBe("1 GB");
    });

    it("formats GB with one decimal when not round", () => {
        expect(formatFileSize(1500, "en")).toBe("1.5 GB");
    });

    it("formats GB without trailing .0", () => {
        expect(formatFileSize(2000, "en")).toBe("2 GB");
    });

    it("uses Mo/Go for fr locale", () => {
        expect(formatFileSize(500, "fr")).toBe("500 Mo");
        expect(formatFileSize(2000, "fr")).toBe("2 Go");
    });

    it("falls back to en units for unknown locale", () => {
        expect(formatFileSize(100, "xx")).toBe("100 MB");
    });

    it("handles locale with region code like fr-FR", () => {
        expect(formatFileSize(500, "fr-FR")).toBe("500 Mo");
    });
});
