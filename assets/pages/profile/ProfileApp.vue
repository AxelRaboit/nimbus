<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";

const { t } = useI18n();

const props = defineProps({
    userName: { type: String, default: "" },
    userEmail: { type: String, default: "" },
    locale: { type: String, default: "fr" },
    updatePath: { type: String, required: true },
    passwordPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    localePath: { type: String, required: true },
    deleteCsrf: { type: String, required: true },
    loginPath: { type: String, required: true },
});

const selectedLocale = ref(props.locale);

async function changeLocale() {
    await fetch(props.localePath, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ locale: selectedLocale.value }),
    });
    window.location.reload();
}

const infoName = ref(props.userName);
const infoEmail = ref(props.userEmail);
const infoErrors = ref({});
const infoSuccess = ref(false);
const infoLoading = ref(false);

async function saveInfo() {
    infoErrors.value = {};
    infoSuccess.value = false;
    infoLoading.value = true;
    try {
        const res = await fetch(props.updatePath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name: infoName.value, email: infoEmail.value }),
        });
        const data = await res.json();
        if (data.success) {
            infoSuccess.value = true;
        } else {
            infoErrors.value = data.errors || {};
        }
    } catch {
        infoErrors.value = { name: t('profile.info.saved') };
    } finally {
        infoLoading.value = false;
    }
}

const currentPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");
const passwordErrors = ref({});
const passwordSuccess = ref(false);
const passwordLoading = ref(false);

async function savePassword() {
    passwordErrors.value = {};
    passwordSuccess.value = false;
    passwordLoading.value = true;
    try {
        const res = await fetch(props.passwordPath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: confirmPassword.value,
            }),
        });
        const data = await res.json();
        if (data.success) {
            passwordSuccess.value = true;
            currentPassword.value = "";
            newPassword.value = "";
            confirmPassword.value = "";
        } else {
            passwordErrors.value = data.errors || {};
        }
    } catch {
        passwordErrors.value = { current_password: t('profile.password.error_current') };
    } finally {
        passwordLoading.value = false;
    }
}

const deleteLoading = ref(false);

async function deleteAccount() {
    if (!confirm(t('profile.danger.confirm'))) return;
    deleteLoading.value = true;
    try {
        const res = await fetch(props.deletePath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ _token: props.deleteCsrf }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = props.loginPath;
        }
    } catch {
    } finally {
        deleteLoading.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-surface border border-base/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('profile.locale.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('profile.locale.subtitle') }}</p>
            </header>
            <div>
                <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.locale.field') }}</label>
                <select
                    v-model="selectedLocale"
                    class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-base focus:border-indigo-500 focus:outline-none transition"
                    v-on:change="changeLocale"
                >
                    <option value="fr">{{ t('locales.fr') }}</option>
                    <option value="en">{{ t('locales.en') }}</option>
                    <option value="es">{{ t('locales.es') }}</option>
                    <option value="de">{{ t('locales.de') }}</option>
                </select>
            </div>
        </div>

        <div class="bg-surface border border-base/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('profile.info.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('profile.info.subtitle') }}</p>
            </header>

            <div v-if="infoSuccess" class="mb-4 text-sm text-emerald-400">{{ t('profile.info.saved') }}</div>

            <form class="space-y-5" v-on:submit.prevent="saveInfo">
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.info.name') }}</label>
                    <input
                        v-model="infoName"
                        type="text"
                        required
                        autocomplete="name"
                        :class="['w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border focus:border-indigo-500 focus:outline-none transition', infoErrors.name ? 'border-red-500' : 'border-base']"
                    >
                    <p v-if="infoErrors.name" class="mt-1 text-xs text-rose-400">{{ infoErrors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.info.email') }}</label>
                    <input
                        v-model="infoEmail"
                        type="email"
                        required
                        autocomplete="email"
                        :class="['w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border focus:border-indigo-500 focus:outline-none transition', infoErrors.email ? 'border-red-500' : 'border-base']"
                    >
                    <p v-if="infoErrors.email" class="mt-1 text-xs text-rose-400">{{ infoErrors.email }}</p>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <AppButton type="submit" :loading="infoLoading">
                        {{ t('common.save') }}
                    </AppButton>
                </div>
            </form>
        </div>

        <div class="bg-surface border border-base/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('profile.password.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('profile.password.subtitle') }}</p>
            </header>

            <div v-if="passwordSuccess" class="mb-4 text-sm text-emerald-400">{{ t('profile.password.saved') }}</div>

            <form class="space-y-5" v-on:submit.prevent="savePassword">
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.current') }}</label>
                    <input
                        v-model="currentPassword"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        :class="['w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border focus:border-indigo-500 focus:outline-none transition', passwordErrors.current_password ? 'border-red-500' : 'border-base']"
                    >
                    <p v-if="passwordErrors.current_password" class="mt-1 text-xs text-rose-400">{{ passwordErrors.current_password }}</p>
                </div>

                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.new') }}</label>
                    <input
                        v-model="newPassword"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :class="['w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border focus:border-indigo-500 focus:outline-none transition', passwordErrors.password ? 'border-red-500' : 'border-base']"
                    >
                    <p v-if="passwordErrors.password" class="mt-1 text-xs text-rose-400">{{ passwordErrors.password }}</p>
                </div>

                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.confirm') }}</label>
                    <input
                        v-model="confirmPassword"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :class="['w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border focus:border-indigo-500 focus:outline-none transition', passwordErrors.password_confirmation ? 'border-red-500' : 'border-base']"
                    >
                    <p v-if="passwordErrors.password_confirmation" class="mt-1 text-xs text-rose-400">{{ passwordErrors.password_confirmation }}</p>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <AppButton type="submit" :loading="passwordLoading">
                        {{ t('common.save') }}
                    </AppButton>
                </div>
            </form>
        </div>

        <div class="bg-surface border border-rose-900/40 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-rose-400">{{ t('profile.danger.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('profile.danger.description') }}</p>
            </header>

            <button
                type="button"
                :disabled="deleteLoading"
                class="px-4 py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-600/40 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                v-on:click="deleteAccount"
            >
                {{ t('profile.danger.submit') }}
            </button>
        </div>
    </div>
</template>
