<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { RotateCcw, X, HelpCircle, Lock, Sparkles } from "lucide-vue-next";
import AppLogo from "@/components/AppLogo.vue";
import TransferForm from "./components/TransferForm.vue";
import UploadProgress from "./components/UploadProgress.vue";
import TransferSuccess from "./components/TransferSuccess.vue";
import AppButton from "@/components/AppButton.vue";
import { useTransferDraft } from "@/composables/useTransferDraft.js";
import { formatFileSize } from "@/utils/validation.js";


const { t, locale } = useI18n();
const { saveDraft, getDraft, clearDraft, clearTusFingerprints } = useTransferDraft();

const props = defineProps({
    userEmail:              { type: String, default: "" },
    isGuest:                { type: Boolean, default: false },
    maxSizeMb:              { type: Number, default: 500 },
    maxFiles:               { type: Number, default: 20 },
    maxRecipients:          { type: Number, default: 20 },
    maxExpiryDays:          { type: Number, default: 7 },
    expiryOptions:          { type: String, default: "[24]" },
    extensionGroups:        { type: String, default: "{}" },
    accessPasswordEnabled:  { type: Boolean, default: false },
    accessGranted:          { type: Boolean, default: true },
    isPro:                  { type: Boolean, default: false },
    planPath:               { type: String, default: "/plan" },
    loginPath:              { type: String, default: "/login" },
    registerPath:           { type: String, default: "/register" },
    registrationEnabled:    { type: Boolean, default: true },
});

const fileTypeGroups = computed(() => {
    const groups = JSON.parse(props.extensionGroups || "{}");
    return Object.entries(groups).map(([key, exts]) => ({
        label: t(`home.file_groups.${key}`, key),
        exts,
    }));
});

const GUEST_MODAL_KEY = "nimbus-guest-modal-dismissed";
const showGuestModal = ref(props.isGuest && !localStorage.getItem(GUEST_MODAL_KEY));
function dismissGuestModal() {
    showGuestModal.value = false;
    localStorage.setItem(GUEST_MODAL_KEY, "1");
}

const showHelp = ref(false);
const showAccessModal = ref(false);
const accessGrantedLocal = ref(props.accessGranted);
const accessModalPassword = ref("");
const accessModalError = ref("");
const accessModalLoading = ref(false);
const pendingSubmit = ref(null);

if (typeof window !== "undefined") {
    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            dismissGuestModal();
            showHelp.value = false;
            if (showAccessModal.value) {
                showAccessModal.value = false;
                pendingSubmit.value = null;
                accessModalPassword.value = "";
                accessModalError.value = "";
            }
        }
    });
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
        const res = await fetch(`/api/transfer/${draft.token}/resume-check`);
        if (res.ok) {
            const data = await res.json();
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
        const res = await fetch("/api/home/verify-access", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ password: accessModalPassword.value }),
        });
        if (res.ok) {
            accessGrantedLocal.value = true;
            showAccessModal.value = false;
            const formData = pendingSubmit.value;
            pendingSubmit.value = null;
            accessModalPassword.value = "";
            await onFormSubmit(formData);
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
    if (props.accessPasswordEnabled && !accessGrantedLocal.value) {
        pendingSubmit.value = formData;
        showAccessModal.value = true;
        return;
    }

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
        const res = await fetch("/api/transfer", {
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
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.error || `Erreur serveur (${res.status})`);
        }
        const data = await res.json();
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
            fileNames: formData.files.map((f) => ({ name: f.name, size: f.size })),
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
        const res = await fetch(`/api/transfer/${transferToken.value}/finalize`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ uploadKeys: keys, password: pendingFormData.value?.password || null }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            if (data.error === "zip_content_not_allowed") {
                const names = (data.disallowed_files ?? []).join(", ");
                throw new Error(t("transfer.dropzone.error_zip", { files: names || "?" }));
            }
            throw new Error(data.error || `Erreur serveur (${res.status})`);
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
    <div class="w-full max-w-xl mx-auto">
        <div v-if="resumeDraft" class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 flex items-start gap-3">
            <RotateCcw class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" :stroke-width="2" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-amber-400">Transfert en cours détecté</p>
                <p class="text-xs text-secondary mt-0.5 truncate">
                    {{ resumeDraft.fileNames?.map(f => f.name).join(', ') || 'Fichiers inconnus' }}
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

        <div class="rounded-xl border border-base bg-surface shadow-lg shadow-indigo-500/10 p-4 sm:p-6">
            <TransferForm
                v-if="step === 'form'"
                :key="formKey"
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

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showGuestModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/50" v-on:click="dismissGuestModal" />
                <div class="relative bg-surface border border-base rounded-2xl shadow-2xl w-full max-w-sm">
                    <button
                        class="absolute top-3 right-3 text-muted hover:text-primary transition-colors p-1"
                        v-on:click="dismissGuestModal"
                    >
                        <X class="w-4 h-4" :stroke-width="2" />
                    </button>

                    <div class="px-6 pt-6 pb-4 text-center">
                        <div class="flex justify-center mb-4">
                            <AppLogo :size="48" />
                        </div>
                        <h2 class="text-base font-bold text-primary">Bienvenue sur Nimbus</h2>
                        <p class="text-sm text-secondary mt-1.5">
                            L'envoi est gratuit et sans inscription. Créez un compte pour accéder au plan Pro et débloquer plus de limites.
                        </p>
                    </div>

                    <div class="px-6 pb-6 flex flex-col gap-2">
                        <a
                            :href="loginPath"
                            class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors"
                        >
                            Se connecter
                        </a>
                        <a
                            v-if="registrationEnabled"
                            :href="registerPath"
                            class="w-full flex items-center justify-center gap-2 bg-surface-2 hover:bg-surface-3 text-primary text-sm font-medium px-4 py-2.5 rounded-xl transition-colors"
                        >
                            Créer un compte
                        </a>
                        <button
                            class="text-xs text-muted hover:text-secondary transition-colors mt-1"
                            v-on:click="dismissGuestModal"
                        >
                            Continuer sans compte
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="modal">
            <div
                v-if="showAccessModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                v-on:click.self="showAccessModal = false; pendingSubmit = null; accessModalPassword = ''; accessModalError = '';"
            >
                <div class="absolute inset-0 bg-black/50" v-on:click="showAccessModal = false; pendingSubmit = null; accessModalPassword = ''; accessModalError = '';" />
                <div class="relative bg-surface border border-base rounded-2xl shadow-2xl w-full max-w-sm">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-base">
                        <h2 class="text-base font-semibold text-primary flex items-center gap-2">
                            <Lock class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                            {{ t('home.access_password.title') }}
                        </h2>
                        <button class="text-muted hover:text-primary transition-colors" v-on:click="showAccessModal = false; pendingSubmit = null; accessModalPassword = ''; accessModalError = '';">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                    <div class="px-6 py-5 flex flex-col gap-4">
                        <p class="text-sm text-secondary">{{ t('home.access_password.subtitle') }}</p>
                        <div v-if="accessModalError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ accessModalError }}
                        </div>
                        <form class="flex flex-col gap-4" v-on:submit.prevent="verifyAccess">
                            <input
                                v-model="accessModalPassword"
                                type="password"
                                autofocus
                                required
                                class="block w-full rounded-md border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                placeholder="••••••••"
                            >
                            <AppButton type="submit" :loading="accessModalLoading" class="w-full">
                                {{ t('home.access_password.submit') }}
                            </AppButton>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showHelp"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                v-on:click.self="showHelp = false"
            >
                <div class="absolute inset-0 bg-black/50" v-on:click="showHelp = false" />
                <div class="relative bg-surface border border-base rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-base">
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
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                                <div>
                                    <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_1_title') }}</p>
                                    <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_1_desc') }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                                <div>
                                    <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_2_title') }}</p>
                                    <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_2_desc') }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                                <div>
                                    <p class="font-semibold text-primary text-sm">{{ t('home.hero.step_3_title') }}</p>
                                    <p class="text-secondary text-xs mt-0.5">{{ t('home.hero.step_3_desc') }}</p>
                                </div>
                            </li>
                        </ol>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                            <div class="flex items-center justify-between border-b border-base pb-3">
                                <span class="text-xs text-muted">Taille max</span>
                                <span class="text-sm font-semibold text-primary">{{ formatFileSize(maxSizeMb, locale) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-base pb-3">
                                <span class="text-xs text-muted">Fichiers</span>
                                <span class="text-sm font-semibold text-primary">{{ maxFiles }} max</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-base pb-3">
                                <span class="text-xs text-muted">Destinataires</span>
                                <span class="text-sm font-semibold text-primary">{{ maxRecipients }} max</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-base pb-3">
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
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
