<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { Check, FileText, Settings, UploadCloud } from "lucide-vue-next";
import { useFileSize } from "@/composables/useFileSize.js";
import { useDateFormat } from "@/composables/useDateFormat.js";
import AppButton from "@/components/AppButton.vue";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDate } = useDateFormat();

const LIMIT = 10;

const transfers = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const hasMore = ref(false);
const error = ref(null);

async function fetchTransfers(offset = 0) {
    const res = await fetch(`/api/transfers?offset=${offset}`);
    if (!res.ok) throw new Error();
    return res.json();
}

onMounted(async () => {
    try {
        const data = await fetchTransfers(0);
        transfers.value = data.items;
        hasMore.value = data.hasMore;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
});

async function loadMore() {
    loadingMore.value = true;
    try {
        const data = await fetchTransfers(transfers.value.length);
        transfers.value.push(...data.items);
        hasMore.value = data.hasMore;
    } catch {
        error.value = true;
    } finally {
        loadingMore.value = false;
    }
}

const statusClass = {
    pending: "bg-badge-warning-bg text-badge-warning-text",
    ready:   "bg-badge-success-bg text-badge-success-text",
    expired: "bg-surface-2 text-muted",
    deleted: "bg-badge-danger-bg text-badge-danger-text",
};

function manageUrl(ownerToken) {
    return `/manage/${ownerToken}`;
}

function downloadedCount(recipients) {
    return recipients.filter((r) => r.downloaded).length;
}

function totalSize(files) {
    return files.reduce((acc, f) => acc + f.size, 0);
}
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <div v-if="loading" class="flex items-center justify-center py-20 text-muted text-sm">
            {{ t('dashboard.loading') }}
        </div>

        <div v-else-if="error" class="text-sm text-red-500 py-10 text-center">
            {{ t('dashboard.error') }}
        </div>

        <div v-else-if="transfers.length === 0" class="flex flex-col items-center justify-center py-20 gap-4 text-center">
            <div class="w-14 h-14 rounded-full bg-surface-2 flex items-center justify-center">
                <UploadCloud class="w-6 h-6 text-muted" :stroke-width="1.5" />
            </div>
            <div>
                <p class="text-sm font-medium text-primary">{{ t('dashboard.empty_title') }}</p>
                <p class="text-xs text-muted mt-1">{{ t('dashboard.empty_desc') }}</p>
            </div>
            <a href="/" class="text-xs text-link hover:text-link-hover font-medium transition-colors">
                {{ t('dashboard.new_transfer') }}
            </a>
        </div>

        <div v-else class="flex flex-col gap-3">
            <div
                v-for="transfer in transfers"
                :key="transfer.reference"
                class="rounded-xl border border-base bg-surface p-4 flex flex-col sm:flex-row sm:items-center gap-4"
            >
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm font-bold text-primary tracking-widest">{{ transfer.reference }}</span>
                        <span
                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                            :class="statusClass[transfer.status] ?? 'bg-surface-2 text-muted'"
                        >
                            {{ t(`transfer.status.${transfer.status}`, transfer.status) }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
                        <span class="flex items-center gap-1">
                            <FileText class="w-3.5 h-3.5 shrink-0" :stroke-width="2" />
                            {{ transfer.files.length }} · {{ formatSize(totalSize(transfer.files)) }}
                        </span>

                        <span v-if="transfer.isPublic">
                            {{ t('dashboard.public_link') }} · {{ transfer.publicDownloadCount }} {{ t('dashboard.downloads') }}
                        </span>
                        <span v-else class="flex items-center gap-1">
                            <Check class="w-3.5 h-3.5 shrink-0" :stroke-width="2" />
                            {{ downloadedCount(transfer.recipients) }}/{{ transfer.recipients.length }} {{ t('dashboard.downloaded') }}
                        </span>

                        <span>{{ t('dashboard.expires') }} {{ formatDate(transfer.expiresAt) }}</span>
                    </div>
                </div>

                <a
                    :href="manageUrl(transfer.ownerToken)"
                    class="shrink-0 flex items-center gap-1.5 text-xs font-medium text-secondary hover:text-primary border border-base rounded-lg px-3 py-2 transition-colors hover:bg-surface-2"
                >
                    <Settings class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('dashboard.manage') }}
                </a>
            </div>

            <div v-if="hasMore" class="flex justify-center pt-2">
                <AppButton variant="secondary" size="sm" :loading="loadingMore" v-on:click="loadMore">
                    {{ t('dashboard.load_more') }}
                </AppButton>
            </div>
        </div>
    </div>
</template>
