import { EMAIL_REGEX } from "@/utils/validation.js";

/**
 * Returns an error message if the value is empty, null or undefined.
 * @param {string} msg
 * @returns {(value: any) => string|null}
 */
export const required = (msg) => (value) => {
    if (value === null || value === undefined) return msg;
    if (typeof value === "string" && !value.trim()) return msg;
    if (Array.isArray(value) && value.length === 0) return msg;
    return null;
};

/**
 * Returns an error message if the value is present but not a valid email.
 * @param {string} msg
 * @returns {(value: string) => string|null}
 */
export const email = (msg) => (value) => {
    if (!value || !String(value).trim()) return null;
    return EMAIL_REGEX.test(String(value).trim()) ? null : msg;
};

/**
 * Runs validators in order and returns the first error encountered.
 * @param {...Function} validators
 * @returns {(value: any) => string|null}
 */
export const compose =
    (...validators) =>
    (value) => {
        for (const validator of validators) {
            const error = validator(value);
            if (error) return error;
        }
        return null;
    };
