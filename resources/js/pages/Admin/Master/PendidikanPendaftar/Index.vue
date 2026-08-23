<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import {
    index,
    store,
    update,
    destroy,
} from '@/routes/admin/master/pendidikan-sebelumnya';

import {
    store as tingkatStore,
    update as tingkatUpdate,
    destroy as tingkatDestroy,
} from '@/routes/admin/master/pendidikan-sebelumnya/tingkat';

const props = defineProps<{
    pendidikan: any;
    filters: any;
}>();

const activeTab = ref(props.filters?.tab || 'umum');

const tabs = [
    {
        id: 'umum',
        name: 'Sekolah Umum',
        tipe: 'Umum',
    },
    {
        id: 'madrasah',
        name: 'Madrasah',
        tipe: 'Madrasah',
    },
    {
        id: 'perguruan-tinggi',
        name: 'Perguruan Tinggi',
        tipe: 'Perguruan Tinggi',
    },
];

const switchTab = (tabId: string) => {
    activeTab.value = tabId;
    router.get(
        index.url(),
        {
            tab: tabId,
            search: props.filters?.search,
            limit: props.filters?.limit || 10,
        },
        { preserveState: true, replace: true },
    );
};

// Kolom untuk Tabel Induk (Jenjang)
const columns = [
    { key: 'name', label: 'NAMA JENJANG' },
    { key: 'tingkats_count', label: 'SUB-TINGKAT' },
];

const tipeOptions = [
    { value: 'Umum', label: 'Umum' },
    { value: 'Madrasah', label: 'Madrasah' },
    { value: 'Perguruan Tinggi', label: 'Perguruan Tinggi' },
];

const handleSearch = (search: string) => {
    router.get(
        index.url(),
        { search, tab: activeTab.value, limit: props.filters?.limit || 5 },
        { preserveState: true, replace: true },
    );
};

const handleLimit = (limit: number) => {
    router.get(
        index.url(),
        { search: props.filters?.search, tab: activeTab.value, limit },
        { preserveState: true, replace: true },
    );
};

// ==========================================
// Form & Modal: Induk (Jenjang Pendidikan)
// ==========================================
const showIndukModal = ref(false);
const isEditingInduk = ref(false);
const indukToDelete = ref<any>(null);
const showDeleteIndukModal = ref(false);

const formInduk = useForm({
    id: '',
    tipe: 'Umum',
    name: '',
});

const openAddIndukModal = () => {
    isEditingInduk.value = false;
    formInduk.reset();
    formInduk.clearErrors();
    formInduk.tipe =
        tabs.find((t) => t.id === activeTab.value)?.tipe || 'Umum';
    showIndukModal.value = true;
};

const openEditIndukModal = (item: any) => {
    isEditingInduk.value = true;
    formInduk.id = item.id;
    formInduk.tipe =
        item.tipe || tabs.find((t) => t.id === activeTab.value)?.tipe || 'Umum';
    formInduk.name = item.name;
    formInduk.clearErrors();
    showIndukModal.value = true;
};

const closeIndukModal = () => {
    showIndukModal.value = false;
    formInduk.reset();
    formInduk.clearErrors();
};

const submitInduk = () => {
    if (isEditingInduk.value) {
        formInduk.put(update.url({ pendidikanPendaftar: formInduk.id }), {
            onSuccess: () => closeIndukModal(),
            preserveScroll: true,
        });
    } else {
        formInduk.post(store.url(), {
            onSuccess: () => closeIndukModal(),
            preserveScroll: true,
        });
    }
};

const openDeleteIndukModal = (item: any) => {
    indukToDelete.value = item;
    showDeleteIndukModal.value = true;
};

const deleteInduk = () => {
    if (indukToDelete.value) {
        router.delete(
            destroy.url({ pendidikanPendaftar: indukToDelete.value.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteIndukModal.value = false;
                    indukToDelete.value = null;
                },
            },
        );
    }
};

// ==========================================
// Form & Modal: Sub-Tingkat
// ==========================================
const showTingkatModal = ref(false);
const isEditingTingkat = ref(false);
const tingkatToDelete = ref<any>(null);
const showDeleteTingkatModal = ref(false);
const activeIndukForTingkat = ref<any>(null);

const formTingkat = useForm({
    id: '',
    pendidikan_pendaftar_id: '',
    name: '',
});

const openAddTingkatModal = (induk: any) => {
    isEditingTingkat.value = false;
    activeIndukForTingkat.value = induk;
    formTingkat.reset();
    formTingkat.clearErrors();
    formTingkat.pendidikan_pendaftar_id = induk.id;
    showTingkatModal.value = true;
};

const openEditTingkatModal = (tingkat: any) => {
    isEditingTingkat.value = true;
    formTingkat.id = tingkat.id;
    formTingkat.pendidikan_pendaftar_id = tingkat.pendidikan_pendaftar_id;
    formTingkat.name = tingkat.name;
    formTingkat.clearErrors();
    showTingkatModal.value = true;
};

const closeTingkatModal = () => {
    showTingkatModal.value = false;
    formTingkat.reset();
    formTingkat.clearErrors();
    activeIndukForTingkat.value = null;
};

const submitTingkat = () => {
    if (isEditingTingkat.value) {
        formTingkat.put(tingkatUpdate.url({ tingkat: formTingkat.id }), {
            onSuccess: () => closeTingkatModal(),
            preserveScroll: true,
        });
    } else {
        formTingkat.post(
            tingkatStore.url({
                pendidikanPendaftar: formTingkat.pendidikan_pendaftar_id,
            }),
            {
                onSuccess: () => closeTingkatModal(),
                preserveScroll: true,
            },
        );
    }
};

const openDeleteTingkatModal = (tingkat: any) => {
    tingkatToDelete.value = tingkat;
    showDeleteTingkatModal.value = true;
};

const deleteTingkat = () => {
    if (tingkatToDelete.value) {
        router.delete(
            tingkatDestroy.url({ tingkat: tingkatToDelete.value.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteTingkatModal.value = false;
                    tingkatToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Master Pendidikan Sebelumnya" />

        <!-- Header Page -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Master Pendidikan Sebelumnya
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola jenjang dan sub-tingkat pendidikan sekolah pendaftar
                    sebelumnya.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton @click="openAddIndukModal">
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
                    Tambah Jenjang
                </PrimaryButton>
            </div>
        </div>

        <!-- Layout with Sidebar Tabs -->
        <div class="flex flex-col gap-8 md:flex-row">
            <!-- Sidebar Tab Navigation -->
            <div class="w-full flex-shrink-0 md:w-64">
                <nav
                    class="flex flex-col space-y-2"
                    aria-label="Tabs Navigation"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="switchTab(tab.id)"
                        :class="[
                            activeTab === tab.id
                                ? 'bg-primary font-bold text-white shadow-md dark:bg-primary-dark'
                                : 'border border-slate-100 bg-white text-slate-600 shadow-sm hover:bg-slate-100 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100',
                            'group flex w-full cursor-pointer items-center rounded-xl px-4 py-3.5 text-sm transition-all duration-200',
                        ]"
                    >
                        <div
                            class="mr-3 -ml-1 flex h-5 w-5 shrink-0 items-center justify-center"
                        >
                            <!-- Icon Sekolah Umum (Academic Building) -->
                            <svg
                                v-if="tab.id === 'umum'"
                                :class="[
                                    activeTab === tab.id
                                        ? 'text-white'
                                        : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300',
                                    'h-5 w-5 transition-colors',
                                ]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                            <!-- Icon Pondok Pesantren (Book / Kitab) -->
                            <svg
                                v-else
                                :class="[
                                    activeTab === tab.id
                                        ? 'text-white'
                                        : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300',
                                    'h-5 w-5 transition-colors',
                                ]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </div>
                        <span class="truncate font-semibold">{{
                            tab.name
                        }}</span>
                    </button>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="min-w-0 flex-1 space-y-6">
                <!-- Table Induk -->
                <DataTable
                    :columns="columns"
                    :data="pendidikan.data"
                    :pagination="pendidikan"
                    expandable
                    @search="handleSearch"
                    @limit="handleLimit"
                >
                    <!-- Custom Cell: Nama Jenjang -->
                    <template #cell-name="{ row }">
                        <div>
                            <p
                                class="text-[14px] font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ row.name }}
                            </p>
                            <p
                                class="mt-0.5 text-[12px] font-medium text-slate-500 dark:text-slate-400"
                            >
                                {{ row.tipe }}
                            </p>
                        </div>
                    </template>

                    <!-- Custom Cell: Sub-Tingkat Count -->
                    <template #cell-tingkats_count="{ row }">
                        <span
                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ row.tingkats ? row.tingkats.length : 0 }}
                            Sub-tingkat
                        </span>
                    </template>

                    <!-- Actions Induk (Hanya Edit dan Hapus, karena Tambah Sub-tingkat sudah ada di collapse) -->
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
                                <button
                                    @click="openEditIndukModal(row)"
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
                                    @click="openDeleteIndukModal(row)"
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

                    <!-- Expanded Row: Matching Standard Table from Tagihan Pendaftaran -->
                    <template #expanded-row="{ row }">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 shadow-inner dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-800/60"
                        >
                            <!-- Top Header inside Collapse -->
                            <div
                                class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-sm shadow-primary/20 dark:bg-primary-dark"
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
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4
                                            class="flex items-center gap-2 text-sm font-extrabold text-slate-800 dark:text-slate-100"
                                        >
                                            Struktur Sub-Tingkat:
                                            <span
                                                class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary dark:bg-blue-950/60 dark:text-blue-400"
                                            >
                                                {{ row.name }}
                                            </span>
                                        </h4>
                                        <p
                                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                                        >
                                            Daftar tingkatan kelas / level yang
                                            terhubung dengan jenjang ini.
                                        </p>
                                    </div>
                                </div>

                                <PrimaryButton
                                    @click="openAddTingkatModal(row)"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-all hover:bg-primary/90 dark:bg-primary-dark"
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
                                    <span>Tambah Sub-tingkat</span>
                                </PrimaryButton>
                            </div>

                            <!-- Normal Standard-Sized Sub Table -->
                            <div
                                v-if="row.tingkats && row.tingkats.length > 0"
                                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900"
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
                                                Nama Sub-Tingkat
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
                                        class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900"
                                    >
                                        <tr
                                            v-for="(
                                                tingkat, idx
                                            ) in row.tingkats"
                                            :key="tingkat.id"
                                            class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/60"
                                        >
                                            <td
                                                class="px-6 py-4 text-center text-sm font-bold text-slate-400 dark:text-slate-500"
                                            >
                                                {{ idx + 1 }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm font-bold text-slate-800 dark:text-slate-100"
                                            >
                                                {{ tingkat.name }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div
                                                    class="flex justify-center"
                                                >
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
                                                                    openEditTingkatModal(
                                                                        tingkat,
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
                                                                    openDeleteTingkatModal(
                                                                        tingkat,
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
                                </table>
                            </div>

                            <!-- Empty State inside Collapse -->
                            <div
                                v-else
                                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900"
                            >
                                <p
                                    class="text-sm font-bold text-slate-700 dark:text-slate-300"
                                >
                                    Belum ada sub-tingkat untuk jenjang
                                    {{ row.name }}.
                                </p>
                                <p
                                    class="mt-1 text-xs text-slate-400 dark:text-slate-500"
                                >
                                    Klik tombol
                                    <strong>+ Tambah Sub-tingkat</strong> di
                                    atas untuk menambahkan tingkat kelas baru.
                                </p>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Modal Form Induk (Jenjang) -->
        <Modal
            :show="showIndukModal"
            @close="closeIndukModal"
            maxWidth="md"
            :title="isEditingInduk ? 'Edit Jenjang' : 'Tambah Jenjang'"
            :description="
                isEditingInduk ? 'Perbarui data jenjang' : 'Tambah jenjang baru'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary-dark/20 dark:text-blue-400"
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
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="indukForm"
                @submit.prevent="submitInduk"
                class="space-y-4"
            >
                <CustomSelect
                    label="Tipe Jenjang"
                    v-model="formInduk.tipe"
                    :options="tipeOptions"
                    placeholder="-- Pilih Tipe Jenjang --"
                    :error="formInduk.errors.tipe"
                    required
                />

                <TextInput
                    id="induk_name"
                    label="Nama Jenjang"
                    v-model="formInduk.name"
                    :error="formInduk.errors.name"
                    placeholder="Contoh: SD, SMP, Madrasah Aliyah"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeIndukModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    form="indukForm"
                    type="submit"
                    :loading="formInduk.processing"
                >
                    {{ isEditingInduk ? 'Simpan' : 'Tambah Jenjang' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete Induk -->
        <Modal
            :show="showDeleteIndukModal"
            @close="showDeleteIndukModal = false"
            maxWidth="sm"
            title="Konfirmasi Hapus"
            description="Tindakan ini tidak dapat dibatalkan"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>

            <div class="text-center sm:text-left">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus jenjang
                    <strong class="font-bold text-slate-800 dark:text-slate-100"
                        >"{{ indukToDelete?.name }}"</strong
                    >? Seluruh sub-tingkat di dalamnya juga akan terhapus secara
                    permanen.
                </p>
                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                    Data yang sudah dihapus tidak dapat dikembalikan.
                </p>
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteIndukModal = false"
                    type="button"
                >
                    Batal
                </SecondaryButton>
                <DangerButton @click="deleteInduk"> Ya, Hapus </DangerButton>
            </template>
        </Modal>

        <!-- Modal Form Sub-Tingkat -->
        <Modal
            :show="showTingkatModal"
            @close="closeTingkatModal"
            maxWidth="md"
            :title="
                isEditingTingkat ? 'Edit Sub-tingkat' : 'Tambah Sub-tingkat'
            "
            :description="
                isEditingTingkat
                    ? 'Perbarui data sub-tingkat'
                    : 'Tambah sub-tingkat baru'
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
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="tingkatForm"
                @submit.prevent="submitTingkat"
                class="space-y-4"
            >
                <TextInput
                    id="tingkat_name"
                    label="Nama Sub-tingkat"
                    v-model="formTingkat.name"
                    :error="formTingkat.errors.name"
                    placeholder="Contoh: Kelas 1, Kelas 7"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeTingkatModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    form="tingkatForm"
                    type="submit"
                    :loading="formTingkat.processing"
                >
                    {{ isEditingTingkat ? 'Simpan' : 'Tambah Sub-tingkat' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete Sub-Tingkat -->
        <Modal
            :show="showDeleteTingkatModal"
            @close="showDeleteTingkatModal = false"
            maxWidth="sm"
            title="Konfirmasi Hapus"
            description="Tindakan ini tidak dapat dibatalkan"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>

            <div class="text-center sm:text-left">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus sub-tingkat
                    <strong class="font-bold text-slate-800 dark:text-slate-100"
                        >"{{ tingkatToDelete?.name }}"</strong
                    >?
                </p>
                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                    Data yang sudah dihapus tidak dapat dikembalikan.
                </p>
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteTingkatModal = false"
                    type="button"
                >
                    Batal
                </SecondaryButton>
                <DangerButton @click="deleteTingkat"> Ya, Hapus </DangerButton>
            </template>
        </Modal>
    </div>
</template>
