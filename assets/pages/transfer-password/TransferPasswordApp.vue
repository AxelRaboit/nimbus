<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Lock } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";

const { t: translate } = useI18n();

const props = defineProps({
    token: { type: String, required: true },
    unlockPath: { type: String, required: true },
    csrfToken: { type: String, required: true },
});

const password = ref("");
const error = ref("");
const loading = ref(false);

async function submit() {
    error.value = "";
    loading.value = true;
    try {
        const response = await fetch(props.unlockPath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ password: password.value, _token: props.csrfToken }),
        });
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            error.value = data.error || translate('transfer_password.error');
        }
    } catch {
        error.value = translate('transfer_password.error');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex justify-center">
        <div class="w-full max-w-sm flex flex-col gap-6">
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
                    <Lock class="w-7 h-7 text-indigo-600" />
                </div>
                <h1 class="text-2xl font-bold text-primary">{{ translate('transfer_password.title') }}</h1>
                <p class="text-sm text-muted mt-1">{{ translate('transfer_password.subtitle') }}</p>
            </div>

            <div class="rounded-lg border border-line bg-surface shadow-lg shadow-indigo-500/10 p-6">
                <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ error }}
                </div>

                <form class="flex flex-col gap-4" v-on:submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label class="block text-xs text-secondary uppercase tracking-wide">{{ translate('transfer_password.label') }}</label>
                        <input
                            v-model="password"
                            type="password"
                            autofocus
                            required
                            class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            placeholder="••••••••"
                        >
                    </div>

                    <AppButton type="submit" :loading="loading" class="w-full">
                        {{ translate('transfer_password.submit') }}
                    </AppButton>
                </form>
            </div>
        </div>
    </div>
</template>
