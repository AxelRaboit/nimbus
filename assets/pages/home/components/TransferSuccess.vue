<script setup>
import { ref } from "vue";
import AppButton from "@/components/AppButton.vue";

const props = defineProps({
    reference: { type: String, required: true },
    manageUrl: { type: String, required: true },
});

const copied = ref(false);

async function copyManageLink() {
    try {
        await navigator.clipboard.writeText(props.manageUrl);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // fallback: select the input
    }
}
</script>

<template>
    <div class="flex flex-col items-center gap-6 text-center py-4">
        <!-- Icon -->
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <!-- Title -->
        <div>
            <h2 class="text-lg font-semibold text-primary">Transfert envoyé !</h2>
            <p class="text-sm text-muted mt-1">
                Vos destinataires vont recevoir un email avec le lien de téléchargement.
            </p>
        </div>

        <!-- Reference -->
        <div class="bg-surface-2 rounded-xl px-6 py-3">
            <p class="text-xs text-muted mb-1">Référence du transfert</p>
            <p class="text-2xl font-bold text-primary tracking-widest">{{ reference }}</p>
        </div>

        <!-- Manage link -->
        <div class="w-full flex flex-col gap-2">
            <p class="text-xs text-muted">
                Conservez ce lien pour gérer ou supprimer votre transfert
            </p>
            <div class="flex items-center gap-2">
                <input
                    :value="manageUrl"
                    readonly
                    class="block w-full rounded-lg border border-base bg-surface px-3 py-2 text-sm text-primary focus:outline-none truncate"
                    v-on:click="$event.target.select()"
                >
                <AppButton variant="secondary" size="md" class="shrink-0" v-on:click="copyManageLink">
                    <svg
                        v-if="!copied"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ copied ? 'Copié !' : 'Copier' }}
                </AppButton>
            </div>
        </div>

        <!-- New transfer -->
        <AppButton variant="ghost" size="sm" v-on:click="$emit('reset')">
            Envoyer un nouveau transfert
        </AppButton>
    </div>
</template>
