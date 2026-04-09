<script setup>
import { computed } from "vue";
import AppButton from "@/components/AppButton.vue";

const props = defineProps({
    token: { type: String, required: true },
    reference: { type: String, required: true },
    expiresAt: { type: String, required: true },
    senderName: { type: String, default: "" },
    files: { type: String, default: "[]" },
});

const parsedFiles = computed(() => JSON.parse(props.files));

const expiresDate = computed(() =>
    new Intl.DateTimeFormat("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(new Date(props.expiresAt))
);

const totalSize = computed(() =>
    parsedFiles.value.reduce((acc, f) => acc + f.size, 0)
);

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
}

const downloadUrl = computed(() => `/t/${props.token}/download`);
</script>

<template>
    <div class="min-h-screen bg-bg flex flex-col">
        <!-- Header -->
        <header class="border-b border-base bg-surface">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center gap-3">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                        </svg>
                    </div>
                    <span class="text-base font-bold text-primary">Nimbus</span>
                </a>
            </div>
        </header>

        <!-- Main -->
        <main class="flex-1 flex items-start justify-center px-4 py-10">
            <div class="w-full max-w-xl flex flex-col gap-6">
                <!-- Title -->
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-primary">Fichiers disponibles</h1>
                    <p class="text-sm text-muted mt-1">
                        <span v-if="senderName">Envoyé par <span class="font-medium text-secondary">{{ senderName }}</span> · </span>
                        Expire le {{ expiresDate }}
                    </p>
                </div>

                <!-- Card -->
                <div class="rounded-2xl border border-base bg-surface shadow-sm overflow-hidden">
                    <!-- Reference -->
                    <div class="px-5 py-3 bg-surface-2 border-b border-base flex items-center justify-between">
                        <span class="text-xs text-muted">Référence</span>
                        <span class="text-sm font-bold text-primary tracking-widest">{{ reference }}</span>
                    </div>

                    <!-- File list -->
                    <ul class="divide-y divide-base">
                        <li
                            v-for="(file, index) in parsedFiles"
                            :key="index"
                            class="flex items-center gap-3 px-5 py-3"
                        >
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-primary truncate">{{ file.name }}</p>
                                <p class="text-xs text-muted">{{ formatSize(file.size) }}</p>
                            </div>
                        </li>
                    </ul>

                    <!-- Footer: total + download -->
                    <div class="px-5 py-4 border-t border-base flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs text-muted">
                                {{ parsedFiles.length }} fichier{{ parsedFiles.length > 1 ? 's' : '' }}
                            </p>
                            <p class="text-sm font-semibold text-primary">{{ formatSize(totalSize) }}</p>
                        </div>
                        <a :href="downloadUrl">
                            <AppButton size="md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ parsedFiles.length > 1 ? 'Tout télécharger' : 'Télécharger' }}
                            </AppButton>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
