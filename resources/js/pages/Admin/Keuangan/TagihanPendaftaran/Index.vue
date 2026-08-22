<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
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
    jenjang_id?: string;
    name: string;
    total_biaya?: number;
    item_biayas?: ItemBiaya[];
    itemBiayas?: ItemBiaya[];
}

interface JenjangItem {
    id: string;
    code?: string;
    name: string;
    singkatan?: string;
    logo_path?: string;
    keterangan?: string;
}

const props = defineProps<{
    jenjangs: JenjangItem[];
    kategoriBiayas: KategoriBiaya[];
}>();

// Order map to strictly enforce MTs, MA, S1, S2, S3 (Matching ProgramPendidikan)
const jenjangOrderMap: Record<string, number> = {
    MTS: 1,
    MA: 2,
    S1: 3,
    S2: 4,
    S3: 5,
};

const orderedJenjangs = computed(() => {
    if (!props.jenjangs) {
        return [];
    }

    return [...props.jenjangs].sort((a, b) => {
        const orderA =
            jenjangOrderMap[(a.code || a.singkatan || '').toUpperCase()] ?? 99;
        const orderB =
            jenjangOrderMap[(b.code || b.singkatan || '').toUpperCase()] ?? 99;

        return orderA - orderB;
    });
});

// Helper logo jenjang (No CSS filter/invert, original logo)
const getJenjangLogo = (jenjangOrCode?: JenjangItem | string) => {
    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code =
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || '';
    const found = props.jenjangs?.find(
        (j) =>
            (j.code || j.singkatan || '').toUpperCase() ===
            (code || '').toUpperCase(),
    );

    if (found?.logo_path) {
        return found.logo_path.startsWith('/')
            ? found.logo_path
            : `/${found.logo_path}`;
    }

    const c = (code || '').toUpperCase();

    if (c === 'MTS') {
        return '/image/logos/jenjang/logo-mts.png';
    }

    if (c === 'MA') {
        return '/image/logos/jenjang/logo-ma.png';
    }

    return '/image/logos/jenjang/logo-uii dalwa.png';
};

// ==========================================
// Offcanvas Drawer & Active Tab State
// ==========================================
const isJenjangDrawerOpen = ref(false);

const getInitialJenjangId = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tabParam = params.get('tab') || params.get('jenjang_id');

        if (tabParam && orderedJenjangs.value.length > 0) {
            const found = orderedJenjangs.value.find(
                (j) =>
                    (j.code &&
                        j.code.toUpperCase() === tabParam.toUpperCase()) ||
                    j.id === tabParam,
            );

            if (found) {
                return found.id;
            }
        }
    }

    return orderedJenjangs.value[0]?.id || '';
};

const activeJenjangId = ref<string>(getInitialJenjangId());

onMounted(() => {
    const initialId = getInitialJenjangId();

    if (initialId) {
        activeJenjangId.value = initialId;
    }
});

watch(
    orderedJenjangs,
    (newJenjangs) => {
        if (newJenjangs.length > 0 && !activeJenjangId.value) {
            activeJenjangId.value = getInitialJenjangId();
        }
    },
    { immediate: true },
);

watch(activeJenjangId, (newId) => {
    if (typeof window !== 'undefined' && newId) {
        const currentJenjang = orderedJenjangs.value.find(
            (j) => j.id === newId,
        );
        const tabValue = currentJenjang?.code
            ? currentJenjang.code.toUpperCase()
            : newId;
        const url = new URL(window.location.href);

        if (url.searchParams.get('tab') !== tabValue) {
            url.searchParams.set('tab', tabValue);
            window.history.replaceState({}, '', url);
        }
    }
});

const activeJenjang = computed(() => {
    return (
        orderedJenjangs.value.find((j) => j.id === activeJenjangId.value) ||
        orderedJenjangs.value[0] || {
            id: '',
            code: 'MTs',
            name: 'Madrasah Tsanawiyah',
            singkatan: 'MTs',
            logo_path: 'image/logos/jenjang/logo-mts.png',
        }
    );
});

// Scope text helper for drawer & header
const getJenjangScopeText = (code?: string) => {
    switch ((code || '').toUpperCase()) {
        case 'MTS':
            return 'Tingkat Pendidikan Menengah Pertama (Kelas 7 - 9)';
        case 'MA':
            return 'Tingkat Pendidikan Menengah Atas (Kelas 10 - 12)';
        case 'S1':
            return 'Program Pendidikan Sarjana (Strata 1)';
        case 'S2':
            return 'Program Pendidikan Magister (Strata 2)';
        case 'S3':
            return 'Program Pendidikan Doktoral (Strata 3)';
        default:
            return 'Program Pendidikan Formal';
    }
};

const columns = [
    { key: 'name', label: 'NAMA KATEGORI BIAYA' },
    { key: 'total_biaya', label: 'TOTAL BIAYA' },
];

const searchQuery = ref<string>('');
const perPage = ref<number>(5);
const currentPage = ref<number>(1);

watch([activeJenjangId, searchQuery, perPage], () => {
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
        const matchesTab =
            !activeJenjangId.value ||
            kategori.jenjang_id === activeJenjangId.value;
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query || kategori.name.toLowerCase().includes(query);

        return matchesTab && matchesSearch;
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
    jenis: 'pendaftaran',
    jenjang_id: '',
    name: '',
});

const currentFormJenjang = computed(() => {
    const jId = kategoriForm.jenjang_id || activeJenjangId.value;

    return props.jenjangs?.find((j) => j.id === jId) || activeJenjang.value;
});

const openAddKategoriModal = () => {
    isEditingKategori.value = false;
    kategoriForm.reset();
    kategoriForm.clearErrors();
    kategoriForm.jenis = 'pendaftaran';
    kategoriForm.jenjang_id = activeJenjangId.value;
    modalKategoriOpen.value = true;
};

const openEditKategoriModal = (kategori: KategoriBiaya) => {
    isEditingKategori.value = true;
    kategoriForm.id = kategori.id;
    kategoriForm.jenis = 'pendaftaran';
    kategoriForm.jenjang_id = kategori.jenjang_id || activeJenjangId.value;
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
        <Head title="Tagihan Pendaftaran" />

        <!-- Right Offcanvas / Overcanvas Drawer for Jenjang Tabs (Matching ProgramPendidikan) -->
        <Teleport to="body">
            <div
                v-if="isJenjangDrawerOpen"
                class="fixed inset-0 z-50 overflow-hidden"
            >
                <!-- Backdrop Overlay -->
                <div
                    @click="isJenjangDrawerOpen = false"
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
                                            d="M12 14l9-5-9-5-9 5 9 5z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                                    >
                                        Jenjang Pendidikan
                                    </h2>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        Pilih jenjang untuk mengelola
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="isJenjangDrawerOpen = false"
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

                        <!-- Drawer Content: List of Jenjang Cards (MTs, MA, S1, S2, S3) -->
                        <div class="flex-1 space-y-3 overflow-y-auto p-4">
                            <button
                                v-for="j in orderedJenjangs"
                                :key="j.id"
                                @click="
                                    activeJenjangId = j.id;
                                    isJenjangDrawerOpen = false;
                                "
                                class="group flex w-full cursor-pointer items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="[
                                    activeJenjangId === j.id
                                        ? 'border-primary bg-primary text-white shadow-lg ring-2 shadow-primary/20 ring-primary/30 dark:bg-primary-dark'
                                        : 'border-gray-200 bg-white text-gray-800 hover:border-primary/50 hover:bg-primary/5 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-700 dark:hover:bg-slate-800/50',
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <img
                                        :src="getJenjangLogo(j.code)"
                                        :alt="j.name"
                                        class="h-9 w-9 shrink-0 object-contain"
                                    />
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-black tracking-wide"
                                            :class="
                                                activeJenjangId === j.id
                                                    ? 'text-white'
                                                    : 'text-gray-900 dark:text-slate-100'
                                            "
                                        >
                                            {{
                                                j.code ||
                                                j.name
                                                    .substring(0, 3)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-xs font-bold"
                                            :class="
                                                activeJenjangId === j.id
                                                    ? 'text-white/90'
                                                    : 'text-gray-700 dark:text-slate-200 dark:text-slate-300'
                                            "
                                        >
                                            {{ j.name }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[11px]"
                                            :class="
                                                activeJenjangId === j.id
                                                    ? 'text-white/75'
                                                    : 'text-gray-400 dark:text-slate-400 dark:text-slate-500'
                                            "
                                        >
                                            {{ getJenjangScopeText(j.code) }}
                                        </span>
                                    </div>
                                </div>
                                <svg
                                    class="h-5 w-5 shrink-0 transition-transform group-hover:translate-x-1"
                                    :class="
                                        activeJenjangId === j.id
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
                                >Urutan Jenjang: MTs, MA, S1, S2, S3</span
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
                    Tagihan Pendaftaran
                </h1>
                <p
                    class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Kelola rincian biaya pendaftaran, formulir seleksi, dan
                    daftar ulang santri per jenjang pendidikan.
                </p>
            </div>

            <!-- Floating Right Tab Trigger Button (2 short concise lines: JENJANG / CODE) -->
            <button
                @click="isJenjangDrawerOpen = true"
                class="group absolute top-0 right-0 -mt-2 -mr-4 flex shrink-0 cursor-pointer items-center gap-2.5 rounded-l-2xl bg-[#1a2e4a] px-3.5 py-2.5 text-white shadow-xl shadow-slate-900/20 transition-all duration-300 hover:bg-[#15253d] hover:pr-5 focus:outline-none sm:relative sm:top-auto sm:right-auto md:-mr-6 lg:-mr-8 dark:bg-slate-800 dark:hover:bg-slate-700"
                title="Klik untuk memilih Jenjang Pendidikan"
            >
                <img
                    :src="getJenjangLogo(activeJenjang.code)"
                    :alt="activeJenjang.name"
                    class="h-7 w-auto shrink-0 object-contain"
                />
                <div class="flex flex-col text-left">
                    <span
                        class="text-[9px] leading-none font-black tracking-wider text-slate-300 uppercase"
                        >JENJANG</span
                    >
                    <span
                        class="mt-1 text-xs leading-none font-black text-white"
                        >{{ activeJenjang.code || 'MTs' }}</span
                    >
                </div>
            </button>
        </div>

        <!-- Active Jenjang Banner Card (Matching ProgramPendidikan Style) -->
        <div
            class="mb-6 flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Logo Image without box/card -->
                <img
                    :src="getJenjangLogo(activeJenjang.code)"
                    :alt="activeJenjang.name"
                    class="h-12 w-auto max-w-[70px] shrink-0 object-contain sm:h-16 sm:max-w-[80px]"
                />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-xs font-black tracking-widest text-primary uppercase dark:text-blue-400"
                        >
                            KODE: {{ activeJenjang.code || 'MTS' }}
                        </span>
                        <span
                            class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Urutan
                            {{
                                orderedJenjangs.findIndex(
                                    (j) => j.id === activeJenjang.id,
                                ) + 1
                            }}
                            dari {{ orderedJenjangs.length }}
                        </span>
                    </div>
                    <h2
                        class="mt-1 text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                    >
                        {{ activeJenjang.name }}
                    </h2>
                    <p
                        class="mt-0.5 text-xs font-medium text-gray-500 dark:text-slate-400"
                    >
                        {{ getJenjangScopeText(activeJenjang.code) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Professional Statistics Cards Grid for Active Jenjang -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Stat Card 1: Total Kategori Biaya -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Kategori Biaya
                    </span>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400"
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
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                    >
                        {{ filteredKategoriBiayas.length }}
                    </span>
                    <span
                        class="text-xs font-bold text-slate-500 dark:text-slate-400"
                    >
                        Kategori
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"
                    ></span>
                    <span class="truncate">
                        Tingkat {{ activeJenjang.name }} ({{
                            activeJenjang.code || 'MTS'
                        }})
                    </span>
                </div>
            </div>

            <!-- Stat Card 2: Total Rincian Komponen Item -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Rincian Komponen
                    </span>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400"
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
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
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
                        class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>
                    <span class="truncate">
                        Rata-rata {{ avgComponentsPerKategori }} item per
                        kategori
                    </span>
                </div>
            </div>

            <!-- Stat Card 3: Total Akumulasi Biaya Tagihan -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Total Biaya Pendaftaran
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
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-2xl font-black tracking-tight text-emerald-700 dark:text-emerald-400"
                    >
                        {{ formatRupiah(totalAccumulatedBiaya) }}
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"
                    ></span>
                    <span class="truncate">
                        Estimasi seluruh biaya per santri ({{
                            activeJenjang.code || 'MTS'
                        }})
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

            <!-- Cell: Nama Kategori Biaya -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3.5 py-1">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 shadow-xs dark:border-slate-700/80"
                    >
                        <svg
                            class="h-5 w-5 text-primary"
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
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-800">
                            {{ row.name }}
                        </span>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700"
                            >
                                {{ getItemBiayas(row).length }} Komponen
                            </span>
                            <span
                                class="text-[11px] text-gray-400 dark:text-slate-500"
                            >
                                Jenjang {{ activeJenjang.name }}
                            </span>
                        </div>
                    </div>
                </div>
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

            <!-- Expanded Sub-View: Standard Full-Sized Table Design -->
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
                                    class="flex items-center gap-2 text-sm font-extrabold text-slate-800"
                                >
                                    Rincian Biaya:
                                    <span
                                        class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary"
                                    >
                                        {{ row.name }}
                                    </span>
                                </h4>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Daftar komponen biaya terperinci untuk
                                    kategori ini.
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
                            Belum ada rincian komponen biaya untuk kategori ini.
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Klik tombol <strong>+ Tambah Rincian</strong> di
                            atas untuk menambahkan rincian baru.
                        </p>
                    </div>
                </div>
            </template>
        </DataTable>

        <!-- ========================================== -->
        <!-- MODAL: TAMBAH / EDIT KATEGORI BIAYA        -->
        <!-- ========================================== -->
        <Modal
            :show="modalKategoriOpen"
            @close="closeKategoriModal"
            maxWidth="md"
            :title="isEditingKategori ? 'Edit Kategori' : 'Tambah Kategori'"
            :description="
                isEditingKategori
                    ? 'Perbarui nama kategori biaya pendaftaran'
                    : 'Buat kategori biaya pendaftaran baru'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
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

            <div
                class="mb-5 rounded-xl border border-slate-100 bg-slate-50 p-3.5 text-center"
            >
                <p class="text-xs text-slate-500">
                    Jenjang Pendidikan Terpilih:
                </p>
                <p class="mt-0.5 text-sm font-bold text-slate-800">
                    {{ currentFormJenjang.name }} ({{
                        currentFormJenjang.code || 'MTs'
                    }})
                </p>
            </div>

            <form
                id="kategoriFormSubmit"
                @submit.prevent="submitKategori"
                class="space-y-4"
            >
                <TextInput
                    label="Nama Kategori Biaya Pendaftaran"
                    v-model="kategoriForm.name"
                    :error="kategoriForm.errors.name"
                    placeholder="Contoh: Biaya Pendaftaran & Formulir"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeKategoriModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="kategoriFormSubmit"
                    :loading="kategoriForm.processing"
                >
                    {{ isEditingKategori ? 'Simpan' : 'Tambah Kategori' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ========================================== -->
        <!-- MODAL: TAMBAH / EDIT ITEM BIAYA            -->
        <!-- ========================================== -->
        <Modal
            :show="modalItemOpen"
            @close="closeItemModal"
            maxWidth="md"
            :title="isEditingItem ? 'Edit Rincian' : 'Tambah Rincian'"
            :description="
                activeKategoriForItem
                    ? `Kategori: ${activeKategoriForItem.name}`
                    : 'Rincian komponen biaya pendaftaran'
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
                id="itemFormSubmit"
                @submit.prevent="submitItem"
                class="space-y-4"
            >
                <TextInput
                    label="Nama Komponen Biaya"
                    v-model="itemForm.name"
                    :error="itemForm.errors.name"
                    placeholder="Contoh: Formulir Pendaftaran, Seragam & Kitab"
                    required
                />

                <CurrencyInput
                    label="Nominal Biaya"
                    v-model="itemForm.nominal"
                    :error="itemForm.errors.nominal"
                    placeholder="Contoh: 250.000"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="closeItemModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="itemFormSubmit"
                    :loading="itemForm.processing"
                >
                    {{ isEditingItem ? 'Simpan' : 'Tambah Rincian' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ========================================== -->
        <!-- MODAL: HAPUS KONFIRMASI                    -->
        <!-- ========================================== -->
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
