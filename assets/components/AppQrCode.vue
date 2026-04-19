<script setup>
import { ref, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import QRCode from "qrcode";
import { Download } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    url: { type: String, required: true },
    size: { type: Number, default: 160 },
});

const dataUrl = ref("");

async function generate() {
    if (!props.url) return;
    dataUrl.value = await QRCode.toDataURL(props.url, {
        width: props.size * 2,
        margin: 1,
        color: { dark: "#000000", light: "#ffffff" },
    });
}

onMounted(generate);
watch(() => props.url, generate);

function download() {
    const anchorElement = document.createElement("a");
    anchorElement.href = dataUrl.value;
    anchorElement.download = "qrcode.png";
    anchorElement.click();
}
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <div class="rounded-lg border border-line bg-white p-2 shadow-sm">
            <img
                v-if="dataUrl"
                :src="dataUrl"
                :width="size"
                :height="size"
                alt="QR Code"
                class="block"
            >
            <div v-else :style="{ width: size + 'px', height: size + 'px' }" class="bg-surface-2 animate-pulse rounded" />
        </div>
        <button
            type="button"
            class="flex items-center gap-1 text-xs text-muted hover:text-primary transition-colors"
            v-on:click="download"
        >
            <Download class="w-3 h-3" :stroke-width="2" />
            {{ t('common.download_qr') }}
        </button>
    </div>
</template>
