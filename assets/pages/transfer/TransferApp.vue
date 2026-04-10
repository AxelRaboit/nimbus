<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { DownloadCloud, FileText, Download, ArrowDownToLine } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import { useFileSize } from "@/composables/useFileSize.js";
import { useDateFormat } from "@/composables/useDateFormat.js";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDate } = useDateFormat();

const props = defineProps({
    token: { type: String, required: true },
    reference: { type: String, required: true },
    expiresAt: { type: String, required: true },
    senderName: { type: String, default: "" },
    files: { type: String, default: "[]" },
});

const parsedFiles = computed(() => JSON.parse(props.files));

const expiresDate = computed(() => formatDate(props.expiresAt).value);

const totalSize = computed(() =>
    parsedFiles.value.reduce((acc, f) => acc + f.size, 0)
);

const downloadUrl = computed(() => `/t/${props.token}/download`);

function fileDownloadUrl(filename) {
    return `/t/${props.token}/download/${filename}`;
}
</script>

<template>
    <div class="flex justify-center">
        <div class="w-full max-w-xl flex flex-col gap-6">
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
                    <DownloadCloud class="w-7 h-7 text-indigo-600" :stroke-width="2" />
                </div>
                <h1 class="text-2xl font-bold text-primary">{{ t('transfer.show.title') }}</h1>
                <p class="text-sm text-muted mt-1">
                    <span v-if="senderName">{{ t('transfer.show.sent_by') }} <span class="font-medium text-secondary">{{ senderName }}</span> · </span>
                    {{ t('transfer.show.expires') }} {{ expiresDate }}
                </p>
            </div>

            <div class="rounded-lg border border-base bg-surface shadow-lg shadow-indigo-500/10 overflow-hidden">
                <div class="px-5 py-3 bg-surface-2 border-b border-base flex items-center justify-between">
                    <span class="text-xs text-secondary uppercase tracking-wide">{{ t('transfer.show.reference') }}</span>
                    <span class="text-sm font-bold text-primary tracking-widest">{{ reference }}</span>
                </div>

                <ul class="divide-y divide-base">
                    <li v-for="(file, index) in parsedFiles" :key="index" class="flex items-center gap-3 px-5 py-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                            <FileText class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-primary truncate">{{ file.name }}</p>
                            <p class="text-xs text-muted">{{ formatSize(file.size) }}</p>
                        </div>
                        <a :href="fileDownloadUrl(file.filename)" class="shrink-0 p-1.5 text-muted hover:text-primary transition-colors" :title="t('transfer.show.download')">
                            <ArrowDownToLine class="w-4 h-4" :stroke-width="2" />
                        </a>
                    </li>
                </ul>

                <div class="px-5 py-4 border-t border-base flex items-center justify-between gap-4 bg-surface-2">
                    <div>
                        <p class="text-xs text-secondary uppercase tracking-wide">
                            {{ t('transfer.show.files_count', { n: parsedFiles.length }, parsedFiles.length) }}
                        </p>
                        <p class="text-sm font-bold text-primary">{{ formatSize(totalSize) }}</p>
                    </div>
                    <a :href="downloadUrl">
                        <AppButton size="md">
                            <Download class="w-4 h-4" :stroke-width="2" />
                            {{ parsedFiles.length > 1 ? t('transfer.show.download_all') : t('transfer.show.download') }}
                        </AppButton>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
