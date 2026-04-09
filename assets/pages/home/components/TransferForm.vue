<script setup>
import { ref, computed } from "vue";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import DropZone from "./DropZone.vue";

const emit = defineEmits(["submit"]);

// Files
const files = ref([]);

// Recipients
const recipients = ref([""]);

function addRecipient() {
    if (recipients.value.length < 20) {
        recipients.value.push("");
    }
}

function removeRecipient(index) {
    recipients.value.splice(index, 1);
}

// Sender
const senderEmail = ref("");
const senderName = ref("");
const message = ref("");

// Advanced options
const showAdvanced = ref(false);
const expiresIn = ref(7);
const password = ref("");
const showPassword = ref(false);

// Validation errors
const errors = ref({});

// Computed
const validRecipients = computed(() =>
    recipients.value.filter((r) => r.trim() !== "")
);

function validate() {
    const e = {};

    if (files.value.length === 0) {
        e.files = "Ajoutez au moins un fichier.";
    }

    if (!senderEmail.value.trim()) {
        e.senderEmail = "L'adresse email est requise.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(senderEmail.value.trim())) {
        e.senderEmail = "Adresse email invalide.";
    }

    if (validRecipients.value.length === 0) {
        e.recipients = "Ajoutez au moins un destinataire.";
    } else {
        const invalidRecipient = validRecipients.value.find(
            (r) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(r.trim())
        );
        if (invalidRecipient) {
            e.recipients = `Adresse invalide : ${invalidRecipient}`;
        }
    }

    errors.value = e;
    return Object.keys(e).length === 0;
}

function submit() {
    if (!validate()) return;

    emit("submit", {
        files: files.value,
        senderEmail: senderEmail.value.trim(),
        senderName: senderName.value.trim(),
        recipients: validRecipients.value.map((r) => r.trim()),
        message: message.value.trim(),
        expiresIn: expiresIn.value,
        password: password.value,
    });
}
</script>

<template>
    <form novalidate class="flex flex-col gap-6" v-on:submit.prevent="submit">
        <!-- Files -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-primary">Fichiers</label>
            <DropZone v-model:files="files" />
            <p v-if="errors.files" class="text-xs text-red-500">{{ errors.files }}</p>
        </div>

        <!-- Recipients -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-primary">
                Destinataires
                <span class="text-red-500 ml-0.5">*</span>
            </label>

            <div class="flex flex-col gap-2">
                <div
                    v-for="(_, index) in recipients"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <input
                        v-model="recipients[index]"
                        type="email"
                        :placeholder="`destinataire${index + 1}@exemple.com`"
                        class="block w-full rounded-lg border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.recipients && index === 0 }"
                    >
                    <button
                        v-if="recipients.length > 1"
                        type="button"
                        class="text-muted hover:text-red-500 transition shrink-0"
                        title="Supprimer"
                        v-on:click="removeRecipient(index)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <p v-if="errors.recipients" class="text-xs text-red-500">{{ errors.recipients }}</p>

            <button
                v-if="recipients.length < 20"
                type="button"
                class="self-start text-xs text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1 transition"
                v-on:click="addRecipient"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un destinataire
            </button>
        </div>

        <!-- Sender -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <AppInput
                v-model="senderEmail"
                type="email"
                label="Votre email"
                placeholder="vous@exemple.com"
                :error="errors.senderEmail"
                required
            />
            <AppInput
                v-model="senderName"
                label="Votre nom"
                placeholder="Optionnel"
            />
        </div>

        <!-- Message -->
        <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-primary">Message</label>
            <textarea
                v-model="message"
                rows="3"
                placeholder="Un message pour vos destinataires (optionnel)"
                class="block w-full rounded-lg border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
            />
        </div>

        <!-- Advanced options -->
        <div class="flex flex-col gap-3">
            <button
                type="button"
                class="self-start flex items-center gap-1.5 text-sm text-secondary hover:text-primary transition font-medium"
                v-on:click="showAdvanced = !showAdvanced"
            >
                <svg
                    class="w-4 h-4 transition-transform"
                    :class="showAdvanced ? 'rotate-90' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                Options avancées
            </button>

            <div v-if="showAdvanced" class="flex flex-col gap-4 pl-4 border-l-2 border-base">
                <!-- Expiration -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-primary">Expiration</label>
                    <select
                        v-model="expiresIn"
                        class="block w-full rounded-lg border border-base bg-surface px-3 py-2 text-sm text-primary focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                    >
                        <option :value="1">1 jour</option>
                        <option :value="3">3 jours</option>
                        <option :value="7">7 jours</option>
                        <option :value="14">14 jours</option>
                        <option :value="30">30 jours</option>
                    </select>
                </div>

                <!-- Password -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-primary">Mot de passe</label>
                    <div class="relative">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Laisser vide pour aucun mot de passe"
                            class="block w-full rounded-lg border border-base bg-surface px-3 py-2 pr-10 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition"
                            v-on:click="showPassword = !showPassword"
                        >
                            <svg
                                v-if="!showPassword"
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <AppButton type="submit" size="lg" class="w-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Envoyer
        </AppButton>
    </form>
</template>
