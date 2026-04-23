<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Check, X, Sparkles } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import { formatFileSize } from "@/utils/validation.js";
import { submitForm } from "@/utils/formSubmit.js";

const { t, locale } = useI18n();

const props = defineProps({
    isPro:               { type: Boolean, default: false },
    trialEndsAt:            { type: String,  default: null },
    proPrice:            { type: Number,  default: 9.99 },
    freeMaxSizeMb:       { type: Number,  default: 100 },
    freeMaxFiles:        { type: Number,  default: 3 },
    freeMaxExpiryHours:  { type: Number,  default: 24 },
    proMaxSizeMb:        { type: Number,  default: 10000 },
    proMaxFiles:         { type: Number,  default: 20 },
    proMaxExpiryDays:    { type: Number,  default: 7 },
    upgradePath:         { type: String,  required: true },
    downgradePath:       { type: String,  required: true },
    csrfToken:           { type: String,  required: true },
});

const upgradeLoading   = ref(false);
const downgradeLoading = ref(false);

const trialEndsAtFormatted = computed(() => {
    if (!props.trialEndsAt) return null;
    return new Intl.DateTimeFormat(locale.value, { day: "numeric", month: "long", year: "numeric" }).format(new Date(props.trialEndsAt));
});

function upgrade() {
    upgradeLoading.value = true;
    submitForm(props.upgradePath, props.csrfToken);
}

function downgrade() {
    downgradeLoading.value = true;
    submitForm(props.downgradePath, props.csrfToken);
}
</script>

<template>
    <div class="mx-auto max-w-4xl space-y-8">
        <p class="text-center text-secondary text-sm">
            {{ t("plan.subtitle") }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Free card -->
            <div class="bg-surface border border-line/60 rounded-xl p-6 flex flex-col relative">
                <span
                    v-if="!isPro"
                    class="absolute top-4 right-4 text-xs font-bold bg-indigo-600/15 text-indigo-400 px-2 py-0.5 rounded-full"
                >
                    {{ t("plan.currentPlan") }}
                </span>

                <h2 class="text-xl font-bold text-primary">{{ t("plan.free.name") }}</h2>

                <p class="text-3xl font-extrabold text-primary mt-2">
                    {{ t("plan.free.price") }}
                    <span class="text-base font-normal text-muted">/{{ t("plan.perMonth") }}</span>
                </p>

                <p class="text-secondary text-sm mt-1">{{ t("plan.free.tagline") }}</p>

                <ul class="mt-6 space-y-3 flex-1">
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxSize", { size: formatFileSize(freeMaxSizeMb, locale) }) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxFiles", freeMaxFiles) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxExpiry", freeMaxExpiryHours) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.password") }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.message") }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <X class="w-4 h-4 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t("plan.features.myTransfers") }}</span>
                    </li>
                </ul>

                <div class="mt-6">
                    <AppButton
                        v-if="isPro"
                        variant="link"
                        size="none"
                        :loading="downgradeLoading"
                        class="text-sm"
                        v-on:click="downgrade"
                    >
                        {{ t("plan.downgradeFree") }}
                    </AppButton>
                    <div v-else class="h-9" />
                </div>
            </div>

            <!-- Pro card -->
            <div class="bg-surface border-2 border-indigo-600/60 rounded-xl p-6 flex flex-col relative shadow-lg shadow-indigo-500/10">
                <span
                    v-if="isPro"
                    class="absolute top-4 right-4 text-xs font-bold bg-indigo-600/15 text-indigo-400 px-2 py-0.5 rounded-full"
                >
                    {{ t("plan.currentPlan") }}
                </span>
                <span
                    v-else
                    class="absolute top-4 right-4 text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full"
                >
                    {{ t("plan.pro.name") }}
                </span>

                <h2 class="text-xl font-bold text-primary">{{ t("plan.pro.name") }}</h2>

                <p class="text-3xl font-extrabold text-primary mt-2">
                    {{ t("plan.pro.price") }}
                    <span class="text-base font-normal text-muted">/{{ t("plan.perMonth") }}</span>
                </p>

                <p class="text-secondary text-sm mt-1">{{ t("plan.pro.tagline") }}</p>

                <ul class="mt-6 space-y-3 flex-1">
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxSize", { size: formatFileSize(proMaxSizeMb, locale) }) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxFiles", proMaxFiles) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.maxExpiryDays", proMaxExpiryDays) }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.password") }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.message") }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t("plan.features.myTransfers") }}</span>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm">
                        <Sparkles class="w-4 h-4 shrink-0 text-indigo-400" />
                        <span class="text-indigo-400 italic">{{ t("plan.pro.tagline") }}</span>
                    </li>
                </ul>

                <div class="mt-6">
                    <div v-if="!isPro">
                        <AppButton
                            class="w-full"
                            :loading="upgradeLoading"
                            v-on:click="upgrade"
                        >
                            {{ t("plan.upgradeCta") }}
                        </AppButton>
                        <p class="text-xs text-muted text-center mt-2">
                            🔒 {{ t("plan.stripeSoon") }}
                        </p>
                    </div>
                    <div v-else class="text-center py-2">
                        <p class="text-sm text-indigo-400 font-medium">{{ t("plan.alreadyPro") }}</p>
                        <p v-if="trialEndsAtFormatted" class="text-xs text-muted mt-1">{{ t("plan.trialUntil", { date: trialEndsAtFormatted }) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-muted">
            {{ t("plan.disclaimer") }}
        </p>
    </div>
</template>
