<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Cloud, ChevronDown, Check, Sun, Moon } from "lucide-vue-next";
import { useTheme } from "@/composables/useTheme.js";
import { useLocale, SUPPORTED_LOCALES } from "@/composables/useLocale.js";

const { t } = useI18n();
const { theme, toggle } = useTheme();
const { locale, setLocale } = useLocale();

const langOpen = ref(false);

async function switchLocale(code) {
    langOpen.value = false;
    await setLocale(code);
}
</script>

<template>
    <header class="border-b border-base bg-surface">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <Cloud class="w-4 h-4 text-white" :stroke-width="2" />
                </div>
                <span class="text-base font-bold text-primary">Nimbus</span>
            </a>

            <div class="flex items-center gap-1">
                <div class="relative">
                    <button
                        type="button"
                        class="h-8 px-2 flex items-center gap-1.5 rounded text-secondary hover:text-primary hover:bg-surface-2 transition text-xs font-bold uppercase tracking-wide"
                        v-on:click="langOpen = !langOpen"
                        v-on:blur="langOpen = false"
                    >
                        {{ locale }}
                        <ChevronDown class="w-3 h-3 transition-transform" :class="langOpen ? 'rotate-180' : ''" :stroke-width="2.5" />
                    </button>

                    <div
                        v-if="langOpen"
                        class="absolute right-0 top-full mt-1 w-36 rounded-md border border-base bg-surface shadow-lg shadow-indigo-500/10 overflow-hidden z-50"
                    >
                        <button
                            v-for="loc in SUPPORTED_LOCALES"
                            :key="loc.code"
                            type="button"
                            class="w-full text-left px-3 py-2 text-sm flex items-center justify-between gap-2 hover:bg-surface-2 transition"
                            :class="loc.code === locale ? 'text-indigo-600 font-bold' : 'text-primary'"
                            v-on:mousedown.prevent="switchLocale(loc.code)"
                        >
                            {{ loc.label }}
                            <Check v-if="loc.code === locale" class="w-3.5 h-3.5 text-indigo-600 shrink-0" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-8 h-8 flex items-center justify-center rounded text-secondary hover:text-primary hover:bg-surface-2 transition"
                    :title="theme === 'dark' ? t('app.theme_light') : t('app.theme_dark')"
                    v-on:click="toggle"
                >
                    <Sun v-if="theme === 'dark'" class="w-4 h-4" :stroke-width="2" />
                    <Moon v-else class="w-4 h-4" :stroke-width="2" />
                </button>
            </div>
        </div>
    </header>
</template>
