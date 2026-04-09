<script setup>
import { ref } from "vue";

const props = defineProps({
    files: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:files"]);

const isDragging = ref(false);

function onDrop(e) {
    isDragging.value = false;
    const dropped = Array.from(e.dataTransfer.files);
    addFiles(dropped);
}

function onFileInput(e) {
    addFiles(Array.from(e.target.files));
    e.target.value = "";
}

function addFiles(newFiles) {
    const merged = [...props.files];
    for (const file of newFiles) {
        if (!merged.find((f) => f.name === file.name && f.size === file.size)) {
            merged.push(file);
        }
    }
    emit("update:files", merged);
}

function removeFile(index) {
    const updated = props.files.filter((_, i) => i !== index);
    emit("update:files", updated);
}

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
}
</script>

<template>
    <div>
        <div
            class="relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed transition-colors cursor-pointer p-8"
            :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-base hover:border-indigo-400 bg-surface-2'"
            v-on:dragover.prevent="isDragging = true"
            v-on:dragleave.prevent="isDragging = false"
            v-on:drop.prevent="onDrop"
            v-on:click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                class="hidden"
                v-on:change="onFileInput"
            >

            <div class="flex flex-col items-center gap-2 text-center pointer-events-none">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-primary">
                        {{ isDragging ? 'Déposez vos fichiers ici' : 'Glissez vos fichiers ou cliquez pour parcourir' }}
                    </p>
                    <p class="text-xs text-muted mt-0.5">Tous types de fichiers acceptés</p>
                </div>
            </div>
        </div>

        <ul v-if="files.length" class="mt-3 flex flex-col gap-1.5">
            <li
                v-for="(file, index) in files"
                :key="index"
                class="flex items-center justify-between gap-3 rounded-lg border border-base bg-surface px-3 py-2 text-sm"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="truncate text-primary font-medium">{{ file.name }}</span>
                    <span class="text-muted shrink-0">{{ formatSize(file.size) }}</span>
                </div>
                <button
                    type="button"
                    class="text-muted hover:text-red-500 transition shrink-0"
                    v-on:click.stop="removeFile(index)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </li>
        </ul>
    </div>
</template>
