<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { UploadCloud, Check, AlertCircle, FileText } from "lucide-vue-next";
import { useFileSize } from "@/composables/useFileSize.js";
import * as tus from "tus-js-client";

const { t } = useI18n();

const props = defineProps({
    files: { type: Array, required: true },
    transferToken: { type: String, required: true },
});

const emit = defineEmits(["done", "error"]);

const fileStates = ref(
    props.files.map((f) => ({
        name: f.name,
        size: f.size,
        progress: 0,
        status: "pending",
        uploadKey: null,
    }))
);

const { formatSize } = useFileSize();
const uploadKeys = ref([]);
const globalError = ref(null);

async function uploadFile(file, index) {
    return new Promise((resolve, reject) => {
        const upload = new tus.Upload(file, {
            endpoint: "/tus",
            retryDelays: [0, 3000, 5000, 10000],
            chunkSize: 5 * 1024 * 1024,
            storeFingerprintForResuming: true,
            metadata: {
                filename: file.name.replace(/['"&/\\?#:]/g, "_"),
                originalName: file.name,
                filetype: file.type,
                transferToken: props.transferToken,
            },
            onProgress(bytesUploaded, bytesTotal) {
                fileStates.value[index].progress = Math.round(
                    (bytesUploaded / bytesTotal) * 100
                );
                fileStates.value[index].status = "uploading";
            },
            onSuccess() {
                const key = upload.url.split("/").pop();
                fileStates.value[index].uploadKey = key;
                fileStates.value[index].progress = 100;
                fileStates.value[index].status = "done";
                resolve(key);
            },
            onError(err) {
                fileStates.value[index].status = "error";
                reject(err);
            },
        });

        upload.findPreviousUploads().then((previous) => {
            if (previous.length > 0) {
                const best = previous.reduce((a, b) =>
                    (a.uploadOffset ?? 0) >= (b.uploadOffset ?? 0) ? a : b
                );
                upload.resumeFromPreviousUpload(best);
            }
            upload.start();
        });
    });
}

onMounted(async () => {
    try {
        for (let i = 0; i < props.files.length; i++) {
            const key = await uploadFile(props.files[i], i);
            uploadKeys.value.push(key);
        }
        emit("done", { uploadKeys: uploadKeys.value });
    } catch (err) {
        globalError.value = t("transfer.progress.error");
        emit("error", err);
    }
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-3">
                <UploadCloud class="w-6 h-6 text-indigo-600 animate-pulse" :stroke-width="2" />
            </div>
            <p class="text-sm font-semibold text-primary">{{ t('transfer.progress.uploading') }}</p>
            <p class="text-xs text-muted mt-0.5">{{ t('transfer.progress.do_not_close') }}</p>
        </div>

        <ul class="flex flex-col gap-2">
            <li
                v-for="(state, index) in fileStates"
                :key="index"
                class="rounded-lg border border-base bg-surface px-3 py-2.5"
            >
                <div class="flex items-center justify-between gap-3 mb-1.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <Check v-if="state.status === 'done'" class="w-4 h-4 text-green-500 shrink-0" :stroke-width="2" />
                        <AlertCircle v-else-if="state.status === 'error'" class="w-4 h-4 text-red-500 shrink-0" :stroke-width="2" />
                        <FileText v-else class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                        <span class="truncate text-sm font-medium text-primary">{{ state.name }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs text-muted">{{ formatSize(state.size) }}</span>
                        <span
                            class="text-xs font-medium"
                            :class="{
                                'text-indigo-600': state.status === 'uploading',
                                'text-green-500': state.status === 'done',
                                'text-red-500': state.status === 'error',
                                'text-muted': state.status === 'pending',
                            }"
                        >{{ state.progress }}%</span>
                    </div>
                </div>
                <div class="h-1.5 w-full bg-surface-2 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="{
                            'bg-indigo-500': state.status !== 'done' && state.status !== 'error',
                            'bg-green-500': state.status === 'done',
                            'bg-red-500': state.status === 'error',
                        }"
                        :style="{ width: `${state.progress}%` }"
                    />
                </div>
            </li>
        </ul>

        <p v-if="globalError" class="text-sm text-red-500 text-center">{{ globalError }}</p>
    </div>
</template>
