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
    ChevronLeft, ChevronRight, ExternalLink,
    ShieldCheck, Clock, Trash2, AlertCircle, Pencil, Check, X, Lock,
} from "lucide-vue-next";

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler);

const { t, locale } = useI18n();
const { formatSize } = useFileSize();

const props = defineProps({
    tab:           { type: String, default: "stats" },
    stats:         { type: String, default: "{}" },
    transfers:     { type: String, default: "{}" },
    status:        { type: String, default: "" },
    statsPath:          { type: String, required: true },
    transfersPath:      { type: String, required: true },
    parameterUpdatePath: { type: String, required: true },
});

const activeTab = ref(props.tab);
const parsedStats = computed(() => {
    try { return JSON.parse(props.stats); } catch { return {}; }
});
const parsedTransfers = computed(() => {
    try { return JSON.parse(props.transfers); } catch { return {}; }
});

// ── Chart theme (follows CSS vars for dark/light) ───────────────────────────
const isDark = ref(document.documentElement.classList.contains("dark"));
const textColor  = computed(() => isDark.value ? "#94a3b8" : "#64748b");
const gridColor  = computed(() => isDark.value ? "rgba(255,255,255,0.06)" : "rgba(0,0,0,0.06)");
const baseOpts   = computed(() => ({
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
    plugins: {
        legend: { position: "bottom", labels: { color: textColor.value, padding: 14, boxWidth: 12 } },
    },
}));

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmtMonth(yyyyMm) {
    const [y, m] = yyyyMm.split("-");
    return new Intl.DateTimeFormat(locale.value, { month: "short", year: "2-digit" }).format(new Date(+y, +m - 1));
}

function fmtDate(iso) {
    return new Intl.DateTimeFormat(locale.value, { day: "numeric", month: "short", year: "numeric" }).format(new Date(iso));
}

// ── Charts data ──────────────────────────────────────────────────────────────
const usersLineData = computed(() => ({
    labels: parsedStats.value.usersByMonth?.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{
        data: parsedStats.value.usersByMonth?.map(m => m.count) ?? [],
        borderColor: "#6366f1",
        backgroundColor: "rgba(99,102,241,0.12)",
        borderWidth: 2,
        pointRadius: 3,
        tension: 0.4,
        fill: true,
    }],
}));

const transfersBarData = computed(() => ({
    labels: parsedStats.value.transfersByMonth?.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{
        data: parsedStats.value.transfersByMonth?.map(m => m.count) ?? [],
        backgroundColor: "#6366f1",
        borderRadius: 6,
    }],
}));

const statusColors = {
    ready:   "#10b981",
    pending: "#f59e0b",
    expired: "#6b7280",
    deleted: "#ef4444",
};

const statusDonutData = computed(() => {
    const byStatus = parsedStats.value.transfers?.byStatus ?? {};
    const entries = Object.entries(byStatus).filter(([, v]) => v > 0);
    return {
        labels: entries.map(([k]) => k),
        datasets: [{
            data: entries.map(([, v]) => v),
            backgroundColor: entries.map(([k]) => statusColors[k] ?? "#6b7280"),
            borderWidth: 0,
        }],
    };
});

// ── Parameters inline edit ────────────────────────────────────────────────────
const editingKey   = ref(null);
const editingValue = ref("");
const editSaving   = ref(false);

function startEdit(param) {
    editingKey.value   = param.key;
    editingValue.value = param.value ?? "";
}

function cancelEdit() {
    editingKey.value = null;
}

async function saveEdit(param) {
    if (editSaving.value) return;
    editSaving.value = true;
    try {
        const url = props.parameterUpdatePath.replace("__key__", encodeURIComponent(param.key));
        const res = await fetch(url, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ value: editingValue.value }),
        });
        if (res.ok) {
            param.value    = editingValue.value || null;
            editingKey.value = null;
        }
    } finally {
        editSaving.value = false;
    }
}

// ── Transfers tab ─────────────────────────────────────────────────────────────
const currentStatus = ref(props.status);
const currentPage   = ref(parsedTransfers.value.page ?? 1);

function navigate(url) { window.location.href = url; }

function transfersUrl(page, status) {
    const u = new URL(props.transfersPath, window.location.origin);
    if (page > 1) u.searchParams.set("page", page);
    if (status)   u.searchParams.set("status", status);
    return u.toString();
}

const statusBadge = {
    ready:   "bg-badge-success-bg text-badge-success-text",
    pending: "bg-badge-warning-bg text-badge-warning-text",
    expired: "bg-surface-2 text-muted",
    deleted: "bg-badge-danger-bg text-badge-danger-text",
};

const statusIcon = { ready: ShieldCheck, pending: Clock, expired: AlertCircle, deleted: Trash2 };
</script>

<template>
    <div>
        <!-- Tabs -->
        <div class="border-b border-base mb-6 flex gap-1 px-1">
            <a
                :href="statsPath"
                class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                :class="activeTab === 'stats'
                    ? 'border-indigo-500 text-primary'
                    : 'border-transparent text-secondary hover:text-primary hover:border-base'"
            >
                Statistiques
            </a>
            <a
                :href="transfersPath"
                class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                :class="activeTab === 'transfers'
                    ? 'border-indigo-500 text-primary'
                    : 'border-transparent text-secondary hover:text-primary hover:border-base'"
            >
                Transferts
            </a>
        </div>

        <!-- ── Stats tab ─────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'stats'" class="space-y-6">
            <!-- KPIs -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">Utilisateurs</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-primary">{{ parsedStats.users?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">depuis le début</p>
                    <p class="text-xs text-secondary mt-1.5">
                        <span class="text-emerald-500 font-medium">+{{ parsedStats.users?.newThisMonth ?? 0 }}</span>
                        ce mois
                    </p>
                </div>

                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">Transferts</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <ArrowUpRight class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-primary">{{ parsedStats.transfers?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">depuis le début</p>
                    <p class="text-xs text-secondary mt-1.5">
                        <span class="text-emerald-500 font-medium">{{ parsedStats.transfers?.active ?? 0 }} actifs</span>
                        en ce moment
                    </p>
                </div>

                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">Fichiers</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <FileStack class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-primary">{{ parsedStats.files?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">depuis le début</p>
                    <p class="text-xs text-secondary mt-1.5">{{ formatSize(parsedStats.files?.totalSize ?? 0) }} au total</p>
                </div>

                <div class="bg-surface border border-base rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">Téléchargements</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <Activity class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-primary">{{ parsedStats.recipients?.downloaded ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">depuis le début</p>
                    <p class="text-xs text-secondary mt-1.5">
                        sur {{ parsedStats.recipients?.total ?? 0 }} destinataires
                    </p>
                </div>
            </div>

            <!-- Charts row 1 -->
            <div class="grid lg:grid-cols-2 gap-4">
                <div class="bg-surface border border-base rounded-xl p-5">
                    <p class="text-sm font-semibold text-primary mb-4">Nouveaux utilisateurs (6 mois)</p>
                    <div class="h-48">
                        <Line :data="usersLineData" :options="axisOpts" />
                    </div>
                </div>
                <div class="bg-surface border border-base rounded-xl p-5">
                    <p class="text-sm font-semibold text-primary mb-4">Transferts créés (6 mois)</p>
                    <div class="h-48">
                        <Bar :data="transfersBarData" :options="axisOpts" />
                    </div>
                </div>
            </div>

            <!-- Charts row 2 -->
            <div class="grid lg:grid-cols-1 gap-4">
                <div class="bg-surface border border-base rounded-xl p-5">
                    <p class="text-sm font-semibold text-primary mb-4">Statut des transferts</p>
                    <div class="h-48 flex items-center justify-center">
                        <Doughnut :data="statusDonutData" :options="donutOpts" />
                    </div>
                </div>
            </div>

            <!-- Application parameters -->
            <div v-if="parsedStats.parameters?.length" class="bg-surface border border-base rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-base bg-surface-2">
                    <p class="text-sm font-semibold text-primary">Paramètres applicatifs</p>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-base/40">
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted w-1/3">Clé</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted w-1/4">Valeur</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">Description</th>
                            <th class="px-4 py-3 w-16" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr
                            v-for="param in parsedStats.parameters"
                            :key="param.key"
                            class="group hover:bg-surface-2/50 transition-colors"
                        >
                            <td class="px-5 py-3 font-mono text-xs text-indigo-500 font-medium w-1/3">{{ param.key }}</td>
                            <td class="px-5 py-3 w-1/4">
                                <template v-if="editingKey === param.key">
                                    <input
                                        v-model="editingValue"
                                        class="w-full bg-surface-2 border border-base rounded-lg px-2.5 py-1 text-sm text-primary focus:outline-none focus:border-indigo-500"
                                        autofocus
                                        v-on:keydown.enter="saveEdit(param)"
                                        v-on:keydown.esc="cancelEdit"
                                    >
                                </template>
                                <span v-else class="font-medium text-primary">{{ param.value ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-secondary text-xs">{{ param.description ?? '' }}</td>
                            <td class="px-4 py-3 w-16">
                                <div class="flex items-center gap-1 justify-end">
                                    <template v-if="editingKey === param.key">
                                        <button
                                            :disabled="editSaving"
                                            class="p-1.5 text-muted hover:text-emerald-400 transition-colors"
                                            v-on:click="saveEdit(param)"
                                        >
                                            <Check class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            class="p-1.5 text-muted hover:text-rose-400 transition-colors"
                                            v-on:click="cancelEdit"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </template>
                                    <button
                                        v-else
                                        class="p-1.5 text-muted hover:text-primary transition-colors opacity-0 group-hover:opacity-100"
                                        v-on:click="startEdit(param)"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Transfers tab ──────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'transfers'" class="space-y-4">
            <!-- Status filter -->
            <div class="flex items-center gap-2 flex-wrap">
                <a
                    v-for="s in ['', 'ready', 'pending', 'expired', 'deleted']"
                    :key="s"
                    :href="transfersUrl(1, s)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    :class="currentStatus === s
                        ? 'bg-indigo-600 text-white'
                        : 'bg-surface-2 text-secondary hover:bg-surface-3 hover:text-primary'"
                >
                    {{ s === '' ? 'Tous' : s.charAt(0).toUpperCase() + s.slice(1) }}
                </a>
            </div>

            <!-- Table -->
            <div class="bg-surface border border-base rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-base bg-surface-2">
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Référence</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Expéditeur</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Fichiers</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Destinataires</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Expiration</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-secondary uppercase tracking-wide">Créé le</th>
                            <th class="w-10" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base">
                        <tr
                            v-for="tr in parsedTransfers.items"
                            :key="tr.id"
                            class="hover:bg-surface-2/40 transition-colors"
                        >
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-bold text-primary tracking-widest">{{ tr.reference }}</span>
                                <Lock v-if="tr.isPasswordProtected" class="inline-block ml-2 w-3.5 h-3.5 text-muted" :stroke-width="2" :title="'Protégé par mot de passe'" />
                            </td>
                            <td class="px-4 py-3 max-w-[180px]">
                                <p v-if="tr.senderName" class="text-primary font-medium truncate">{{ tr.senderName }}</p>
                                <p class="text-xs text-secondary truncate">{{ tr.senderEmail ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full"
                                    :class="statusBadge[tr.status] ?? 'bg-surface-2 text-muted'"
                                >
                                    <component :is="statusIcon[tr.status]" class="w-3 h-3" :stroke-width="2.5" />
                                    {{ tr.isExpired && tr.status === 'ready' ? 'expiré' : tr.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-secondary">
                                {{ tr.filesCount }} · {{ formatSize(tr.totalSize) }}
                            </td>
                            <td class="px-4 py-3 text-secondary">
                                {{ tr.downloadedCount }}/{{ tr.recipientsCount }}
                            </td>
                            <td class="px-4 py-3 text-xs text-secondary">{{ fmtDate(tr.expiresAt) }}</td>
                            <td class="px-4 py-3 text-xs text-secondary">{{ fmtDate(tr.createdAt) }}</td>
                            <td class="px-4 py-3">
                                <a
                                    :href="`/manage/${tr.ownerToken}`"
                                    target="_blank"
                                    class="p-1.5 text-muted hover:text-primary transition-colors inline-flex"
                                    title="Gérer"
                                >
                                    <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!parsedTransfers.items?.length">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-muted">Aucun transfert.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="parsedTransfers.totalPages > 1"
                    class="border-t border-base px-4 py-3 flex items-center justify-between bg-surface-2"
                >
                    <p class="text-xs text-secondary">
                        Page {{ parsedTransfers.page }} / {{ parsedTransfers.totalPages }}
                        · {{ parsedTransfers.total }} transferts
                    </p>
                    <div class="flex items-center gap-2">
                        <a
                            :href="transfersUrl(parsedTransfers.page - 1, currentStatus)"
                            class="p-1.5 rounded-lg transition-colors"
                            :class="parsedTransfers.page > 1
                                ? 'text-secondary hover:text-primary hover:bg-surface-3'
                                : 'text-subtle cursor-not-allowed pointer-events-none'"
                        >
                            <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                        </a>
                        <a
                            :href="transfersUrl(parsedTransfers.page + 1, currentStatus)"
                            class="p-1.5 rounded-lg transition-colors"
                            :class="parsedTransfers.page < parsedTransfers.totalPages
                                ? 'text-secondary hover:text-primary hover:bg-surface-3'
                                : 'text-subtle cursor-not-allowed pointer-events-none'"
                        >
                            <ChevronRight class="w-4 h-4" :stroke-width="2" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
