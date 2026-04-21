<script setup>
import { AlertTriangle } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";

const { t } = useI18n();

defineProps({
    show:           { type: Boolean, default: false },
    message:        { type: String,  default: "" },
    confirmLabel:   { type: String,  default: null },
    confirmVariant: { type: String,  default: "danger" },
});

defineEmits(["confirm", "cancel"]);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-black/60" v-on:click="$emit('cancel')" />

            <div class="relative z-10 w-full max-w-md rounded-xl bg-surface-2 border border-line p-4 sm:p-6 shadow-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-badge-danger-bg">
                        <AlertTriangle class="h-5 w-5 text-badge-danger-text" :stroke-width="1.5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-primary">{{ t("common.confirm") }}</h3>
                        <p class="text-sm text-secondary mt-1">{{ message }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <AppButton variant="secondary" v-on:click="$emit('cancel')">
                        {{ t("common.cancel") }}
                    </AppButton>
                    <AppButton :variant="confirmVariant" v-on:click="$emit('confirm')">
                        {{ confirmLabel ?? t("common.delete") }}
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
