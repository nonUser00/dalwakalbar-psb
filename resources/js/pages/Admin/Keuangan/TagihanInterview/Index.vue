<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { store, update, destroy } from '@/routes/admin/keuangan/master';

interface ItemBiaya {
    id: string;
    kategori_biaya_id: string;
    name: string;
    nominal: number | string;
}

interface KategoriBiaya {
    id: string;
    jenis?: string;
    name: string;
    total_biaya?: number;
    item_biayas?: ItemBiaya[];
    itemBiayas?: ItemBiaya[];
}

const props = defineProps<{
    kategoriBiayas: KategoriBiaya[];
}>();

const columns = [
    { key: 'name', label: 'NAMA KATEGORI BIAYA INTERVIEW' },
    { key: 'total_biaya', label: 'JUMLAH TOTAL BIAYA' },
];

const searchQuery = ref<string>('');
const perPage = ref<number>(5);
const currentPage = ref<number>(1);

watch([searchQuery, perPage], () => {
    currentPage.value = 1;
});

const getItemBiayas = (kategori: KategoriBiaya): ItemBiaya[] => {
    return kategori.item_biayas || kategori.itemBiayas || [];
};

const getKategoriTotalBiaya = (kategori: KategoriBiaya): number => {
    const items = getItemBiayas(kategori);

    return items.reduce((acc, curr) => acc + Number(curr.nominal), 0);
};

const filteredKategoriBiayas = computed(() => {
    if (!props.kategoriBiayas) {
        return [];
    }

    const query = searchQuery.value.toLowerCase();

    if (!query) {
        return props.kategoriBiayas;
    }

    return props.kategoriBiayas.filter((kategori) =>
        kategori.name.toLowerCase().includes(query),
    );
});

const totalComponentsCount = computed(() => {
    return filteredKategoriBiayas.value.reduce((acc, curr) => {
        return acc + getItemBiayas(curr).length;
    }, 0);
});

const totalAccumulatedBiaya = computed(() => {
    return filteredKategoriBiayas.value.reduce((acc, curr) => {
        return acc + getKategoriTotalBiaya(curr);
    }, 0);
});

const totalFilteredItems = computed(() => filteredKategoriBiayas.value.length);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalFilteredItems.value / perPage.value)),
);

const paginatedKategoriBiayas = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;

    return filteredKategoriBiayas.value.slice(start, start + perPage.value);
});

const showingFrom = computed(() =>
    totalFilteredItems.value === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, totalFilteredItems.value),
);

const tablePagination = computed(() => {
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
// MODAL STATE: KATEGORI BIAYA
// ==========================================
const modalKategoriOpen = ref(false);
const isEditingKategori = ref(false);

const kategoriForm = useForm({
    id: '',
    jenis: 'interview',
    name: '',
});

const openAddKategoriModal = () => {
    isEditingKategori.value = false;
    kategoriForm.reset();
    kategoriForm.clearErrors();
    kategoriForm.jenis = 'interview';
    modalKategoriOpen.value = true;
};

const openEditKategoriModal = (kategori: KategoriBiaya) => {
    isEditingKategori.value = true;
    kategoriForm.id = kategori.id;
    kategoriForm.jenis = 'interview';
    kategoriForm.name = kategori.name;
    kategoriForm.clearErrors();
    modalKategoriOpen.value = true;
};

const closeKategoriModal = () => {
    modalKategoriOpen.value = false;
    kategoriForm.reset();
    kategoriForm.clearErrors();
};

const submitKategori = () => {
    if (isEditingKategori.value) {
        kategoriForm.put(
            update.url({ model: 'kategori-biaya', id: kategoriForm.id }),
            {
                onSuccess: () => closeKategoriModal(),
                preserveScroll: true,
                preserveState: true,
            },
        );
    } else {
        kategoriForm.post(store.url({ model: 'kategori-biaya' }), {
            onSuccess: () => closeKategoriModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// ==========================================
// MODAL STATE: ITEM BIAYA
// ==========================================
const modalItemOpen = ref(false);
const isEditingItem = ref(false);
const activeKategoriForItem = ref<KategoriBiaya | null>(null);

const itemForm = useForm({
    id: '',
    kategori_biaya_id: '',
    name: '',
    nominal: '' as string | number,
});

const openAddItemModal = (kategori: KategoriBiaya) => {
    activeKategoriForItem.value = kategori;
    isEditingItem.value = false;
    itemForm.reset();
    itemForm.clearErrors();
    itemForm.kategori_biaya_id = kategori.id;
    modalItemOpen.value = true;
};

const openEditItemModal = (item: ItemBiaya, kategori: KategoriBiaya) => {
    activeKategoriForItem.value = kategori;
    isEditingItem.value = true;
    itemForm.id = item.id;
    itemForm.kategori_biaya_id = kategori.id;
    itemForm.name = item.name;
    itemForm.nominal = item.nominal;
    itemForm.clearErrors();
    modalItemOpen.value = true;
};

const closeItemModal = () => {
    modalItemOpen.value = false;
    itemForm.reset();
    itemForm.clearErrors();
};

const submitItem = () => {
    if (isEditingItem.value) {
        itemForm.put(update.url({ model: 'item-biaya', id: itemForm.id }), {
            onSuccess: () => closeItemModal(),
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        itemForm.post(store.url({ model: 'item-biaya' }), {
            onSuccess: () => closeItemModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// ==========================================
// MODAL DELETE KONFIRMASI
// ==========================================
const modalDeleteOpen = ref(false);
const deleteTarget = ref<{
    modelKey: 'kategori-biaya' | 'item-biaya';
    id: string;
    name: string;
} | null>(null);

const openDeleteKategoriModal = (kategori: KategoriBiaya) => {
    deleteTarget.value = {
        modelKey: 'kategori-biaya',
        id: kategori.id,
        name: kategori.name,
    };
    modalDeleteOpen.value = true;
};

const openDeleteItemModal = (item: ItemBiaya) => {
    deleteTarget.value = {
        modelKey: 'item-biaya',
        id: item.id,
        name: item.name,
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
    <div class="relative min-h-screen w-full">
        <Head title="Tagihan Interview" />

        <div class="space-y-6">
            <!-- Header Card -->
            <div
                class="relative overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 p-2 text-purple-600 shadow-inner dark:bg-purple-950/60 dark:text-purple-400"
                        >
                            <svg
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1
                                    class="text-2xl font-black tracking-tight text-gray-900 md:text-3xl dark:text-slate-100"
                                >
                                    Tagihan Interview & Ujian
                                </h1>
                                <span
                                    class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1 text-xs font-black tracking-wide text-purple-700 uppercase dark:bg-purple-950/60 dark:text-purple-400"
                                >
                                    Global / Seluruh Santri
                                </span>
                            </div>
                            <p
                                class="mt-1.5 text-sm font-medium text-gray-500 dark:text-slate-400"
                            >
                                Atur komponen dan biaya pelaksanaan tes
                                interview, uji kelayakan, dan seleksi masuk
                                calon santri.
                            </p>
                        </div>
                    </div>

                    <!-- Header Actions -->
                    <div
                        class="flex flex-wrap items-center gap-3 self-start lg:self-auto"
                    >
                        <PrimaryButton
                            @click="openAddKategoriModal"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-primary/25 hover:bg-primary/90 dark:bg-blue-600 dark:hover:bg-blue-500"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>Tambah Kategori</span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400"
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
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase dark:text-slate-400"
                        >
                            Kategori Biaya
                        </p>
                        <p
                            class="mt-0.5 text-xl font-black text-gray-900 dark:text-slate-100"
                        >
                            {{ filteredKategoriBiayas.length }} Kategori
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
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
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase dark:text-slate-400"
                        >
                            Rincian Item Biaya
                        </p>
                        <p
                            class="mt-0.5 text-xl font-black text-gray-900 dark:text-slate-100"
                        >
                            {{ totalComponentsCount }} Komponen
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-xs sm:col-span-2 lg:col-span-1 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
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
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 uppercase dark:text-slate-400"
                        >
                            Total Biaya Interview
                        </p>
                        <p
                            class="mt-0.5 text-xl font-black text-gray-900 dark:text-slate-100"
                        >
                            {{ formatRupiah(totalAccumulatedBiaya) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- DataTable Component -->
            <DataTable
                :columns="columns"
                :data="paginatedKategoriBiayas"
                :pagination="tablePagination"
                expandable
                @search="(val) => (searchQuery = val)"
                @limit="(val) => (perPage = val)"
            >
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3.5 py-1">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-purple-100 bg-purple-50 text-purple-600 shadow-xs dark:border-purple-900/50 dark:bg-purple-950/60 dark:text-purple-400"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ row.name }}
                            </span>
                            <div class="mt-0.5 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-[11px] font-bold text-purple-700 dark:bg-purple-950/60 dark:text-purple-400"
                                >
                                    {{ getItemBiayas(row).length }} Komponen
                                </span>
                                <span
                                    class="text-[11px] text-gray-400 dark:text-slate-500"
                                >
                                    Skema Global
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell-total_biaya="{ row }">
                    <span
                        class="text-sm font-black text-emerald-700 dark:text-emerald-400"
                    >
                        {{ formatRupiah(getKategoriTotalBiaya(row)) }}
                    </span>
                </template>

                <!-- Row Actions (ActionMenu with Trigger and Content Slots) -->
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
                                    @click="openEditKategoriModal(row)"
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
                                    @click="openDeleteKategoriModal(row)"
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
                    </div>
                </template>

                <!-- Expanded Sub-View: Matching Standard Normal-Sized Table -->
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
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-600 text-white shadow-sm shadow-purple-200 dark:bg-purple-600 dark:shadow-none"
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
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4
                                        class="flex items-center gap-2 text-sm font-extrabold text-slate-800 dark:text-slate-100"
                                    >
                                        Rincian Biaya Interview:
                                        <span
                                            class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-950/60 dark:text-purple-400"
                                        >
                                            {{ row.name }}
                                        </span>
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Daftar rincian komponen biaya interview
                                        dan seleksi.
                                    </p>
                                </div>
                            </div>

                            <PrimaryButton
                                @click="openAddItemModal(row)"
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
                                <span>Tambah Rincian</span>
                            </PrimaryButton>
                        </div>

                        <!-- Normal Standard-Sized Sub Table -->
                        <div
                            v-if="getItemBiayas(row).length > 0"
                            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900"
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
                                            Nama Komponen Biaya
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
                                    class="divide-y divide-slate-100 bg-white dark:divide-slate-800/60 dark:bg-slate-800 dark:bg-slate-900"
                                >
                                    <tr
                                        v-for="(item, idx) in getItemBiayas(
                                            row,
                                        )"
                                        :key="item.id"
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
                                            {{ item.name }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-right text-sm font-black text-emerald-700 dark:text-emerald-400"
                                        >
                                            {{ formatRupiah(item.nominal) }}
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
                                                                openEditItemModal(
                                                                    item,
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
                                                                openDeleteItemModal(
                                                                    item,
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
                                    class="border-t-2 border-slate-200 bg-slate-50/90 font-bold text-slate-700 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-300"
                                >
                                    <tr>
                                        <td
                                            colspan="2"
                                            class="px-6 py-3.5 text-right text-xs font-black text-slate-600 uppercase dark:text-slate-300"
                                        >
                                            Subtotal:
                                        </td>
                                        <td
                                            class="px-6 py-3.5 text-right text-sm font-black text-emerald-700 dark:text-emerald-400"
                                        >
                                            {{
                                                formatRupiah(
                                                    getKategoriTotalBiaya(row),
                                                )
                                            }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Empty State for Items -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900"
                        >
                            <p
                                class="text-sm font-bold text-slate-700 dark:text-slate-200"
                            >
                                Belum ada rincian biaya untuk kategori interview
                                ini.
                            </p>
                            <p
                                class="mt-1 text-xs text-slate-400 dark:text-slate-500"
                            >
                                Klik tombol <strong>+ Tambah Rincian</strong> di
                                atas untuk menambahkan rincian biaya interview.
                            </p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- MODAL: KATEGORI BIAYA -->
        <Modal
            :show="modalKategoriOpen"
            @close="closeKategoriModal"
            maxWidth="md"
            :title="isEditingKategori ? 'Edit Kategori' : 'Tambah Kategori'"
            :description="
                isEditingKategori
                    ? 'Perbarui nama kategori interview'
                    : 'Buat kategori interview baru (berlaku global)'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400"
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="kategoriInterviewFormSubmit"
                @submit.prevent="submitKategori"
                class="space-y-4"
            >
                <TextInput
                    label="Nama Kategori Biaya Interview"
                    v-model="kategoriForm.name"
                    :error="kategoriForm.errors.name"
                    placeholder="Contoh: Biaya Ujian Seleksi & Interview"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeKategoriModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="kategoriInterviewFormSubmit"
                    :loading="kategoriForm.processing"
                >
                    {{ isEditingKategori ? 'Simpan' : 'Tambah Kategori' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL: ITEM BIAYA -->
        <Modal
            :show="modalItemOpen"
            @close="closeItemModal"
            maxWidth="md"
            :title="isEditingItem ? 'Edit Rincian' : 'Tambah Rincian'"
            :description="
                activeKategoriForItem
                    ? `Kategori: ${activeKategoriForItem.name}`
                    : 'Rincian komponen biaya interview'
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
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="itemInterviewFormSubmit"
                @submit.prevent="submitItem"
                class="space-y-4"
            >
                <TextInput
                    label="Nama Komponen Biaya"
                    v-model="itemForm.name"
                    :error="itemForm.errors.name"
                    placeholder="Contoh: Biaya Penguji, Sertifikat Ujian"
                    required
                />

                <CurrencyInput
                    label="Nominal Biaya"
                    v-model="itemForm.nominal"
                    :error="itemForm.errors.nominal"
                    placeholder="Contoh: 150.000"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeItemModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="itemInterviewFormSubmit"
                    :loading="itemForm.processing"
                >
                    {{ isEditingItem ? 'Simpan' : 'Tambah Rincian' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL: HAPUS KONFIRMASI -->
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <div class="text-center sm:text-left">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus data
                    <strong class="font-bold text-slate-800 dark:text-slate-100"
                        >"{{ deleteTarget?.name }}"</strong
                    >?
                </p>
                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                    Data yang sudah dihapus akan hilang dari sistem.
                </p>
            </div>

            <template #footer>
                <SecondaryButton @click="modalDeleteOpen = false">
                    Batal
                </SecondaryButton>
                <DangerButton @click="executeDelete"> Ya, Hapus </DangerButton>
            </template>
        </Modal>
    </div>
</template>
