<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
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

interface CabangItem {
    id: string;
    name: string;
    code?: string;
    singkatan?: string;
    logo_path?: string;
    alamat?: string;
    telepon?: string;
    is_active?: boolean;
}

interface KategoriBiaya {
    id: string;
    jenis?: string;
    cabang_id?: string;
    jenis_rombongan?: 'PESAWAT' | 'KAPAL';
    name: string;
    total_biaya?: number;
    cabang?: CabangItem;
    item_biayas?: ItemBiaya[];
    itemBiayas?: ItemBiaya[];
}

const props = defineProps<{
    cabangs: CabangItem[];
    kategoriBiayas: KategoriBiaya[];
}>();

// ==========================================
// Offcanvas Drawer & Active Tab Cabang State
// ==========================================
const isCabangDrawerOpen = ref<boolean>(false);
const activeCabangId = ref<string>('');

// Helper Logo Cabang
const getCabangLogo = (cabangOrCode?: CabangItem | string) => {
    if (typeof cabangOrCode === 'object' && cabangOrCode?.logo_path) {
        return cabangOrCode.logo_path.startsWith('/')
            ? cabangOrCode.logo_path
            : `/${cabangOrCode.logo_path}`;
    }

    const nameOrCode =
        typeof cabangOrCode === 'string'
            ? cabangOrCode
            : cabangOrCode?.name ||
              cabangOrCode?.singkatan ||
              cabangOrCode?.code ||
              '';

    const lower = (nameOrCode || '').toLowerCase();

    if (lower.includes('barat') || lower.includes('kalbar')) {
        return '/image/cabang/kalbar.svg';
    }

    if (lower.includes('timur') || lower.includes('kaltim')) {
        return '/image/cabang/kaltim.png';
    }

    return '/image/cabang/kalbar.svg';
};

const getCabangScopeText = (cabang?: CabangItem) => {
    if (!cabang) {
        return 'Pusat / Seluruh Wilayah Cabang';
    }

    if (cabang.alamat) {
        return cabang.alamat;
    }

    const lower = (cabang.name || '').toLowerCase();

    if (lower.includes('barat')) {
        return 'Wilayah Kalimantan Barat & Sekitarnya';
    }

    if (lower.includes('timur')) {
        return 'Wilayah Kalimantan Timur & Sekitarnya';
    }

    return `Wilayah ${cabang.name}`;
};

const selectCabang = (id: string) => {
    activeCabangId.value = id;

    if (typeof window !== 'undefined') {
        localStorage.setItem('active_tagihan_rombongan_cabang_id', id);
        const url = new URL(window.location.href);
        url.searchParams.set('cabang_id', id);
        window.history.replaceState({}, '', url.toString());
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const fromUrl = urlParams.get('cabang_id');
        const fromStorage = localStorage.getItem(
            'active_tagihan_rombongan_cabang_id',
        );

        const targetId = fromUrl || fromStorage;

        if (targetId && props.cabangs.some((c) => c.id === targetId)) {
            activeCabangId.value = targetId;
        } else if (props.cabangs.length > 0) {
            activeCabangId.value = props.cabangs[0].id;
        }
    }
});

watch(
    () => props.cabangs,
    (newCabangs) => {
        if (newCabangs.length > 0 && !activeCabangId.value) {
            activeCabangId.value = newCabangs[0].id;
        }
    },
    { immediate: true },
);

const activeCabang = computed(() => {
    return (
        props.cabangs.find((c) => c.id === activeCabangId.value) ||
        props.cabangs[0] || {
            id: '',
            name: 'Kalimantan Barat',
            singkatan: 'Kalbar',
            logo_path: 'image/cabang/kalbar.svg',
        }
    );
});

// ==========================================
// Filter Transportasi State (FilterModal)
// ==========================================
const isFilterModalOpen = ref<boolean>(false);
const activeJenisRombongan = ref<'ALL' | 'PESAWAT' | 'KAPAL'>('ALL');
const filterForm = ref<{
    jenis_rombongan: 'ALL' | 'PESAWAT' | 'KAPAL';
}>({
    jenis_rombongan: 'ALL',
});

const isFilterActive = computed(() => {
    return activeJenisRombongan.value !== 'ALL';
});

const applyFilters = () => {
    activeJenisRombongan.value = filterForm.value.jenis_rombongan;
    isFilterModalOpen.value = false;
};

const resetFilters = () => {
    filterForm.value.jenis_rombongan = 'ALL';
    activeJenisRombongan.value = 'ALL';
    isFilterModalOpen.value = false;
};

// ==========================================
// DataTable State & Computations
// ==========================================
const columns = [
    { key: 'name', label: 'NAMA KATEGORI BIAYA ROMBONGAN' },
    { key: 'jenis_rombongan', label: 'TRANSPORTASI' },
    { key: 'total_biaya', label: 'JUMLAH TOTAL BIAYA' },
];

const searchQuery = ref<string>('');
const perPage = ref<number>(5);
const currentPage = ref<number>(1);

watch([activeCabangId, activeJenisRombongan, searchQuery, perPage], () => {
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

    return props.kategoriBiayas.filter((kategori) => {
        const matchesCabang =
            !activeCabangId.value ||
            kategori.cabang_id === activeCabangId.value;
        const matchesJenis =
            activeJenisRombongan.value === 'ALL' ||
            kategori.jenis_rombongan === activeJenisRombongan.value;
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            kategori.name.toLowerCase().includes(query) ||
            (kategori.jenis_rombongan &&
                kategori.jenis_rombongan.toLowerCase().includes(query));

        return matchesCabang && matchesJenis && matchesSearch;
    });
});

const totalComponentsCount = computed(() => {
    return filteredKategoriBiayas.value.reduce((acc, curr) => {
        return acc + getItemBiayas(curr).length;
    }, 0);
});

const avgComponentsPerKategori = computed(() => {
    if (filteredKategoriBiayas.value.length === 0) {
        return '0';
    }

    return (
        totalComponentsCount.value / filteredKategoriBiayas.value.length
    ).toFixed(1);
});

const totalBiayaPesawat = computed(() => {
    return (props.kategoriBiayas || [])
        .filter(
            (k) =>
                (!activeCabangId.value ||
                    k.cabang_id === activeCabangId.value) &&
                k.jenis_rombongan === 'PESAWAT',
        )
        .reduce((acc, curr) => acc + getKategoriTotalBiaya(curr), 0);
});

const totalBiayaKapal = computed(() => {
    return (props.kategoriBiayas || [])
        .filter(
            (k) =>
                (!activeCabangId.value ||
                    k.cabang_id === activeCabangId.value) &&
                k.jenis_rombongan === 'KAPAL',
        )
        .reduce((acc, curr) => acc + getKategoriTotalBiaya(curr), 0);
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
// MODAL STATE: KATEGORI BIAYA ROMBONGAN
// ==========================================
const modalKategoriOpen = ref(false);
const isEditingKategori = ref(false);

const kategoriForm = useForm({
    id: '',
    jenis: 'rombongan',
    cabang_id: '',
    jenis_rombongan: 'PESAWAT' as 'PESAWAT' | 'KAPAL',
    name: '',
});

const currentFormCabang = computed(() => {
    const cId = kategoriForm.cabang_id || activeCabangId.value;

    return props.cabangs?.find((c) => c.id === cId) || activeCabang.value;
});

const openAddKategoriModal = () => {
    isEditingKategori.value = false;
    kategoriForm.reset();
    kategoriForm.clearErrors();
    kategoriForm.jenis = 'rombongan';
    kategoriForm.cabang_id = activeCabangId.value;
    kategoriForm.jenis_rombongan = 'PESAWAT';
    modalKategoriOpen.value = true;
};

const openEditKategoriModal = (kategori: KategoriBiaya) => {
    isEditingKategori.value = true;
    kategoriForm.id = kategori.id;
    kategoriForm.jenis = 'rombongan';
    kategoriForm.cabang_id = kategori.cabang_id || activeCabangId.value;
    kategoriForm.jenis_rombongan = kategori.jenis_rombongan || 'PESAWAT';
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
// MODAL STATE: ITEM BIAYA (RINCIAN BIAYA)
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
        <Head title="Tagihan Rombongan" />

        <!-- Right Offcanvas / Overcanvas Drawer for Cabang Tabs -->
        <Teleport to="body">
            <div
                v-if="isCabangDrawerOpen"
                class="fixed inset-0 z-50 overflow-hidden"
            >
                <!-- Backdrop Overlay -->
                <div
                    @click="isCabangDrawerOpen = false"
                    class="fixed inset-0 bg-gray-900/40 backdrop-blur-xs transition-opacity duration-300 dark:bg-slate-950/70"
                ></div>

                <!-- Drawer Container on Right -->
                <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div
                        class="flex h-full w-80 max-w-sm transform flex-col border-l border-gray-100 bg-white shadow-2xl transition-transform duration-300 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <!-- Drawer Header -->
                        <div
                            class="flex items-center justify-between border-b border-gray-100 bg-gray-50 p-5 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/80"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-md shadow-primary/20 dark:bg-primary-dark"
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
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                                    >
                                        Cabang Rombongan
                                    </h2>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        Pilih cabang untuk mengelola
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="isCabangDrawerOpen = false"
                                class="cursor-pointer rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:bg-slate-800 dark:text-slate-300 dark:text-slate-400 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Drawer Content: List of Cabang Cards -->
                        <div class="flex-1 space-y-3 overflow-y-auto p-4">
                            <button
                                v-for="c in props.cabangs"
                                :key="c.id"
                                @click="
                                    selectCabang(c.id);
                                    isCabangDrawerOpen = false;
                                "
                                class="group flex w-full cursor-pointer items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="[
                                    activeCabangId === c.id
                                        ? 'border-primary bg-primary text-white shadow-lg ring-2 shadow-primary/20 ring-primary/30 dark:bg-primary-dark'
                                        : 'border-gray-200 bg-white text-gray-800 hover:border-primary/50 hover:bg-primary/5 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-700 dark:hover:bg-slate-800/50',
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <img
                                        :src="getCabangLogo(c)"
                                        :alt="c.name"
                                        class="h-9 w-9 shrink-0 object-contain"
                                    />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-black tracking-wide"
                                            :class="
                                                activeCabangId === c.id
                                                    ? 'text-white'
                                                    : 'text-gray-900 dark:text-slate-100'
                                            "
                                        >
                                            {{
                                                c.singkatan ||
                                                c.code ||
                                                'CABANG'
                                            }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-xs font-bold"
                                            :class="
                                                activeCabangId === c.id
                                                    ? 'text-white/90'
                                                    : 'text-gray-700 dark:text-slate-200 dark:text-slate-300'
                                            "
                                        >
                                            {{ c.name }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[11px]"
                                            :class="
                                                activeCabangId === c.id
                                                    ? 'text-white/75'
                                                    : 'text-gray-400 dark:text-slate-400 dark:text-slate-500'
                                            "
                                        >
                                            {{ getCabangScopeText(c) }}
                                        </span>
                                    </div>
                                </div>
                                <svg
                                    class="h-5 w-5 shrink-0 transition-transform group-hover:translate-x-1"
                                    :class="
                                        activeCabangId === c.id
                                            ? 'text-white'
                                            : 'text-gray-400 dark:text-slate-400 dark:text-slate-500'
                                    "
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
                        </div>

                        <!-- Drawer Footer -->
                        <div
                            class="border-t border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/50"
                        >
                            <span
                                class="text-[11px] font-medium text-gray-400 dark:text-slate-500"
                                >Daftar Cabang Rombongan Santri</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Main Page Header with Responsive Floating Right Tab Trigger Button -->
        <div
            class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="pr-24 sm:pr-0">
                <h1
                    class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                >
                    Tagihan Rombongan
                </h1>
                <p
                    class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Kelola rincian biaya transportasi dan rombongan santri per
                    cabang (Pesawat & Kapal Laut).
                </p>
            </div>

            <!-- Floating Right Tab Trigger Button (2 short concise lines: CABANG / [SINGKATAN]) -->
            <button
                @click="isCabangDrawerOpen = true"
                class="group absolute top-0 right-0 -mt-2 -mr-4 flex shrink-0 cursor-pointer items-center gap-2.5 rounded-l-2xl bg-[#1a2e4a] px-3.5 py-2.5 text-white shadow-xl shadow-slate-900/20 transition-all duration-300 hover:bg-[#15253d] hover:pr-5 focus:outline-none sm:relative sm:top-auto sm:right-auto md:-mr-6 lg:-mr-8 dark:bg-slate-800 dark:hover:bg-slate-700"
                title="Klik untuk memilih Cabang Rombongan"
            >
                <img
                    :src="getCabangLogo(activeCabang)"
                    :alt="activeCabang.name"
                    class="h-7 w-auto shrink-0 object-contain"
                />
                <div class="flex flex-col text-left">
                    <span
                        class="text-[9px] leading-none font-black tracking-wider text-slate-300 uppercase"
                        >CABANG</span
                    >
                    <span
                        class="mt-1 text-xs leading-none font-black text-white"
                        >{{
                            activeCabang.singkatan ||
                            activeCabang.code ||
                            activeCabang.name
                        }}</span
                    >
                </div>
            </button>
        </div>

        <!-- Active Cabang Banner Card (Matching Tagihan Pendaftaran Style) -->
        <div
            class="mb-6 flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Logo Image without box/card -->
                <img
                    :src="getCabangLogo(activeCabang)"
                    :alt="activeCabang.name"
                    class="h-12 w-auto max-w-[70px] shrink-0 object-contain sm:h-16 sm:max-w-[80px]"
                />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-xs font-black tracking-widest text-primary uppercase dark:text-blue-400"
                        >
                            CABANG:
                            {{
                                (
                                    activeCabang.singkatan ||
                                    activeCabang.code ||
                                    activeCabang.name
                                ).toUpperCase()
                            }}
                        </span>
                        <span
                            class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{
                                props.cabangs.findIndex(
                                    (c) => c.id === activeCabang.id,
                                ) + 1
                            }}
                            dari {{ props.cabangs.length }} Cabang
                        </span>
                    </div>
                    <h2
                        class="mt-1 text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                    >
                        {{ activeCabang.name }}
                    </h2>
                    <p
                        class="mt-0.5 text-xs font-medium text-gray-500 dark:text-slate-400"
                    >
                        {{ getCabangScopeText(activeCabang) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Professional Statistics Cards Grid for Active Cabang -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Stat Card 1: Total Rombongan Pesawat -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Rombongan Pesawat
                    </span>
                    <img
                        src="/image/rombongan/pesawat.png"
                        alt="Pesawat"
                        class="h-12 w-auto max-w-16 object-contain transition-transform duration-200 group-hover:scale-110"
                    />
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-2xl font-black tracking-tight text-blue-700 dark:text-blue-400"
                    >
                        {{ formatRupiah(totalBiayaPesawat) }}
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"
                    ></span>
                    <span class="truncate">
                        Tarif rombongan moda transportasi udara
                    </span>
                </div>
            </div>

            <!-- Stat Card 2: Total Rombongan Kapal -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Rombongan Kapal Laut
                    </span>
                    <img
                        src="/image/rombongan/kapal.png"
                        alt="Kapal"
                        class="h-12 w-auto max-w-16 object-contain transition-transform duration-200 group-hover:scale-110"
                    />
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-2xl font-black tracking-tight text-teal-700 dark:text-teal-400"
                    >
                        {{ formatRupiah(totalBiayaKapal) }}
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-teal-500"
                    ></span>
                    <span class="truncate">
                        Tarif rombongan moda transportasi laut
                    </span>
                </div>
            </div>

            <!-- Stat Card 3: Total Komponen Biaya Rombongan -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Jumlah Komponen
                    </span>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400"
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
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                    >
                        {{ totalComponentsCount }}
                    </span>
                    <span
                        class="text-xs font-bold text-slate-500 dark:text-slate-400"
                    >
                        Komponen
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"
                    ></span>
                    <span class="truncate">
                        Rata-rata {{ avgComponentsPerKategori }} item per
                        kategori
                    </span>
                </div>
            </div>
        </div>

        <!-- DataTable with Full Actions and Premium Expandable Subview -->
        <DataTable
            :columns="columns"
            :data="paginatedKategoriBiayas"
            :pagination="tablePagination"
            expandable
            @search="(val) => (searchQuery = val)"
            @limit="(val) => (perPage = val)"
        >
            <!-- Actions slot on Top Right of Table Header -->
            <template #actions>
                <PrimaryButton
                    @click="openAddKategoriModal"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-primary/25 transition-all duration-200 hover:bg-primary/90"
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
            </template>

            <!-- Filters Slot with FilterModal (Matching Pegawai Pattern) -->
            <template #filters>
                <!-- Trigger Button -->
                <button
                    @click="isFilterModalOpen = true"
                    class="group inline-flex h-full cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-700 shadow-xs transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    <svg
                        class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500"
                        :class="isFilterActive ? 'text-primary' : ''"
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
                        class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2"
                    ></span>
                </button>

                <!-- Filter Modal -->
                <FilterModal
                    :show="isFilterModalOpen"
                    title="Filter Tagihan Rombongan"
                    description="Saring data berdasarkan jenis moda transportasi rombongan"
                    @close="isFilterModalOpen = false"
                    @reset="resetFilters"
                    @apply="applyFilters"
                >
                    <div>
                        <label
                            class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                            >Moda Transportasi</label
                        >
                        <CustomSelect
                            v-model="filterForm.jenis_rombongan"
                            :options="[
                                { value: 'ALL', label: 'Semua Transportasi' },
                                { value: 'PESAWAT', label: 'Pesawat Terbang' },
                                { value: 'KAPAL', label: 'Kapal Laut' },
                            ]"
                            placeholder="Pilih Transportasi"
                        />
                    </div>
                </FilterModal>
            </template>

            <!-- Cell: Nama Kategori Biaya Rombongan -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3.5 py-1">
                    <img
                        v-if="row.jenis_rombongan === 'PESAWAT'"
                        src="/image/rombongan/pesawat.png"
                        alt="Pesawat"
                        class="h-9 w-auto max-w-14 shrink-0 object-contain"
                    />
                    <img
                        v-else
                        src="/image/rombongan/kapal.png"
                        alt="Kapal"
                        class="h-9 w-auto max-w-14 shrink-0 object-contain"
                    />
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-800">
                            {{ row.name }}
                        </span>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold"
                                :class="
                                    row.jenis_rombongan === 'PESAWAT'
                                        ? 'bg-blue-50 text-blue-700'
                                        : 'bg-teal-50 text-teal-700'
                                "
                            >
                                {{ getItemBiayas(row).length }} Komponen
                            </span>
                            <span
                                class="text-[11px] text-gray-400 dark:text-slate-500"
                            >
                                Cabang {{ activeCabang.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Cell: Transportasi -->
            <template #cell-jenis_rombongan="{ row }">
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold"
                    :class="
                        row.jenis_rombongan === 'PESAWAT'
                            ? 'border border-blue-100 bg-blue-50 text-blue-700'
                            : 'border border-teal-100 bg-teal-50 text-teal-700'
                    "
                >
                    {{
                        row.jenis_rombongan === 'PESAWAT'
                            ? 'Pesawat'
                            : 'Kapal Laut'
                    }}
                </span>
            </template>

            <!-- Cell: Total Biaya -->
            <template #cell-total_biaya="{ row }">
                <span class="text-sm font-black text-emerald-700">
                    {{ formatRupiah(getKategoriTotalBiaya(row)) }}
                </span>
            </template>

            <!-- Row Actions (ActionMenu with Trigger and Content Slots) -->
            <template #row-actions="{ row }">
                <div class="flex justify-end">
                    <ActionMenu>
                        <template #trigger>
                            <button
                                class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:bg-slate-700"
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
                                class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4"
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
                    class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 shadow-inner dark:border-slate-700/80"
                >
                    <!-- Header inside Collapse -->
                    <div
                        class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-sm shadow-primary/20"
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
                                    class="flex flex-wrap items-center gap-2 text-sm font-extrabold text-slate-800"
                                >
                                    Rincian Biaya Rombongan:
                                    <span
                                        class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary"
                                    >
                                        {{ row.name }}
                                    </span>
                                </h4>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Komponen tiket, bagasi tambahan, akomodasi
                                    transit, dan biaya rombongan lainnya.
                                </p>
                            </div>
                        </div>

                        <PrimaryButton
                            @click="openAddItemModal(row)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-all hover:bg-primary/90"
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
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-900"
                    >
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead
                                class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-700"
                            >
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-16 px-6 py-3.5 text-center text-xs font-bold tracking-wider text-slate-500 uppercase"
                                    >
                                        No
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-bold tracking-wider text-slate-500 uppercase"
                                    >
                                        Nama Komponen Biaya
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-right text-xs font-bold tracking-wider text-slate-500 uppercase"
                                    >
                                        Nominal Biaya
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-28 px-6 py-3.5 text-center text-xs font-bold tracking-wider text-slate-500 uppercase"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 bg-white dark:bg-slate-900"
                            >
                                <tr
                                    v-for="(item, idx) in getItemBiayas(row)"
                                    :key="item.id"
                                    class="transition-colors hover:bg-slate-50/60"
                                >
                                    <td
                                        class="px-6 py-4 text-center text-sm font-bold text-slate-400"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-bold text-slate-800"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm font-black text-emerald-700"
                                    >
                                        {{ formatRupiah(item.nominal) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
                                            <ActionMenu>
                                                <template #trigger>
                                                    <button
                                                        class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:bg-slate-700"
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
                                                        class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4"
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
                                class="border-t-2 border-slate-200 bg-slate-50/90 font-bold text-slate-700 dark:border-slate-700"
                            >
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-6 py-3.5 text-right text-xs font-black text-slate-600 uppercase"
                                    >
                                        Subtotal:
                                    </td>
                                    <td
                                        class="px-6 py-3.5 text-right text-sm font-black text-emerald-700"
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
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:bg-slate-900"
                    >
                        <p class="text-sm font-bold text-slate-700">
                            Belum ada rincian biaya untuk kategori rombongan
                            ini.
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Klik tombol <strong>+ Tambah Rincian</strong> di
                            atas untuk menambahkan rincian tiket, bagasi, dll.
                        </p>
                    </div>
                </div>
            </template>
        </DataTable>

        <!-- MODAL: KATEGORI BIAYA ROMBONGAN -->
        <Modal
            :show="modalKategoriOpen"
            @close="closeKategoriModal"
            maxWidth="md"
            :title="isEditingKategori ? 'Edit Kategori' : 'Tambah Kategori'"
            :description="
                isEditingKategori
                    ? 'Perbarui nama & moda transportasi rombongan'
                    : 'Buat kategori biaya rombongan baru'
            "
        >
            <template #icon>
                <img
                    :src="
                        kategoriForm.jenis_rombongan === 'KAPAL'
                            ? '/image/rombongan/kapal.png'
                            : '/image/rombongan/pesawat.png'
                    "
                    :alt="kategoriForm.jenis_rombongan"
                    class="h-14 w-auto max-w-16 object-contain"
                />
            </template>

            <div
                class="mb-5 rounded-xl border border-slate-100 bg-slate-50 p-3.5 text-center"
            >
                <p class="text-xs text-slate-500">Cabang Terpilih:</p>
                <p class="mt-0.5 text-sm font-bold text-slate-800">
                    {{ currentFormCabang.name }}
                </p>
            </div>

            <form
                id="kategoriRombonganFormSubmit"
                @submit.prevent="submitKategori"
                class="space-y-4"
            >
                <div>
                    <label class="block text-xs font-bold text-slate-700">
                        Jenis Transportasi Rombongan
                        <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1.5 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="kategoriForm.jenis_rombongan = 'PESAWAT'"
                            class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border p-3 text-xs font-bold transition-all"
                            :class="[
                                kategoriForm.jenis_rombongan === 'PESAWAT'
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                            ]"
                        >
                            <img
                                src="/image/rombongan/pesawat.png"
                                alt="Pesawat"
                                class="h-6 w-auto max-w-8 object-contain"
                            />
                            <span>Pesawat Terbang</span>
                        </button>
                        <button
                            type="button"
                            @click="kategoriForm.jenis_rombongan = 'KAPAL'"
                            class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border p-3 text-xs font-bold transition-all"
                            :class="[
                                kategoriForm.jenis_rombongan === 'KAPAL'
                                    ? 'border-teal-600 bg-teal-50 text-teal-700 ring-2 ring-teal-500/20'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                            ]"
                        >
                            <img
                                src="/image/rombongan/kapal.png"
                                alt="Kapal"
                                class="h-6 w-auto max-w-8 object-contain"
                            />
                            <span>Kapal Laut</span>
                        </button>
                    </div>
                </div>

                <TextInput
                    label="Nama Kategori Biaya Rombongan"
                    v-model="kategoriForm.name"
                    :error="kategoriForm.errors.name"
                    placeholder="Contoh: Biaya Keberangkatan Pesawat Pontianak"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeKategoriModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="kategoriRombonganFormSubmit"
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
                    : 'Rincian komponen biaya rombongan'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
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
                id="itemRombonganFormSubmit"
                @submit.prevent="submitItem"
                class="space-y-4"
            >
                <TextInput
                    label="Nama Komponen Biaya"
                    v-model="itemForm.name"
                    :error="itemForm.errors.name"
                    placeholder="Contoh: Tiket Pesawat, Bagasi Tambahan, Bus Shuttle"
                    required
                />

                <CurrencyInput
                    label="Nominal Biaya"
                    v-model="itemForm.nominal"
                    :error="itemForm.errors.nominal"
                    placeholder="Contoh: 1.750.000"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeItemModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="itemRombonganFormSubmit"
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
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600"
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <div class="text-center sm:text-left">
                <p class="text-sm text-slate-600">
                    Apakah Anda yakin ingin menghapus data
                    <strong class="font-bold text-slate-800"
                        >"{{ deleteTarget?.name }}"</strong
                    >?
                </p>
                <p class="mt-2 text-xs text-slate-400">
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
