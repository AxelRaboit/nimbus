<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { RotateCcw, X, HelpCircle, Lock, Sparkles, Eye, EyeOff, KeyRound, CheckCircle } from "lucide-vue-next";
import AppLogo from "@/components/AppLogo.vue";
import AppModal from "@/components/AppModal.vue";
import TransferForm from "./components/TransferForm.vue";
import UploadProgress from "./components/UploadProgress.vue";
import TransferSuccess from "./components/TransferSuccess.vue";
import AppButton from "@/components/AppButton.vue";
import { useTransferDraft } from "@/composables/useTransferDraft.js";
import { formatFileSize, normalizeServerErrors } from "@/utils/validation.js";


const { t, locale } = useI18n();
const { saveDraft, getDraft, clearDraft, clearTusFingerprints } = useTransferDraft();

const transferServerErrors = ref({});

const props = defineProps({
    userEmail:              { type: String, default: "" },
    isGuest:                { type: Boolean, default: false },
    maxSizeMb:              { type: Number, default: 500 },
    maxFiles:               { type: Number, default: 20 },
    maxRecipients:          { type: Number, default: 20 },
    maxExpiryDays:              { type: Number, default: 7 },
    tusCleanupMaxAgeHours:      { type: Number, default: 12 },
    expiryOptions:          { type: String, default: "[24]" },
    extensionGroups:        { type: String, default: "{}" },
    accessPasswordEnabled:  { type: Boolean, default: false },
    accessGranted:          { type: Boolean, default: true },
    isPro:                  { type: Boolean, default: false },
    planPath:               { type: String, default: "/plan" },
    loginPath:              { type: String, default: "/login" },
    registerPath:           { type: String, default: "/register" },
    registrationEnabled:    { type: Boolean, default: true },
    proMaxSizeMb:           { type: Number, default: 10000 },
});

const fileTypeGroups = computed(() => {
    const groups = JSON.parse(props.extensionGroups || "{}");
    return Object.entries(groups).map(([key, exts]) => ({
        label: t(`home.file_groups.${key}`, key),
        exts,
    }));
});

const GUEST_MODAL_KEY = "nimbus-guest-modal-dismissed";
const showGuestModal = ref(props.isGuest && !sessionStorage.getItem(GUEST_MODAL_KEY));
function dismissGuestModal() {
    showGuestModal.value = false;
    sessionStorage.setItem(GUEST_MODAL_KEY, "1");
}

const showHelp = ref(false);
const accessGrantedLocal = ref(props.accessGranted);
const accessModalPassword = ref("");
const accessModalError = ref("");
const accessModalLoading = ref(false);
const accessPasswordVisible = ref(false);

// ── Request access ────────────────────────────────────────────────────────────
const showRequestForm = ref(false);
const requestEmail = ref("");
const requestName = ref("");
const requestMessage = ref("");
const requestedFileSizeMb = ref(null);
const requestLoading = ref(false);
const requestError = ref("");
const requestSent = ref(false);

const fileSizeOptions = computed(() => {
    const allOptions = [
        { label: "Pas de préférence", value: null },
        { label: "100 Mo", value: 100 },
        { label: "500 Mo", value: 500 },
        { label: "1 Go", value: 1000 },
        { label: "5 Go", value: 5000 },
        { label: "10 Go", value: 10000 },
        { label: "20 Go", value: 20000 },
        { label: "50 Go", value: 50000 },
    ];
    return allOptions.filter(opt => opt.value === null || opt.value <= props.proMaxSizeMb);
});

async function submitAccessRequest() {
    requestError.value = "";
    if (!requestEmail.value.trim()) {
        requestError.value = "L'adresse e-mail est requise.";
        return;
    }
    requestLoading.value = true;
    try {
        const response = await fetch("/api/home/request-access", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                email: requestEmail.value.trim(),
                name: requestName.value.trim() || null,
                message: requestMessage.value.trim() || null,
                requestedFileSizeMb: requestedFileSizeMb.value,
            }),
        });
        if (!response.ok) {
            requestError.value = "Une erreur est survenue. Veuillez réessayer.";
            return;
        }
        requestSent.value = true;
    } catch {
        requestError.value = "Une erreur est survenue. Veuillez réessayer.";
    } finally {
        requestLoading.value = false;
    }
}


const step = ref("form");
const pendingFiles = ref([]);
const pendingFormData = ref(null);
const transferToken = ref(null);
const transferOwnerToken = ref(null);
const transferReference = ref(null);
const transferIsPublic = ref(false);
const uploadKeys = ref([]);
const apiError = ref(null);

const resumeDraft = ref(null);
const formKey     = ref(0);

onMounted(async () => {
    const draft = getDraft();
    if (!draft?.token) return;

    try {
        const response = await fetch(`/api/transfer/${draft.token}/resume-check`);
        if (response.ok) {
            const data = await response.json();
            if (data.resumable) {
                resumeDraft.value = draft;
                return;
            }
        }
    } catch {}

    clearDraft();
    clearTusFingerprints();
});

async function abandonResume() {
    const draft = resumeDraft.value;
    resumeDraft.value = null;
    formKey.value++;
    clearDraft();
    clearTusFingerprints();
    if (draft?.token) {
        await fetch(`/api/transfer/${draft.token}/abandon`, { method: "DELETE" }).catch(() => {});
    }
}

const manageUrl = computed(() => {
    if (!transferOwnerToken.value) return "";
    return `${window.location.origin}/manage/${transferOwnerToken.value}`;
});

async function verifyAccess() {
    accessModalLoading.value = true;
    accessModalError.value = "";
    try {
        const response = await fetch("/api/home/verify-access", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ password: accessModalPassword.value }),
        });
        if (response.ok) {
            accessGrantedLocal.value = true;
            accessModalPassword.value = "";
        } else {
            accessModalError.value = t("home.access_password.error");
        }
    } catch {
        accessModalError.value = t("home.access_password.error");
    } finally {
        accessModalLoading.value = false;
    }
}

async function onFormSubmit(formData) {
    apiError.value = null;

    const existing = resumeDraft.value ?? (getDraft()?.token ? getDraft() : null);
    if (existing?.token) {
        resumeDraft.value = null;
        transferToken.value = existing.token;
        transferOwnerToken.value = existing.ownerToken;
        transferReference.value = existing.reference;
        pendingFiles.value = formData.files;
        pendingFormData.value = formData;
        step.value = "uploading";
        return;
    }

    try {
        const response = await fetch("/api/transfer", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                isPublic: formData.isPublic ?? false,
                senderEmail: formData.senderEmail || undefined,
                senderName: formData.senderName || undefined,
                recipients: formData.isPublic ? undefined : formData.recipients,
                message: formData.message || null,
                expiresInHours: formData.expiresIn,
                password: formData.password || null,
            }),
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            if (response.status === 422 && data.errors) {
                transferServerErrors.value = normalizeServerErrors(data.errors);
                return;
            }
            throw new Error(data.error || `Erreur serveur (${response.status})`);
        }
        const data = await response.json();
        transferToken.value = data.token;
        transferOwnerToken.value = data.ownerToken;
        transferReference.value = data.reference;
        transferIsPublic.value = data.isPublic ?? false;
        pendingFiles.value = formData.files;
        pendingFormData.value = formData;

        saveDraft({
            token: data.token,
            ownerToken: data.ownerToken,
            reference: data.reference,
            isPublic: formData.isPublic ?? false,
            senderEmail: formData.senderEmail,
            senderName: formData.senderName,
            recipients: formData.recipients,
            message: formData.message,
            expiresIn: formData.expiresIn,
            password: formData.password,
            fileNames: formData.files.map((file) => ({ name: file.name, size: file.size })),
            savedAt: Date.now(),
        });

        step.value = "uploading";
    } catch (err) {
        apiError.value = err.message || "Une erreur est survenue.";
    }
}

async function onUploadDone({ uploadKeys: keys }) {
    apiError.value = null;
    uploadKeys.value = keys;
    try {
        const response = await fetch(`/api/transfer/${transferToken.value}/finalize`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ uploadKeys: keys, password: pendingFormData.value?.password || null }),
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            if (data.error === "zip_content_not_allowed") {
                const names = (data.disallowed_files ?? []).join(", ");
                throw new Error(t("transfer.dropzone.error_zip", { files: names || "?" }));
            }
            throw new Error(data.error || `Erreur serveur (${response.status})`);
        }
        clearDraft();
        clearTusFingerprints();
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
    transferIsPublic.value = false;
    uploadKeys.value = [];
    apiError.value = null;
}
</script>

<template>
    <div v-if="accessPasswordEnabled && !accessGrantedLocal" class="w-full max-w-sm mx-auto">
        <div class="rounded-xl border border-line bg-surface shadow-lg shadow-indigo-500/10 p-6 sm:p-8">
            <!-- Success state after request sent -->
            <div v-if="requestSent" class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center">
                        <CheckCircle class="w-6 h-6 text-emerald-500" :stroke-width="2" />
                    </div>
                </div>
                <h2 class="text-lg font-bold text-primary">Demande envoyée</h2>
                <p class="text-sm text-secondary mt-1.5">Vous recevrez un e-mail dès que l'administrateur aura approuvé votre demande.</p>
                <button type="button" class="mt-4 text-xs text-link hover:text-link-hover transition-colors" v-on:click="requestSent = false; showRequestForm = false">
                    Retour
                </button>
            </div>

            <!-- Request access form -->
            <template v-else-if="showRequestForm">
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-500/10 flex items-center justify-center">
                            <KeyRound class="w-6 h-6 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <h2 class="text-lg font-bold text-primary">Demander l'accès</h2>
                    <p class="text-sm text-secondary mt-1.5">L'administrateur recevra votre demande et vous contactera par e-mail.</p>
                </div>
                <div v-if="requestError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">
                    {{ requestError }}
                </div>
                <form class="flex flex-col gap-3" v-on:submit.prevent="submitAccessRequest">
                    <input
                        v-model="requestEmail"
                        type="email"
                        required
                        placeholder="votre@email.com"
                        class="block w-full rounded-lg border border-line bg-surface-2 px-3 py-2.5 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                    >
                    <input
                        v-model="requestName"
                        type="text"
                        placeholder="Votre nom (optionnel)"
                        class="block w-full rounded-lg border border-line bg-surface-2 px-3 py-2.5 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                    >
                    <textarea
                        v-model="requestMessage"
                        placeholder="Message (optionnel)"
                        rows="3"
                        class="block w-full rounded-lg border border-line bg-surface-2 px-3 py-2.5 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
                    />
                    <div>
                        <label class="block text-xs text-muted mb-1">Taille de fichier souhaitée (optionnel)</label>
                        <select
                            v-model="requestedFileSizeMb"
                            class="block w-full rounded-lg border border-line bg-surface-2 px-3 py-2.5 text-sm text-primary focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        >
                            <option v-for="opt in fileSizeOptions" :key="String(opt.value)" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <AppButton type="submit" :loading="requestLoading" class="w-full">
                        Envoyer la demande
                    </AppButton>
                    <button type="button" class="text-xs text-muted hover:text-secondary transition-colors text-center" v-on:click="showRequestForm = false">
                        Retour
                    </button>
                </form>
            </template>

            <!-- Password form -->
            <template v-else>
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-500/10 flex items-center justify-center">
                            <Lock class="w-6 h-6 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <h2 class="text-lg font-bold text-primary">{{ t('home.access_password.title') }}</h2>
                    <p class="text-sm text-secondary mt-1.5">{{ t('home.access_password.subtitle') }}</p>
                </div>
                <div v-if="accessModalError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">
                    {{ accessModalError }}
                </div>
                <form class="flex flex-col gap-3" v-on:submit.prevent="verifyAccess">
                    <div class="relative">
                        <input
                            v-model="accessModalPassword"
                            :type="accessPasswordVisible ? 'text' : 'password'"
                            autofocus
                            required
                            class="block w-full rounded-lg border border-line bg-surface-2 px-3 py-2.5 pr-10 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-muted hover:text-primary transition-colors"
                            tabindex="-1"
                            v-on:click="accessPasswordVisible = !accessPasswordVisible"
                        >
                            <EyeOff v-if="accessPasswordVisible" class="w-4 h-4" :stroke-width="2" />
                            <Eye v-else class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                    <AppButton type="submit" :loading="accessModalLoading" class="w-full">
                        {{ t('home.access_password.submit') }}
                    </AppButton>
                </form>
                <div class="mt-4 pt-4 border-t border-line text-center">
                    <button type="button" class="text-xs text-link hover:text-link-hover transition-colors flex items-center gap-1 mx-auto" v-on:click="showRequestForm = true">
                        <KeyRound class="w-3 h-3" :stroke-width="2" />
                        Demander l'accès
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div v-else class="w-full max-w-xl mx-auto">
        <div v-if="resumeDraft" class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 flex items-start gap-3">
            <RotateCcw class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" :stroke-width="2" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-amber-400">Transfert en cours détecté</p>
                <p class="text-xs text-secondary mt-0.5 truncate">
                    {{ resumeDraft.fileNames?.map(file => file.name).join(', ') || 'Fichiers inconnus' }}
                </p>
                <p class="text-xs text-muted mt-1">Re-sélectionnez vos fichiers pour reprendre automatiquement.</p>
            </div>
            <button class="p-1 text-muted hover:text-primary transition-colors shrink-0" v-on:click="abandonResume">
                <X class="w-3.5 h-3.5" :stroke-width="2" />
            </button>
        </div>

        <div v-if="apiError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ apiError }}
        </div>

        <div class="rounded-xl border border-line bg-surface shadow-lg shadow-indigo-500/10 p-4 sm:p-6">
            <TransferForm
                v-if="step === 'form'"
                :key="formKey"
                :server-errors="transferServerErrors"
                :prefill-email="props.userEmail"
                :draft="resumeDraft"
                :max-files="Number(props.maxFiles)"
                :max-recipients="Number(props.maxRecipients)"
                :max-size-mb="Number(props.maxSizeMb)"
                :max-expiry-days="Number(props.maxExpiryDays)"
                :expiry-options="JSON.parse(props.expiryOptions)"
                v-on:submit="onFormSubmit"
                v-on:open-help="showHelp = true"
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
                :is-guest="props.isGuest"
                :is-public="transferIsPublic"
                :transfer-token="transferToken"
                v-on:reset="reset"
            />
        </div>
    </div>

    <AppModal :show="showGuestModal" max-width="sm" v-on:close="dismissGuestModal">
        <div class="relative text-center">
            <button
                class="absolute -top-1 -right-1 text-muted hover:text-primary transition-colors p-1"
                v-on:click="dismissGuestModal"
            >
                <X class="w-4 h-4" :stroke-width="2" />
            </button>
            <div class="flex justify-center mb-4">
                <AppLogo :size="48" />
            </div>
            <h2 class="text-base font-bold text-primary">Bienvenue sur Nimbus</h2>
            <p class="text-sm text-secondary mt-1.5">
                L'envoi est gratuit et sans inscription. Créez un compte pour accéder au plan Pro et débloquer plus de limites.
            </p>
        </div>
        <div class="flex flex-col gap-2">
            <AppButton :href="loginPath" class="w-full">Se connecter</AppButton>
            <AppButton v-if="registrationEnabled" :href="registerPath" variant="secondary" class="w-full">Créer un compte</AppButton>
            <button
                class="text-xs text-muted hover:text-secondary transition-colors mt-1"
                v-on:click="dismissGuestModal"
            >
                Continuer sans compte
            </button>
        </div>
    </AppModal>

    <AppModal
        :show="showHelp"
        max-width="md"
        no-padding
        scrollable
        v-on:close="showHelp = false"
    >
        <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <h2 class="text-base font-semibold text-primary flex items-center gap-2">
                <HelpCircle class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                {{ t('home.hero.heading') }} {{ t('home.hero.heading_accent') }}
            </h2>
            <button class="text-muted hover:text-primary transition-colors" v-on:click="showHelp = false">
                <X class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>

        <div class="px-6 py-5 space-y-6">
            <ol class="space-y-4">
                <li class="flex items-start gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                    <div>
                        <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_1_title') }}</p>
                        <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_1_desc') }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <div>
                        <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_2_title') }}</p>
                        <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_2_desc') }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <div>
                        <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_3_title') }}</p>
                        <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_3_desc') }}</p>
                    </div>
                </li>
            </ol>

            <div class="rounded-lg border border-amber-500/30 bg-amber-500/5 px-4 py-3 space-y-1">
                <p class="text-xs font-semibold text-amber-400 flex items-center gap-1.5">
                    <svg
                        class="w-3.5 h-3.5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reprise automatique des envois interrompus
                </p>
                <p class="text-xs text-secondary">
                    Si votre connexion est coupée pendant un envoi, Nimbus détecte automatiquement le transfert en cours lors de votre prochaine visite et vous propose de reprendre là où vous vous étiez arrêté.
                </p>
                <p class="text-xs text-secondary">
                    Les fichiers temporaires sont conservés pendant <span class="font-semibold text-primary">{{ tusCleanupMaxAgeHours }}h</span>. Passé ce délai, le transfert est considéré comme abandonné et les données sont supprimées.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <span class="text-xs text-muted">Taille max</span>
                    <span class="text-sm font-semibold text-primary">{{ formatFileSize(maxSizeMb, locale) }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <span class="text-xs text-muted">Fichiers</span>
                    <span class="text-sm font-semibold text-primary">{{ maxFiles }} max</span>
                </div>
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <span class="text-xs text-muted">Destinataires</span>
                    <span class="text-sm font-semibold text-primary">{{ maxRecipients }} max</span>
                </div>
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <span class="text-xs text-muted">Durée</span>
                    <span class="text-sm font-semibold text-primary">jusqu'à {{ maxExpiryDays }}j</span>
                </div>
            </div>

            <p class="text-xs text-muted flex items-center gap-1.5">
                <svg
                    class="w-3.5 h-3.5 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Protection par mot de passe optionnelle
            </p>

            <a
                v-if="!isPro && !isGuest"
                :href="planPath"
                class="flex items-center gap-3 bg-indigo-600/10 border border-indigo-500/30 rounded-xl px-4 py-3 hover:bg-indigo-600/15 transition-colors group"
            >
                <Sparkles class="w-4 h-4 text-indigo-400 shrink-0" :stroke-width="2" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-indigo-400">Passer au plan Pro</p>
                    <p class="text-xs text-secondary mt-0.5">Plus de stockage, plus de fichiers, expiration plus longue.</p>
                </div>
                <span class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full shrink-0">Pro</span>
            </a>

            <div>
                <p class="text-xs text-muted uppercase tracking-wide mb-3">Formats acceptés</p>
                <div class="flex flex-col gap-2">
                    <div v-for="group in fileTypeGroups" :key="group.label" class="flex items-baseline gap-2">
                        <span class="text-xs text-muted w-20 shrink-0">{{ group.label }}</span>
                        <p class="text-xs font-mono text-secondary leading-relaxed">{{ group.exts.join('  ') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppModal>
</template>
