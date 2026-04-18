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
        const validationErrors = {};
        for (const [field, check] of Object.entries(checks)) {
            const error = check();
            if (error) validationErrors[field] = error;
        }
        errors.value = validationErrors;
        return Object.keys(validationErrors).length === 0;
    }

    function setErrors(newErrors) {
        errors.value = newErrors;
    }

    function clearErrors() {
        errors.value = {};
    }

    return { errors, validate, setErrors, clearErrors };
}
