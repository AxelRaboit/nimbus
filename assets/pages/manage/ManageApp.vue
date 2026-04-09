<script setup>
import { ref, computed } from "vue";
import AppButton from "@/components/AppButton.vue";

const props = defineProps({
    ownerToken: { type: String, required: true },
    reference: { type: String, required: true },
    status: { type: String, required: true },
    expiresAt: { type: String, required: true },
    files: { type: String, default: "[]" },
    recipients: { type: String, default: "[]" },
});

const parsedFiles = computed(() => JSON.parse(props.files));
const parsedRecipients = computed(() => JSON.parse(props.recipients));

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

const statusLabel = {
    pending: "En attente",
    ready: "Actif",
    expired: "Expiré",
    deleted: "Supprimé",
};

const statusClass = {
    pending: "bg-yellow-100 text-yellow-700",
    ready: "bg-green-100 text-green-700",
    expired: "bg-surface-2 text-muted",
    deleted: "bg-red-100 text-red-700",
};

// Delete confirmation
const confirmDelete = ref(false);
const deleting = ref(false);

const deleteUrl = computed(() => `/manage/${props.ownerToken}/delete`);
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
                <div>
                    <h1 class="text-2xl font-bold text-primary">Gérer mon transfert</h1>
                    <p class="text-sm text-muted mt-1">Consultez les détails et supprimez si nécessaire.</p>
                </div>

                <!-- Info card -->
                <div class="rounded-2xl border border-base bg-surface shadow-sm overflow-hidden">
                    <!-- Header row -->
                    <div class="px-5 py-4 flex items-center justify-between border-b border-base">
                        <div>
                            <p class="text-xs text-muted mb-0.5">Référence</p>
                            <p class="text-xl font-bold text-primary tracking-widest">{{ reference }}</p>
                        </div>
                        <span
                            class="text-xs font-semibold px-2.5 py-1 rounded-full"
                            :class="statusClass[status] ?? 'bg-surface-2 text-muted'"
                        >
                            {{ statusLabel[status] ?? status }}
                        </span>
                    </div>

                    <!-- Meta -->
                    <div class="px-5 py-3 border-b border-base flex items-center gap-6 text-sm">
                        <div>
                            <p class="text-xs text-muted">Expiration</p>
                            <p class="font-medium text-primary">{{ expiresDate }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Fichiers</p>
                            <p class="font-medium text-primary">
                                {{ parsedFiles.length }} · {{ formatSize(totalSize) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Destinataires</p>
                            <p class="font-medium text-primary">{{ parsedRecipients.length }}</p>
                        </div>
                    </div>

                    <!-- Files -->
                    <div class="px-5 py-3 border-b border-base">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Fichiers</p>
                        <ul class="flex flex-col gap-1.5">
                            <li
                                v-for="(file, index) in parsedFiles"
                                :key="index"
                                class="flex items-center gap-2 text-sm"
                            >
                                <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="truncate text-primary font-medium">{{ file.name }}</span>
                                <span class="text-muted shrink-0">{{ formatSize(file.size) }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Recipients -->
                    <div class="px-5 py-3">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Destinataires</p>
                        <ul class="flex flex-col gap-1.5">
                            <li
                                v-for="(recipient, index) in parsedRecipients"
                                :key="index"
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span class="truncate text-primary">{{ recipient.email }}</span>
                                <span
                                    class="text-xs font-medium shrink-0 flex items-center gap-1"
                                    :class="recipient.downloaded ? 'text-green-600' : 'text-muted'"
                                >
                                    <svg
                                        v-if="recipient.downloaded"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ recipient.downloaded ? 'Téléchargé' : 'En attente' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Delete -->
                <div class="rounded-2xl border border-red-200 bg-surface shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-red-600 mb-1">Zone de danger</h2>
                    <p class="text-xs text-muted mb-4">
                        La suppression est définitive. Les fichiers seront effacés et les destinataires ne pourront plus télécharger.
                    </p>

                    <div v-if="!confirmDelete">
                        <AppButton variant="danger" size="sm" v-on:click="confirmDelete = true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer ce transfert
                        </AppButton>
                    </div>

                    <div v-else class="flex items-center gap-3">
                        <p class="text-sm text-primary font-medium">Confirmer la suppression ?</p>
                        <form :action="deleteUrl" method="POST" class="flex items-center gap-2">
                            <AppButton
                                type="submit"
                                variant="danger"
                                size="sm"
                                :loading="deleting"
                                v-on:click="deleting = true"
                            >
                                Oui, supprimer
                            </AppButton>
                            <AppButton type="button" variant="secondary" size="sm" v-on:click="confirmDelete = false">
                                Annuler
                            </AppButton>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
