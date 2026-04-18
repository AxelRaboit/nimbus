<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { X, Plus, Send, Link, HelpCircle } from "lucide-vue-next";
import { isValidEmail, formatFileSize } from "@/utils/validation.js";
import { required, email, compose } from "@/utils/validators.js";
import { useForm } from "@/composables/useForm.js";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppSelect from "@/components/AppSelect.vue";
import DropZone from "./DropZone.vue";

const { t: translate, locale } = useI18n();
const emit = defineEmits(["submit", "open-help"]);

const props = defineProps({
    prefillEmail:  { type: String,  default: "" },
    draft:         { type: Object,  default: null },
    serverErrors:  { type: Object,  default: () => ({}) },
    maxFiles:      { type: Number,  default: 20 },
    maxRecipients: { type: Number,  default: 20 },
    maxSizeMb:     { type: Number,  default: 10000 },
    maxExpiryDays: { type: Number,  default: 7 },
    expiryOptions: { type: Array,   default: () => [24] },
});

const files        = ref([]);
const recipients   = ref([""]);
const senderEmail  = ref(props.prefillEmail);
const senderName   = ref("");
const message      = ref("");
const expiresIn    = ref(1);
const password     = ref("");
const isPublic     = ref(false);

const { errors, validate, setErrors } = useForm();

watch(() => props.serverErrors, (errs) => {
    if (errs && Object.keys(errs).length > 0) setErrors(errs);
});

watch(() => props.draft, (draft) => {
    if (!draft) return;
    isPublic.value    = draft.isPublic ?? false;
    if (draft.recipients?.length) recipients.value = [...draft.recipients];
    senderEmail.value = draft.senderEmail ?? props.prefillEmail;
    senderName.value  = draft.senderName ?? "";
    message.value     = draft.message ?? "";
    expiresIn.value   = draft.expiresIn ?? 1;
    password.value    = draft.password ?? "";
}, { immediate: true });

watch(() => props.maxExpiryDays, (max) => {
    if (expiresIn.value > max * 24) expiresIn.value = max * 24;
});

const locked = computed(() => !!props.draft && files.value.length === 0);
const validRecipients = computed(() =>
    recipients.value.filter((recipient) => recipient.trim() !== "")
);

function addRecipient() {
    if (recipients.value.length < props.maxRecipients) recipients.value.push("");
}

function removeRecipient(index) {
    recipients.value.splice(index, 1);
}

function submit() {
    const isValid = validate({
        files: () => {
            if (files.value.length === 0) return translate("transfer.create.error_files");
            if (files.value.length > props.maxFiles) return translate("transfer.create.error_max_files", { max: props.maxFiles });
            const total = files.value.reduce((sum, file) => sum + (file.size ?? 0), 0);
            if (total > props.maxSizeMb * 1024 * 1024) return translate("transfer.create.error_max_size", { max: formatFileSize(props.maxSizeMb, locale.value) });
            return null;
        },
        senderEmail: () => isPublic.value ? null : compose(
            required(translate("transfer.create.error_email_required")),
            email(translate("transfer.create.error_email_invalid")),
        )(senderEmail.value),
        recipients: () => {
            if (isPublic.value) return null;
            if (validRecipients.value.length === 0) return translate("transfer.create.error_recipients_required");
            const firstInvalidEmail = validRecipients.value.find((recipient) => !isValidEmail(recipient));
            return firstInvalidEmail ? translate("transfer.create.error_recipient_invalid", { email: firstInvalidEmail }) : null;
        },
    });

    if (!isValid) return;

    emit("submit", {
        files: files.value,
        isPublic: isPublic.value,
        senderEmail: isPublic.value ? null : senderEmail.value.trim(),
        senderName: isPublic.value ? null : senderName.value.trim(),
        recipients: isPublic.value ? [] : validRecipients.value.map((recipient) => recipient.trim()),
        message: message.value.trim(),
        expiresIn: expiresIn.value,
        password: password.value,
    });
}
</script>

<template>
    <form novalidate class="space-y-5" v-on:submit.prevent="submit">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <label class="block text-xs text-secondary uppercase tracking-wide">{{ translate('transfer.create.files_label') }}</label>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted">
                        <span class="sm:hidden">{{ formatFileSize(Number(props.maxSizeMb), locale) }}</span>
                        <span class="hidden sm:inline">{{ translate('transfer.create.max_size', { max: formatFileSize(Number(props.maxSizeMb), locale) }) }}</span>
                    </span>
                    <button
                        type="button"
                        class="text-xs text-link hover:text-link-hover transition-colors flex items-center"
                        :aria-label="translate('transfer.create.how_it_works')"
                        v-on:click="emit('open-help')"
                    >
                        <HelpCircle class="w-4 h-4 sm:hidden" :stroke-width="2" />
                        <span class="hidden sm:inline">{{ translate('transfer.create.how_it_works') }}</span>
                    </button>
                </div>
            </div>
            <DropZone v-model:files="files" />
            <p v-if="errors.files" class="text-xs text-red-500">{{ errors.files }}</p>
        </div>

        <div class="flex flex-col gap-1.5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <label class="block text-xs text-secondary uppercase tracking-wide">{{ translate('transfer.create.mode_label') }}</label>
            <div class="flex rounded-lg border border-line overflow-hidden text-sm">
                <button
                    type="button"
                    class="flex-1 px-3 py-2 font-medium transition-colors"
                    :class="!isPublic ? 'bg-indigo-600 text-white' : 'bg-surface text-secondary hover:bg-surface-2'"
                    v-on:click="isPublic = false"
                >
                    {{ translate('transfer.create.mode_email') }}
                </button>
                <button
                    type="button"
                    class="flex-1 px-3 py-2 font-medium transition-colors flex items-center justify-center gap-1.5"
                    :class="isPublic ? 'bg-indigo-600 text-white' : 'bg-surface text-secondary hover:bg-surface-2'"
                    v-on:click="isPublic = true"
                >
                    <Link class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ translate('transfer.create.mode_link') }}
                </button>
            </div>
        </div>

        <div v-if="!isPublic" class="flex flex-col gap-1.5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <label class="block text-xs text-secondary uppercase tracking-wide">
                {{ translate('transfer.create.recipients_label') }}
                <span class="text-red-500 ml-0.5">*</span>
            </label>
            <div class="flex flex-col gap-2">
                <div v-for="(_, index) in recipients" :key="index" class="flex items-center gap-2">
                    <input
                        v-model="recipients[index]"
                        type="email"
                        :placeholder="`destinataire${index + 1}@exemple.com`"
                        class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
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
                {{ translate('transfer.create.add_recipient') }}
            </button>
        </div>

        <div v-if="!isPublic" class="grid grid-cols-1 sm:grid-cols-2 gap-4" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <AppInput
                v-model="senderEmail"
                type="email"
                :label="translate('transfer.create.sender_email')"
                :placeholder="translate('transfer.create.sender_email_placeholder')"
                :error="errors.senderEmail"
                required
            />
            <AppInput
                v-model="senderName"
                :label="translate('transfer.create.sender_name')"
                :placeholder="translate('transfer.create.sender_name_placeholder')"
            />
        </div>

        <AppTextarea
            v-model="message"
            :label="translate('transfer.create.message_label')"
            :placeholder="translate('transfer.create.message_placeholder')"
            :class="{ 'opacity-40 pointer-events-none select-none': locked }"
        />

        <div class="space-y-5" :class="{ 'opacity-40 pointer-events-none select-none': locked }">
            <AppSelect v-model="expiresIn" :label="translate('transfer.create.expiry_label')">
                <option v-for="hours in props.expiryOptions" :key="hours" :value="hours">
                    {{ hours < 24
                        ? translate('transfer.create.expiry_hours', { n: hours }, hours)
                        : translate('transfer.create.expiry_days', { n: hours / 24 }, hours / 24) }}
                </option>
            </AppSelect>

            <AppInput
                v-model="password"
                :label="translate('transfer.create.password_label')"
                :placeholder="translate('transfer.create.password_placeholder')"
                toggleable
            />
        </div>

        <AppButton type="submit" size="lg" class="w-full" :disabled="locked">
            <Send class="w-4 h-4" :stroke-width="2" />
            {{ translate('transfer.create.submit') }}
        </AppButton>
    </form>
</template>
