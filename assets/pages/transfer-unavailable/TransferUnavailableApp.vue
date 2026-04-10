<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Clock, Trash2, AlertCircle } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    status: { type: String, required: true },
    homePath: { type: String, default: "/" },
});

const isExpired = computed(() => props.status === "expired");
const isDeleted = computed(() => props.status === "deleted");
</script>

<template>
    <div class="flex justify-center py-10">
        <div class="w-full max-w-md text-center flex flex-col items-center gap-5">
            <template v-if="isExpired">
                <div class="w-16 h-16 rounded-full bg-surface-2 flex items-center justify-center">
                    <Clock class="w-8 h-8 text-muted" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-primary">{{ t('transfer_unavailable.expired_title') }}</h1>
                    <p class="text-sm text-muted mt-1">{{ t('transfer_unavailable.expired_desc') }}</p>
                </div>
            </template>

            <template v-else-if="isDeleted">
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
                    <Trash2 class="w-8 h-8 text-red-400" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-primary">{{ t('transfer_unavailable.deleted_title') }}</h1>
                    <p class="text-sm text-muted mt-1">{{ t('transfer_unavailable.deleted_desc') }}</p>
                </div>
            </template>

            <template v-else>
                <div class="w-16 h-16 rounded-full bg-surface-2 flex items-center justify-center">
                    <AlertCircle class="w-8 h-8 text-muted" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-primary">{{ t('transfer_unavailable.title') }}</h1>
                    <p class="text-sm text-muted mt-1">{{ t('transfer_unavailable.other_desc') }}</p>
                </div>
            </template>

            <a :href="homePath" class="text-sm text-link hover:text-link-hover font-medium underline transition-colors">
                {{ t('transfer_unavailable.back') }}
            </a>
        </div>
    </div>
</template>
