<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { store, update, destroy } from '@/routes/admin/akademik/program/index';

interface TingkatItem {
    id: string;
    jenjang_id: string;
    name: string;
    gender_allowed: 'L' | 'P' | 'ALL';
}

interface JurusanItem {
    id: string;
    jenjang_id: string;
    code?: string;
    name: string;
    gender_allowed: 'L' | 'P' | 'ALL';
}

interface ProdiItem {
    id: string;
    fakultas_id: string;
    code?: string;
    name: string;
    gender_allowed: 'L' | 'P' | 'ALL';
}

interface FakultasItem {
    id: string;
    jenjang_id: string;
    code?: string;
    name: string;
    prodis?: ProdiItem[];
}

interface JenjangItem {
    id: string;
    code?: string;
    name: string;
    singkatan?: string;
    logo_path?: string;
    gender_allowed?: 'L' | 'P' | 'ALL';
    tingkats?: TingkatItem[];
    jurusans?: JurusanItem[];
    fakultas?: FakultasItem[];
}

const props = defineProps<{
    jenjangs: JenjangItem[];
}>();

// Order map to strictly enforce MTs, MA, S1, S2, S3
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
        const tabParam = params.get('tab');

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
        orderedJenjangs.value[0] || { id: '', name: '', code: '' }
    );
});

// Scope text helper for drawer & header
const getJenjangScopeText = (code?: string) => {
    switch ((code || '').toUpperCase()) {
        case 'MTS':
            return 'Tingkat Kelas 7 - 9';
        case 'MA':
            return 'Tingkat & Jurusan Aliyah';
        case 'S1':
            return 'Fakultas & Prodi Sarjana';
        case 'S2':
            return 'Fakultas & Prodi Magister';
        case 'S3':
            return 'Fakultas & Prodi Doktoral';
        default:
            return 'Program Pendidikan';
    }
};

// Flat list prodi for PT (S1/S2/S3)
const activeProdis = computed(() => {
    if (!activeJenjang.value?.fakultas) {
        return [];
    }

    const list: (ProdiItem & { fakultas_name?: string })[] = [];
    activeJenjang.value.fakultas.forEach((f) => {
        if (f.prodis) {
            f.prodis.forEach((p) => {
                list.push({
                    ...p,
                    fakultas_name: f.name,
                });
            });
        }
    });

    return list;
});

// Helper Badge Gender
const getGenderBadge = (allowed: 'L' | 'P' | 'ALL') => {
    if (allowed === 'L') {
        return {
            label: 'Laki-Laki Only',
            class: 'bg-blue-50 text-blue-700 ring-1 ring-blue-700/10',
        };
    }

    if (allowed === 'P') {
        return {
            label: 'Perempuan Only',
            class: 'bg-pink-50 text-pink-700 ring-1 ring-pink-700/10',
        };
    }

    return {
        label: 'Laki-Laki & Perempuan',
        class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
    };
};

// ==========================================
// Modals State
// ==========================================
const activeModalType = ref<
    'tingkat' | 'jurusan' | 'fakultas' | 'prodi' | 'jenjang_gender' | null
>(null);
const isEditing = ref(false);

const deleteModalOpen = ref(false);
const itemToDelete = ref<{
    modelKey: 'tingkat' | 'jurusan' | 'fakultas' | 'prodi';
    id: string;
    name: string;
} | null>(null);

// Forms
const jenjangForm = useForm({
    id: '',
    name: '',
    code: '',
    gender_allowed: 'ALL' as 'L' | 'P' | 'ALL',
    allow_laki: true,
    allow_perempuan: true,
});
const tingkatForm = useForm({
    id: '',
    jenjang_id: '',
    name: '',
    gender_allowed: 'ALL' as 'L' | 'P' | 'ALL',
    allow_laki: true,
    allow_perempuan: true,
});

const jurusanForm = useForm({
    id: '',
    jenjang_id: '',
    code: '',
    name: '',
    gender_allowed: 'ALL' as 'L' | 'P' | 'ALL',
    allow_laki: true,
    allow_perempuan: true,
});

const fakultasForm = useForm({
    id: '',
    jenjang_id: '',
    code: '',
    name: '',
});

const prodiForm = useForm({
    id: '',
    fakultas_id: '',
    code: '',
    name: '',
    gender_allowed: 'ALL' as 'L' | 'P' | 'ALL',
    allow_laki: true,
    allow_perempuan: true,
});

// Gender Checkbox Sync
const syncGenderAllowed = (formObj: any) => {
    if (formObj.allow_laki && formObj.allow_perempuan) {
        formObj.gender_allowed = 'ALL';
    } else if (formObj.allow_laki) {
        formObj.gender_allowed = 'L';
    } else if (formObj.allow_perempuan) {
        formObj.gender_allowed = 'P';
    } else {
        formObj.allow_laki = true;
        formObj.allow_perempuan = true;
        formObj.gender_allowed = 'ALL';
    }
};

// Open Modals
const openTingkatModal = (item?: TingkatItem) => {
    isEditing.value = !!item;
    tingkatForm.reset();
    tingkatForm.clearErrors();
    tingkatForm.jenjang_id = activeJenjang.value.id;

    if (item) {
        tingkatForm.id = item.id;
        tingkatForm.name = item.name;
        tingkatForm.gender_allowed = item.gender_allowed;
        tingkatForm.allow_laki =
            item.gender_allowed === 'L' || item.gender_allowed === 'ALL';
        tingkatForm.allow_perempuan =
            item.gender_allowed === 'P' || item.gender_allowed === 'ALL';
    } else {
        tingkatForm.allow_laki = true;
        tingkatForm.allow_perempuan = true;
        tingkatForm.gender_allowed = 'ALL';
    }

    activeModalType.value = 'tingkat';
};

const openJurusanModal = (item?: JurusanItem) => {
    isEditing.value = !!item;
    jurusanForm.reset();
    jurusanForm.clearErrors();
    jurusanForm.jenjang_id = activeJenjang.value.id;

    if (item) {
        jurusanForm.id = item.id;
        jurusanForm.code = item.code || '';
        jurusanForm.name = item.name;
        jurusanForm.gender_allowed = item.gender_allowed;
        jurusanForm.allow_laki =
            item.gender_allowed === 'L' || item.gender_allowed === 'ALL';
        jurusanForm.allow_perempuan =
            item.gender_allowed === 'P' || item.gender_allowed === 'ALL';
    } else {
        jurusanForm.allow_laki = true;
        jurusanForm.allow_perempuan = true;
        jurusanForm.gender_allowed = 'ALL';
    }

    activeModalType.value = 'jurusan';
};

const openFakultasModal = (item?: FakultasItem) => {
    isEditing.value = !!item;
    fakultasForm.reset();
    fakultasForm.clearErrors();
    fakultasForm.jenjang_id = activeJenjang.value.id;

    if (item) {
        fakultasForm.id = item.id;
        fakultasForm.code = item.code || '';
        fakultasForm.name = item.name;
    }

    activeModalType.value = 'fakultas';
};

const openProdiModal = (item?: ProdiItem) => {
    isEditing.value = !!item;
    prodiForm.reset();
    prodiForm.clearErrors();

    if (item) {
        prodiForm.id = item.id;
        prodiForm.fakultas_id = item.fakultas_id;
        prodiForm.code = item.code || '';
        prodiForm.name = item.name;
        prodiForm.gender_allowed = item.gender_allowed;
        prodiForm.allow_laki =
            item.gender_allowed === 'L' || item.gender_allowed === 'ALL';
        prodiForm.allow_perempuan =
            item.gender_allowed === 'P' || item.gender_allowed === 'ALL';
    } else {
        prodiForm.fakultas_id = activeJenjang.value.fakultas?.[0]?.id || '';
        prodiForm.allow_laki = true;
        prodiForm.allow_perempuan = true;
        prodiForm.gender_allowed = 'ALL';
    }

    activeModalType.value = 'prodi';
};

const openJenjangGenderModal = (item: JenjangItem) => {
    jenjangForm.reset();
    jenjangForm.clearErrors();
    jenjangForm.id = item.id;
    jenjangForm.name = item.name;
    jenjangForm.code = item.code || '';
    const g = item.gender_allowed || 'ALL';
    jenjangForm.gender_allowed = g;
    jenjangForm.allow_laki = g === 'L' || g === 'ALL';
    jenjangForm.allow_perempuan = g === 'P' || g === 'ALL';
    activeModalType.value = 'jenjang_gender';
};

const closeModal = () => {
    activeModalType.value = null;
    jenjangForm.reset();
    jenjangForm.clearErrors();
    tingkatForm.reset();
    tingkatForm.clearErrors();
    jurusanForm.reset();
    jurusanForm.clearErrors();
    fakultasForm.reset();
    fakultasForm.clearErrors();
    prodiForm.reset();
    prodiForm.clearErrors();
};

// Submits
const submitJenjangGender = () => {
    syncGenderAllowed(jenjangForm);
    jenjangForm.put(update.url({ model: 'jenjang', id: jenjangForm.id }), {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};

const submitTingkat = () => {
    if (
        activeJenjang.value.code === 'MTs' ||
        activeJenjang.value.code === 'MA'
    ) {
        tingkatForm.gender_allowed = 'ALL';
        tingkatForm.allow_laki = true;
        tingkatForm.allow_perempuan = true;
    } else {
        syncGenderAllowed(tingkatForm);
    }

    if (isEditing.value) {
        tingkatForm.put(update.url({ model: 'tingkat', id: tingkatForm.id }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        tingkatForm.post(store.url({ model: 'tingkat' }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const submitJurusan = () => {
    syncGenderAllowed(jurusanForm);

    if (isEditing.value) {
        jurusanForm.put(update.url({ model: 'jurusan', id: jurusanForm.id }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        jurusanForm.post(store.url({ model: 'jurusan' }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const submitFakultas = () => {
    if (isEditing.value) {
        fakultasForm.put(
            update.url({ model: 'fakultas', id: fakultasForm.id }),
            {
                onSuccess: () => closeModal(),
                preserveScroll: true,
            },
        );
    } else {
        fakultasForm.post(store.url({ model: 'fakultas' }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const submitProdi = () => {
    syncGenderAllowed(prodiForm);

    if (isEditing.value) {
        prodiForm.put(update.url({ model: 'prodi', id: prodiForm.id }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        prodiForm.post(store.url({ model: 'prodi' }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

// Deletes
const openDeleteModal = (
    modelKey: 'tingkat' | 'jurusan' | 'fakultas' | 'prodi',
    id: string,
    name: string,
) => {
    itemToDelete.value = { modelKey, id, name };
    deleteModalOpen.value = true;
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
                onSuccess: () => {
                    deleteModalOpen.value = false;
                    itemToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Program Pendidikan & Jurusan" />

        <!-- Right Offcanvas / Overcanvas Drawer for Jenjang Tabs -->
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
                            class="dark:bg-slate-800/50/80 flex items-center justify-between border-b border-gray-100 bg-gray-50 p-5 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-md shadow-primary/20 dark:bg-blue-600"
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
                                class="rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:bg-slate-800 dark:text-slate-300 dark:text-slate-400 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                class="group flex w-full items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="[
                                    activeJenjangId === j.id
                                        ? 'border-primary bg-primary text-white shadow-lg ring-2 shadow-primary/20 ring-primary/30 dark:bg-blue-600 dark:shadow-blue-500/20'
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
                            class="dark:bg-slate-800/50/50 border-t border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800"
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
                    Program Pendidikan & Jurusan
                </h1>
                <p
                    class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Kelola tingkatan kelas, jurusan Aliyah, fakultas & program
                    studi per jenjang pendidikan.
                </p>
            </div>

            <!-- Floating Right Tab Trigger Button (Original logo colors without invert bug) -->
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

        <!-- Active Jenjang Banner Card (Plain Logo without box card) -->
        <div
            class="mb-6 flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Logo Image without box/card (polosan) -->
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

            <!-- Stats summary pills -->
            <div
                class="flex flex-wrap items-center gap-2.5 border-t border-gray-100 pt-3 sm:border-t-0 sm:pt-0 dark:border-slate-800"
            >
                <div
                    v-if="activeJenjang.code === 'MTs'"
                    class="flex items-center gap-2 rounded-xl border border-sky-100 bg-sky-50 px-3.5 py-2 dark:border-sky-900/50 dark:bg-sky-950/40"
                >
                    <span
                        class="text-xs font-extrabold text-sky-800 dark:text-sky-300"
                        >Total Tingkat Kelas:</span
                    >
                    <span
                        class="font-mono text-sm font-black text-sky-700 dark:text-sky-400"
                        >{{
                            activeJenjang.tingkats
                                ? activeJenjang.tingkats.length
                                : 0
                        }}</span
                    >
                </div>
                <template v-else-if="activeJenjang.code === 'MA'">
                    <div
                        class="flex items-center gap-2 rounded-xl border border-sky-100 bg-sky-50 px-3 py-1.5 dark:border-sky-900/50 dark:bg-sky-950/40"
                    >
                        <span
                            class="text-xs font-bold text-sky-800 dark:text-sky-300"
                            >Tingkat Kelas:</span
                        >
                        <span
                            class="font-mono text-sm font-black text-sky-700 dark:text-sky-400"
                            >{{
                                activeJenjang.tingkats
                                    ? activeJenjang.tingkats.length
                                    : 0
                            }}</span
                        >
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-xl border border-purple-100 bg-purple-50 px-3 py-1.5 dark:border-purple-900/50 dark:bg-purple-950/40"
                    >
                        <span
                            class="text-xs font-bold text-purple-800 dark:text-purple-300"
                            >Jurusan:</span
                        >
                        <span
                            class="font-mono text-sm font-black text-purple-700 dark:text-purple-400"
                            >{{
                                activeJenjang.jurusans
                                    ? activeJenjang.jurusans.length
                                    : 0
                            }}</span
                        >
                    </div>
                </template>
                <template v-else>
                    <div
                        class="flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-1.5 dark:border-indigo-900/50 dark:bg-indigo-950/40"
                    >
                        <span
                            class="text-xs font-bold text-indigo-800 dark:text-indigo-300"
                            >Fakultas:</span
                        >
                        <span
                            class="font-mono text-sm font-black text-indigo-700 dark:text-indigo-400"
                            >{{
                                activeJenjang.fakultas
                                    ? activeJenjang.fakultas.length
                                    : 0
                            }}</span
                        >
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-1.5 dark:border-emerald-900/50 dark:bg-emerald-950/40"
                    >
                        <span
                            class="text-xs font-bold text-emerald-800 dark:text-emerald-300"
                            >Program Studi:</span
                        >
                        <span
                            class="font-mono text-sm font-black text-emerald-700 dark:text-emerald-400"
                            >{{ activeProdis.length }}</span
                        >
                    </div>
                </template>
            </div>
        </div>

        <!-- Content Area Per Jenjang -->
        <div class="space-y-6">
            <!-- ==================================================== -->
            <!-- JENJANG MTs (Information Panel & Grid Cards Tingkat) -->
            <!-- ==================================================== -->
            <div
                v-if="activeJenjang.code === 'MTs'"
                class="grid grid-cols-1 gap-6 lg:grid-cols-12"
            >
                <!-- Info & Guidance Panel (4 Cols) -->
                <div class="space-y-4 lg:col-span-4">
                    <div
                        class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-sky-100 bg-sky-50 text-sky-600"
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
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3
                                    class="text-sm font-extrabold text-gray-900 dark:text-slate-100"
                                >
                                    Ketentuan Jenjang MTs
                                </h3>
                                <p
                                    class="text-[11px] text-gray-500 dark:text-slate-400"
                                >
                                    Madrasah Tsanawiyah
                                </p>
                            </div>
                        </div>

                        <!-- Gender Configuration Widget for Jenjang MTs -->
                        <div
                            class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700/80"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="text-xs font-bold text-slate-700"
                                    >Gender Diizinkan:</span
                                >
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="
                                        getGenderBadge(
                                            activeJenjang.gender_allowed ||
                                                'ALL',
                                        ).class
                                    "
                                >
                                    {{
                                        getGenderBadge(
                                            activeJenjang.gender_allowed ||
                                                'ALL',
                                        ).label
                                    }}
                                </span>
                            </div>
                            <SecondaryButton
                                @click="openJenjangGenderModal(activeJenjang)"
                                class="w-full justify-center text-xs"
                            >
                                <svg
                                    class="mr-1.5 h-4 w-4 text-slate-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                </svg>
                                Ubah Gender
                            </SecondaryButton>
                        </div>

                        <div
                            class="space-y-2.5 border-t border-gray-100 pt-3 text-xs leading-relaxed text-gray-600 dark:border-slate-800 dark:text-slate-300"
                        >
                            <div class="flex items-start gap-2">
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span
                                    >Pengaturan gender berlaku untuk seluruh
                                    calon santri
                                    <strong>Jenjang MTs</strong>.</span
                                >
                            </div>
                            <div class="flex items-start gap-2">
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span
                                    >Tingkat kelas MTs (Kelas 7, 8, 9) tidak
                                    memiliki opsi gender terpisah per
                                    kelas.</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-3.5"
                        >
                            <span class="text-xs font-bold text-slate-700"
                                >Total Tingkat Kelas:</span
                            >
                            <span class="text-sm font-black text-primary"
                                >{{
                                    activeJenjang.tingkats
                                        ? activeJenjang.tingkats.length
                                        : 0
                                }}
                                Kelas</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Grid Cards Tingkat Kelas MTs (8 Cols) -->
                <div class="space-y-4 lg:col-span-8">
                    <div
                        class="mb-4 flex items-center justify-between gap-3 sm:mb-5"
                    >
                        <div>
                            <h3
                                class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                            >
                                Daftar Tingkat Kelas MTs
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Tingkat kelas aktif untuk calon santri Madrasah
                                Tsanawiyah
                            </p>
                        </div>
                        <PrimaryButton
                            @click="openTingkatModal()"
                            class="justify-center"
                        >
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
                            Tambah Tingkat
                        </PrimaryButton>
                    </div>

                    <!-- Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="(t, idx) in activeJenjang.tingkats"
                            :key="t.id"
                            class="group relative flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-primary/50 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 text-lg font-black text-sky-700 transition-colors group-hover:bg-primary group-hover:text-white"
                                >
                                    {{
                                        t.name.replace(/[^0-9]/g, '') || idx + 1
                                    }}
                                </div>
                                <div>
                                    <h4
                                        class="text-base font-black text-gray-900 transition-colors group-hover:text-primary dark:text-slate-100"
                                    >
                                        {{ t.name }}
                                    </h4>
                                    <span
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-gray-500 dark:text-slate-400"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Tingkat Aktif
                                    </span>
                                </div>
                            </div>

                            <ActionMenu>
                                <template #trigger>
                                    <button
                                        class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:border-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                        @click="openTingkatModal(t)"
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-amber-500"
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
                                                'tingkat',
                                                t.id,
                                                t.name,
                                            )
                                        "
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-rose-500"
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

                        <!-- Dashed Add Card -->
                        <button
                            @click="openTingkatModal()"
                            class="group flex cursor-pointer items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-300 p-5 text-gray-500 transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800/50"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 transition-colors group-hover:bg-primary group-hover:text-white dark:bg-slate-800 dark:group-hover:bg-slate-700"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-bold"
                                >Tambah Tingkat</span
                            >
                        </button>
                    </div>

                    <div
                        v-if="
                            !activeJenjang.tingkats ||
                            activeJenjang.tingkats.length === 0
                        "
                        class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-400 italic dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500"
                    >
                        Belum ada data tingkat kelas. Klik tombol di atas untuk
                        menambah kelas.
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- JENJANG MA (Asymmetric Grid 4:8 with High-End Cards) -->
            <!-- ==================================================== -->
            <div
                v-else-if="activeJenjang.code === 'MA'"
                class="grid grid-cols-1 gap-6 lg:grid-cols-12"
            >
                <!-- Section Tingkat MA (4 Cols) -->
                <div class="space-y-4 lg:col-span-4">
                    <!-- Guidance Card for MA Tingkat -->
                    <div
                        class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-400"
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
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="text-xs font-extrabold text-gray-900 dark:text-slate-100"
                            >
                                Tingkat Kelas MA
                            </h3>
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-gray-500 dark:text-slate-400"
                        >
                            Tingkatan kelas aktif untuk Madrasah Aliyah. Gender
                            calon santri diatur khusus per
                            <strong>Jurusan MA</strong>.
                        </p>
                    </div>

                    <!-- Header & Add Button -->
                    <div class="flex items-center justify-between gap-3">
                        <h3
                            class="text-sm font-black text-gray-900 dark:text-slate-100"
                        >
                            Daftar Kelas MA
                        </h3>
                        <PrimaryButton
                            @click="openTingkatModal()"
                            class="px-3 py-1.5 text-xs"
                        >
                            + Tingkat
                        </PrimaryButton>
                    </div>

                    <!-- Vertical Class Cards List -->
                    <div class="space-y-3">
                        <div
                            v-for="(t, idx) in activeJenjang.tingkats"
                            :key="t.id"
                            class="group relative flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-primary/50 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-purple-100 bg-purple-50 text-sm font-black text-purple-700 transition-colors group-hover:bg-primary group-hover:text-white dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-400 dark:group-hover:bg-blue-600"
                                >
                                    {{
                                        t.name.replace(/[^0-9]/g, '') ||
                                        idx + 10
                                    }}
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-gray-900 transition-colors group-hover:text-primary dark:text-slate-100"
                                    >
                                        {{ t.name }}
                                    </h4>
                                    <span
                                        class="text-[10px] font-medium text-gray-400 dark:text-slate-500"
                                        >Madrasah Aliyah</span
                                    >
                                </div>
                            </div>

                            <ActionMenu>
                                <template #trigger>
                                    <button
                                        class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:border-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                        @click="openTingkatModal(t)"
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-amber-500"
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
                                                'tingkat',
                                                t.id,
                                                t.name,
                                            )
                                        "
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-rose-500"
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
                        <div
                            v-if="
                                !activeJenjang.tingkats ||
                                activeJenjang.tingkats.length === 0
                            "
                            class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-xs text-gray-400 italic dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500"
                        >
                            Belum ada tingkat kelas MA.
                        </div>
                    </div>
                </div>

                <!-- Section Jurusan MA (8 Cols) -->
                <div class="space-y-4 lg:col-span-8">
                    <div
                        class="mb-4 flex items-center justify-between gap-3 sm:mb-5"
                    >
                        <div>
                            <h3
                                class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                            >
                                Daftar Jurusan MA
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Pilihan jurusan & spesifikasi gender calon
                                santri Aliyah
                            </p>
                        </div>
                        <PrimaryButton
                            @click="openJurusanModal()"
                            class="justify-center"
                        >
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
                            Tambah Jurusan
                        </PrimaryButton>
                    </div>

                    <!-- Jurusan Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="j in activeJenjang.jurusans"
                            :key="j.id"
                            class="group relative flex flex-col justify-between space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-primary/50 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="rounded-xl border border-purple-100 bg-purple-50 px-2.5 py-1 text-xs font-black text-purple-700 transition-colors group-hover:bg-primary group-hover:text-white dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-400 dark:group-hover:bg-blue-600"
                                    >
                                        {{ j.code || 'MA' }}
                                    </span>
                                    <div>
                                        <h4
                                            class="text-base font-black text-gray-900 transition-colors group-hover:text-primary dark:text-slate-100"
                                        >
                                            {{ j.name }}
                                        </h4>
                                        <span
                                            class="text-[11px] font-medium text-gray-400 dark:text-slate-500"
                                            >Madrasah Aliyah</span
                                        >
                                    </div>
                                </div>
                                <ActionMenu>
                                    <template #trigger>
                                        <button
                                            class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:border-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                            @click="openJurusanModal(j)"
                                            class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            <svg
                                                class="mr-2 h-4 w-4 text-amber-500"
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
                                                    'jurusan',
                                                    j.id,
                                                    j.name,
                                                )
                                            "
                                            class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50"
                                        >
                                            <svg
                                                class="mr-2 h-4 w-4 text-rose-500"
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

                            <div
                                class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-slate-800"
                            >
                                <span
                                    class="text-[11px] font-bold text-gray-500 dark:text-slate-400"
                                    >Gender Diizinkan:</span
                                >
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="
                                        getGenderBadge(j.gender_allowed).class
                                    "
                                >
                                    {{ getGenderBadge(j.gender_allowed).label }}
                                </span>
                            </div>
                        </div>

                        <!-- Dashed Add Card -->
                        <button
                            @click="openJurusanModal()"
                            class="group flex min-h-[120px] cursor-pointer items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-300 p-5 text-gray-500 transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800/50"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 transition-colors group-hover:bg-primary group-hover:text-white dark:bg-slate-800 dark:group-hover:bg-slate-700"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-bold"
                                >Tambah Jurusan</span
                            >
                        </button>
                    </div>

                    <div
                        v-if="
                            !activeJenjang.jurusans ||
                            activeJenjang.jurusans.length === 0
                        "
                        class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-400 italic dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500"
                    >
                        Belum ada data jurusan MA. Klik tombol di atas untuk
                        menambah jurusan.
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- JENJANG S1 / S2 / S3 (Fakultas & Program Studi 4:8 Cards Grid) -->
            <!-- ==================================================== -->
            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Section Fakultas (4 Cols) -->
                <div class="space-y-4 lg:col-span-4">
                    <!-- Guidance Card for PT -->
                    <div
                        class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-sky-100 bg-sky-50 text-sky-600 dark:border-sky-900/50 dark:bg-sky-950/40 dark:text-sky-400"
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
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0v-4c0-.656.126-1.283.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-4c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="text-xs font-extrabold text-gray-900 dark:text-slate-100"
                            >
                                Ketentuan Akademik {{ activeJenjang.code }}
                            </h3>
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-gray-500 dark:text-slate-400"
                        >
                            Program Studi (Prodi) berada di bawah naungan
                            <strong>Fakultas</strong>. Pengaturan gender
                            mahasiswa diatur khusus per
                            <strong>Program Studi</strong>.
                        </p>
                    </div>

                    <!-- Header & Add Button -->
                    <div class="flex items-center justify-between gap-3">
                        <h3
                            class="text-sm font-black text-gray-900 dark:text-slate-100"
                        >
                            Daftar Fakultas
                        </h3>
                        <PrimaryButton
                            @click="openFakultasModal()"
                            class="px-3 py-1.5 text-xs"
                        >
                            + Fakultas
                        </PrimaryButton>
                    </div>

                    <!-- Vertical Fakultas Cards List -->
                    <div class="space-y-3">
                        <div
                            v-for="f in activeJenjang.fakultas"
                            :key="f.id"
                            class="group relative flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-primary/50 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="shrink-0 rounded-xl border border-indigo-100 bg-indigo-50 px-2.5 py-1.5 text-xs font-black text-indigo-700 transition-colors group-hover:bg-primary group-hover:text-white dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-400 dark:group-hover:bg-blue-600"
                                >
                                    {{ f.code || 'FAK' }}
                                </span>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-gray-900 transition-colors group-hover:text-primary dark:text-slate-100"
                                    >
                                        {{ f.name }}
                                    </h4>
                                    <span
                                        class="text-[10px] font-medium text-gray-400 dark:text-slate-500"
                                    >
                                        {{ f.prodis ? f.prodis.length : 0 }}
                                        Program Studi
                                    </span>
                                </div>
                            </div>

                            <ActionMenu>
                                <template #trigger>
                                    <button
                                        class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:border-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                        @click="openFakultasModal(f)"
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-amber-500"
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
                                                'fakultas',
                                                f.id,
                                                f.name,
                                            )
                                        "
                                        class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4 text-rose-500"
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
                        <div
                            v-if="
                                !activeJenjang.fakultas ||
                                activeJenjang.fakultas.length === 0
                            "
                            class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-xs text-gray-400 italic dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500"
                        >
                            Belum ada fakultas.
                        </div>
                    </div>
                </div>

                <!-- Section Program Studi / Prodi Cards Grid (8 Cols) -->
                <div class="space-y-4 lg:col-span-8">
                    <div
                        class="mb-4 flex items-center justify-between gap-3 sm:mb-5"
                    >
                        <div>
                            <h3
                                class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                            >
                                Daftar Program Studi (Prodi)
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Program studi per fakultas & spesifikasi gender
                                calon mahasiswa {{ activeJenjang.code }}
                            </p>
                        </div>
                        <PrimaryButton
                            @click="openProdiModal()"
                            class="justify-center"
                            :disabled="
                                !activeJenjang.fakultas ||
                                activeJenjang.fakultas.length === 0
                            "
                        >
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
                            Tambah Prodi
                        </PrimaryButton>
                    </div>

                    <!-- Prodi Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="p in activeProdis"
                            :key="p.id"
                            class="group relative flex flex-col justify-between space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-primary/50 hover:shadow-md dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="rounded-xl border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 transition-colors group-hover:bg-primary group-hover:text-white"
                                    >
                                        {{ p.code || 'PRODI' }}
                                    </span>
                                    <div>
                                        <h4
                                            class="text-base font-black text-gray-900 transition-colors group-hover:text-primary dark:text-slate-100"
                                        >
                                            {{ p.name }}
                                        </h4>
                                        <span
                                            class="text-[11px] font-medium text-gray-400 dark:text-slate-500"
                                            >{{
                                                p.fakultas_name || 'Fakultas'
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <ActionMenu>
                                    <template #trigger>
                                        <button
                                            class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:border-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                                            @click="openProdiModal(p)"
                                            class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            <svg
                                                class="mr-2 h-4 w-4 text-amber-500"
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
                                                    'prodi',
                                                    p.id,
                                                    p.name,
                                                )
                                            "
                                            class="flex w-full items-center px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50"
                                        >
                                            <svg
                                                class="mr-2 h-4 w-4 text-rose-500"
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

                            <div
                                class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-slate-800"
                            >
                                <span
                                    class="text-[11px] font-bold text-gray-500 dark:text-slate-400"
                                    >Gender Diizinkan:</span
                                >
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="
                                        getGenderBadge(p.gender_allowed).class
                                    "
                                >
                                    {{ getGenderBadge(p.gender_allowed).label }}
                                </span>
                            </div>
                        </div>

                        <!-- Dashed Add Card -->
                        <button
                            @click="openProdiModal()"
                            :disabled="
                                !activeJenjang.fakultas ||
                                activeJenjang.fakultas.length === 0
                            "
                            class="group flex min-h-[120px] cursor-pointer items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-300 p-5 text-gray-500 transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:text-primary disabled:cursor-not-allowed disabled:opacity-50 dark:text-slate-400 dark:hover:bg-slate-800/50"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 transition-colors group-hover:bg-primary group-hover:text-white dark:bg-slate-800 dark:group-hover:bg-slate-700"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-bold">Tambah Prodi</span>
                        </button>
                    </div>

                    <div
                        v-if="activeProdis.length === 0"
                        class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-400 italic dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500"
                    >
                        Belum ada data program studi. Klik tombol di atas untuk
                        menambah prodi.
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- MODAL FORM GENDER JENJANG (MTs) -->
        <!-- ==================================================== -->
        <Modal
            :show="activeModalType === 'jenjang_gender'"
            @close="closeModal"
            maxWidth="md"
            :title="`Konfigurasi Gender Jenjang ${jenjangForm.code || jenjangForm.name}`"
            description="Atur gender santri yang diizinkan mendaftar untuk jenjang ini secara keseluruhan."
        >
            <form
                id="jenjangGenderForm"
                @submit.prevent="submitJenjangGender"
                class="space-y-4"
            >
                <div class="space-y-2">
                    <label
                        class="block text-sm font-bold text-gray-700 dark:text-slate-200"
                        >Gender yang Diizinkan Mendaftar *</label
                    >
                    <div
                        class="flex items-center space-x-6 rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-slate-700 dark:bg-slate-800/50"
                    >
                        <label
                            class="flex cursor-pointer items-center space-x-2.5"
                        >
                            <Checkbox
                                v-model:checked="jenjangForm.allow_laki"
                            />
                            <span
                                class="text-sm font-bold text-gray-800 dark:text-slate-200"
                                >Laki-Laki (Putra)</span
                            >
                        </label>
                        <label
                            class="flex cursor-pointer items-center space-x-2.5"
                        >
                            <Checkbox
                                v-model:checked="jenjangForm.allow_perempuan"
                            />
                            <span
                                class="text-sm font-bold text-gray-800 dark:text-slate-200"
                                >Perempuan (Putri)</span
                            >
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Pilihan ini menentukan gender calon santri yang boleh
                        memilih jenjang {{ jenjangForm.name }} saat pendaftaran.
                    </p>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="jenjangGenderForm"
                    type="submit"
                    :disabled="jenjangForm.processing"
                >
                    Simpan Perubahan
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL FORM TINGKAT -->
        <!-- ==================================================== -->
        <Modal
            :show="activeModalType === 'tingkat'"
            @close="closeModal"
            maxWidth="md"
            :title="isEditing ? 'Edit Tingkat Kelas' : 'Tambah Tingkat Kelas'"
            :description="`Konfigurasi tingkatan kelas untuk jenjang ${activeJenjang.name}`"
        >
            <form
                id="tingkatForm"
                @submit.prevent="submitTingkat"
                class="space-y-4"
            >
                <TextInput
                    id="tingkat_name"
                    label="Nama Tingkat Kelas *"
                    v-model="tingkatForm.name"
                    :error="tingkatForm.errors.name"
                    placeholder="Contoh: Kelas 7, Kelas 8, Kelas 10"
                    required
                />

                <div
                    v-if="
                        activeJenjang.code !== 'MTs' &&
                        activeJenjang.code !== 'MA'
                    "
                    class="space-y-2"
                >
                    <label
                        class="block text-sm font-bold text-gray-700 dark:text-slate-200"
                        >Gender yang Diizinkan Mendaftar *</label
                    >
                    <div
                        class="flex items-center space-x-6 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800/50"
                    >
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox
                                v-model:checked="tingkatForm.allow_laki"
                            />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Laki-Laki</span
                            >
                        </label>
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox
                                v-model:checked="tingkatForm.allow_perempuan"
                            />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Perempuan</span
                            >
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="tingkatForm"
                    type="submit"
                    :disabled="tingkatForm.processing"
                >
                    {{ isEditing ? 'Simpan' : 'Tambah' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL FORM JURUSAN -->
        <!-- ==================================================== -->
        <Modal
            :show="activeModalType === 'jurusan'"
            @close="closeModal"
            maxWidth="md"
            :title="isEditing ? 'Edit Jurusan MA' : 'Tambah Jurusan MA'"
            :description="`Konfigurasi pilihan jurusan pada jenjang Madrasah Aliyah`"
        >
            <form
                id="jurusanForm"
                @submit.prevent="submitJurusan"
                class="space-y-4"
            >
                <TextInput
                    id="jurusan_code"
                    label="Kode Jurusan *"
                    v-model="jurusanForm.code"
                    :error="jurusanForm.errors.code"
                    placeholder="Contoh: IPA, IPS, AGM"
                    required
                />
                <TextInput
                    id="jurusan_name"
                    label="Nama Jurusan *"
                    v-model="jurusanForm.name"
                    :error="jurusanForm.errors.name"
                    placeholder="Contoh: Ilmu Pengetahuan Alam"
                    required
                />

                <div class="space-y-2">
                    <label
                        class="block text-sm font-bold text-gray-700 dark:text-slate-200"
                        >Gender yang Diizinkan Mendaftar *</label
                    >
                    <div
                        class="flex items-center space-x-6 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800/50"
                    >
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox
                                v-model:checked="jurusanForm.allow_laki"
                            />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Laki-Laki</span
                            >
                        </label>
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox
                                v-model:checked="jurusanForm.allow_perempuan"
                            />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Perempuan</span
                            >
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="jurusanForm"
                    type="submit"
                    :disabled="jurusanForm.processing"
                >
                    {{ isEditing ? 'Simpan' : 'Tambah' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL FORM FAKULTAS -->
        <!-- ==================================================== -->
        <Modal
            :show="activeModalType === 'fakultas'"
            @close="closeModal"
            maxWidth="md"
            :title="isEditing ? 'Edit Fakultas' : 'Tambah Fakultas'"
            :description="`Konfigurasi fakultas perguruan tinggi jenjang ${activeJenjang.code}`"
        >
            <form
                id="fakultasForm"
                @submit.prevent="submitFakultas"
                class="space-y-4"
            >
                <TextInput
                    id="fakultas_code"
                    label="Kode Fakultas *"
                    v-model="fakultasForm.code"
                    :error="fakultasForm.errors.code"
                    placeholder="Contoh: FT, FS, FIK"
                    required
                />
                <TextInput
                    id="fakultas_name"
                    label="Nama Fakultas *"
                    v-model="fakultasForm.name"
                    :error="fakultasForm.errors.name"
                    placeholder="Contoh: Fakultas Tarbiyah"
                    required
                />
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="fakultasForm"
                    type="submit"
                    :disabled="fakultasForm.processing"
                >
                    {{ isEditing ? 'Simpan' : 'Tambah' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL FORM PRODI -->
        <!-- ==================================================== -->
        <Modal
            :show="activeModalType === 'prodi'"
            @close="closeModal"
            maxWidth="md"
            :title="isEditing ? 'Edit Program Studi' : 'Tambah Program Studi'"
            :description="`Konfigurasi prodi perkuliahan jenjang ${activeJenjang.code}`"
        >
            <form
                id="prodiForm"
                @submit.prevent="submitProdi"
                class="space-y-4"
            >
                <CustomSelect
                    id="prodi_fakultas"
                    label="Fakultas *"
                    v-model="prodiForm.fakultas_id"
                    :options="
                        activeJenjang.fakultas
                            ? activeJenjang.fakultas.map((f) => ({
                                  value: f.id,
                                  label: `${f.code ? f.code + ' - ' : ''}${f.name}`,
                              }))
                            : []
                    "
                    :error="prodiForm.errors.fakultas_id"
                    required
                />

                <TextInput
                    id="prodi_code"
                    label="Kode Program Studi *"
                    v-model="prodiForm.code"
                    :error="prodiForm.errors.code"
                    placeholder="Contoh: PAI, PBA, HKI"
                    required
                />

                <TextInput
                    id="prodi_name"
                    label="Nama Program Studi *"
                    v-model="prodiForm.name"
                    :error="prodiForm.errors.name"
                    placeholder="Contoh: Pendidikan Agama Islam"
                    required
                />

                <div class="space-y-2">
                    <label
                        class="block text-sm font-bold text-gray-700 dark:text-slate-200"
                        >Gender yang Diizinkan Mendaftar *</label
                    >
                    <div
                        class="flex items-center space-x-6 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800/50"
                    >
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox v-model:checked="prodiForm.allow_laki" />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Laki-Laki</span
                            >
                        </label>
                        <label
                            class="flex cursor-pointer items-center space-x-2"
                        >
                            <Checkbox
                                v-model:checked="prodiForm.allow_perempuan"
                            />
                            <span
                                class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                >Perempuan</span
                            >
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                <PrimaryButton
                    form="prodiForm"
                    type="submit"
                    :disabled="prodiForm.processing"
                >
                    {{ isEditing ? 'Simpan' : 'Tambah' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ==================================================== -->
        <!-- MODAL DELETE KONFIRMASI -->
        <!-- ==================================================== -->
        <Modal
            :show="deleteModalOpen"
            @close="deleteModalOpen = false"
            maxWidth="sm"
            title="Konfirmasi Hapus"
            description="Tindakan ini tidak dapat dibatalkan"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50"
                >
                    <svg
                        class="h-6 w-6 text-rose-500"
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
                <SecondaryButton @click="deleteModalOpen = false"
                    >Batal</SecondaryButton
                >
                <DangerButton @click="executeDelete">Ya, Hapus</DangerButton>
            </template>
        </Modal>
    </div>
</template>
