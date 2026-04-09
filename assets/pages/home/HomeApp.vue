<script setup>
import { ref, computed } from "vue";
import TransferForm from "./components/TransferForm.vue";
import UploadProgress from "./components/UploadProgress.vue";
import TransferSuccess from "./components/TransferSuccess.vue";

// Steps: form → uploading → success
const step = ref("form");

// Data from form submission
const pendingFiles = ref([]);
const pendingFormData = ref(null);

// Data from API
const transferToken = ref(null);
const transferOwnerToken = ref(null);
const transferReference = ref(null);

// Upload keys collected after TUS
const uploadKeys = ref([]);

// Errors
const apiError = ref(null);

// Computed manage URL
const manageUrl = computed(() => {
    if (!transferOwnerToken.value) return "";
    return `${window.location.origin}/manage/${transferOwnerToken.value}`;
});

async function onFormSubmit(formData) {
    apiError.value = null;

    try {
        const res = await fetch("/api/transfer", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                senderEmail: formData.senderEmail,
                senderName: formData.senderName,
                recipients: formData.recipients,
                message: formData.message || null,
                expiresInDays: formData.expiresIn,
                password: formData.password || null,
            }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.error || `Erreur serveur (${res.status})`);
        }

        const data = await res.json();
        transferToken.value = data.token;
        transferOwnerToken.value = data.ownerToken;
        transferReference.value = data.reference;

        pendingFiles.value = formData.files;
        pendingFormData.value = formData;

        step.value = "uploading";
    } catch (err) {
        apiError.value = err.message || "Une erreur est survenue. Veuillez réessayer.";
    }
}

async function onUploadDone({ uploadKeys: keys }) {
    apiError.value = null;
    uploadKeys.value = keys;

    try {
        const res = await fetch(`/api/transfer/${transferToken.value}/finalize`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                uploadKeys: keys,
            }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.error || `Erreur serveur (${res.status})`);
        }

        step.value = "success";
    } catch (err) {
        apiError.value = err.message || "Une erreur est survenue lors de la finalisation.";
        step.value = "form";
    }
}

function onUploadError() {
    apiError.value = "L'envoi a échoué. Veuillez réessayer.";
    step.value = "form";
}

function reset() {
    step.value = "form";
    pendingFiles.value = [];
    pendingFormData.value = null;
    transferToken.value = null;
    transferOwnerToken.value = null;
    transferReference.value = null;
    uploadKeys.value = [];
    apiError.value = null;
}
</script>

<template>
    <div class="min-h-screen bg-bg flex flex-col">
        <!-- Header -->
        <header class="border-b border-base bg-surface">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                </div>
                <span class="text-base font-bold text-primary">Nimbus</span>
            </div>
        </header>

        <!-- Main -->
        <main class="flex-1 flex items-start justify-center px-4 py-10">
            <div class="w-full max-w-xl">
                <!-- Step indicator (form + uploading only) -->
                <div v-if="step !== 'success'" class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-primary">
                        {{ step === 'form' ? 'Envoyer des fichiers' : 'Envoi en cours…' }}
                    </h1>
                    <p class="text-sm text-muted mt-1">
                        {{ step === 'form'
                            ? 'Partagez vos fichiers en quelques secondes, de façon sécurisée.'
                            : 'Vos fichiers sont en cours de téléversement.' }}
                    </p>
                </div>

                <!-- API Error -->
                <div v-if="apiError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ apiError }}
                </div>

                <!-- Card -->
                <div class="rounded-2xl border border-base bg-surface shadow-sm p-6">
                    <TransferForm
                        v-if="step === 'form'"
                        v-on:submit="onFormSubmit"
                    />

                    <UploadProgress
                        v-else-if="step === 'uploading'"
                        :files="pendingFiles"
                        :transfer-token="transferToken"
                        v-on:done="onUploadDone"
                        v-on:error="onUploadError"
                    />

                    <TransferSuccess
                        v-else-if="step === 'success'"
                        :reference="transferReference"
                        :manage-url="manageUrl"
                        v-on:reset="reset"
                    />
                </div>
            </div>
        </main>
    </div>
</template>
