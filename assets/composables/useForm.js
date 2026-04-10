import { ref } from "vue";

/**
 * Lightweight form state composable.
 *
 * Usage:
 *   const { errors, validate, setErrors, clearErrors } = useForm();
 *
 *   // Each check is a thunk: () => string | null
 *   const ok = validate({
 *     email: () => compose(required(t('...')), email(t('...')))(emailRef.value),
 *     name:  () => required(t('...'))(nameRef.value),
 *   });
 */
export function useForm() {
    const errors = ref({});

    /**
     * @param {Record<string, () => string|null>} checks
     * @returns {boolean} true if no errors
     */
    function validate(checks) {
        const e = {};
        for (const [field, check] of Object.entries(checks)) {
            const error = check();
            if (error) e[field] = error;
        }
        errors.value = e;
        return Object.keys(e).length === 0;
    }

    function setErrors(e) {
        errors.value = e;
    }

    function clearErrors() {
        errors.value = {};
    }

    return { errors, validate, setErrors, clearErrors };
}
