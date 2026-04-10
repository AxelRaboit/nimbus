<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { X, Plus, Eye, EyeOff, Send, Link } from "lucide-vue-next";
import { isValidEmail, formatFileSize } from "@/utils/validation.js";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import DropZone from "./DropZone.vue";

const { t, locale } = useI18n();
const emit = defineEmits(["submit", "open-help"]);

const props = defineProps({
    prefillEmail:  { type: String,  default: "" },
    draft:         { type: Object,  default: null },
    maxFiles:      { type: Number,  default: 20 },
    maxRecipients: { type: Number,  default: 20 },
    maxSizeMb:     { type: Number,  default: 10000 },
    maxExpiryDays: { type: Number,  default: 7 },
    expiryOptions: { type: Array,   default: () => [24] },
});

const files = ref([]);
const recipients = ref([""]);
const senderEmail = ref(props.prefillEmail);
const senderName  = ref("");
const message     = ref("");
const expiresIn   = ref(24);
const password    = ref("");
const isPublic    = ref(false);

// When draft prop arrives (async), pre-fill the fields
watch(() => props.draft, (draft) => {
    if (!draft) return;
    if (draft.recipients?.length) recipients.value = [...draft.recipients];
    senderEmail.value = draft.senderEmail ?? props.prefillEmail;
    senderName.value  = draft.senderName ?? "";
    message.value     = draft.message ?? "";
    expiresIn.value   = draft.expiresIn ?? 24;
    password.value    = draft.password ?? "";
}, { immediate: true });

// Clamp expiresIn (hours) when maxExpiryDays changes
watch(() => props.maxExpiryDays, (max) => {
    if (expiresIn.value > max * 24) expiresIn.value = max * 24;
});

// Locked as long as draft is set and no files selected yet
const locked = computed(() => !!props.draft && files.value.length === 0);
const showPassword = ref(false);
const errors = ref({});

const validRecipients = computed(() =>
    recipients.value.filter((r) => r.trim() !== "")
);

function addRecipient() {
    if (recipients.value.length < props.maxRecipients) recipients.value.push("");
}

function removeRecipient(index) {
    recipients.value.splice(index, 1);
}

function validate() {
    const e = {};
    if (files.value.length === 0) {
        e.files = t("transfer.create.error_files");
    } else if (files.value.length > props.maxFiles) {
        e.files = t("transfer.create.error_max_files", { max: props.maxFiles });
    } else {
        const totalBytes = files.value.reduce((sum, f) => sum + (f.size ?? 0), 0);
        if (totalBytes > props.maxSizeMb * 1024 * 1024) {
            e.files = t("transfer.create.error_max_size", { max: formatFileSize(props.maxSizeMb, locale.value) });
        }
    }
    if (!isPublic.value) {
        if (!senderEmail.value.trim()) {
            e.senderEmail = t("transfer.create.error_email_required");
        } else if (!isValidEmail(senderEmail.value)) {
            e.senderEmail = t("transfer.create.error_email_invalid");
        }
        if (validRecipients.value.length === 0) {
            e.recipients = t("transfer.create.error_recipients_required");
        } else {
            const bad = validRecipients.value.find((r) => !isValidEmail(r));
            if (bad) e.recipients = t("transfer.create.error_recipient_invalid", { email: bad });
        }
    }
    errors.value = e;
    return Object.keys(e).length === 0;
}

function submit() {
    if (!validate()) return;
    emit("submit", {
        files: files.value,
        isPublic: isPublic.value,
        senderEmail: isPublic.value ? null : senderEmail.value.trim(),
        senderName: isPublic.value ? null : senderName.value.trim(),
        recipients: isPublic.value ? [] : validRecipients.value.map((r) => r.trim()),
        message: message.value.trim(),
        expiresIn: expiresIn.value,
        password: password.value,
    });
}
</script>

<template>
    <form novalidate class="space-y-5" v-on:submit.prevent="submit">
        <!-- Files -->
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('transfer.create.files_label') }}</label>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted">{{ t('transfer.create.max_size', { max: formatFileSize(Number(props.maxSizeMb), locale) }) }}</span>
                    <button type="button" class="text-xs text-link hover:text-link-hover transition-colors" v-on:click="emit('open-help')">{{ t('transfer.create.how_it_works') }}</button>
                </div>
            </div>
            <DropZone v-model:files="files" />
            <p v-if="errors.files" class="text-xs text-red-500">{{ errors.files }}</p>
        </div>

        <!-- Mode toggle -->
        <div class="flex flex-col gap-1.5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('transfer.create.mode_label') }}</label>
            <div class="flex rounded-lg border border-base overflow-hidden text-sm">
                <button
                    type="button"
                    class="flex-1 px-3 py-2 font-medium transition-colors"
                    :class="!isPublic ? 'bg-indigo-600 text-white' : 'bg-surface text-secondary hover:bg-surface-2'"
                    v-on:click="isPublic = false"
                >
                    {{ t('transfer.create.mode_email') }}
                </button>
                <button
                    type="button"
                    class="flex-1 px-3 py-2 font-medium transition-colors flex items-center justify-center gap-1.5"
                    :class="isPublic ? 'bg-indigo-600 text-white' : 'bg-surface text-secondary hover:bg-surface-2'"
                    v-on:click="isPublic = true"
                >
                    <Link class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('transfer.create.mode_link') }}
                </button>
            </div>
        </div>

        <!-- Recipients -->
        <div v-if="!isPublic" class="flex flex-col gap-1.5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <label class="block text-xs text-secondary uppercase tracking-wide">
                {{ t('transfer.create.recipients_label') }}
                <span class="text-red-500 ml-0.5">*</span>
            </label>
            <div class="flex flex-col gap-2">
                <div v-for="(_, index) in recipients" :key="index" class="flex items-center gap-2">
                    <input
                        v-model="recipients[index]"
                        type="email"
                        :placeholder="`destinataire${index + 1}@exemple.com`"
                        class="block w-full rounded-md border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.recipients && index === 0 }"
                    >
                    <button
                        v-if="recipients.length > 1"
                        type="button"
                        class="text-muted hover:text-red-500 transition shrink-0"
                        v-on:click="removeRecipient(index)"
                    >
                        <X class="w-4 h-4" :stroke-width="2" />
                    </button>
                </div>
            </div>
            <p v-if="errors.recipients" class="text-xs text-red-500">{{ errors.recipients }}</p>
            <button
                v-if="recipients.length < props.maxRecipients"
                type="button"
                class="self-start text-xs text-link hover:text-link-hover font-medium flex items-center gap-1 transition-colors"
                v-on:click="addRecipient"
            >
                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                {{ t('transfer.create.add_recipient') }}
            </button>
        </div>

        <!-- Sender -->
        <div v-if="!isPublic" class="grid grid-cols-1 sm:grid-cols-2 gap-4" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <AppInput
                v-model="senderEmail"
                type="email"
                :label="t('transfer.create.sender_email')"
                :placeholder="t('transfer.create.sender_email_placeholder')"
                :error="errors.senderEmail"
                required
            />
            <AppInput
                v-model="senderName"
                :label="t('transfer.create.sender_name')"
                :placeholder="t('transfer.create.sender_name_placeholder')"
            />
        </div>

        <!-- Message -->
        <div class="flex flex-col gap-1.5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('transfer.create.message_label') }}</label>
            <textarea
                v-model="message"
                rows="3"
                :placeholder="t('transfer.create.message_placeholder')"
                class="block w-full rounded-md border border-base bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
            />
        </div>

        <!-- Advanced options -->
        <div class="space-y-5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <!-- Expiry -->
            <div class="flex flex-col gap-1.5">
                <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('transfer.create.expiry_label') }}</label>
                <select
                    v-model="expiresIn"
                    class="block w-full rounded-md border border-base bg-surface px-3 py-2 text-sm text-primary focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                >
                    <option v-for="h in props.expiryOptions" :key="h" :value="h">
                        {{ h < 24
                            ? t('transfer.create.expiry_hours', { n: h }, h)
                            : t('transfer.create.expiry_days', { n: h / 24 }, h / 24) }}
                    </option>
                </select>
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('transfer.create.password_label') }}</label>
                <div class="relative">
                    <input
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        :placeholder="t('transfer.create.password_placeholder')"
                        class="block w-full rounded-md border border-base bg-surface px-3 py-2 pr-10 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                    >
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition"
                        v-on:click="showPassword = !showPassword"
                    >
                        <Eye v-if="!showPassword" class="w-4 h-4" :stroke-width="2" />
                        <EyeOff v-else class="w-4 h-4" :stroke-width="2" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <AppButton type="submit" size="lg" class="w-full" :disabled="locked">
            <Send class="w-4 h-4" :stroke-width="2" />
            {{ t('transfer.create.submit') }}
        </AppButton>
    </form>
</template>
