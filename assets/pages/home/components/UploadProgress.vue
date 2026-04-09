<script setup>
import { ref, onMounted } from "vue";
import * as tus from "tus-js-client";

const props = defineProps({
    files: { type: Array, required: true },
    transferToken: { type: String, required: true },
});

const emit = defineEmits(["done", "error"]);

// Per-file state: { name, size, progress (0-100), status: 'pending'|'uploading'|'done'|'error', uploadKey }
const fileStates = ref(
    props.files.map((f) => ({
        name: f.name,
        size: f.size,
        progress: 0,
        status: "pending",
        uploadKey: null,
    }))
);

const uploadKeys = ref([]);
const globalError = ref(null);

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
}

async function uploadFile(file, index) {
    return new Promise((resolve, reject) => {
        const upload = new tus.Upload(file, {
            endpoint: "/tus",
            retryDelays: [0, 3000, 5000, 10000],
            chunkSize: 5 * 1024 * 1024, // 5 MB
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
                // Extract uploadKey from the upload URL (last segment)
                const url = upload.url;
                const key = url.split("/").pop();
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

        upload.start();
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
        globalError.value = "Une erreur est survenue lors de l'envoi. Veuillez réessayer.";
        emit("error", err);
    }
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-indigo-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-primary">Envoi en cours…</p>
            <p class="text-xs text-muted mt-0.5">Ne fermez pas cette page</p>
        </div>

        <ul class="flex flex-col gap-2">
            <li
                v-for="(state, index) in fileStates"
                :key="index"
                class="rounded-lg border border-base bg-surface px-3 py-2.5"
            >
                <div class="flex items-center justify-between gap-3 mb-1.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <!-- Status icon -->
                        <svg
                            v-if="state.status === 'done'"
                            class="w-4 h-4 text-green-500 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg
                            v-else-if="state.status === 'error'"
                            class="w-4 h-4 text-red-500 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4 text-muted shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
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

                <!-- Progress bar -->
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
