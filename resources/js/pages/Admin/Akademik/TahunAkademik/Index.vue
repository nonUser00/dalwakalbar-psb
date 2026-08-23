<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { store, update, destroy } from '@/routes/admin/akademik/tahun_akademik';
import periode from '@/routes/admin/akademik/tahun_akademik/periode';

interface PivotJenjang {
    kuota?: number | null;
}

interface JenjangItem {
    id: string;
    name: string;
    code?: string;
    pivot?: PivotJenjang;
}

interface PeriodeItem {
    id: string;
    tahun_akademik_id: string;
    name: string;
    jalur_pendaftaran?: string;
    status: 'buka' | 'tutup' | 'draft';
    kuota?: number | null;
    start_date?: string;
    end_date?: string;
    jenjangs?: JenjangItem[];
    tahun_akademik?: TahunAkademikItem;
}

interface TahunAkademikItem {
    id: string;
    name: string;
    is_active: boolean;
    periodes?: PeriodeItem[];
}

const props = defineProps<{
    tahunAkademiks: TahunAkademikItem[];
    jenjangs: JenjangItem[];
}>();

// Read initial active tab from URL query parameter ?tab=
const getInitialTab = (): 'tahun_akademik' | 'periode' => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');

        if (tab === 'periode' || tab === 'tahun_akademik') {
            return tab;
        }
    }

    return 'tahun_akademik';
};

const activeTab = ref<'tahun_akademik' | 'periode'>(getInitialTab());

const switchTab = (tab: 'tahun_akademik' | 'periode') => {
    activeTab.value = tab;

    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }
};

// Filter Periode Tab directly when clicking TOTAL PERIODE in TA table
const filterPeriodeByTa = (taId: string) => {
    selectedTaFilter.value = taId;
    switchTab('periode');
};

// ====================================================
// TAB 1: MASTER TAHUN AKADEMIK STATE & COLUMNS
// ====================================================
const taColumns = [
    { key: 'name', label: 'NAMA TAHUN AKADEMIK' },
    { key: 'periodes', label: 'TOTAL PERIODE' },
    { key: 'is_active', label: 'STATUS KEAKTIFAN' },
];

const searchTaQuery = ref<string>('');
const perPageTa = ref<number>(5);
const currentPageTa = ref<number>(1);

watch([searchTaQuery, perPageTa], () => {
    currentPageTa.value = 1;
});

const filteredTahunAkademiks = computed(() => {
    if (!props.tahunAkademiks) {
        return [];
    }

    return props.tahunAkademiks.filter(
        (ta) =>
            !searchTaQuery.value ||
            ta.name.toLowerCase().includes(searchTaQuery.value.toLowerCase()),
    );
});

const totalFilteredTaItems = computed(
    () => filteredTahunAkademiks.value.length,
);
const totalTaPages = computed(() =>
    Math.max(1, Math.ceil(totalFilteredTaItems.value / perPageTa.value)),
);

const paginatedTahunAkademiks = computed(() => {
    const start = (currentPageTa.value - 1) * perPageTa.value;

    return filteredTahunAkademiks.value.slice(start, start + perPageTa.value);
});

const showingTaFrom = computed(() =>
    totalFilteredTaItems.value === 0
        ? 0
        : (currentPageTa.value - 1) * perPageTa.value + 1,
);
const showingTaTo = computed(() =>
    Math.min(currentPageTa.value * perPageTa.value, totalFilteredTaItems.value),
);

const taPagination = computed(() => {
    const total = totalFilteredTaItems.value;
    const perPage = perPageTa.value;
    const current = currentPageTa.value;
    const lastPage = totalTaPages.value;

    const links = [];
    links.push({
        url: current > 1 ? '#' : null,
        label: '&laquo;',
        active: false,
        onClick: () => current > 1 && currentPageTa.value--,
    });

    for (let p = 1; p <= lastPage; p++) {
        links.push({
            url: '#',
            label: String(p),
            active: p === current,
            onClick: () => (currentPageTa.value = p),
        });
    }

    links.push({
        url: current < lastPage ? '#' : null,
        label: '&raquo;',
        active: false,
        onClick: () => current < lastPage && currentPageTa.value++,
    });

    return {
        from: showingTaFrom.value,
        to: showingTaTo.value,
        total,
        per_page: perPage,
        current_page: current,
        last_page: lastPage,
        links,
    };
});

// ====================================================
// TAB 2: PERIODE PENDAFTARAN STATE & COLUMNS
// ====================================================
const periodeColumns = [
    { key: 'name', label: 'NAMA PERIODE' },
    { key: 'tahun_akademik', label: 'TAHUN AKADEMIK' },
    { key: 'jenjangs', label: 'JENJANG & RINCIAN KUOTA' },
    { key: 'tanggal', label: 'TANGGAL BUKA - TUTUP' },
    { key: 'status', label: 'STATUS' },
];

const selectedTaFilter = ref<string>('all');
const selectedStatusFilter = ref<string>('all');
const searchQuery = ref<string>('');
const perPagePeriode = ref<number>(5);
const currentPagePeriode = ref<number>(1);
const isFilterModalOpen = ref(false);

const isFilterActive = computed(() => {
    return (
        selectedTaFilter.value !== 'all' || selectedStatusFilter.value !== 'all'
    );
});

const taOptions = computed(() => {
    const list = [{ value: 'all', label: 'Semua Tahun Akademik' }];

    if (props.tahunAkademiks) {
        props.tahunAkademiks.forEach((ta) => {
            list.push({
                value: ta.id,
                label: `TA ${ta.name}${ta.is_active ? ' (Aktif)' : ''}`,
            });
        });
    }

    return list;
});

const statusOptions = [
    { value: 'all', label: 'Semua Status' },
    { value: 'buka', label: 'Buka' },
    { value: 'tutup', label: 'Tutup' },
    { value: 'draft', label: 'Draft' },
];

const applyFilters = () => {
    isFilterModalOpen.value = false;
};

const resetFilters = () => {
    selectedTaFilter.value = 'all';
    selectedStatusFilter.value = 'all';
    isFilterModalOpen.value = false;
};

watch(
    [searchQuery, selectedTaFilter, selectedStatusFilter, perPagePeriode],
    () => {
        currentPagePeriode.value = 1;
    },
);

// Active TA
const activeTa = computed(() => {
    return props.tahunAkademiks
        ? props.tahunAkademiks.find((ta) => ta.is_active)
        : undefined;
});

// All Periodes compiled with TA reference
const allPeriodes = computed(() => {
    const list: PeriodeItem[] = [];

    if (props.tahunAkademiks) {
        props.tahunAkademiks.forEach((ta) => {
            if (ta.periodes) {
                ta.periodes.forEach((p) => {
                    list.push({
                        ...p,
                        tahun_akademik: ta,
                    });
                });
            }
        });
    }

    return list;
});

// Periodes of current Active TA ONLY
const activeTaPeriodes = computed(() => {
    if (!activeTa.value) {
        return [];
    }

    return allPeriodes.value.filter(
        (p) => p.tahun_akademik_id === activeTa.value?.id,
    );
});

// Active (Buka) Periodes for Active TA
const openPeriodes = computed(() => {
    return activeTaPeriodes.value.filter((p) => p.status === 'buka');
});

// Closed (Tutup) Periodes for Active TA
const closedPeriodes = computed(() => {
    return activeTaPeriodes.value.filter((p) => p.status === 'tutup');
});

// Draft Periodes for Active TA
const draftPeriodes = computed(() => {
    return activeTaPeriodes.value.filter((p) => p.status === 'draft');
});

// Filtered Periodes
const filteredPeriodes = computed(() => {
    return allPeriodes.value.filter((p) => {
        const matchesTa =
            selectedTaFilter.value === 'all' ||
            p.tahun_akademik_id === selectedTaFilter.value;
        const matchesStatus =
            selectedStatusFilter.value === 'all' ||
            p.status === selectedStatusFilter.value;
        const matchesSearch =
            !searchQuery.value ||
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (p.tahun_akademik?.name || '')
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase());

        return matchesTa && matchesStatus && matchesSearch;
    });
});

const totalFilteredPeriodeItems = computed(() => filteredPeriodes.value.length);
const totalPeriodePages = computed(() =>
    Math.max(
        1,
        Math.ceil(totalFilteredPeriodeItems.value / perPagePeriode.value),
    ),
);

const paginatedPeriodes = computed(() => {
    const start = (currentPagePeriode.value - 1) * perPagePeriode.value;

    return filteredPeriodes.value.slice(start, start + perPagePeriode.value);
});

const showingPeriodeFrom = computed(() =>
    totalFilteredPeriodeItems.value === 0
        ? 0
        : (currentPagePeriode.value - 1) * perPagePeriode.value + 1,
);
const showingPeriodeTo = computed(() =>
    Math.min(
        currentPagePeriode.value * perPagePeriode.value,
        totalFilteredPeriodeItems.value,
    ),
);

const periodePagination = computed(() => {
    const total = totalFilteredPeriodeItems.value;
    const perPage = perPagePeriode.value;
    const current = currentPagePeriode.value;
    const lastPage = totalPeriodePages.value;

    const links = [];
    links.push({
        url: current > 1 ? '#' : null,
        label: '&laquo;',
        active: false,
        onClick: () => current > 1 && currentPagePeriode.value--,
    });

    for (let p = 1; p <= lastPage; p++) {
        links.push({
            url: '#',
            label: String(p),
            active: p === current,
            onClick: () => (currentPagePeriode.value = p),
        });
    }

    links.push({
        url: current < lastPage ? '#' : null,
        label: '&raquo;',
        active: false,
        onClick: () => current < lastPage && currentPagePeriode.value++,
    });

    return {
        from: showingPeriodeFrom.value,
        to: showingPeriodeTo.value,
        total,
        per_page: perPage,
        current_page: current,
        last_page: lastPage,
        links,
    };
});

// Format ISO date string into readable Indonesian date
const formatDate = (dateStr?: string) => {
    if (!dateStr) {
        return '-';
    }

    const cleanStr = dateStr.split('T')[0];
    const parts = cleanStr.split('-');

    if (parts.length === 3) {
        const year = parts[0];
        const monthIndex = parseInt(parts[1], 10) - 1;
        const day = parts[2];
        const months = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des',
        ];

        if (monthIndex >= 0 && monthIndex < 12) {
            return `${day} ${months[monthIndex]} ${year}`;
        }
    }

    return dateStr;
};

// Modals State
const modalTaOpen = ref(false);
const modalDeleteOpen = ref(false);
const isEditingTa = ref(false);
const itemToDelete = ref<{
    modelKey: 'tahun-akademik' | 'periode';
    id: string;
    name: string;
} | null>(null);

// Form for TA
const taForm = useForm({
    id: '',
    name: '',
    is_active: false,
});

// Openers
const openAddTaModal = () => {
    isEditingTa.value = false;
    taForm.reset();
    taForm.clearErrors();
    modalTaOpen.value = true;
};

const openEditTaModal = (item: TahunAkademikItem) => {
    isEditingTa.value = true;
    taForm.id = item.id;
    taForm.name = item.name;
    taForm.is_active = Boolean(item.is_active);
    taForm.clearErrors();
    modalTaOpen.value = true;
};

const toggleTaActive = (item: TahunAkademikItem) => {
    router.put(
        update.url({ model: 'tahun-akademik', id: item.id }),
        {
            name: item.name,
            is_active: !item.is_active,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const closeModal = () => {
    modalTaOpen.value = false;
    modalDeleteOpen.value = false;
    taForm.reset();
    taForm.clearErrors();
};

const submitTa = () => {
    if (isEditingTa.value) {
        taForm.put(update.url({ model: 'tahun-akademik', id: taForm.id }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        taForm.post(store.url({ model: 'tahun-akademik' }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const openDeleteModal = (
    modelKey: 'tahun-akademik' | 'periode',
    id: string,
    name: string,
) => {
    itemToDelete.value = { modelKey, id, name };
    modalDeleteOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(
            destroy.url({
                model: itemToDelete.value.modelKey,
                id: itemToDelete.value.id,
            }),
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    modalDeleteOpen.value = false;
                    itemToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Tahun Akademik & Periode Pendaftaran" />

        <!-- Header Page -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Tahun Akademik & Periode Pendaftaran
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola master tahun akademik santri baru serta konfigurasi
                    gelombang & periode pendaftaran.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <SecondaryButton @click="openAddTaModal" class="font-bold">
                    <svg
                        class="mr-2 h-4 w-4 text-gray-500 dark:text-slate-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Tambah TA
                </SecondaryButton>

                <Link :href="periode.create.url()">
                    <PrimaryButton class="font-bold">
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Tambah Periode
                    </PrimaryButton>
                </Link>
            </div>
        </div>

        <!-- Stat Cards (ALWAYS DISPLAYED AT TOP ABOVE TABS) -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Card 1: Tahun Akademik Aktif -->
            <div
                class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/70 via-white to-emerald-50/30 p-5 shadow-xs transition-all hover:shadow-md dark:border-emerald-900/40 dark:bg-gradient-to-br dark:from-emerald-950/40 dark:via-slate-900 dark:to-emerald-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-xs font-bold tracking-wider text-emerald-800 uppercase dark:text-emerald-400"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        Tahun Akademik Aktif
                    </span>
                    <span
                        v-if="activeTa"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/40 bg-emerald-500/15 px-2.5 py-1 text-xs font-extrabold text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-500/20 dark:text-emerald-300"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-emerald-600"
                            ></span>
                        </span>
                        Aktif
                    </span>
                </div>
                <div class="mt-3">
                    <span
                        class="text-2xl font-black tracking-tight text-emerald-950 dark:text-emerald-100"
                    >
                        {{
                            activeTa ? 'TA ' + activeTa.name : 'Belum Set Aktif'
                        }}
                    </span>
                    <p
                        class="mt-1 text-xs font-medium text-emerald-700/80 dark:text-emerald-400/80"
                    >
                        Dari {{ tahunAkademiks.length }} master tahun akademik
                        terdaftar
                    </p>
                </div>
            </div>

            <!-- Card 2: Periode Pendaftaran Aktif (Status Buka TA Aktif) -->
            <div
                class="relative overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/70 via-white to-blue-50/30 p-5 shadow-xs transition-all hover:shadow-md dark:border-indigo-900/40 dark:bg-gradient-to-br dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-xs font-bold tracking-wider text-indigo-800 uppercase dark:text-indigo-400"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                                />
                            </svg>
                        </div>
                        Periode Pendaftaran Aktif
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-500/10 px-2.5 py-1 text-xs font-extrabold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        {{ openPeriodes.length }} Buka
                    </span>
                </div>
                <div class="mt-3">
                    <span
                        class="text-2xl font-black tracking-tight text-indigo-950 dark:text-indigo-100"
                    >
                        {{ openPeriodes.length }} Periode Buka
                    </span>
                    <p
                        class="mt-1 text-xs font-medium text-indigo-700/80 dark:text-indigo-400/80"
                    >
                        Periode aktif pada
                        {{ activeTa ? 'TA ' + activeTa.name : 'TA Aktif' }}
                    </p>
                </div>
            </div>

            <!-- Card 3: Ringkasan Periode TA Aktif -->
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        Ringkasan Periode TA Aktif
                    </span>
                    <span
                        class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-extrabold text-gray-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300"
                    >
                        {{ activeTaPeriodes.length }} Total
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        {{ openPeriodes.length }} Buka
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-rose-500"
                        ></span>
                        {{ closedPeriodes.length }} Tutup
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-amber-500"
                        ></span>
                        {{ draftPeriodes.length }} Draft
                    </span>
                </div>
                <p
                    class="mt-2 text-xs font-medium text-gray-500 dark:text-slate-400"
                >
                    Status gelombang di
                    {{ activeTa ? 'TA ' + activeTa.name : 'TA Aktif' }}
                </p>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <div
            class="mb-6 border-b border-gray-200 dark:border-slate-700 dark:border-slate-800"
        >
            <nav class="-mb-px flex space-x-6">
                <!-- TAB 1: Tahun Akademik -->
                <button
                    @click="switchTab('tahun_akademik')"
                    class="flex cursor-pointer items-center gap-2 border-b-2 px-1 py-3 text-sm font-bold transition-colors"
                    :class="
                        activeTab === 'tahun_akademik'
                            ? 'border-primary text-primary dark:border-blue-500 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200'
                    "
                >
                    <svg
                        class="h-4.5 w-4.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                    Tahun Akademik
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="
                            activeTab === 'tahun_akademik'
                                ? 'bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300'
                        "
                    >
                        {{ tahunAkademiks.length }}
                    </span>
                </button>

                <!-- TAB 2: Periode Pendaftaran -->
                <button
                    @click="switchTab('periode')"
                    class="flex cursor-pointer items-center gap-2 border-b-2 px-1 py-3 text-sm font-bold transition-colors"
                    :class="
                        activeTab === 'periode'
                            ? 'border-primary text-primary dark:border-blue-500 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200'
                    "
                >
                    <svg
                        class="h-4.5 w-4.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    Periode Pendaftaran
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="
                            activeTab === 'periode'
                                ? 'bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300'
                        "
                    >
                        {{ allPeriodes.length }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- ==================================================== -->
        <!-- TAB 1: MASTER TAHUN AKADEMIK (Using DataTable Component) -->
        <!-- ==================================================== -->
        <div v-if="activeTab === 'tahun_akademik'" class="space-y-4">
            <DataTable
                :columns="taColumns"
                :data="paginatedTahunAkademiks"
                :pagination="taPagination"
                @search="(val) => (searchTaQuery = val)"
                @limit="(val) => (perPageTa = val)"
            >
                <template #cell-name="{ row }">
                    <p
                        class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                    >
                        TA {{ row.name }}
                    </p>
                </template>

                <template #cell-periodes="{ row }">
                    <button
                        type="button"
                        @click="filterPeriodeByTa(row.id)"
                        class="hover:text-primary-hover group inline-flex cursor-pointer items-center gap-1.5 text-[13px] font-medium text-primary transition-colors hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                        title="Klik untuk melihat periode pendaftaran TA ini"
                    >
                        <span
                            >{{
                                row.periodes ? row.periodes.length : 0
                            }}
                            Periode Pendaftaran</span
                        >
                        <svg
                            class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </template>

                <template #cell-is_active="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold"
                        :class="
                            row.is_active
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400'
                                : 'border-gray-200 bg-gray-100 text-gray-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                        "
                    >
                        <span
                            v-if="row.is_active"
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        {{ row.is_active ? 'Aktif (Berjalan)' : 'Non-Aktif' }}
                    </span>
                </template>

                <template #row-actions="{ row }">
                    <ActionMenu>
                        <template #trigger>
                            <button
                                class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                title="Opsi"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                    />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <Link
                                :href="
                                    periode.create.url({
                                        query: { tahun_akademik_id: row.id },
                                    })
                                "
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-emerald-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Periode
                            </Link>
                            <button
                                @click="toggleTaActive(row)"
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold transition-colors sm:px-4"
                                :class="
                                    row.is_active
                                        ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40'
                                        : 'text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40'
                                "
                            >
                                <svg
                                    v-if="row.is_active"
                                    class="mr-3 h-4 w-4 text-rose-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="mr-3 h-4 w-4 text-blue-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{ row.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button
                                @click="openEditTaModal(row)"
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-amber-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                                Edit
                            </button>
                            <button
                                @click="
                                    openDeleteModal(
                                        'tahun-akademik',
                                        row.id,
                                        `TA ${row.name}`,
                                    )
                                "
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            >
                                <svg
                                    class="mr-2.5 h-4 w-4 text-rose-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                Hapus
                            </button>
                        </template>
                    </ActionMenu>
                </template>
            </DataTable>
        </div>

        <!-- ==================================================== -->
        <!-- TAB 2: DAFTAR PERIODE PENDAFTARAN (Using DataTable Component) -->
        <!-- ==================================================== -->
        <div v-if="activeTab === 'periode'" class="space-y-4">
            <DataTable
                :columns="periodeColumns"
                :data="paginatedPeriodes"
                :pagination="periodePagination"
                @search="(val) => (searchQuery = val)"
                @limit="(val) => (perPagePeriode = val)"
            >
                <template #filters>
                    <!-- Standard Filter Modal Button matching Pegawai / RolePermission style -->
                    <button
                        @click="isFilterModalOpen = true"
                        class="group inline-flex h-full cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <svg
                            class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-400 dark:text-slate-500 dark:group-hover:text-blue-400"
                            :class="
                                isFilterActive
                                    ? 'text-primary dark:text-blue-400'
                                    : ''
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                            />
                        </svg>
                        <span class="ml-2 hidden sm:inline">Filter</span>
                        <span
                            v-if="isFilterActive"
                            class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2 dark:bg-blue-500"
                        ></span>
                    </button>

                    <!-- Filter Modal Popup -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Periode Pendaftaran"
                        description="Saring data berdasarkan Tahun Akademik atau Status"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="space-y-4">
                            <CustomSelect
                                label="Tahun Akademik"
                                v-model="selectedTaFilter"
                                :options="taOptions"
                            />
                            <CustomSelect
                                label="Status Periode"
                                v-model="selectedStatusFilter"
                                :options="statusOptions"
                            />
                        </div>
                    </FilterModal>
                </template>

                <template #cell-name="{ row }">
                    <div class="flex flex-col gap-0.5">
                        <p
                            class="text-[15px] font-bold whitespace-nowrap text-slate-800 dark:text-slate-100"
                        >
                            {{ row.name }}
                        </p>
                        <p
                            class="text-[13px] font-medium whitespace-nowrap text-slate-500 dark:text-slate-400"
                        >
                            Jalur: {{ row.jalur_pendaftaran || 'Semua Jalur' }}
                        </p>
                    </div>
                </template>

                <template #cell-tahun_akademik="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold whitespace-nowrap"
                        :class="
                            row.tahun_akademik?.is_active
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400'
                                : 'border-gray-200 bg-gray-100 text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300'
                        "
                    >
                        <span
                            v-if="row.tahun_akademik?.is_active"
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        TA {{ row.tahun_akademik?.name || '-' }}
                    </span>
                </template>

                <template #cell-jenjangs="{ row }">
                    <div
                        v-if="row.jenjangs && row.jenjangs.length > 0"
                        class="space-y-1.5 py-0.5"
                    >
                        <div
                            v-for="j in row.jenjangs"
                            :key="j.id"
                            class="flex items-center text-[13px] whitespace-nowrap"
                        >
                            <span
                                class="w-[180px] shrink-0 font-bold whitespace-nowrap text-slate-800 dark:text-slate-100"
                                >{{ j.name }}</span
                            >
                            <span
                                class="mr-2.5 shrink-0 font-bold text-gray-400 dark:text-slate-500"
                                >:</span
                            >
                            <span
                                class="font-medium whitespace-nowrap text-slate-700 dark:text-slate-300"
                            >
                                {{
                                    j.pivot &&
                                    j.pivot.kuota !== null &&
                                    j.pivot.kuota !== undefined
                                        ? j.pivot.kuota + ' Santri'
                                        : 'Tanpa Batas Kuota'
                                }}
                            </span>
                        </div>
                    </div>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 italic dark:text-slate-500"
                        >Belum disetting</span
                    >
                </template>

                <template #cell-tanggal="{ row }">
                    <div
                        v-if="row.start_date || row.end_date"
                        class="inline-flex items-center gap-2 text-[13px] font-medium whitespace-nowrap text-slate-700 dark:text-slate-300"
                    >
                        <svg
                            class="h-4 w-4 shrink-0 text-gray-400 dark:text-slate-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span
                            class="font-bold text-slate-800 dark:text-slate-100"
                            >{{ formatDate(row.start_date) }}</span
                        >
                        <span
                            class="font-normal text-gray-400 dark:text-slate-500"
                            >s/d</span
                        >
                        <span
                            class="font-bold text-slate-800 dark:text-slate-100"
                            >{{ formatDate(row.end_date) }}</span
                        >
                    </div>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 italic dark:text-slate-500"
                        >-</span
                    >
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold capitalize"
                        :class="{
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400':
                                row.status === 'buka',
                            'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400':
                                row.status === 'tutup',
                            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400':
                                row.status === 'draft',
                        }"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="{
                                'bg-emerald-500': row.status === 'buka',
                                'bg-rose-500': row.status === 'tutup',
                                'bg-amber-500': row.status === 'draft',
                            }"
                        ></span>
                        {{ row.status }}
                    </span>
                </template>

                <template #row-actions="{ row }">
                    <ActionMenu>
                        <template #trigger>
                            <button
                                class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                title="Opsi"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                    />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <Link
                                :href="periode.edit.url(row.id)"
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-amber-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                                Edit
                            </Link>
                            <button
                                @click="
                                    openDeleteModal('periode', row.id, row.name)
                                "
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            >
                                <svg
                                    class="mr-2.5 h-4 w-4 text-rose-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                Hapus
                            </button>
                        </template>
                    </ActionMenu>
                </template>
            </DataTable>
        </div>

        <!-- ==================================================== -->
        <!-- MODAL FORM TAHUN AKADEMIK -->
        <!-- ==================================================== -->
        <Modal
            :show="modalTaOpen"
            @close="closeModal"
            maxWidth="md"
            :title="isEditingTa ? 'Edit TA' : 'Tambah TA'"
            :description="isEditingTa ? 'Perbarui data TA' : 'Tambah TA baru'"
        >
            <form id="taForm" @submit.prevent="submitTa" class="space-y-4">
                <TextInput
                    id="ta_name"
                    label="Nama Tahun Akademik *"
                    v-model="taForm.name"
                    :error="taForm.errors.name"
                    placeholder="Contoh: 2026/2027, 2027/2028"
                    required
                />

                <div
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800"
                >
                    <div>
                        <span
                            class="block text-sm font-semibold text-gray-900 dark:text-slate-100"
                            >Set Sebagai TA Aktif</span
                        >
                        <span class="text-xs text-gray-500 dark:text-slate-400"
                            >Hanya ada 1 Tahun Akademik yang aktif
                            bersamaan</span
                        >
                    </div>
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <Checkbox v-model:checked="taForm.is_active" />
                    </label>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="taForm"
                    type="submit"
                    :disabled="taForm.processing"
                >
                    {{ isEditingTa ? 'Simpan' : 'Tambah' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL DELETE KONFIRMASI -->
        <!-- ==================================================== -->
        <Modal
            :show="modalDeleteOpen"
            @close="modalDeleteOpen = false"
            maxWidth="sm"
            :title="
                itemToDelete?.modelKey === 'tahun-akademik'
                    ? 'Hapus TA?'
                    : 'Hapus Periode?'
            "
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-950/50"
                >
                    <svg
                        class="h-6 w-6 text-rose-500 dark:text-rose-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>
            <div
                class="p-5 text-sm leading-relaxed text-gray-600 dark:text-slate-300"
            >
                Apakah Anda yakin ingin menghapus data
                <strong class="text-gray-900 dark:text-slate-100"
                    >"{{ itemToDelete?.name }}"</strong
                >?
            </div>
            <template #footer>
                <SecondaryButton @click="modalDeleteOpen = false"
                    >Batal</SecondaryButton
                >
                <DangerButton @click="executeDelete">Ya, Hapus</DangerButton>
            </template>
        </Modal>
    </div>
</template>
