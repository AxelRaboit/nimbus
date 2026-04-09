<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1">
        <label v-if="label" class="text-sm font-medium text-primary">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            class="block w-full rounded-lg border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
            v-on:input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
