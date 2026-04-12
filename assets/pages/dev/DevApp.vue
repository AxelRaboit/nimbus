<script setup>
import { ref, computed, onMounted } from "vue";
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
    Shield, UserRound, Mail, KeyRound, HardDrive,
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
    parameters:                  { type: String, default: "{}" },
    accessRequests:              { type: String, default: "{}" },
    search:                      { type: String, default: "" },
    status:                      { type: String, default: "" },
    statsPath:                   { type: String, required: true },
    transfersPath:                { type: String, required: true },
    usersPath:                   { type: String, required: true },
    invitationsPath:             { type: String, required: true },
    invitationSendPath:          { type: String, required: true },
    parametersPath:              { type: String, required: true },
    parameterUpdatePath:         { type: String, required: true },
    userDeletePath:              { type: String, required: true },
    userToggleRolePath:          { type: String, required: true },
    accessRequestsPath:          { type: String, required: true },
    accessRequestApprovePath:         { type: String, required: true },
    accessRequestRejectPath:          { type: String, required: true },
    accessRequestPurgeApprovedPath:   { type: String, required: true },
    userCustomFileSizePath:           { type: String, required: true },
    csrfToken:                   { type: String, default: "" },
});

// ── Parsed data ──────────────────────────────────────────────────────────────

const parsedStats = computed(() => { try { return JSON.parse(props.stats); } catch { return {}; } });
const parsedTransfers = computed(() => { try { return JSON.parse(props.transfers); } catch { return {}; } });
const parsedUsers = computed(() => { try { return JSON.parse(props.users); } catch { return {}; } });
const parsedParameters = computed(() => { try { return JSON.parse(props.parameters); } catch { return {}; } });
const parsedAccessRequests = computed(() => { try { return JSON.parse(props.accessRequests); } catch { return {}; } });

// ── Tab nav scroll ───────────────────────────────────────────────────────────

const tabNav = ref(null);
onMounted(() => {
    const active = tabNav.value?.querySelector('[aria-current="page"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'center' });
});

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

function submitForm(action, extraFields = {}) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = action;
    const csrf = document.createElement("input");
    csrf.type = "hidden";
    csrf.name = "_token";
    csrf.value = props.csrfToken;
    form.appendChild(csrf);
    for (const [name, value] of Object.entries(extraFields)) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value ?? "";
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

function confirmDelete(user) { pendingDelete.value = user; }
function doDelete() {
    if (!pendingDelete.value) return;
    submitForm(props.userDeletePath.replace("__id__", pendingDelete.value.id));
    pendingDelete.value = null;
}

// ── Access requests tab ───────────────────────────────────────────────────────

const statusBadgeAR = {
    pending: "bg-badge-warning-bg text-badge-warning-text",
    approved: "bg-badge-success-bg text-badge-success-text",
    rejected: "bg-surface-2 text-muted",
};

const statusLabelAR = {
    pending: "En attente",
    approved: "Approuvé",
    rejected: "Rejeté",
};

const confirmPurge = ref(false);

function doPurgeApproved() {
    submitForm(props.accessRequestPurgeApprovedPath);
    confirmPurge.value = false;
}

function accessRequestsUrl(page) {
    const u = new URL(props.accessRequestsPath, window.location.origin);
    if (page > 1) u.searchParams.set("page", page);
    return u.toString();
}

const pendingApprove = ref(null);
const pendingReject = ref(null);
const approveGrantedSize = ref(null);

function openApproveModal(r) {
    pendingApprove.value = r;
    approveGrantedSize.value = r.requestedFileSizeMb ?? null;
}

function doApproveRequest() {
    if (!pendingApprove.value) return;
    submitForm(
        props.accessRequestApprovePath.replace("__id__", pendingApprove.value.id),
        { granted_file_size_mb: approveGrantedSize.value ?? "" },
    );
    pendingApprove.value = null;
}

function doRejectRequest() {
    if (!pendingReject.value) return;
    submitForm(props.accessRequestRejectPath.replace("__id__", pendingReject.value.id));
    pendingReject.value = null;
}

const pendingCustomSize = ref(null);
const pendingCustomSizeValue = ref(null);

function openCustomSizeModal(user) {
    pendingCustomSize.value = user;
    pendingCustomSizeValue.value = user.customFileSizeMb ?? null;
}

function doUpdateCustomSize() {
    if (!pendingCustomSize.value) return;
    submitForm(
        props.userCustomFileSizePath.replace("__id__", pendingCustomSize.value.id),
        { custom_file_size_mb: pendingCustomSizeValue.value ?? "" },
    );
    pendingCustomSize.value = null;
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
            <nav ref="tabNav" class="flex gap-6 sm:gap-8 whitespace-nowrap min-w-max">
                <a :href="statsPath" :aria-current="tab === 'stats' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'stats' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.stats.title") }}
                </a>
                <a :href="usersPath" :aria-current="tab === 'users' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'users' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.users.title") }}
                </a>
                <a :href="invitationsPath" :aria-current="tab === 'invitations' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'invitations' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.invitations.title") }}
                </a>
                <a :href="parametersPath" :aria-current="tab === 'parameters' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'parameters' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.parameters.title") }}
                </a>
                <a :href="transfersPath" :aria-current="tab === 'transfers' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium" :class="tab === 'transfers' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    {{ t("admin.transfers.title") }}
                </a>
                <a :href="accessRequestsPath" :aria-current="tab === 'access_requests' ? 'page' : undefined" class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base font-medium flex items-center gap-1.5" :class="tab === 'access_requests' ? 'border-indigo-500 text-primary' : 'border-transparent text-secondary hover:text-primary'">
                    <KeyRound class="w-3.5 h-3.5" :stroke-width="2" />
                    Demandes d'accès
                </a>
            </nav>
        </div>

        <!-- Stats tab -->
        <div v-if="tab === 'stats'" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
            <div class="flex flex-col sm:flex-row gap-2">
                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="t('admin.users.searchPlaceholder')"
                    class="flex-1 px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    v-on:keyup.enter="performSearch"
                >
                <button class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium" v-on:click="performSearch">
                    {{ t("admin.users.search") }}
                </button>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <AppNoData v-if="!parsedUsers.items?.length" :message="t('admin.users.noResults')" />
                <div v-for="user in parsedUsers.items" :key="user.id" class="bg-surface border border-base rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ user.name }}</p>
                            <p class="text-xs text-secondary truncate">{{ user.email }}</p>
                        </div>
                        <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full shrink-0" :class="user.plan === 'pro' ? 'bg-amber-500/15 text-amber-400' : 'bg-surface-2 text-muted'">
                            {{ user.plan === 'pro' ? 'Pro' : 'Free' }}
                        </span>
                    </div>
                    <div v-if="user.customFileSizeMb" class="text-xs text-muted">
                        Limite custom :
                        <span class="font-medium" :class="user.isCapped ? 'text-amber-400 line-through' : 'text-emerald-400'">{{ user.customFileSizeMb >= 1000 ? (user.customFileSizeMb / 1000).toFixed(1) + ' Go' : user.customFileSizeMb + ' Mo' }}</span>
                        <span v-if="user.isCapped" class="text-amber-400 font-medium ml-1">→ {{ user.effectiveFileSizeMb >= 1000 ? (user.effectiveFileSizeMb / 1000).toFixed(1) + ' Go' : user.effectiveFileSizeMb + ' Mo' }} (cappé)</span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-base">
                        <p class="text-xs text-muted">{{ fmtDate(user.createdAt) }}</p>
                        <div class="flex items-center gap-1">
                            <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" title="Limite de taille" v-on:click="openCustomSizeModal(user)">
                                <HardDrive class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button class="p-1.5 text-muted transition-colors rounded" :class="user.isDevRole ? 'hover:text-indigo-400' : 'hover:text-rose-400'" :title="user.isDevRole ? t('admin.users.makeUser') : t('admin.users.makeDev')" v-on:click="confirmToggleRole(user)">
                                <component :is="user.isDevRole ? UserRound : Shield" class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="t('admin.users.deleteConfirm', { name: user.name })" v-on:click="confirmDelete(user)">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
                <AppPagination
                    :page="parsedUsers.page"
                    :total-pages="parsedUsers.totalPages"
                    :total="parsedUsers.total"
                    :per-page="20"
                    :url-fn="usersUrl"
                />
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-base rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-base">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.users.name") }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.users.email") }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t("admin.users.plan") }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">Taille custom</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t("admin.users.created") }}</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-primary">{{ t("admin.users.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr v-for="user in parsedUsers.items" :key="user.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ user.name }}</p>
                            </td>
                            <td class="px-6 py-3 text-secondary">{{ user.email }}</td>
                            <td class="px-6 py-3 hidden md:table-cell">
                                <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full" :class="user.plan === 'pro' ? 'bg-amber-500/15 text-amber-400' : 'bg-surface-2 text-muted'">
                                    {{ user.plan === 'pro' ? 'Pro' : 'Free' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <template v-if="user.customFileSizeMb">
                                    <span class="text-xs font-medium" :class="user.isCapped ? 'text-amber-400 line-through' : 'text-emerald-400'">{{ user.customFileSizeMb >= 1000 ? (user.customFileSizeMb / 1000).toFixed(1) + ' Go' : user.customFileSizeMb + ' Mo' }}</span>
                                    <span v-if="user.isCapped" class="text-xs text-amber-400 font-medium ml-1">→ {{ user.effectiveFileSizeMb >= 1000 ? (user.effectiveFileSizeMb / 1000).toFixed(1) + ' Go' : user.effectiveFileSizeMb + ' Mo' }}</span>
                                </template>
                                <span v-else class="text-xs text-muted">—</span>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(user.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" title="Limite de taille" v-on:click="openCustomSizeModal(user)">
                                        <HardDrive class="w-4 h-4" :stroke-width="2" />
                                    </button>
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
                            <td colspan="6"><AppNoData :message="t('admin.users.noResults')" /></td>
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

            <!-- Custom file size modal -->
            <div v-if="pendingCustomSize" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <div>
                        <p class="text-sm font-medium text-primary mb-0.5">Limite de taille — {{ pendingCustomSize.name }}</p>
                        <p class="text-xs text-muted">Laissez vide pour revenir à la limite du plan.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model.number="pendingCustomSizeValue"
                            type="number"
                            min="1"
                            placeholder="Défaut plan"
                            class="flex-1 rounded-lg border border-base bg-surface-2 px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        >
                        <span class="text-xs text-muted shrink-0">Mo</span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="pendingCustomSize = null">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors" v-on:click="doUpdateCustomSize">Enregistrer</button>
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
                <button type="submit" :disabled="invitationSending || !invitationEmail" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors text-sm font-medium">
                    <Mail class="w-4 h-4" :stroke-width="2" />
                    {{ invitationSending ? t("admin.invitations.sending") : t("admin.invitations.send") }}
                </button>
            </form>
        </div>

        <!-- Parameters tab -->
        <div v-else-if="tab === 'parameters'" class="space-y-4">
            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <div v-for="param in parsedParameters.items" :key="param.key" class="bg-surface border border-base rounded-lg p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-mono text-sm text-indigo-400 font-medium break-all">{{ param.key }}</p>
                        <button v-if="editingKey !== param.key" class="p-1.5 text-muted hover:text-primary transition-colors shrink-0" v-on:click="startEdit(param)">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <template v-if="editingKey === param.key">
                        <input
                            v-model="editingValue"
                            class="w-full bg-surface-2 border border-base rounded-lg px-2.5 py-1.5 text-sm text-primary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            autofocus
                            v-on:keydown.enter="saveEdit(param)"
                            v-on:keydown.esc="cancelEdit"
                        >
                        <div class="flex gap-2">
                            <button :disabled="editSaving" class="flex-1 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg transition-colors" v-on:click="saveEdit(param)">Enregistrer</button>
                            <button class="flex-1 py-1.5 text-sm text-secondary hover:text-primary border border-base rounded-lg transition-colors" v-on:click="cancelEdit">Annuler</button>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-sm font-medium text-primary">{{ param.value ?? '—' }}</p>
                        <p v-if="param.description" class="text-xs text-secondary">{{ param.description }}</p>
                    </template>
                </div>
                <AppPagination
                    :page="parsedParameters.page"
                    :total-pages="parsedParameters.totalPages"
                    :total="parsedParameters.total"
                    :per-page="20"
                    :url-fn="parametersUrl"
                />
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-base rounded-xl overflow-hidden">
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

            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <AppNoData v-if="!parsedTransfers.items?.length" :message="t('admin.transfers.empty')" />
                <div v-for="tr in parsedTransfers.items" :key="tr.id" class="bg-surface border border-base rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="font-mono text-xs font-bold text-primary tracking-widest">{{ tr.reference }}</span>
                                <Lock v-if="tr.isPasswordProtected" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                            </div>
                            <p v-if="tr.senderName" class="text-sm text-secondary truncate">{{ tr.senderName }}</p>
                            <p class="text-xs text-muted truncate">{{ tr.senderEmail ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="statusBadge[tr.status] ?? 'bg-surface-2 text-muted'">
                                <component :is="statusIcon[tr.status]" class="w-3 h-3" :stroke-width="2.5" />
                                {{ t('transfer.status.' + (tr.isExpired && tr.status === 'ready' ? 'expired' : tr.status)) }}
                            </span>
                            <a :href="`/manage/${tr.ownerToken}`" target="_blank" class="p-1.5 text-muted hover:text-primary transition-colors">
                                <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-base text-xs text-muted">
                        <span>{{ tr.filesCount }} fichier{{ tr.filesCount > 1 ? 's' : '' }} · {{ formatSize(tr.totalSize) }} · {{ tr.downloadedCount }}/{{ tr.recipientsCount }}</span>
                        <span>expire {{ fmtDate(tr.expiresAt) }}</span>
                    </div>
                </div>
                <AppPagination
                    :page="parsedTransfers.page"
                    :total-pages="parsedTransfers.totalPages"
                    :total="parsedTransfers.total"
                    :per-page="20"
                    :url-fn="(p) => transfersUrl(p, currentStatus)"
                />
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-base rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-base bg-surface-2">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_reference") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_sender") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary">{{ t("admin.transfers.col_status") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t("admin.transfers.col_files") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t("admin.transfers.col_recipients") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t("admin.transfers.col_expiry") }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t("admin.transfers.col_created") }}</th>
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
                            <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ tr.filesCount }} · {{ formatSize(tr.totalSize) }}</td>
                            <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ tr.downloadedCount }}/{{ tr.recipientsCount }}</td>
                            <td class="px-4 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(tr.expiresAt) }}</td>
                            <td class="px-4 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(tr.createdAt) }}</td>
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

        <!-- Access requests tab -->
        <div v-else-if="tab === 'access_requests'" class="space-y-4">
            <div class="flex justify-end">
                <button class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-muted hover:text-rose-400 hover:bg-rose-500/10 border border-base rounded-lg transition-colors" v-on:click="confirmPurge = true">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    Purger
                </button>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <AppNoData v-if="!parsedAccessRequests.items?.length" message="Aucune demande d'accès." />
                <div v-for="r in parsedAccessRequests.items" :key="r.id" class="bg-surface border border-base rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ r.requesterName ?? '—' }}</p>
                            <p class="text-xs text-secondary truncate">{{ r.requesterEmail }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full shrink-0" :class="statusBadgeAR[r.status]">
                            <component :is="r.status === 'pending' ? Clock : r.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                            {{ statusLabelAR[r.status] ?? r.status }}
                        </span>
                    </div>
                    <p v-if="r.message" class="text-sm text-secondary">{{ r.message }}</p>
                    <div v-if="r.requestedFileSizeMb || r.grantedFileSizeMb" class="flex items-center gap-3 text-xs text-muted">
                        <span v-if="r.requestedFileSizeMb">Demandé : <strong class="text-secondary">{{ r.requestedFileSizeMb >= 1000 ? (r.requestedFileSizeMb / 1000).toFixed(1) + ' Go' : r.requestedFileSizeMb + ' Mo' }}</strong></span>
                        <span v-if="r.grantedFileSizeMb">Accordé : <strong class="text-emerald-400">{{ r.grantedFileSizeMb >= 1000 ? (r.grantedFileSizeMb / 1000).toFixed(1) + ' Go' : r.grantedFileSizeMb + ' Mo' }}</strong></span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-base">
                        <p class="text-xs text-muted">{{ fmtDate(r.createdAt) }} · expire {{ fmtDate(r.expiresAt) }}</p>
                        <div v-if="r.status === 'pending'" class="flex items-center gap-1">
                            <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" title="Approuver" v-on:click="openApproveModal(r)">
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" title="Rejeter" v-on:click="pendingReject = r">
                                <X class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
                <AppPagination
                    :page="parsedAccessRequests.page"
                    :total-pages="parsedAccessRequests.totalPages"
                    :total="parsedAccessRequests.total"
                    :per-page="20"
                    :url-fn="(p) => accessRequestsUrl(p)"
                />
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-base rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-base">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">Demandeur</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">Message</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">Taille</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">Statut</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">Date</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">Expire</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-primary">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr v-for="r in parsedAccessRequests.items" :key="r.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ r.requesterName ?? '—' }}</p>
                                <p class="text-xs text-secondary">{{ r.requesterEmail }}</p>
                            </td>
                            <td class="px-6 py-3 max-w-xs hidden md:table-cell">
                                <p class="text-sm text-secondary truncate">{{ r.message ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-3 hidden md:table-cell">
                                <div class="text-xs space-y-0.5">
                                    <p v-if="r.requestedFileSizeMb" class="text-muted">{{ r.requestedFileSizeMb >= 1000 ? (r.requestedFileSizeMb / 1000).toFixed(1) + ' Go' : r.requestedFileSizeMb + ' Mo' }} demandé</p>
                                    <p v-if="r.grantedFileSizeMb" class="text-emerald-400 font-medium">{{ r.grantedFileSizeMb >= 1000 ? (r.grantedFileSizeMb / 1000).toFixed(1) + ' Go' : r.grantedFileSizeMb + ' Mo' }} accordé</p>
                                    <p v-if="!r.requestedFileSizeMb && !r.grantedFileSizeMb" class="text-muted">—</p>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="statusBadgeAR[r.status]">
                                    <component :is="r.status === 'pending' ? Clock : r.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                                    {{ statusLabelAR[r.status] ?? r.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(r.createdAt) }}</td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ fmtDate(r.expiresAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="r.status === 'pending'">
                                        <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" title="Approuver" v-on:click="openApproveModal(r)">
                                            <Check class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                        <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" title="Rejeter" v-on:click="pendingReject = r">
                                            <X class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!parsedAccessRequests.items?.length">
                            <td colspan="7"><AppNoData message="Aucune demande d'accès." /></td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 pb-4">
                    <AppPagination
                        :page="parsedAccessRequests.page"
                        :total-pages="parsedAccessRequests.totalPages"
                        :total="parsedAccessRequests.total"
                        :per-page="20"
                        :url-fn="(p) => accessRequestsUrl(p)"
                    />
                </div>
            </div>

            <!-- Confirm approve modal -->
            <div v-if="pendingApprove" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">Approuver la demande de <strong>{{ pendingApprove.requesterName ?? pendingApprove.requesterEmail }}</strong> ? Un e-mail lui sera envoyé avec un lien d'accès.</p>
                    <div class="space-y-1.5">
                        <label class="text-xs text-muted">
                            Limite de taille accordée
                            <span v-if="pendingApprove.requestedFileSizeMb" class="text-muted">(demandée : {{ pendingApprove.requestedFileSizeMb >= 1000 ? (pendingApprove.requestedFileSizeMb / 1000).toFixed(1) + ' Go' : pendingApprove.requestedFileSizeMb + ' Mo' }})</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="approveGrantedSize"
                                type="number"
                                min="1"
                                placeholder="Défaut plan"
                                class="flex-1 rounded-lg border border-base bg-surface-2 px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                            >
                            <span class="text-xs text-muted shrink-0">Mo</span>
                        </div>
                        <p class="text-xs text-muted">Laisser vide pour appliquer la limite du plan.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="pendingApprove = null">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors" v-on:click="doApproveRequest">Approuver</button>
                    </div>
                </div>
            </div>

            <!-- Confirm reject modal -->
            <div v-if="pendingReject" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">Rejeter la demande de <strong>{{ pendingReject.requesterName ?? pendingReject.requesterEmail }}</strong> ?</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="pendingReject = null">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="doRejectRequest">Rejeter</button>
                    </div>
                </div>
            </div>

            <!-- Confirm purge modal -->
            <div v-if="confirmPurge" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-base rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">Supprimer toutes les demandes <strong>approuvées et rejetées</strong> ? Cette action est irréversible.</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="confirmPurge = false">Annuler</button>
                        <button class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="doPurgeApproved">Purger</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
