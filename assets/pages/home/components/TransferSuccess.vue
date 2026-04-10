<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Check, Copy } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import AppQrCode from "@/components/AppQrCode.vue";

const { t } = useI18n();

defineEmits(["reset"]);

const props = defineProps({
    reference: { type: String, required: true },
    manageUrl: { type: String, required: true },
    isGuest: { type: Boolean, default: false },
    isPublic: { type: Boolean, default: false },
    transferToken: { type: String, default: "" },
});

const downloadUrl = computed(() =>
    props.transferToken ? `${window.location.origin}/t/${props.transferToken}` : ""
);

const copied = ref(false);
const copiedManage = ref(false);

async function copyLink(text, flag) {
    try {
        await navigator.clipboard.writeText(text);
        flag.value = true;
        setTimeout(() => (flag.value = false), 2000);
    } catch {}
}
</script>

<template>
    <div class="flex flex-col items-center gap-6 text-center py-4">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
            <Check class="w-8 h-8 text-green-600" :stroke-width="2" />
        </div>

        <div>
            <h2 class="text-lg font-semibold text-primary">{{ t('transfer.success.title') }}</h2>
            <p class="text-sm text-muted mt-1">{{ t('transfer.success.subtitle') }}</p>
        </div>

        <div class="bg-surface-2 rounded-lg px-6 py-3">
            <p class="text-xs text-muted mb-1">{{ t('transfer.success.reference_label') }}</p>
            <p class="text-2xl font-bold text-primary tracking-widest">{{ reference }}</p>
        </div>

        <div v-if="isGuest" class="w-full rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30 px-4 py-3 text-sm text-amber-700 dark:text-amber-400">
            {{ t('transfer.success.guest_warning') }}
        </div>

        <div v-if="downloadUrl" class="w-full flex flex-col gap-2">
            <p class="text-xs text-muted">{{ isPublic ? t('transfer.success.public_link_hint') : t('transfer.success.download_link_hint') }}</p>
            <div class="flex items-center gap-2">
                <input
                    :value="downloadUrl"
                    readonly
                    class="block w-full rounded border border-base bg-surface px-3 py-2 text-sm text-primary focus:outline-none truncate min-w-0"
                    v-on:click="$event.target.select()"
                >
                <AppButton variant="secondary" size="sm" class="shrink-0" v-on:click="copyLink(downloadUrl, copied)">
                    <Check v-if="copied" class="w-4 h-4 text-green-500" :stroke-width="2" />
                    <Copy v-else class="w-4 h-4" :stroke-width="2" />
                    <span class="hidden sm:inline">{{ copied ? t('transfer.success.copied') : t('transfer.success.copy') }}</span>
                </AppButton>
            </div>
        </div>

        <AppQrCode v-if="downloadUrl" :url="downloadUrl" :size="140" />

        <div class="w-full flex flex-col gap-2">
            <p class="text-xs text-muted">{{ t('transfer.success.manage_hint') }}</p>
            <div class="flex items-center gap-2">
                <input
                    :value="manageUrl"
                    readonly
                    class="block w-full rounded border border-base bg-surface px-3 py-2 text-sm text-primary focus:outline-none truncate min-w-0"
                    v-on:click="$event.target.select()"
                >
                <AppButton variant="secondary" size="sm" class="shrink-0" v-on:click="copyLink(manageUrl, copiedManage)">
                    <Check v-if="copiedManage" class="w-4 h-4 text-green-500" :stroke-width="2" />
                    <Copy v-else class="w-4 h-4" :stroke-width="2" />
                    <span class="hidden sm:inline">{{ copiedManage ? t('transfer.success.copied') : t('transfer.success.copy') }}</span>
                </AppButton>
            </div>
        </div>

        <AppButton variant="ghost" size="sm" v-on:click="$emit('reset')">
            {{ t('transfer.success.new_transfer') }}
        </AppButton>
    </div>
</template>
