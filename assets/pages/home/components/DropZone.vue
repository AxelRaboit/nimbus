<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { UploadCloud, FileText, X, AlertCircle, FolderOpen } from "lucide-vue-next";
import { useFileSize } from "@/composables/useFileSize.js";
import { ALLOWED_EXTENSIONS, ALLOWED_EXTENSIONS_ACCEPT } from "@/utils/allowedExtensions.js";
import { getDisallowedZipFiles } from "@/utils/zipValidator.js";

const { t } = useI18n();

const props = defineProps({
    files: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:files"]);

const { formatSize } = useFileSize();
const isDragging  = ref(false);
const dropErrors  = ref([]);

function isAllowedExt(name) {
    const dotIndex = name.lastIndexOf(".");
    const ext = dotIndex !== -1 ? name.slice(dotIndex).toLowerCase() : "";
    return { ext, allowed: ALLOWED_EXTENSIONS.includes(ext) };
}

function wrapWithPath(file, path) {
    if (!path || path === file.name) return file;
    return new File([file], path, { type: file.type });
}

async function readDirectoryEntry(entry, prefix = "") {
    const files = [];
    const reader = entry.createReader();

    let batch;
    do {
        batch = await new Promise((res, rej) => reader.readEntries(res, rej));
        for (const child of batch) {
            const childPath = prefix ? `${prefix}/${child.name}` : child.name;
            if (child.isFile) {
                const file = await new Promise((res, rej) => child.file(res, rej));
                files.push(wrapWithPath(file, childPath));
            } else if (child.isDirectory) {
                const sub = await readDirectoryEntry(child, childPath);
                files.push(...sub);
            }
        }
    } while (batch.length > 0);

    return files;
}

async function onDrop(e) {
    isDragging.value = false;
    const rawFiles = [];

    if (e.dataTransfer.items) {
        for (const item of e.dataTransfer.items) {
            const entry = item.webkitGetAsEntry?.();
            if (!entry) continue;
            if (entry.isDirectory) {
                const dirFiles = await readDirectoryEntry(entry, entry.name);
                rawFiles.push(...dirFiles);
            } else {
                const file = item.getAsFile();
                if (file) rawFiles.push(file);
            }
        }
    } else {
        rawFiles.push(...Array.from(e.dataTransfer.files));
    }

    await addFiles(rawFiles);
}

async function onFileInput(e) {
    await addFiles(Array.from(e.target.files));
    e.target.value = "";
}

async function onFolderInput(e) {
    const wrapped = Array.from(e.target.files).map((f) =>
        wrapWithPath(f, f.webkitRelativePath || f.name)
    );
    await addFiles(wrapped);
    e.target.value = "";
}

async function addFiles(newFiles) {
    dropErrors.value = [];
    const valid      = [];
    const typeErrors = [];
    const zipErrors  = [];

    for (const file of newFiles) {
        if (props.files.find((f) => f.name === file.name && f.size === file.size)) continue;

        const { ext, allowed } = isAllowedExt(file.name);

        if (!allowed) {
            typeErrors.push(file.name);
            continue;
        }

        if (ext === ".zip") {
            try {
                const disallowed = await getDisallowedZipFiles(file, ALLOWED_EXTENSIONS);
                if (disallowed.length > 0) {
                    zipErrors.push({ fileName: file.name, disallowed });
                    continue;
                }
            } catch (err) {
                console.warn("[DropZone] Failed to validate zip contents:", err);
            }
        }

        valid.push(file);
    }

    if (typeErrors.length > 0) {
        dropErrors.value.push(t("transfer.dropzone.error_type", { files: typeErrors.join(", ") }));
    }
    if (zipErrors.length > 0) {
        const names = zipErrors.flatMap((e) => e.disallowed).join(", ");
        dropErrors.value.push(t("transfer.dropzone.error_zip", { files: names }));
    }

    if (valid.length > 0) {
        emit("update:files", [...props.files, ...valid]);
    }
}

function removeFile(index) {
    emit("update:files", props.files.filter((_, i) => i !== index));
}
</script>

<template>
    <div>
        <input
            ref="fileInput"
            type="file"
            multiple
            :accept="ALLOWED_EXTENSIONS_ACCEPT"
            class="hidden"
            v-on:change="onFileInput"
        >
        <input
            ref="folderInput"
            type="file"
            webkitdirectory
            class="hidden"
            v-on:change="onFolderInput"
        >

        <div
            class="relative flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed transition-colors cursor-pointer p-5 sm:p-8"
            :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-base hover:border-indigo-400 bg-surface-2'"
            v-on:dragover.prevent="isDragging = true"
            v-on:dragleave.prevent="isDragging = false"
            v-on:drop.prevent="onDrop"
            v-on:click="$refs.fileInput.click()"
        >
            <div class="flex flex-col items-center gap-2 text-center pointer-events-none">
                <div class="w-12 h-12 rounded-full bg-indigo-500/15 flex items-center justify-center">
                    <UploadCloud class="w-6 h-6 text-indigo-600" :stroke-width="2" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-primary">
                        {{ isDragging ? t('transfer.dropzone.drop') : t('transfer.dropzone.drag') }}
                    </p>
                    <p class="text-xs text-muted mt-0.5">{{ t('transfer.dropzone.hint') }}</p>
                </div>
            </div>
        </div>

        <button
            type="button"
            class="mt-2 flex items-center gap-1.5 text-xs text-muted hover:text-primary transition-colors"
            v-on:click="$refs.folderInput.click()"
        >
            <FolderOpen class="w-3.5 h-3.5" :stroke-width="2" />
            {{ t('transfer.dropzone.upload_folder') }}
        </button>

        <div v-if="dropErrors.length" class="mt-2 flex flex-col gap-1">
            <p
                v-for="(err, i) in dropErrors"
                :key="i"
                class="flex items-start gap-1.5 text-xs text-red-500"
            >
                <AlertCircle class="w-3.5 h-3.5 shrink-0 mt-0.5" :stroke-width="2" />
                {{ err }}
            </p>
        </div>

        <ul v-if="files.length" class="mt-3 flex flex-col gap-1.5">
            <li
                v-for="(file, index) in files"
                :key="index"
                class="flex items-center justify-between gap-3 rounded border border-base bg-surface px-3 py-2 text-sm"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <FileText class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                    <span class="truncate text-primary font-medium">{{ file.name }}</span>
                    <span class="text-muted shrink-0">{{ formatSize(file.size) }}</span>
                </div>
                <button
                    type="button"
                    class="text-muted hover:text-red-500 transition shrink-0"
                    v-on:click.stop="removeFile(index)"
                >
                    <X class="w-4 h-4" :stroke-width="2" />
                </button>
            </li>
        </ul>
    </div>
</template>
