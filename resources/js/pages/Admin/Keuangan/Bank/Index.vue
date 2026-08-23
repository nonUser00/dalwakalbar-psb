<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { getBankLogo, handleBankLogoError } from '@/lib/bank';

import { store, update, destroy } from '@/routes/admin/keuangan/master';

interface BiayaAdminItem {
    id: string;
    bank_id: string;
    name: string;
    nominal: number | string;
}

interface BankItem {
    id: string;
    kode_bank?: string | null;
    singkatan?: string | null;
    name: string;
    logo_path?: string | null;
    logo_url?: string | null;
    is_active: boolean;
    total_fee?: number;
    biaya_admins?: BiayaAdminItem[];
    biayaAdmins?: BiayaAdminItem[];
}

const props = defineProps<{
    banks: BankItem[];
}>();

const columns = [
    { key: 'logo', label: 'LOGO' },
    { key: 'kode_bank', label: 'KODE' },
    { key: 'singkatan', label: 'SINGKATAN' },
    { key: 'name', label: 'NAMA LENGKAP' },
    { key: 'total_fee', label: 'TOTAL BIAYA ADMIN' },
    { key: 'is_active', label: 'STATUS' },
];

const searchQuery = ref<string>('');
const perPage = ref<number>(5);
const currentPage = ref<number>(1);

watch([searchQuery, perPage], () => {
    currentPage.value = 1;
});

const getBiayaAdmins = (bank: BankItem): BiayaAdminItem[] => {
    return bank.biaya_admins || bank.biayaAdmins || [];
};

const getBankTotalFee = (bank: BankItem): number => {
    const fees = getBiayaAdmins(bank);

    return fees.reduce((acc, curr) => acc + Number(curr.nominal), 0);
};

const filteredBanks = computed(() => {
    if (!props.banks) {
        return [];
    }

    return props.banks.filter((bank) => {
        const query = searchQuery.value.toLowerCase();

        return (
            !query ||
            bank.name.toLowerCase().includes(query) ||
            (bank.kode_bank && bank.kode_bank.toLowerCase().includes(query))
        );
    });
});

const totalFilteredItems = computed(() => filteredBanks.value.length);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalFilteredItems.value / perPage.value)),
);

const paginatedBanks = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;

    return filteredBanks.value.slice(start, start + perPage.value);
});

const showingFrom = computed(() =>
    totalFilteredItems.value === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, totalFilteredItems.value),
);

const bankPagination = computed(() => {
    const total = totalFilteredItems.value;
    const limit = perPage.value;
    const current = currentPage.value;
    const lastPage = totalPages.value;

    const links = [];
    links.push({
        url: current > 1 ? '#' : null,
        label: '&laquo;',
        active: false,
        onClick: () => current > 1 && currentPage.value--,
    });

    for (let p = 1; p <= lastPage; p++) {
        links.push({
            url: '#',
            label: String(p),
            active: p === current,
            onClick: () => (currentPage.value = p),
        });
    }

    links.push({
        url: current < lastPage ? '#' : null,
        label: '&raquo;',
        active: false,
        onClick: () => current < lastPage && currentPage.value++,
    });

    return {
        from: showingFrom.value,
        to: showingTo.value,
        total,
        per_page: limit,
        current_page: current,
        last_page: lastPage,
        links,
    };
});

const formatRupiah = (value: number | string | null | undefined): string => {
    const num = Number(value) || 0;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

// ==========================================
// MODAL STATE: BANK
// ==========================================
const modalBankOpen = ref(false);
const isEditingBank = ref(false);
const logoPreview = ref<string | null>(null);

const bankForm = useForm({
    id: '',
    kode_bank: '',
    singkatan: '',
    name: '',
    is_active: true,
    logo: null as File | null,
});

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        bankForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const openAddBankModal = () => {
    isEditingBank.value = false;
    logoPreview.value = null;
    bankForm.reset();
    bankForm.clearErrors();
    bankForm.is_active = true;
    modalBankOpen.value = true;
};

const openEditBankModal = (bank: BankItem) => {
    isEditingBank.value = true;
    bankForm.id = bank.id;
    bankForm.kode_bank = bank.kode_bank || '';
    bankForm.singkatan = bank.singkatan || '';
    bankForm.name = bank.name;
    bankForm.is_active = Boolean(bank.is_active);
    bankForm.logo = null;
    logoPreview.value = getBankLogo(bank);
    bankForm.clearErrors();
    modalBankOpen.value = true;
};

const toggleBankActive = (bank: BankItem) => {
    router.put(
        update.url({ model: 'bank', id: bank.id }),
        {
            name: bank.name,
            singkatan: bank.singkatan,
            kode_bank: bank.kode_bank,
            is_active: !bank.is_active,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const closeBankModal = () => {
    modalBankOpen.value = false;
    logoPreview.value = null;
    bankForm.reset();
    bankForm.clearErrors();
};

const submitBank = () => {
    if (isEditingBank.value) {
        bankForm
            .transform((data) => ({
                ...data,
                _method: 'PUT',
            }))
            .post(update.url({ model: 'bank', id: bankForm.id }), {
                forceFormData: true,
                onSuccess: () => closeBankModal(),
                preserveScroll: true,
                preserveState: true,
            });
    } else {
        bankForm.post(store.url({ model: 'bank' }), {
            forceFormData: true,
            onSuccess: () => closeBankModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// ==========================================
// MODAL STATE: BIAYA ADMIN BANK
// ==========================================
const modalFeeOpen = ref(false);
const isEditingFee = ref(false);
const activeBankForFee = ref<BankItem | null>(null);

const feeForm = useForm({
    id: '',
    bank_id: '',
    name: '',
    nominal: '' as string | number,
});

const openAddFeeModal = (bank: BankItem) => {
    activeBankForFee.value = bank;
    isEditingFee.value = false;
    feeForm.reset();
    feeForm.clearErrors();
    feeForm.bank_id = bank.id;
    modalFeeOpen.value = true;
};

const openEditFeeModal = (fee: BiayaAdminItem, bank: BankItem) => {
    activeBankForFee.value = bank;
    isEditingFee.value = true;
    feeForm.id = fee.id;
    feeForm.bank_id = bank.id;
    feeForm.name = fee.name;
    feeForm.nominal = fee.nominal;
    feeForm.clearErrors();
    modalFeeOpen.value = true;
};

const closeFeeModal = () => {
    modalFeeOpen.value = false;
    feeForm.reset();
    feeForm.clearErrors();
};

const submitFee = () => {
    if (isEditingFee.value) {
        feeForm.put(update.url({ model: 'biaya-admin', id: feeForm.id }), {
            onSuccess: () => closeFeeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        feeForm.post(store.url({ model: 'biaya-admin' }), {
            onSuccess: () => closeFeeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// ==========================================
// MODAL DELETE
// ==========================================
const modalDeleteOpen = ref(false);
const deleteTarget = ref<{
    modelKey: 'bank' | 'biaya-admin';
    id: string;
    name: string;
} | null>(null);

const openDeleteBankModal = (bank: BankItem) => {
    deleteTarget.value = {
        modelKey: 'bank',
        id: bank.id,
        name: bank.name,
    };
    modalDeleteOpen.value = true;
};

const openDeleteFeeModal = (fee: BiayaAdminItem) => {
    deleteTarget.value = {
        modelKey: 'biaya-admin',
        id: fee.id,
        name: fee.name,
    };
    modalDeleteOpen.value = true;
};

const executeDelete = () => {
    if (deleteTarget.value) {
        router.delete(
            destroy.url({
                model: deleteTarget.value.modelKey,
                id: deleteTarget.value.id,
            }),
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    modalDeleteOpen.value = false;
                    deleteTarget.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Daftar Bank & Biaya Admin" />

        <!-- Header Page -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Daftar Bank & Biaya Admin
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola daftar bank penerima transfer pembayaran serta
                    rincian tarif biaya admin per transaksi.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton @click="openAddBankModal" class="font-bold">
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
                    Tambah Bank
                </PrimaryButton>
            </div>
        </div>

        <!-- DataTable with Expandable Child Row -->
        <DataTable
            :columns="columns"
            :data="paginatedBanks"
            :pagination="bankPagination"
            expandable
            @search="(val) => (searchQuery = val)"
            @limit="(val) => (perPage = val)"
        >
            <!-- Cell: Logo -->
            <template #cell-logo="{ row }">
                <img
                    :src="getBankLogo(row)"
                    @error="handleBankLogoError($event)"
                    :alt="row.name"
                    class="h-8 w-auto max-w-16 object-contain"
                />
            </template>

            <!-- Cell: Kode Bank -->
            <template #cell-kode_bank="{ row }">
                <span
                    class="rounded-md bg-gray-100 px-2.5 py-1 font-mono text-[13px] font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ row.kode_bank || '-' }}
                </span>
            </template>

            <!-- Cell: Singkatan Bank -->
            <template #cell-singkatan="{ row }">
                <span
                    class="rounded-md bg-indigo-50 px-2.5 py-1 text-[13px] font-extrabold text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    {{ row.singkatan || '-' }}
                </span>
            </template>

            <!-- Cell: Nama Bank -->
            <template #cell-name="{ row }">
                <p
                    class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                >
                    {{ row.name }}
                </p>
            </template>

            <!-- Cell: Total Fee Admin -->
            <template #cell-total_fee="{ row }">
                <span
                    class="text-[13px] font-extrabold text-primary dark:text-blue-400"
                >
                    {{ formatRupiah(getBankTotalFee(row)) }}
                </span>
            </template>

            <!-- Cell: Status -->
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
                        class="h-1.5 w-1.5 rounded-full"
                        :class="
                            row.is_active
                                ? 'bg-emerald-500'
                                : 'bg-gray-400 dark:bg-slate-500'
                        "
                    ></span>
                    {{ row.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
            </template>

            <!-- Row Actions (Tanpa Tambah Biaya Admin) -->
            <template #row-actions="{ row }">
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
                            @click="toggleBankActive(row)"
                            class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold transition-colors sm:px-4"
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
                            <span class="whitespace-nowrap">{{
                                row.is_active ? 'Nonaktifkan' : 'Aktifkan'
                            }}</span>
                        </button>
                        <button
                            @click="openEditBankModal(row)"
                            class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
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
                            <span class="whitespace-nowrap">Edit</span>
                        </button>
                        <button
                            @click="openDeleteBankModal(row)"
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
                            <span class="whitespace-nowrap">Hapus</span>
                        </button>
                    </template>
                </ActionMenu>
            </template>

            <!-- Expanded Sub-View: Matching Standard Table Design -->
            <template #expanded-row="{ row }">
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 shadow-inner dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900/60"
                >
                    <!-- Header inside Collapse -->
                    <div
                        class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-sm shadow-primary/20 dark:bg-blue-600"
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
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="flex items-center gap-2 text-sm font-extrabold text-slate-800 dark:text-slate-100"
                                >
                                    Rincian Biaya Admin:
                                    <span
                                        class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary dark:bg-blue-950/60 dark:text-blue-400"
                                    >
                                        {{ row.name }}
                                    </span>
                                </h4>
                                <p
                                    class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Daftar rincian komponen tarif biaya admin
                                    per transaksi untuk bank ini.
                                </p>
                            </div>
                        </div>

                        <PrimaryButton
                            @click="openAddFeeModal(row)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-all hover:bg-primary/90 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            <svg
                                class="h-3.5 w-3.5"
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
                            <span>Tambah Biaya Admin</span>
                        </PrimaryButton>
                    </div>

                    <!-- Normal Standard-Sized Sub Table -->
                    <div
                        v-if="getBiayaAdmins(row).length > 0"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900"
                    >
                        <table
                            class="min-w-full divide-y divide-slate-200 dark:divide-slate-800"
                        >
                            <thead
                                class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800"
                            >
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-16 px-6 py-3.5 text-center text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                    >
                                        No
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                    >
                                        Nama Biaya Admin
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-right text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                    >
                                        Nominal Biaya
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-28 px-6 py-3.5 text-center text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 bg-white dark:divide-slate-800/60 dark:bg-slate-900"
                            >
                                <tr
                                    v-for="(fee, idx) in getBiayaAdmins(row)"
                                    :key="fee.id"
                                    class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/50"
                                >
                                    <td
                                        class="px-6 py-4 text-center text-sm font-bold text-slate-400 dark:text-slate-500"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-bold text-slate-800 dark:text-slate-100"
                                    >
                                        {{ fee.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm font-black text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ formatRupiah(fee.nominal) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
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
                                                        @click="
                                                            openEditFeeModal(
                                                                fee,
                                                                row,
                                                            )
                                                        "
                                                        class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
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
                                                        <span
                                                            class="whitespace-nowrap"
                                                            >Edit</span
                                                        >
                                                    </button>
                                                    <button
                                                        @click="
                                                            openDeleteFeeModal(
                                                                fee,
                                                            )
                                                        "
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
                                                        <span
                                                            class="whitespace-nowrap"
                                                            >Hapus</span
                                                        >
                                                    </button>
                                                </template>
                                            </ActionMenu>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot
                                class="border-t-2 border-slate-200 bg-slate-50/75 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800/80"
                            >
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-6 py-3.5 text-right text-xs font-black tracking-wider text-slate-600 uppercase dark:text-slate-300"
                                    >
                                        Total Biaya Admin:
                                    </td>
                                    <td
                                        class="px-6 py-3.5 text-right text-sm font-black text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ formatRupiah(getBankTotalFee(row)) }}
                                    </td>
                                    <td class="px-6 py-3.5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Empty State inside Collapse -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900"
                    >
                        <p
                            class="text-sm font-bold text-slate-700 dark:text-slate-200"
                        >
                            Belum ada rincian biaya admin untuk bank
                            {{ row.name }}.
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-400 dark:text-slate-500"
                        >
                            Klik tombol <strong>+ Tambah Biaya Admin</strong> di
                            atas untuk menambahkan tarif baru.
                        </p>
                    </div>
                </div>
            </template>
        </DataTable>

        <!-- MODAL FORM BANK (Tambah / Edit) -->
        <Modal
            :show="modalBankOpen"
            @close="closeBankModal"
            maxWidth="md"
            :title="isEditingBank ? 'Edit Bank' : 'Tambah Bank'"
            :description="
                isEditingBank ? 'Perbarui data bank' : 'Tambah data bank baru'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400"
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
                            d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"
                        />
                    </svg>
                </div>
            </template>

            <form id="bankForm" @submit.prevent="submitBank" class="space-y-4">
                <!-- Upload Logo Bank -->
                <div class="space-y-2">
                    <label
                        class="block text-xs font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                        >Logo Bank</label
                    >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-2 shadow-2xs dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                @error="handleBankLogoError($event)"
                                alt="Preview Logo"
                                class="h-full w-full object-contain"
                            />
                            <svg
                                v-else
                                class="h-8 w-8 text-gray-400 dark:text-slate-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <input
                                id="logo_input"
                                type="file"
                                accept="image/*"
                                @change="handleLogoChange"
                                class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-xl file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-primary hover:file:bg-primary/20 dark:text-slate-400 dark:file:bg-blue-950/60 dark:file:text-blue-400 dark:hover:file:bg-blue-900/60"
                            />
                            <span
                                class="mt-1 block text-[11px] text-gray-400 dark:text-slate-500"
                                >Format: JPG, PNG. Maks 2MB.</span
                            >
                        </div>
                    </div>
                </div>

                <TextInput
                    id="kode_bank"
                    label="Kode Bank"
                    v-model="bankForm.kode_bank"
                    :error="bankForm.errors.kode_bank"
                    placeholder="Contoh: 002, 008, 009, 014, 451"
                />

                <TextInput
                    id="singkatan"
                    label="Singkatan Bank"
                    v-model="bankForm.singkatan"
                    :error="bankForm.errors.singkatan"
                    placeholder="Contoh: BCA, BSI, BRI, BNI, Mandiri, Bank Kalbar"
                    required
                />

                <TextInput
                    id="bank_name"
                    label="Nama Bank Lengkap"
                    v-model="bankForm.name"
                    :error="bankForm.errors.name"
                    placeholder="Contoh: Bank Central Asia, Bank Rakyat Indonesia"
                    required
                />

                <div
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800"
                >
                    <div>
                        <span
                            class="block text-sm font-semibold text-gray-900 dark:text-slate-100"
                            >Status Keaktifan</span
                        >
                        <span class="text-xs text-gray-500 dark:text-slate-400"
                            >Bank aktif dapat dipilih saat pembayaran</span
                        >
                    </div>
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <Checkbox v-model:checked="bankForm.is_active" />
                    </label>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeBankModal" type="button"
                    >Batal</SecondaryButton
                >
                <PrimaryButton
                    form="bankForm"
                    type="submit"
                    :loading="bankForm.processing"
                >
                    {{ isEditingBank ? 'Simpan' : 'Tambah Bank' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL FORM BIAYA ADMIN BANK (Tambah / Edit) -->
        <Modal
            :show="modalFeeOpen"
            @close="closeFeeModal"
            maxWidth="md"
            :title="isEditingFee ? 'Edit Biaya Admin' : 'Tambah Biaya Admin'"
            :description="
                activeBankForFee ? 'Bank: ' + activeBankForFee.name : ''
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
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
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </div>
            </template>

            <form id="feeForm" @submit.prevent="submitFee" class="space-y-4">
                <TextInput
                    id="fee_name"
                    label="Jenis Biaya Admin"
                    v-model="feeForm.name"
                    :error="feeForm.errors.name"
                    placeholder="Contoh: Biaya Transfer Virtual Account"
                    required
                />

                <CurrencyInput
                    id="fee_nominal"
                    label="Nominal Biaya Admin"
                    v-model="feeForm.nominal"
                    :error="feeForm.errors.nominal"
                    placeholder="Contoh: 4.000"
                    required
                />
            </form>
            <template #footer>
                <SecondaryButton @click="closeFeeModal" type="button"
                    >Batal</SecondaryButton
                >
                <PrimaryButton
                    form="feeForm"
                    type="submit"
                    :loading="feeForm.processing"
                >
                    {{ isEditingFee ? 'Simpan' : 'Tambah Biaya' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL DELETE KONFIRMASI -->
        <Modal
            :show="modalDeleteOpen"
            @close="modalDeleteOpen = false"
            maxWidth="sm"
            title="Konfirmasi Hapus"
            description="Tindakan ini tidak dapat dibatalkan"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>
            <div class="text-center sm:text-left">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus
                    <strong class="font-bold text-slate-800 dark:text-slate-100"
                        >"{{ deleteTarget?.name }}"</strong
                    >?
                </p>
                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                    Data yang sudah dihapus tidak dapat dikembalikan.
                </p>
            </div>
            <template #footer>
                <SecondaryButton @click="modalDeleteOpen = false" type="button">
                    Batal
                </SecondaryButton>
                <DangerButton @click="executeDelete"> Ya, Hapus </DangerButton>
            </template>
        </Modal>
    </div>
</template>
