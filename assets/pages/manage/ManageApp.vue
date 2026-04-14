<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { FileText, Check, Trash2, Copy, Link, Send } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import AppQrCode from "@/components/AppQrCode.vue";
import { useFileSize } from "@/composables/useFileSize.js";
import { useDateFormat } from "@/composables/useDateFormat.js";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDate } = useDateFormat();

const props = defineProps({
    ownerToken: { type: String, required: true },
    reference: { type: String, required: true },
    status: { type: String, required: true },
    expiresAt: { type: String, required: true },
    files: { type: String, default: "[]" },
    recipients: { type: String, default: "[]" },
    csrfToken: { type: String, default: "" },
    isPublic: { type: Boolean, default: false },
    publicDownloadCount: { type: Number, default: 0 },
    transferToken: { type: String, default: "" },
});

const parsedFiles = computed(() => JSON.parse(props.files));
const parsedRecipients = computed(() => JSON.parse(props.recipients));
const publicMode = computed(() => props.isPublic);
const downloadCount = computed(() => props.publicDownloadCount);
const downloadUrl = computed(() =>
    props.transferToken ? `${window.location.origin}/t/${props.transferToken}` : ""
);

const expiresDate = computed(() => formatDate(props.expiresAt));

const copiedLink = ref(false);
async function copyLink(text, flag) {
    try {
        await navigator.clipboard.writeText(text);
        flag.value = true;
        setTimeout(() => (flag.value = false), 2000);
    } catch {}
}

const copiedDownload = ref(false);

const totalSize = computed(() =>
    parsedFiles.value.reduce((acc, f) => acc + f.size, 0)
);

const statusClass = {
    pending: "bg-badge-warning-bg text-badge-warning-text",
    ready: "bg-badge-success-bg text-badge-success-text",
    expired: "bg-surface-2 text-muted",
    deleted: "bg-badge-danger-bg text-badge-danger-text",
};

const confirmDelete = ref(false);
const deleting = ref(false);
const deleteUrl = computed(() => `/manage/${props.ownerToken}/delete`);

const reminding = ref(new Set());
const reminded = ref(new Set());

async function remind(email) {
    if (reminding.value.has(email) || reminded.value.has(email)) return;
    reminding.value = new Set([...reminding.value, email]);
    try {
        await fetch(`/api/manage/${props.ownerToken}/remind`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email }),
        });
        reminded.value = new Set([...reminded.value, email]);
    } finally {
        reminding.value = new Set([...reminding.value].filter((e) => e !== email));
    }
}

function submitDeleteForm(e) {
    deleting.value = true;
    e.currentTarget.submit();
}
</script>

<template>
    <div class="max-w-xl mx-auto flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ t('transfer.manage.title') }}</h1>
            <p class="text-sm text-muted mt-1">{{ t('transfer.manage.subtitle') }}</p>
        </div>

        <div class="rounded-lg border border-line bg-surface shadow-lg shadow-indigo-500/10 overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between border-b border-line">
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wide mb-0.5">{{ t('transfer.manage.reference') }}</p>
                    <p class="text-xl font-bold text-primary tracking-widest">{{ reference }}</p>
                </div>
                <span
                    class="text-xs font-bold px-2.5 py-1 rounded-full"
                    :class="statusClass[status] ?? 'bg-surface-2 text-muted'"
                >
                    {{ t(`transfer.status.${status}`, status) }}
                </span>
            </div>

            <div class="px-5 py-3 border-b border-line flex flex-wrap items-center gap-x-6 gap-y-2 text-sm bg-surface-2">
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wide mb-0.5">{{ t('transfer.manage.expires') }}</p>
                    <p class="font-medium text-primary">{{ expiresDate }}</p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wide mb-0.5">{{ t('transfer.manage.files') }}</p>
                    <p class="font-medium text-primary">{{ parsedFiles.length }} · {{ formatSize(totalSize) }}</p>
                </div>
                <div v-if="!publicMode">
                    <p class="text-xs text-secondary uppercase tracking-wide mb-0.5">{{ t('transfer.manage.recipients') }}</p>
                    <p class="font-medium text-primary">{{ parsedRecipients.length }}</p>
                </div>
            </div>

            <div class="px-5 py-3 border-b border-line">
                <p class="text-xs font-bold text-secondary uppercase tracking-wide mb-2">{{ t('transfer.manage.files') }}</p>
                <ul class="flex flex-col gap-1.5">
                    <li v-for="(file, index) in parsedFiles" :key="index" class="flex items-center gap-2 text-sm">
                        <FileText class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                        <span class="truncate text-primary font-medium">{{ file.name }}</span>
                        <span class="text-muted shrink-0">{{ formatSize(file.size) }}</span>
                    </li>
                </ul>
            </div>

            <div class="px-5 py-4 border-b border-line flex justify-center">
                <AppQrCode :url="downloadUrl" :size="140" />
            </div>

            <div v-if="publicMode" class="px-5 py-3 border-b border-line">
                <p class="text-xs font-bold text-secondary uppercase tracking-wide mb-2">{{ t('transfer.manage.public_link') }}</p>
                <div class="flex items-center gap-2">
                    <input
                        :value="downloadUrl"
                        readonly
                        class="block w-full rounded border border-line bg-surface px-3 py-2 text-sm text-primary focus:outline-none truncate min-w-0"
                        v-on:click="$event.target.select()"
                    >
                    <AppButton variant="secondary" size="sm" class="shrink-0" v-on:click="copyLink(downloadUrl, copiedLink)">
                        <Check v-if="copiedLink" class="w-4 h-4 text-green-500" :stroke-width="2" />
                        <Copy v-else class="w-4 h-4" :stroke-width="2" />
                    </AppButton>
                </div>
                <p class="text-xs text-muted mt-2 flex items-center gap-1">
                    <Link class="w-3.5 h-3.5 shrink-0" :stroke-width="2" />
                    {{ t('transfer.manage.public_downloads', { count: downloadCount }) }}
                </p>
            </div>

            <div v-if="!publicMode" class="px-5 py-3 border-b border-line">
                <p class="text-xs font-bold text-secondary uppercase tracking-wide mb-2">{{ t('transfer.manage.download_link') }}</p>
                <div class="flex items-center gap-2">
                    <input
                        :value="downloadUrl"
                        readonly
                        class="block w-full rounded border border-line bg-surface px-3 py-2 text-sm text-primary focus:outline-none truncate min-w-0"
                        v-on:click="$event.target.select()"
                    >
                    <AppButton variant="secondary" size="sm" class="shrink-0" v-on:click="copiedDownload ? null : copyLink(downloadUrl, copiedDownload)">
                        <Check v-if="copiedDownload" class="w-4 h-4 text-green-500" :stroke-width="2" />
                        <Copy v-else class="w-4 h-4" :stroke-width="2" />
                    </AppButton>
                </div>
            </div>

            <div v-if="!publicMode" class="px-5 py-3">
                <p class="text-xs font-bold text-secondary uppercase tracking-wide mb-2">{{ t('transfer.manage.recipients') }}</p>
                <p v-if="parsedRecipients.length === 0" class="text-sm text-muted">{{ t('transfer.manage.no_recipients') }}</p>
                <ul v-else class="flex flex-col gap-1.5 mb-2">
                    <li v-for="(recipient, index) in parsedRecipients" :key="index" class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2 text-sm">
                        <span class="truncate text-primary">{{ recipient.email }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <AppButton
                                v-if="!recipient.downloaded && status === 'ready'"
                                variant="secondary"
                                size="sm"
                                :disabled="reminding.has(recipient.email) || reminded.has(recipient.email)"
                                v-on:click="remind(recipient.email)"
                            >
                                <Check v-if="reminded.has(recipient.email)" class="w-3 h-3 text-green-500" :stroke-width="2.5" />
                                <Send v-else class="w-3 h-3" :stroke-width="2" />
                                {{ reminded.has(recipient.email) ? t('transfer.manage.remind_sent') : t('transfer.manage.remind') }}
                            </AppButton>
                            <span
                                class="text-xs font-bold flex items-center gap-1 px-2 py-0.5 rounded-full"
                                :class="recipient.downloaded ? 'bg-badge-success-bg text-badge-success-text' : 'bg-surface-2 text-muted'"
                            >
                                <Check v-if="recipient.downloaded" class="w-3 h-3" :stroke-width="2.5" />
                                {{ recipient.downloaded ? t('transfer.manage.downloaded') : t('transfer.manage.pending_download') }}
                            </span>
                        </div>
                    </li>
                </ul>
                <p v-if="parsedRecipients.some(r => !r.downloaded) && status === 'ready'" class="text-xs text-muted flex items-center gap-1">
                    <Send class="w-3 h-3 shrink-0" :stroke-width="2" />
                    {{ t('transfer.manage.auto_reminder_hint') }}
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-900/40 bg-surface p-6">
            <h2 class="text-lg font-semibold text-rose-400 mb-1">{{ t('transfer.manage.danger_zone') }}</h2>
            <p class="text-sm text-secondary mt-1 mb-4">{{ t('transfer.manage.danger_description') }}</p>

            <div v-if="!confirmDelete">
                <AppButton variant="danger" size="sm" v-on:click="confirmDelete = true">
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                    {{ t('transfer.manage.delete_btn') }}
                </AppButton>
            </div>

            <div v-else class="flex flex-col sm:flex-row sm:items-center gap-3">
                <p class="text-sm text-primary font-medium">{{ t('transfer.manage.confirm_delete') }}</p>
                <form :action="deleteUrl" method="POST" class="flex items-center gap-2" v-on:submit.prevent="submitDeleteForm">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <AppButton
                        type="submit"
                        variant="danger"
                        size="sm"
                        :loading="deleting"
                    >
                        {{ t('transfer.manage.confirm_yes') }}
                    </AppButton>
                    <AppButton type="button" variant="secondary" size="sm" v-on:click="confirmDelete = false">
                        {{ t('transfer.manage.cancel') }}
                    </AppButton>
                </form>
            </div>
        </div>
    </div>
</template>
