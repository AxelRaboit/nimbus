<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, BarElement, LineElement,
    PointElement, ArcElement, Tooltip, Legend, Filler,
} from "chart.js";
import { Bar, Line, Doughnut } from "vue-chartjs";
import { useFileSize } from "@/composables/useFileSize.js";
import {
    Users, ArrowUpRight, FileStack, Activity,
    ExternalLink,
    ShieldCheck, Clock, Trash2, AlertCircle, Pencil, Check, X, Lock,
    Shield, UserRound, Mail,
} from "lucide-vue-next";
import AppNoData from "@/components/AppNoData.vue";
import AppPagination from "@/components/AppPagination.vue";

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler);

const { t, locale } = useI18n();
const { formatSize } = useFileSize();

const props = defineProps({
    tab:                  { type: String, default: "stats" },
    stats:                { type: String, default: "{}" },
    transfers:            { type: String, default: "{}" },
    users:                { type: String, default: "{}" },
    parameters:           { type: String, default: "{}" },
    search:               { type: String, default: "" },
    status:               { type: String, default: "" },
    statsPath:            { type: String, required: true },
    transfersPath:        { type: String, required: true },
    usersPath:            { type: String, required: true },
    invitationsPath:      { type: String, required: true },
    invitationSendPath:   { type: String, required: true },
    parametersPath:       { type: String, required: true },
    parameterUpdatePath:  { type: String, required: true },
    userDeletePath:       { type: String, required: true },
    userToggleRolePath:   { type: String, required: true },
    csrfToken:            { type: String, default: "" },
});

// ── Parsed data ──────────────────────────────────────────────────────────────

const parsedStats = computed(() => { try { return JSON.parse(props.stats); } catch { return {}; } });
const parsedTransfers = computed(() => { try { return JSON.parse(props.transfers); } catch { return {}; } });
const parsedUsers = computed(() => { try { return JSON.parse(props.users); } catch { return {}; } });
const parsedParameters = computed(() => { try { return JSON.parse(props.parameters); } catch { return {}; } });

// ── Charts ───────────────────────────────────────────────────────────────────

const isDark = ref(document.documentElement.classList.contains("dark"));
const textColor = computed(() => isDark.value ? "#94a3b8" : "#64748b");
const gridColor = computed(() => isDark.value ? "rgba(255,255,255,0.06)" : "rgba(0,0,0,0.06)");
const baseOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    plugins: { legend: { display: false }, tooltip: { mode: "index", intersect: false } },
}));
const axisOpts = computed(() => ({
    ...baseOpts.value,
    scales: {
        x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        y: { ticks: { color: textColor.value }, grid: { color: gridColor.value }, beginAtZero: true },
    },
}));
const donutOpts = computed(() => ({
    ...baseOpts.value,
    plugins: { legend: { position: "bottom", labels: { color: textColor.value, padding: 14, boxWidth: 12 } } },
}));

function fmtMonth(yyyyMm) {
    const [y, m] = yyyyMm.split("-");
    return new Intl.DateTimeFormat(locale.value, { month: "short", year: "2-digit" }).format(new Date(+y, +m - 1));
}

function fmtDate(iso) {
    return new Intl.DateTimeFormat(locale.value, { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }).format(new Date(iso));
}

const usersLineData = computed(() => ({
    labels: parsedStats.value.usersByMonth?.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{ data: parsedStats.value.usersByMonth?.map(m => m.count) ?? [], borderColor: "#6366f1", backgroundColor: "rgba(99,102,241,0.12)", borderWidth: 2, pointRadius: 3, tension: 0.4, fill: true }],
}));

const transfersBarData = computed(() => ({
    labels: parsedStats.value.transfersByMonth?.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{ data: parsedStats.value.transfersByMonth?.map(m => m.count) ?? [], backgroundColor: "#6366f1", borderRadius: 6 }],
}));

const statusColors = { ready: "#10b981", pending: "#f59e0b", expired: "#6b7280", deleted: "#ef4444" };
const statusDonutData = computed(() => {
    const byStatus = parsedStats.value.transfers?.byStatus ?? {};
    const entries = Object.entries(byStatus).filter(([, v]) => v > 0);
    return { labels: entries.map(([k]) => k), datasets: [{ data: entries.map(([, v]) => v), backgroundColor: entries.map(([k]) => statusColors[k] ?? "#6b7280"), borderWidth: 0 }] };
});
const hasStatusData = computed(() => Object.values(parsedStats.value.transfers?.byStatus ?? {}).some(v => v > 0));

// ── Transfers tab ────────────────────────────────────────────────────────────

const currentStatus = ref(props.status);
const statusBadge = { ready: "bg-badge-success-bg text-badge-success-text", pending: "bg-badge-warning-bg text-badge-warning-text", expired: "bg-surface-2 text-muted", deleted: "bg-badge-danger-bg text-badge-danger-text" };
const statusIcon = { ready: ShieldCheck, pending: Clock, expired: AlertCircle, deleted: Trash2 };

function transfersUrl(page, status) {
    const u = new URL(props.transfersPath, window.location.origin);
    if (page > 1) u.searchParams.set("page", page);
    if (status) u.searchParams.set("status", status);
    return u.toString();
}

function usersUrl(page) {
    const u = new URL(props.usersPath, window.location.origin);
    if (page > 1) u.searchParams.set("page", page);
    if (props.search) u.searchParams.set("search", props.search);
    return u.toString();
}

function parametersUrl(page) {
    const u = new URL(props.parametersPath, window.location.origin);
    if (page > 1) u.searchParams.set("page", page);
    return u.toString();
}

// ── Parameters tab ───────────────────────────────────────────────────────────

const editingKey = ref(null);
const editingValue = ref("");
const editSaving = ref(false);

function startEdit(param) { editingKey.value = param.key; editingValue.value = param.value ?? ""; }
function cancelEdit() { editingKey.value = null; }

async function saveEdit(param) {
    if (editSaving.value) return;
    editSaving.value = true;
    try {
        const url = props.parameterUpdatePath.replace("__key__", encodeURIComponent(param.key));
        const res = await fetch(url, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ value: editingValue.value }) });
        if (res.ok) { param.value = editingValue.value || null; editingKey.value = null; }
    } finally { editSaving.value = false; }
}

// ── Users tab ────────────────────────────────────────────────────────────────

const searchInput = ref(props.search);

function performSearch() {
    const u = new URL(props.usersPath, window.location.origin);
    if (searchInput.value) u.searchParams.set("search", searchInput.value);
    window.location.href = u.toString();
}

const pendingDelete = ref(null);
const pendingToggleRole = ref(null);

function submitForm(action) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = action;
    const csrf = document.createElement("input");
    csrf.type = "hidden";
    csrf.name = "_token";
    csrf.value = props.csrfToken;
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

function confirmDelete(user) { pendingDelete.value = user; }
function doDelete() {
    if (!pendingDelete.value) return;
    submitForm(props.userDeletePath.replace("__id__", pendingDelete.value.id));
    pendingDelete.value = null;
}

function confirmToggleRole(user) { pendingToggleRole.value = user; }
function doToggleRole() {
    if (!pendingToggleRole.value) return;
    submitForm(props.userToggleRolePath.replace("__id__", pendingToggleRole.value.id));
    pendingToggleRole.value = null;
}

// ── Invitations tab ──────────────────────────────────────────────────────────

const invitationEmail = ref("");
const invitationMessage = ref("");
const invitationCredentialEmail = ref("");
const invitationCredentialPassword = ref("");
const invitationSending = ref(false);

function submitInvitation() {
    if (!invitationEmail.value || invitationSending.value) return;
    invitationSending.value = true;
    const form = document.createElement("form");
    form.method = "POST";
    form.action = props.invitationSendPath;
    [
        ["email", invitationEmail.value],
        ["message", invitationMessage.value],
        ["credential_email", invitationCredentialEmail.value],
        ["credential_password", invitationCredentialPassword.value],
        ["_token", props.csrfToken],
    ].forEach(([name, value]) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}
</script>

<template>
    <div>
        <!-- Tab navigation -->
        <div class="border-b border-base mb-6 overflow-x-auto">
            <nav class="flex gap-6 sm:gap-8 whitespace-nowrap min-w-max">
                <a :href="statsPath" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'stats' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.stats.title") }}
                </a>
                <a :href="usersPath" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'users' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.users.title") }}
                </a>
                <a :href="invitationsPath" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'invitations' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.invitations.title") }}
                </a>
                <a :href="parametersPath" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'parameters' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.parameters.title") }}
                </a>
                <a :href="transfersPath" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'transfers' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.transfers.title") }}
                </a>
            </nav>
        </div>

        <!-- Stats tab -->
        <div v-if="tab === 'stats'" class="space-y-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.stats.kpi_users") }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ parsedStats.users?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ t("admin.stats.since_start") }}</p>
                    <p class="text-xs text-secondary mt-1.5">
                        <span class="text-indigo-400 font-medium">+{{ parsedStats.users?.newThisMonth ?? 0 }}</span> {{ t("admin.stats.this_month") }}
                    </p>
                </div>
                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.stats.kpi_transfers") }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <ArrowUpRight class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ parsedStats.transfers?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ t("admin.stats.since_start") }}</p>
                    <p class="text-xs text-secondary mt-1.5">
                        <span class="text-emerald-400 font-medium">{{ parsedStats.transfers?.active ?? 0 }} {{ t("admin.stats.active_label") }}</span> {{ t("admin.stats.right_now") }}
                    </p>
                </div>
                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.stats.kpi_files") }}</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <FileStack class="w-4 h-4 text-violet-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-violet-400">{{ parsedStats.files?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ t("admin.stats.since_start") }}</p>
                    <p class="text-xs text-secondary mt-1.5"><span class="text-violet-400 font-medium">{{ formatSize(parsedStats.files?.totalSize ?? 0) }}</span> {{ t("admin.stats.total_label") }}</p>
                </div>
                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.stats.kpi_downloads") }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <Activity class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ parsedStats.recipients?.downloaded ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ t("admin.stats.since_start") }}</p>
                    <p class="text-xs text-secondary mt-1.5">{{ t("admin.stats.out_of") }} <span class="text-amber-400 font-medium">{{ parsedStats.recipients?.total ?? 0 }}</span> {{ t("admin.stats.recipients_label") }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-base rounded-xl p-5">
                    <p class="text-sm font-semibold text-primary mb-4">{{ t("admin.stats.chart_users") }}</p>
                    <div class="h-48 sm:h-64"><Line :data="usersLineData" :options="axisOpts" /></div>
                </div>
                <div class="bg-surface border border-base rounded-xl p-5">
                    <p class="text-sm font-semibold text-primary mb-4">{{ t("admin.stats.chart_transfers") }}</p>
                    <div class="h-48 sm:h-64"><Bar :data="transfersBarData" :options="axisOpts" /></div>
                </div>
            </div>
            <div class="bg-surface border border-base rounded-xl p-5">
                <p class="text-sm font-semibold text-primary mb-4">{{ t("admin.stats.chart_status") }}</p>
                <div class="h-48 sm:h-64 flex items-center justify-center">
                    <Doughnut v-if="hasStatusData" :data="statusDonutData" :options="donutOpts" />
                    <AppNoData v-else :message="t('admin.stats.no_data')" />
                </div>
            </div>
        </div>

        <!-- Users tab -->
        <div v-else-if="tab === 'users'" class="space-y-4">
            <div class="flex gap-2">
                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="t('admin.users.searchPlaceholder')"
                    class="flex-1 px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    v-on:keyup.enter="performSearch"
                >
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium" v-on:click="performSearch">
                    {{ t("admin.users.search") }}
                </button>
            </div>

            <div class="bg-surface border border-base rounded-lg overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead class="bg-surface-2 border-b border-base">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.users.name") }}</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden sm:table-cell">{{ t("admin.users.email") }}</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t("admin.users.plan") }}</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t("admin.users.created") }}</th>
                            <th class="px-4 sm:px-6 py-3 text-right text-sm font-semibold text-primary">{{ t("admin.users.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr v-for="user in parsedUsers.items" :key="user.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 sm:px-6 py-3">
                                <p class="font-medium text-primary">{{ user.name }}</p>
                                <p class="text-xs text-secondary sm:hidden">{{ user.email }}</p>
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-secondary hidden sm:table-cell">{{ user.email }}</td>
                            <td class="px-4 sm:px-6 py-3 hidden md:table-cell">
                                <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full" :class="user.plan === 'pro' ? 'bg-amber-500/15 text-amber-400' : 'bg-surface-2 text-muted'">
                                    {{ user.plan === 'pro' ? 'Pro' : 'Free' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(user.createdAt) }}</td>
                            <td class="px-4 sm:px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button class="p-1.5 text-muted transition-colors rounded" :class="user.isDevRole ? 'hover:text-indigo-400' : 'hover:text-rose-400'" :title="user.isDevRole ? t('admin.users.makeUser') : t('admin.users.makeDev')" v-on:click="confirmToggleRole(user)">
                                        <component :is="user.isDevRole ? UserRound : Shield" class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                    <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="t('admin.users.deleteConfirm', { name: user.name })" v-on:click="confirmDelete(user)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!parsedUsers.items?.length">
                            <td colspan="5"><AppNoData :message="t('admin.users.noResults')" /></td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 pb-4">
                    <AppPagination
                        :page="parsedUsers.page"
                        :total-pages="parsedUsers.totalPages"
                        :total="parsedUsers.total"
                        :per-page="20"
                        :url-fn="usersUrl"
                    />
                </div>
            </div>

            <!-- Confirm delete modal -->
            <div v-if="pendingDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ t("admin.users.deleteConfirm", { name: pendingDelete.name }) }}</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="pendingDelete = null">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="doDelete">Supprimer</button>
                    </div>
                </div>
            </div>

            <!-- Confirm toggle role modal -->
            <div v-if="pendingToggleRole" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ t("admin.users.toggleRoleConfirm", { name: pendingToggleRole.name }) }}</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="pendingToggleRole = null">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors" v-on:click="doToggleRole">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invitations tab -->
        <div v-else-if="tab === 'invitations'" class="max-w-lg space-y-4">
            <p class="text-sm text-secondary">{{ t("admin.invitations.description") }}</p>
            <form class="space-y-4" v-on:submit.prevent="submitInvitation">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-primary">{{ t("admin.invitations.email") }}</label>
                    <input
                        v-model="invitationEmail"
                        type="email"
                        :placeholder="t('admin.invitations.emailPlaceholder')"
                        class="w-full px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-primary">{{ t("admin.invitations.message") }}</label>
                    <textarea v-model="invitationMessage" rows="5" :placeholder="t('admin.invitations.messagePlaceholder')" class="w-full px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" />
                </div>
                <div class="border border-base rounded-lg p-4 space-y-3 bg-surface-2/50">
                    <p class="text-xs text-secondary">{{ t("admin.invitations.credentialsHint") }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-primary">{{ t("admin.invitations.credentialEmail") }}</label>
                            <input v-model="invitationCredentialEmail" type="email" :placeholder="t('admin.invitations.emailPlaceholder')" class="w-full px-4 py-2 rounded-lg bg-surface border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-primary">{{ t("admin.invitations.credentialPassword") }}</label>
                            <input v-model="invitationCredentialPassword" type="text" class="w-full px-4 py-2 rounded-lg bg-surface border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                <button type="submit" :disabled="invitationSending || !invitationEmail" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors text-sm font-medium">
                    <Mail class="w-4 h-4" :stroke-width="2" />
                    {{ invitationSending ? t("admin.invitations.sending") : t("admin.invitations.send") }}
                </button>
            </form>
        </div>

        <!-- Parameters tab -->
        <div v-else-if="tab === 'parameters'">
            <div class="bg-surface border border-base rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-base">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/3">{{ t("admin.parameters.key") }}</th>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/4">{{ t("admin.parameters.value") }}</th>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.parameters.description") }}</th>
                            <th class="px-4 py-3 w-16" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr v-for="param in parsedParameters.items" :key="param.key" class="group hover:bg-surface-2/50 transition-colors">
                            <td class="px-5 py-3 font-mono text-sm text-indigo-500 font-medium w-1/3">{{ param.key }}</td>
                            <td class="px-5 py-3 w-1/4">
                                <template v-if="editingKey === param.key">
                                    <input
                                        v-model="editingValue"
                                        class="w-full bg-surface-2 border border-base rounded-lg px-2.5 py-1 text-sm text-primary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        autofocus
                                        v-on:keydown.enter="saveEdit(param)"
                                        v-on:keydown.esc="cancelEdit"
                                    >
                                </template>
                                <span v-else class="font-medium text-primary">{{ param.value ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-secondary text-sm">{{ param.description ?? '' }}</td>
                            <td class="px-4 py-3 w-16">
                                <div class="flex items-center gap-1 justify-end">
                                    <template v-if="editingKey === param.key">
                                        <button :disabled="editSaving" class="p-1.5 text-muted hover:text-emerald-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" v-on:click="saveEdit(param)"><Check class="w-3.5 h-3.5" /></button>
                                        <button class="p-1.5 text-muted hover:text-rose-400 transition-colors" v-on:click="cancelEdit"><X class="w-3.5 h-3.5" /></button>
                                    </template>
                                    <button v-else class="p-1.5 text-muted hover:text-primary transition-colors opacity-0 group-hover:opacity-100" v-on:click="startEdit(param)">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 pb-4">
                    <AppPagination
                        :page="parsedParameters.page"
                        :total-pages="parsedParameters.totalPages"
                        :total="parsedParameters.total"
                        :per-page="20"
                        :url-fn="parametersUrl"
                    />
                </div>
            </div>
        </div>

        <!-- Transfers tab -->
        <div v-else-if="tab === 'transfers'" class="space-y-4">
            <div class="flex items-center gap-2 flex-wrap">
                <a
                    v-for="s in ['', 'ready', 'pending', 'expired', 'deleted']"
                    :key="s"
                    :href="transfersUrl(1, s)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    :class="currentStatus === s ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3 hover:text-primary'"
                >
                    {{ s === '' ? t('admin.transfers.filter_all') : t('transfer.status.' + s) }}
                </a>
            </div>
            <div class="bg-surface border border-base rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-base bg-surface-2">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_reference") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_sender") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_status") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_files") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_recipients") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_expiry") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_created") }}</th>
                            <th class="w-10" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr v-for="tr in parsedTransfers.items" :key="tr.id" class="hover:bg-surface-2/40 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-bold text-primary tracking-widest">{{ tr.reference }}</span>
                                <Lock v-if="tr.isPasswordProtected" class="inline-block ml-2 w-3.5 h-3.5 text-muted" :stroke-width="2" />
                            </td>
                            <td class="px-4 py-3 max-w-[180px]">
                                <p v-if="tr.senderName" class="text-primary font-medium truncate">{{ tr.senderName }}</p>
                                <p class="text-xs text-secondary truncate">{{ tr.senderEmail ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="statusBadge[tr.status] ?? 'bg-surface-2 text-muted'">
                                    <component :is="statusIcon[tr.status]" class="w-3 h-3" :stroke-width="2.5" />
                                    {{ t('transfer.status.' + (tr.isExpired && tr.status === 'ready' ? 'expired' : tr.status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-secondary">{{ tr.filesCount }} · {{ formatSize(tr.totalSize) }}</td>
                            <td class="px-4 py-3 text-secondary">{{ tr.downloadedCount }}/{{ tr.recipientsCount }}</td>
                            <td class="px-4 py-3 text-sm text-secondary">{{ fmtDate(tr.expiresAt) }}</td>
                            <td class="px-4 py-3 text-sm text-secondary">{{ fmtDate(tr.createdAt) }}</td>
                            <td class="px-4 py-3">
                                <a :href="`/manage/${tr.ownerToken}`" target="_blank" class="p-1.5 text-muted hover:text-primary transition-colors inline-flex">
                                    <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!parsedTransfers.items?.length">
                            <td colspan="8"><AppNoData :message="t('admin.transfers.empty')" /></td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 pb-4">
                    <AppPagination
                        :page="parsedTransfers.page"
                        :total-pages="parsedTransfers.totalPages"
                        :total="parsedTransfers.total"
                        :per-page="20"
                        :url-fn="(p) => transfersUrl(p, currentStatus)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
