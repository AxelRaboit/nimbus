import { describe, it, expect } from "vitest";
import { useForm } from "@/composables/useForm";

describe("useForm", () => {
    describe("validate", () => {
        it("returns true when all checks pass", () => {
            const { validate, errors } = useForm();

            const ok = validate({
                email: () => null,
                name: () => null,
            });

            expect(ok).toBe(true);
            expect(errors.value).toEqual({});
        });

        it("returns false when at least one check fails", () => {
            const { validate } = useForm();

            const ok = validate({
                email: () => "Invalid email",
                name: () => null,
            });

            expect(ok).toBe(false);
        });

        it("stores error messages keyed by field name", () => {
            const { validate, errors } = useForm();

            validate({
                email: () => "Invalid email",
                name: () => null,
            });

            expect(errors.value).toEqual({ email: "Invalid email" });
        });

        it("collects errors from all failing fields at once", () => {
            const { validate, errors } = useForm();

            validate({
                email: () => "Required",
                name: () => "Required",
            });

            expect(errors.value).toEqual({
                email: "Required",
                name: "Required",
            });
        });

        it("replaces previous errors on each call", () => {
            const { validate, errors } = useForm();

            validate({ email: () => "Error" });
            validate({ email: () => null });

            expect(errors.value).toEqual({});
        });
    });

    describe("setErrors", () => {
        it("replaces errors with the given object", () => {
            const { setErrors, errors } = useForm();

            setErrors({ email: "Already taken" });

            expect(errors.value).toEqual({ email: "Already taken" });
        });

        it("overwrites any previously set errors", () => {
            const { setErrors, errors } = useForm();

            setErrors({ email: "Error A" });
            setErrors({ name: "Error B" });

            expect(errors.value).toEqual({ name: "Error B" });
        });
    });

    describe("clearErrors", () => {
        it("resets errors to an empty object", () => {
            const { setErrors, clearErrors, errors } = useForm();

            setErrors({ email: "Already taken" });
            clearErrors();

            expect(errors.value).toEqual({});
        });

        it("is a no-op when errors are already empty", () => {
            const { clearErrors, errors } = useForm();

            clearErrors();

            expect(errors.value).toEqual({});
        });
    });
});
