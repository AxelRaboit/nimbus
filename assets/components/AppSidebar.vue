<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useTheme } from "@/composables/useTheme.js";
import AppLogo from "@/components/AppLogo.vue";
import { Route } from "@/utils/routes.js";
import { Plan } from "@/utils/plans.js";
import {
    UploadCloud,
    User,
    LogOut,
    Moon,
    Sun,
    ChevronsLeft,
    ChevronsRight,
    Menu,
    X,
    Shield,
    History,
    Sparkles,
    Mail,
} from "lucide-vue-next";

const props = defineProps({
    userName: { type: String, default: "" },
    userEmail: { type: String, default: "" },
    appVersion: { type: String, default: "" },
    activeRoute: { type: String, default: "" },
    logoutCsrf: { type: String, default: "" },
    homePath: { type: String, default: "/" },
    profilePath: { type: String, default: "/profile" },
    logoutPath: { type: String, default: "/logout" },
    loginPath: { type: String, default: "/login" },
    isGuest: { type: Boolean, default: false },
    isDev: { type: Boolean, default: false },
    devPath: { type: String, default: "/dev" },
    dashboardPath: { type: String, default: "/transferts" },
    userPlan: { type: String, default: "free" },
    planPath: { type: String, default: "/plan" },
    mailpitUrl: { type: String, default: "" },
});

const isPro = computed(() => props.userPlan === Plan.Pro);
const planActive = computed(() => props.activeRoute === Route.Plan);

const { t } = useI18n();
const { theme, toggle: toggleTheme } = useTheme();

const SIDEBAR_KEY = "nimbus-sidebar";

function collapse() {
    document.documentElement.classList.add("sidebar-collapsed");
    localStorage.setItem(SIDEBAR_KEY, "collapsed");
}
function expand() {
    document.documentElement.classList.remove("sidebar-collapsed");
    localStorage.setItem(SIDEBAR_KEY, "expanded");
}

const mobileOpen = ref(false);
function openMobile() {
    mobileOpen.value = true;
    document.body.style.overflow = "hidden";
}
function closeMobile() {
    mobileOpen.value = false;
    document.body.style.overflow = "";
}

const homeActive      = props.activeRoute === Route.Home;
const dashboardActive = props.activeRoute === Route.Dashboard;
const profileActive   = props.activeRoute?.startsWith(Route.Profile);
const devActive       = props.activeRoute?.startsWith(Route.Dev);
</script>

<template>
    <aside
        id="sidebar"
        class="hidden lg:flex flex-col fixed inset-y-0 left-0 bg-surface border-r border-line z-30 overflow-hidden"
    >
        <div
            class="sh-wrap flex items-center h-16 border-b border-line shrink-0 transition-all duration-200"
        >
            <a :href="homePath" class="sh-logo-expanded flex items-center gap-2.5 min-w-0">
                <AppLogo :size="32" class="shrink-0" />
                <div class="flex flex-col min-w-0">
                    <span
                        class="text-primary font-bold text-lg tracking-tight truncate leading-tight"
                    >Nimbus</span>
                    <span v-if="appVersion" class="text-xs text-muted/50 leading-none">{{
                        appVersion
                    }}</span>
                </div>
            </a>

            <a :href="homePath" class="sh-logo-collapsed">
                <AppLogo :size="32" />
            </a>

            <button
                class="sh-collapse-btn ml-2 p-1.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors shrink-0"
                v-on:click="collapse"
            >
                <ChevronsLeft class="w-4 h-4" />
            </button>
        </div>

        <div v-if="!isGuest" class="sh-logo-expanded flex-col border-b border-line px-4 py-3 shrink-0">
            <p class="text-sm font-medium text-primary truncate">{{ userName }}</p>
            <p class="text-xs text-muted truncate">{{ userEmail }}</p>
        </div>

        <nav class="sidebar-nav flex-1 py-4 space-y-0.5">
            <a
                :href="homePath"
                class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                :class="homeActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
            >
                <UploadCloud class="w-5 h-5 shrink-0" :class="homeActive ? 'text-indigo-400' : 'text-muted'" />
                <span class="si-label truncate">{{ t("nav.send") }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("nav.send") }}
                </span>
            </a>

            <template v-if="!isGuest">
                <a
                    v-if="isPro"
                    :href="dashboardPath"
                    class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                    :class="dashboardActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                >
                    <History class="w-5 h-5 shrink-0" :class="dashboardActive ? 'text-indigo-400' : 'text-muted'" />
                    <span class="si-label truncate">{{ t("nav.my_transfers") }}</span>
                    <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                        {{ t("nav.my_transfers") }}
                    </span>
                </a>
                <span
                    v-else
                    class="si flex items-center rounded-lg text-sm font-medium text-muted opacity-50 cursor-not-allowed relative"
                >
                    <History class="w-5 h-5 shrink-0 text-muted" />
                    <span class="si-label truncate flex-1">{{ t("nav.my_transfers") }}</span>
                    <span class="si-label text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full shrink-0">Pro</span>
                    <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                        {{ t("nav.my_transfers") }} — Pro
                    </span>
                </span>
            </template>

            <a
                v-if="isDev"
                :href="devPath"
                class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                :class="devActive ? 'bg-rose-600/15 text-rose-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
            >
                <Shield class="w-5 h-5 shrink-0" :class="devActive ? 'text-rose-400' : 'text-muted'" />
                <span class="si-label truncate">{{ t("nav.dashboard") }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("nav.dashboard") }}
                </span>
            </a>

        </nav>

        <div class="sidebar-bottom shrink-0 border-t border-line py-3 space-y-0.5">
            <button
                class="sh-expand-btn w-full items-center justify-center py-2.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                v-on:click="expand"
            >
                <ChevronsRight class="w-4 h-4" />
            </button>

            <a
                v-if="mailpitUrl"
                :href="mailpitUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-amber-400 hover:bg-amber-500/10 transition-colors group relative"
            >
                <Mail class="w-5 h-5 shrink-0 text-muted group-hover:text-amber-400 transition-colors" />
                <span class="si-label truncate">Mailpit</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    Mailpit
                </span>
            </a>

            <button
                class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                v-on:click="toggleTheme"
            >
                <Moon v-if="theme !== 'dark'" class="w-5 h-5 shrink-0 text-muted" />
                <Sun v-else class="w-5 h-5 shrink-0 text-muted" />
                <span class="si-label">{{
                    theme === "dark" ? t("nav.lightMode") : t("nav.darkMode")
                }}</span>
                <span
                    class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg"
                >
                    {{ theme === "dark" ? t("nav.lightMode") : t("nav.darkMode") }}
                </span>
            </button>

            <a
                v-if="!isGuest"
                :href="planPath"
                class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                :class="planActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
            >
                <Sparkles class="w-5 h-5 shrink-0" :class="planActive ? 'text-indigo-400' : 'text-muted'" />
                <span class="si-label truncate flex-1">{{ t("nav.plan") }}</span>
                <span v-if="!isPro" class="si-label text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full shrink-0">Pro</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("nav.plan") }}
                </span>
            </a>

            <template v-if="isGuest">
                <a
                    :href="loginPath"
                    class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors group relative"
                >
                    <User class="w-5 h-5 shrink-0 text-muted" />
                    <span class="si-label truncate">{{ t("nav.login") }}</span>
                    <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                        {{ t("nav.login") }}
                    </span>
                </a>
            </template>

            <template v-else>
                <a
                    :href="profilePath"
                    class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                    :class="
                        profileActive
                            ? 'bg-indigo-600/15 text-indigo-400'
                            : 'text-secondary hover:text-primary hover:bg-surface-2'
                    "
                >
                    <User class="w-5 h-5 shrink-0 text-muted" />
                    <span class="si-label truncate">{{ t("nav.profile") }}</span>
                    <span
                        class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg"
                    >
                        {{ t("nav.profile") }}
                    </span>
                </a>

                <form :action="logoutPath" method="POST">
                    <input type="hidden" name="_token" :value="logoutCsrf">
                    <button
                        type="submit"
                        class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors w-full group relative"
                    >
                        <LogOut class="w-5 h-5 shrink-0 text-muted group-hover:text-rose-400 transition-colors" />
                        <span class="si-label">{{ t("nav.logout") }}</span>
                        <span
                            class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg"
                        >
                            {{ t("nav.logout") }}
                        </span>
                    </button>
                </form>
            </template>
        </div>
    </aside>

    <div
        class="lg:hidden fixed top-0 inset-x-0 h-14 bg-surface border-b border-line z-30 flex items-center justify-between px-4"
    >
        <a :href="homePath" class="flex items-center gap-2">
            <AppLogo :size="28" />
            <span class="text-primary font-bold text-base tracking-tight">Nimbus</span>
        </a>
        <button
            class="p-2 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
            v-on:click="openMobile"
        >
            <Menu class="w-5 h-5" />
        </button>
    </div>

    <div
        class="lg:hidden fixed inset-0 z-50 transition-opacity duration-200"
        :class="
            mobileOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
        "
    >
        <div class="absolute inset-0 bg-black/60" v-on:click="closeMobile" />

        <div
            class="relative w-60 max-w-[85vw] bg-surface h-full flex flex-col shadow-2xl transition-transform duration-200"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="flex items-center justify-between px-4 h-16 border-b border-line shrink-0"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <AppLogo :size="32" class="shrink-0" />
                    <div class="flex flex-col min-w-0">
                        <span class="text-primary font-bold text-lg tracking-tight truncate leading-tight">Nimbus</span>
                        <span v-if="appVersion" class="text-xs text-muted/50 leading-none">{{
                            appVersion
                        }}</span>
                    </div>
                </div>
                <button
                    class="p-1.5 text-muted hover:text-primary transition-colors"
                    v-on:click="closeMobile"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div v-if="!isGuest" class="px-4 py-3 border-b border-line shrink-0">
                <p class="text-sm font-medium text-primary">{{ userName }}</p>
                <p class="text-xs text-muted truncate">{{ userEmail }}</p>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                <a
                    :href="homePath"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="homeActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                >
                    <UploadCloud class="w-5 h-5 shrink-0" :class="homeActive ? 'text-indigo-400' : 'text-muted'" />
                    {{ t("nav.send") }}
                </a>

                <template v-if="!isGuest">
                    <a
                        v-if="isPro"
                        :href="dashboardPath"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        :class="dashboardActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                    >
                        <History class="w-5 h-5 shrink-0" :class="dashboardActive ? 'text-indigo-400' : 'text-muted'" />
                        {{ t("nav.my_transfers") }}
                    </a>
                    <span
                        v-else
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-muted opacity-50 cursor-not-allowed"
                    >
                        <History class="w-5 h-5 shrink-0 text-muted" />
                        <span class="flex-1">{{ t("nav.my_transfers") }}</span>
                        <span class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full">Pro</span>
                    </span>
                </template>

                <a
                    v-if="isDev"
                    :href="devPath"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="devActive ? 'bg-rose-600/15 text-rose-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                >
                    <Shield class="w-5 h-5 shrink-0" :class="devActive ? 'text-rose-400' : 'text-muted'" />
                    {{ t("nav.dashboard") }}
                </a>

            </nav>

            <div class="shrink-0 border-t border-line px-3 py-3 space-y-1">
                <a
                    v-if="mailpitUrl"
                    :href="mailpitUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-amber-400 hover:bg-amber-500/10 transition-colors"
                >
                    <Mail class="w-5 h-5 shrink-0 text-muted" />
                    Mailpit
                </a>

                <button
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                    v-on:click="toggleTheme"
                >
                    <Moon v-if="theme !== 'dark'" class="w-5 h-5 text-muted shrink-0" />
                    <Sun v-else class="w-5 h-5 text-muted shrink-0" />
                    <span>{{ theme === "dark" ? t("nav.lightMode") : t("nav.darkMode") }}</span>
                </button>

                <a
                    v-if="!isGuest"
                    :href="planPath"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="planActive ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                >
                    <Sparkles class="w-5 h-5 shrink-0" :class="planActive ? 'text-indigo-400' : 'text-muted'" />
                    <span class="flex-1">{{ t("nav.plan") }}</span>
                    <span v-if="!isPro" class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full">Pro</span>
                </a>

                <template v-if="isGuest">
                    <a
                        :href="loginPath"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                    >
                        <User class="w-5 h-5 text-muted shrink-0" />
                        {{ t("nav.login") }}
                    </a>
                </template>

                <template v-else>
                    <a
                        :href="profilePath"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                    >
                        <User class="w-5 h-5 text-muted shrink-0" />
                        {{ t("nav.profile") }}
                    </a>

                    <form :action="logoutPath" method="POST">
                        <input type="hidden" name="_token" :value="logoutCsrf">
                        <button
                            type="submit"
                            class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                        >
                            <LogOut class="w-5 h-5 shrink-0 text-muted group-hover:text-rose-400 transition-colors" />
                            {{ t("nav.logout") }}
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>
</template>
