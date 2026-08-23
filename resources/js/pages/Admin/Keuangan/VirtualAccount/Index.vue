<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { getBankLogo, handleBankLogoError } from '@/lib/bank';

import { store, destroy } from '@/routes/admin/keuangan/va';

interface BankItem {
    id: string;
    kode_bank?: string;
    singkatan?: string;
    name: string;
    logo_path?: string;
    logo_url?: string;
}

interface PendaftarBasic {
    id: string;
    nik: string;
    name: string;
    nomor_pendaftaran?: string;
    cabang?: string;
    jenjang?: string;
}

interface PendaftarVARow {
    id: string;
    nik: string;
    name: string;
    nomor_pendaftaran?: string;
    cabang?: string;
    jenjang?: string;
    va_numbers: Record<string, string | null>;
    va_ids: Record<string, string | null>;
    all_va_ids: string[];
    has_va?: boolean;
    is_complete?: boolean;
}

const props = defineProps<{
    banks: BankItem[];
    pendaftars: any;
    allPendaftars: PendaftarBasic[];
    cabangs?: string[];
    jenjangs?: string[];
    filters: Record<string, string>;
}>();

// Columns without logo icons
const columns = computed(() => {
    const cols = [
        { key: 'name', label: 'PENDAFTAR' },
        { key: 'nik', label: 'NIK' },
        { key: 'cabang', label: 'CABANG' },
        { key: 'jenjang', label: 'JENJANG' },
    ];

    props.banks.forEach((bank) => {
        cols.push({
            key: `bank_${bank.id}`,
            label: (bank.singkatan || bank.name).toUpperCase(),
        });
    });

    return cols;
});

// Selection state
const selectedRows = ref<string[]>([]);
const handleSelection = (ids: string[]) => {
    selectedRows.value = ids;
};

// ==========================================
// FILTER MODAL STATE (Server-side Matching Pegawai Index)
// ==========================================
const isFilterModalOpen = ref(false);

const filterForm = useForm({
    cabang: props.filters?.cabang || '',
    jenjang: props.filters?.jenjang || '',
    status_va: props.filters?.status_va || '',
});

const isFilterActive = computed(() => {
    return (
        filterForm.cabang !== '' ||
        filterForm.jenjang !== '' ||
        filterForm.status_va !== ''
    );
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        '/admin/keuangan/va',
        {
            search: props.filters?.search || '',
            limit: props.pendaftars?.per_page || 10,
            cabang: filterForm.cabang,
            jenjang: filterForm.jenjang,
            status_va: filterForm.status_va,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.cabang = '';
    filterForm.jenjang = '';
    filterForm.status_va = '';
    isFilterModalOpen.value = false;
    router.get(
        '/admin/keuangan/va',
        {
            search: props.filters?.search || '',
            limit: props.pendaftars?.per_page || 10,
        },
        { preserveState: true, replace: true },
    );
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const handleSearch = (search: string) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/keuangan/va',
            {
                search,
                cabang: filterForm.cabang,
                jenjang: filterForm.jenjang,
                status_va: filterForm.status_va,
                limit: props.pendaftars?.per_page || 10,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
};

const handleLimit = (limit: number) => {
    router.get(
        '/admin/keuangan/va',
        {
            limit,
            search: props.filters?.search,
            cabang: filterForm.cabang,
            jenjang: filterForm.jenjang,
            status_va: filterForm.status_va,
        },
        { preserveState: true, replace: true },
    );
};

// ==========================================
// MODAL STATE: TAMBAH / EDIT VIRTUAL ACCOUNT
// ==========================================
const modalVAMode = ref<'add' | 'edit' | null>(null);
const pendaftarSearchInput = ref<string>('');
const isDropdownOpen = ref<boolean>(false);
const selectedPendaftar = ref<PendaftarBasic | null>(null);

const vaForm = useForm({
    pendaftar_id: '',
    vas: [] as { bank_id: string; nomor_va: string }[],
});

const filteredPendaftarOptions = computed(() => {
    if (!props.allPendaftars) {
        return [];
    }

    const q = pendaftarSearchInput.value.toLowerCase().trim();

    if (!q) {
        return props.allPendaftars.slice(0, 10);
    }

    return props.allPendaftars
        .filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                p.nik.toLowerCase().includes(q) ||
                (p.nomor_pendaftaran || '').toLowerCase().includes(q),
        )
        .slice(0, 10);
});

const selectPendaftarForVA = (pendaftar: PendaftarBasic) => {
    selectedPendaftar.value = pendaftar;
    vaForm.pendaftar_id = pendaftar.id;
    pendaftarSearchInput.value = `${pendaftar.name} (${pendaftar.nik})`;
    isDropdownOpen.value = false;

    const pendaftarList = Array.isArray(props.pendaftars)
        ? props.pendaftars
        : props.pendaftars?.data || [];
    const existing = pendaftarList.find((p: any) => p.id === pendaftar.id);
    vaForm.vas = props.banks.map((bank) => ({
        bank_id: bank.id,
        nomor_va: existing?.va_numbers[bank.id] || '',
    }));
};

const openAddVAModal = () => {
    modalVAMode.value = 'add';
    selectedPendaftar.value = null;
    pendaftarSearchInput.value = '';
    isDropdownOpen.value = false;
    vaForm.reset();
    vaForm.clearErrors();
    vaForm.pendaftar_id = '';
    vaForm.vas = props.banks.map((bank) => ({
        bank_id: bank.id,
        nomor_va: '',
    }));
};

const openEditVAModal = (row: PendaftarVARow) => {
    modalVAMode.value = 'edit';
    selectedPendaftar.value = {
        id: row.id,
        nik: row.nik,
        name: row.name,
        nomor_pendaftaran: row.nomor_pendaftaran,
        cabang: row.cabang,
        jenjang: row.jenjang,
    };
    pendaftarSearchInput.value = `${row.name} (${row.nik})`;
    isDropdownOpen.value = false;
    vaForm.reset();
    vaForm.clearErrors();
    vaForm.pendaftar_id = row.id;
    vaForm.vas = props.banks.map((bank) => ({
        bank_id: bank.id,
        nomor_va: row.va_numbers[bank.id] || '',
    }));
};

const closeVAModal = () => {
    modalVAMode.value = null;
    selectedPendaftar.value = null;
    pendaftarSearchInput.value = '';
    vaForm.reset();
    vaForm.clearErrors();
};

const submitVA = () => {
    vaForm.post(store.url(), {
        onSuccess: () => closeVAModal(),
        preserveScroll: true,
        preserveState: true,
    });
};

// ==========================================
// MODAL STATE: DELETE & BULK DELETE
// ==========================================
const modalDeleteOpen = ref<boolean>(false);
const deleteTarget = ref<{
    isBulk: boolean;
    id?: string;
    name?: string;
} | null>(null);

const openDeleteSingleModal = (row: PendaftarVARow) => {
    deleteTarget.value = {
        isBulk: false,
        id: row.id,
        name: row.name,
    };
    modalDeleteOpen.value = true;
};

const openDeleteBulkModal = () => {
    if (selectedRows.value.length === 0) {
        return;
    }

    deleteTarget.value = {
        isBulk: true,
    };
    modalDeleteOpen.value = true;
};

const executeDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    if (deleteTarget.value.isBulk) {
        router.delete(destroy.url({ va: 'bulk' }), {
            data: { pendaftar_ids: selectedRows.value },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                modalDeleteOpen.value = false;
                selectedRows.value = [];
            },
        });
    } else if (deleteTarget.value.id) {
        router.delete(destroy.url({ va: deleteTarget.value.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                modalDeleteOpen.value = false;
            },
        });
    }
};

const executeExport = (ids: string[] = []) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/keuangan/va/export';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value =
        document.head
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';
    form.appendChild(csrf);

    if (ids.length > 0) {
        ids.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const handleExportSelected = (selectedIds: string[]) => {
    if (selectedIds && selectedIds.length > 0) {
        executeExport(selectedIds);
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Virtual Account Pendaftar" />

        <!-- Page Header & Actions (Matching Pegawai Index) -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Virtual Account Pendaftar
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola data nomor Virtual Account bank pembayaran santri.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Dropdown align="responsive" width="48">
                    <template #trigger>
                        <SecondaryButton type="button" class="h-full">
                            Opsi Lainnya
                            <svg
                                class="-mr-0.5 ml-2 h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </SecondaryButton>
                    </template>
                    <template #content>
                        <DropdownLink href="/admin/keuangan/va/import">
                            <div class="flex items-center">
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    />
                                </svg>
                                Import Data
                            </div>
                        </DropdownLink>
                        <a
                            href="/admin/keuangan/va/template"
                            download
                            class="block px-4 py-2 text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                        >
                            <div class="flex items-center">
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
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Download Template
                            </div>
                        </a>
                        <button
                            type="button"
                            @click="executeExport([])"
                            class="block w-full cursor-pointer px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                        >
                            <div class="flex items-center">
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    />
                                </svg>
                                Export Data
                            </div>
                        </button>
                    </template>
                </Dropdown>

                <PrimaryButton class="h-full" @click="openAddVAModal">
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
                    Tambah VA
                </PrimaryButton>
            </div>
        </div>

        <div class="mt-6">
            <!-- Data Table (Server-side Matching Pegawai Index) -->
            <DataTable
                :columns="columns"
                :data="props.pendaftars?.data || props.pendaftars || []"
                :pagination="props.pendaftars"
                :selectable="true"
                @search="handleSearch"
                @limit="handleLimit"
                @selection-change="handleSelection"
            >
                <!-- Bulk Actions -->
                <template #bulk-actions="{ selectedIds }">
                    <div v-if="selectedIds.length > 0" class="flex gap-2">
                        <button
                            @click="handleExportSelected(selectedIds)"
                            class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4 text-gray-500 dark:text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Export
                        </button>
                        <button
                            @click="openDeleteBulkModal"
                            class="inline-flex cursor-pointer items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-bold text-rose-700 shadow-sm transition-all hover:bg-rose-100 focus:ring-2 focus:ring-rose-500/20 focus:outline-none sm:px-4 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-900/50"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4 text-rose-500 dark:text-rose-400"
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
                    </div>
                </template>

                <!-- Filter Modal Slot (Matching Pegawai Index) -->
                <template #filters>
                    <!-- Trigger Button -->
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

                    <!-- Filter Modal Component -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Data Virtual Account"
                        description="Saring data berdasarkan cabang, jenjang, atau status VA"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Pondok / Cabang</label
                            >
                            <CustomSelect
                                v-model="filterForm.cabang"
                                :options="
                                    props.cabangs || [
                                        'Kalimantan Barat',
                                        'Kalimantan Timur',
                                    ]
                                "
                                placeholder="Semua Cabang"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Jenjang Pendidikan</label
                            >
                            <CustomSelect
                                v-model="filterForm.jenjang"
                                :options="
                                    props.jenjangs || [
                                        'MTs',
                                        'MA',
                                        'S1',
                                        'S2',
                                        'S3',
                                    ]
                                "
                                placeholder="Semua Jenjang"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Status Virtual Account</label
                            >
                            <CustomSelect
                                v-model="filterForm.status_va"
                                :options="[
                                    'Sudah Lengkap',
                                    'Sudah Ada VA',
                                    'Belum Ada VA',
                                ]"
                                placeholder="Semua Status"
                            />
                        </div>
                    </FilterModal>
                </template>

                <!-- Cell: Nama Pendaftar -->
                <template #cell-name="{ row }">
                    <div>
                        <p
                            class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ row.name }}
                        </p>
                        <p
                            class="text-xs font-medium text-slate-400 dark:text-slate-500"
                        >
                            {{ row.nomor_pendaftaran || '-' }}
                        </p>
                    </div>
                </template>

                <!-- Cell: NIK -->
                <template #cell-nik="{ row }">
                    <span
                        class="font-mono text-[13px] font-medium text-slate-700 dark:text-slate-300"
                    >
                        {{ row.nik }}
                    </span>
                </template>

                <!-- Cell: Cabang (Polosan tanpa bg badge / logo) -->
                <template #cell-cabang="{ row }">
                    <span
                        class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                    >
                        {{ row.cabang || '-' }}
                    </span>
                </template>

                <!-- Cell: Jenjang (Polosan tanpa bg badge / logo) -->
                <template #cell-jenjang="{ row }">
                    <span
                        class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                    >
                        {{ row.jenjang || '-' }}
                    </span>
                </template>

                <!-- Cell: Dynamic Bank Columns (Tanpa logo) -->
                <template
                    v-for="bank in props.banks"
                    :key="bank.id"
                    #[`cell-bank_${bank.id}`]="{ row }"
                >
                    <span
                        v-if="row.va_numbers[bank.id]"
                        class="font-mono text-[13px] font-semibold text-slate-800 dark:text-slate-200"
                    >
                        {{ row.va_numbers[bank.id] }}
                    </span>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                    >
                        -
                    </span>
                </template>

                <!-- Row Actions (Matching Pegawai Index) -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <ActionMenu>
                            <template #trigger>
                                <button
                                    class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                <button
                                    @click="openEditVAModal(row)"
                                    class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
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
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    @click="openDeleteSingleModal(row)"
                                    class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-rose-500"
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
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Modal Tambah / Edit Virtual Account (Matching Pegawai Modal Style) -->
        <Modal
            :show="modalVAMode !== null"
            @close="closeVAModal"
            maxWidth="xl"
            :title="
                modalVAMode === 'add'
                    ? 'Tambah Virtual Account'
                    : 'Edit Virtual Account'
            "
            :description="
                modalVAMode === 'add'
                    ? 'Konfigurasi nomor Virtual Account baru untuk pendaftar'
                    : 'Perbarui nomor Virtual Account santri'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </div>
            </template>

            <!-- Mode Tambah: Input Search & Select Pendaftar -->
            <div v-if="modalVAMode === 'add'" class="mb-5">
                <label
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300"
                >
                    Pilih Pendaftar <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        v-model="pendaftarSearchInput"
                        @focus="isDropdownOpen = true"
                        placeholder="Cari NIK, Nama, atau No. Pendaftaran..."
                                    class="relative block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder-gray-400 transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500"
                    />

                    <!-- Dropdown Options -->
                    <div
                        v-if="
                            isDropdownOpen &&
                            filteredPendaftarOptions.length > 0
                        "
                        class="absolute z-50 mt-1.5 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-800"
                    >
                        <button
                            v-for="p in filteredPendaftarOptions"
                            :key="p.id"
                            type="button"
                            @click="selectPendaftarForVA(p)"
                            class="flex w-full cursor-pointer items-center justify-between rounded-lg p-2.5 text-left transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:hover:bg-slate-800"
                        >
                            <div>
                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{ p.name }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-gray-500 dark:text-slate-400"
                                >
                                    NIK: {{ p.nik }}
                                    {{
                                        p.nomor_pendaftaran
                                            ? '| ' + p.nomor_pendaftaran
                                            : ''
                                    }}
                                </p>
                            </div>
                            <div
                                class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-slate-400"
                            >
                                <span v-if="p.cabang">{{ p.cabang }}</span>
                                <span v-if="p.cabang && p.jenjang">-</span>
                                <span v-if="p.jenjang">{{ p.jenjang }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selected Pendaftar Info Box (Matching Pegawai Role Modal Style) -->
            <div
                v-if="selectedPendaftar"
                class="mb-5 rounded-xl border border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800"
            >
                <p class="mb-1 text-xs text-gray-500 dark:text-slate-400">
                    Santri / Pendaftar Terpilih:
                </p>
                <p
                    class="text-base font-bold text-gray-900 dark:text-slate-100"
                >
                    {{ selectedPendaftar.name }}
                </p>
                <p
                    class="mt-1 font-mono text-xs text-gray-500 dark:text-slate-400"
                >
                    NIK: {{ selectedPendaftar.nik }}
                    <span v-if="selectedPendaftar.cabang">
                        | Cabang: {{ selectedPendaftar.cabang }}</span
                    >
                    <span v-if="selectedPendaftar.jenjang">
                        | Jenjang: {{ selectedPendaftar.jenjang }}</span
                    >
                </p>
            </div>

            <!-- Bank VA Cards List (Logo & Nama Bank di atas, Input VA di bawahnya) -->
            <form @submit.prevent="submitVA" class="space-y-4">
                <div>
                    <label
                        class="mb-2.5 block text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                    >
                        Nomor Virtual Account Per Bank
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div
                            v-for="(item, idx) in vaForm.vas"
                            :key="item.bank_id"
                            class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-3.5 transition-all focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-slate-800 dark:bg-slate-800/60 dark:focus-within:border-blue-500"
                        >
                            <!-- Top: Logo di Kiri dan Nama/Singkatan Bank di Kanan -->
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <img
                                    :src="
                                        getBankLogo(
                                            props.banks.find(
                                                (b) => b.id === item.bank_id,
                                            ) || { name: '', id: item.bank_id },
                                        )
                                    "
                                    @error="handleBankLogoError($event)"
                                    :alt="
                                        props.banks.find(
                                            (b) => b.id === item.bank_id,
                                        )?.singkatan ||
                                        props.banks.find(
                                            (b) => b.id === item.bank_id,
                                        )?.name
                                    "
                                    class="h-6 w-auto max-w-[60px] object-contain"
                                />
                                <span
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.banks.find(
                                            (b) => b.id === item.bank_id,
                                        )?.singkatan ||
                                        props.banks.find(
                                            (b) => b.id === item.bank_id,
                                        )?.name
                                    }}
                                </span>
                            </div>

                            <!-- Bottom: Inputan Konsisten di Bawahnya -->
                            <div>
                                <input
                                    type="text"
                                    v-model="vaForm.vas[idx].nomor_va"
                                    placeholder="Masukkan Nomor VA..."
                                    class="relative block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder-gray-400 transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <SecondaryButton @click="closeVAModal"> Batal </SecondaryButton>
                <PrimaryButton
                    @click="submitVA"
                    :disabled="vaForm.processing"
                    :class="{ 'opacity-25': vaForm.processing }"
                >
                    Simpan Virtual Account
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete Confirmation (Matching Pegawai Delete Modal) -->
        <Modal
            :show="modalDeleteOpen"
            @close="modalDeleteOpen = false"
            maxWidth="sm"
            title="Hapus Virtual Account"
            description="Konfirmasi penghapusan data"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <p class="text-sm text-gray-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus Virtual Account ini? Tindakan
                ini tidak dapat dibatalkan.
            </p>

            <template #footer>
                <SecondaryButton @click="modalDeleteOpen = false">
                    Batal
                </SecondaryButton>
                <DangerButton @click="executeDelete"> Hapus </DangerButton>
            </template>
        </Modal>
    </div>
</template>
