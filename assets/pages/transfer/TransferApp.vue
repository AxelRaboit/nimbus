<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { DownloadCloud, FileText, Download, ArrowDownToLine, Eye, X } from "lucide-vue-next";
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
const totalSize = computed(() => parsedFiles.value.reduce((acc, f) => acc + f.size, 0));
const downloadUrl = computed(() => `/t/${props.token}/download`);

function fileDownloadUrl(filename) {
    return `/t/${props.token}/download/${filename}`;
}

function filePreviewUrl(filename) {
    return `/t/${props.token}/preview/${filename}`;
}

const IMAGE_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp", "image/svg+xml", "image/avif"];
const PDF_TYPE = "application/pdf";

function isImage(mimeType) {
    return IMAGE_TYPES.includes(mimeType);
}

function isPdf(mimeType) {
    return mimeType === PDF_TYPE;
}

function isPreviewable(mimeType) {
    return isImage(mimeType) || isPdf(mimeType);
}

// Preview modal
const previewFile = ref(null);

function openPreview(file) {
    previewFile.value = file;
}

function closePreview() {
    previewFile.value = null;
}

if (typeof window !== "undefined") {
    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closePreview();
    });
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
                    <li v-for="(file, index) in parsedFiles" :key="index">
                        <!-- Image preview thumbnail -->
                        <div
                            v-if="isImage(file.mimeType)"
                            class="cursor-pointer overflow-hidden bg-surface-2 border-b border-base"
                            v-on:click="openPreview(file)"
                        >
                            <img
                                :src="filePreviewUrl(file.filename)"
                                :alt="file.name"
                                class="w-full max-h-48 object-cover hover:opacity-90 transition-opacity"
                                loading="lazy"
                            >
                        </div>

                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                <FileText class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-primary truncate">{{ file.name }}</p>
                                <p class="text-xs text-muted">{{ formatSize(file.size) }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    v-if="isPreviewable(file.mimeType)"
                                    type="button"
                                    class="p-1.5 text-muted hover:text-primary transition-colors"
                                    :title="t('transfer.show.preview')"
                                    v-on:click="openPreview(file)"
                                >
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </button>
                                <a
                                    :href="fileDownloadUrl(file.filename)"
                                    class="p-1.5 text-muted hover:text-primary transition-colors"
                                    :title="t('transfer.show.download')"
                                >
                                    <ArrowDownToLine class="w-4 h-4" :stroke-width="2" />
                                </a>
                            </div>
                        </div>
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

    <!-- Preview modal -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="previewFile"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/80" v-on:click="closePreview" />

                <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 py-3 bg-surface border border-base rounded-t-xl">
                        <p class="text-sm font-medium text-primary truncate">{{ previewFile.name }}</p>
                        <button class="ml-3 shrink-0 text-muted hover:text-primary transition-colors" v-on:click="closePreview">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="relative overflow-hidden rounded-b-xl bg-black/50 flex items-center justify-center" style="max-height: calc(90vh - 52px)">
                        <img
                            v-if="isImage(previewFile.mimeType)"
                            :src="filePreviewUrl(previewFile.filename)"
                            :alt="previewFile.name"
                            class="max-w-full max-h-full object-contain"
                        >
                        <iframe
                            v-else-if="isPdf(previewFile.mimeType)"
                            :src="filePreviewUrl(previewFile.filename)"
                            class="w-full bg-white rounded-b-xl"
                            style="height: calc(90vh - 52px)"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
