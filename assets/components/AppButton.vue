<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const base = 'inline-flex items-center justify-center gap-2 rounded-lg transition duration-150 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed font-semibold';

const variants = {
    primary: 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
    secondary: 'bg-surface-3 hover:bg-surface-2 text-primary border border-base focus:ring-2 focus:ring-offset-2 focus:ring-base',
    danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
    ghost: 'bg-transparent hover:bg-surface-2 text-secondary hover:text-primary',
};

const sizes = {
    sm: 'py-1.5 px-3 text-xs',
    md: 'py-2 px-4 text-sm',
    lg: 'py-3 px-6 text-base',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[base, variants[variant] ?? variants.primary, sizes[size] ?? sizes.md]"
    >
        <svg
            v-if="loading"
            class="animate-spin h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
        <slot />
    </button>
</template>
