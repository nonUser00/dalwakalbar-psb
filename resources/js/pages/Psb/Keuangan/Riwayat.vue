<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import DataTable from '@/Components/DataTable.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';

Chart.register(...registerables);

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    tagihans: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        start_date?: string;
        end_date?: string;
        limit?: number;
    };
    stats: {
        total_riwayat: number;
        total_lunas: number;
        total_samaha: number;
        total_nominal_selesai: number;
    };
    chartYear?: number;
    chartData: {
        month: string;
        total: number;
    }[];
}>();

const formatRupiah = (val: number | string | undefined | null) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// DataTable Columns
const columns = [
    { key: 'invoice', label: 'No. Invoice & Tagihan' },
    { key: 'settled_at', label: 'Tgl. Pelunasan' },
    { key: 'status_tagihan', label: 'Status Tagihan' },
    { key: 'total_amount', label: 'Total Pembayaran' },
];

// Search & Pagination Handlers
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const onSearchInput = (searchQuery: string) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/psb/keuangan/riwayat',
            {
                ...props.filters,
                search: searchQuery,
                page: 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 400);
};

const onLimitChange = (newLimit: number) => {
    router.get(
        '/psb/keuangan/riwayat',
        {
            ...props.filters,
            limit: newLimit,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

// Filter Modal State
const isFilterModalOpen = ref(false);
const filterForm = ref({
    status: props.filters.status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        '/psb/keuangan/riwayat',
        {
            ...props.filters,
            status: filterForm.value.status,
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.value.status = '';
    filterForm.value.start_date = '';
    filterForm.value.end_date = '';
    applyFilters();
};

// Chart Instance
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const renderChart = () => {
    if (!chartCanvas.value) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    const isDark = document.documentElement.classList.contains('dark');
    const primaryColor = '#2563eb';
    const primaryLight = isDark ? 'rgba(37, 99, 235, 0.25)' : 'rgba(37, 99, 235, 0.1)';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.5)' : 'rgba(241, 245, 249, 1)';

    const labels = props.chartData.map((d) => d.month);
    const data = props.chartData.map((d) => d.total);

    chartInstance = new Chart(chartCanvas.value, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Nominal Pelunasan (Rp)',
                    data,
                    borderColor: primaryColor,
                    backgroundColor: primaryLight,
                    borderWidth: 2.5,
                    pointBackgroundColor: primaryColor,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: isDark ? '#0f172a' : '#1e293b',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 12,
                    callbacks: {
                        label: (ctx) => {
                            const val = ctx.parsed.y || 0;
                            return ` Total: ${formatRupiah(val)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11,
                            weight: 600,
                        },
                    },
                },
                y: {
                    grid: {
                        color: gridColor,
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11,
                        },
                        callback: (value) => {
                            const num = Number(value);
                            if (num >= 1000000) return `${(num / 1000000).toFixed(1)}jt`;
                            if (num >= 1000) return `${(num / 1000).toFixed(0)}rb`;
                            return num;
                        },
                    },
                },
            },
        },
    });
};

onMounted(() => {
    renderChart();
});

watch(
    () => props.chartData,
    () => {
        renderChart();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Riwayat Tagihan - PSB Dalwa Kalbar" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Riwayat Tagihan Pendaftaran
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Daftar seluruh invoice tagihan yang telah terselesaikan (Lunas & Keringanan Samaha).
                </p>
            </div>
        </div>

        <!-- 2 Column Side-by-Side: 4 Stat Cards (Left) & Chart (Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            <!-- Left Side: 4 Statistics Cards (5 Cols) -->
            <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Total Riwayat -->
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Riwayat</span>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">
                        {{ props.stats.total_riwayat }} <span class="text-xs font-normal text-slate-400">Invoice</span>
                    </div>
                </div>

                <!-- Total Lunas -->
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tagihan Lunas</span>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ props.stats.total_lunas }} <span class="text-xs font-normal text-slate-400">Selesai</span>
                    </div>
                </div>

                <!-- Samaha -->
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Keringanan Samaha</span>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-black text-purple-600 dark:text-purple-400">
                        {{ props.stats.total_samaha }} <span class="text-xs font-normal text-slate-400">Disetujui</span>
                    </div>
                </div>

                <!-- Total Nominal Selesai -->
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Dana Terlunasi</span>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 font-mono text-lg sm:text-xl font-black text-primary dark:text-blue-400 truncate">
                        {{ formatRupiah(props.stats.total_nominal_selesai) }}
                    </div>
                </div>
            </div>

            <!-- Right Side: Chart Section (7 Cols) -->
            <div class="lg:col-span-7 rounded-3xl border border-gray-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100 pb-3 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                                Grafik Tren Pelunasan
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Nominal pembayaran terverifikasi per bulan (Tahun {{ props.chartYear || 2026 }})
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-slate-50 px-3 py-0.5 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 self-start sm:self-auto">
                        Tahun {{ props.chartYear || 2026 }}
                    </span>
                </div>

                <div class="h-52 sm:h-56 w-full pt-1">
                    <canvas ref="chartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- DataTable Riwayat Tagihan -->
        <div class="mt-6">
            <DataTable
                :columns="columns"
                :data="props.tagihans.data"
                :pagination="props.tagihans"
                @search="onSearchInput"
                @limit="onLimitChange"
            >
                <template #filters>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isFilterModalOpen = true"
                            class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Filter</span>
                        </button>
                    </div>

                    <!-- Filter Modal with Date Range & Status -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Riwayat Tagihan"
                        description="Saring data riwayat berdasarkan status atau rentang tanggal"
                        max-width="md"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Status Penyelesaian
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status"
                                    :options="[
                                        { value: '', label: 'Semua Status Selesai' },
                                        { value: 'LUNAS', label: 'Lunas' },
                                        { value: 'SAMAHA', label: 'Samaha (Keringanan)' },
                                    ]"
                                    placeholder="Pilih Status"
                                />
                            </div>

                            <!-- Rentang Tanggal Tagihan -->
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Rentang Tanggal Tagihan
                                </label>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Dari Tanggal</span>
                                        <CustomDatePicker v-model="filterForm.start_date" />
                                    </div>
                                    <div>
                                        <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Sampai Tanggal</span>
                                        <CustomDatePicker v-model="filterForm.end_date" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </FilterModal>
                </template>

                <!-- Column: No. Invoice & Tagihan -->
                <template #cell-invoice="{ row }">
                    <div class="flex flex-col py-0.5">
                        <Link
                            :href="`/psb/keuangan/riwayat/${row.id}`"
                            class="font-bold text-[13.5px] text-slate-900 hover:text-primary transition-colors dark:text-slate-100 dark:hover:text-blue-400 leading-snug"
                        >
                            {{ row.nomor_invoice || 'Invoice Tagihan' }}
                        </Link>
                        <span class="text-[11.5px] text-slate-500 dark:text-slate-400">
                            {{ row.kategori_biaya?.name || (row.items?.[0]?.description ?? 'Biaya Pendaftaran') }}
                        </span>
                    </div>
                </template>

                <!-- Column: Tgl. Pelunasan -->
                <template #cell-settled_at="{ row }">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                        {{ formatDate(row.updated_at || row.created_at) }}
                    </span>
                </template>

                <!-- Column: Status Tagihan -->
                <template #cell-status_tagihan="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider border whitespace-nowrap"
                        :class="[
                            row.status === 'PAID' || row.status === 'LUNAS'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                : 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900/50 dark:bg-purple-950/60 dark:text-purple-300'
                        ]"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="[
                                row.status === 'PAID' || row.status === 'LUNAS'
                                    ? 'bg-emerald-500'
                                    : 'bg-purple-500'
                            ]"
                        ></span>
                        {{ row.status === 'PAID' || row.status === 'LUNAS' ? 'LUNAS' : 'SAMAHA' }}
                    </span>
                </template>

                <!-- Column: Total Pembayaran -->
                <template #cell-total_amount="{ row }">
                    <span class="font-mono text-sm font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">
                        {{ formatRupiah(row.total_amount || row.amount) }}
                    </span>
                </template>

                <!-- Column: Action Eye Icon Only -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <Link
                            :href="`/psb/keuangan/riwayat/${row.id}`"
                            class="cursor-pointer rounded-full border border-transparent p-2 text-slate-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-blue-400"
                            title="Lihat Detail Riwayat Tagihan"
                        >
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
