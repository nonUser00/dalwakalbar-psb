<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { create_bill } from '@/routes/admin/pendaftar/tagihan';

defineOptions({ layout: AdminLayout });

interface ItemBiaya {
    id: string;
    name: string;
    nominal: number | string;
}

interface KategoriBiayaItem {
    id: string;
    jenis: string;
    name: string;
    jenjang_id?: string;
    jenjang_code?: string;
    total_biaya: number;
    items?: ItemBiaya[];
}

const props = defineProps<{
    targetPendaftars?: any[];
    selectedIds?: string[];
    cabangs?: any[];
    jenjangs?: any[];
    activeJenjang?: any;
    tagihanType?: 'pendaftaran' | 'interview';
    jenjangStat?: {
        total_pendaftar: number;
        sudah_dibuat: number;
        belum_dibuat: number;
    };
    jenjangCounts?: Record<
        string,
        { total: number; sudah_dibuat: number; belum_dibuat: number }
    >;
    kategoriBiayas?: KategoriBiayaItem[];
    availablePendaftars?: any[];
}>();

// Jenjang Order Map & Drawer State
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

const isJenjangDrawerOpen = ref(false);

const switchJenjang = (id: string) => {
    isJenjangDrawerOpen.value = false;
    router.get(
        '/admin/pendaftar/tagihan/buat',
        { jenjang_id: id },
        { preserveState: false, preserveScroll: false },
    );
};

const formatBadgeCount = (count?: number) => {
    const num = Number(count || 0);

    if (num > 99) {
return '99+';
}

    return String(num);
};

// Active Jenjang definition from DB
const currentJenjang = computed(() => {
    return (
        props.activeJenjang ||
        (props.jenjangs && props.jenjangs.length > 0 ? props.jenjangs[0] : null)
    );
});

// Dynamic Back URL preserving active jenjang tab
const backUrl = computed(() => {
    if (currentJenjang.value?.id) {
        return `/admin/pendaftar/tagihan?jenjang_id=${currentJenjang.value.id}`;
    }

    return '/admin/pendaftar/tagihan';
});

// Logo and scope helpers matching Index.vue with safe fallback for all jenjangs
const getJenjangLogo = (jenjangOrCode?: any | string) => {
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
        (j: any) =>
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

    if (
        c === 'S1' ||
        c === 'S2' ||
        c === 'S3' ||
        c.includes('UII') ||
        c.includes('UNI')
    ) {
        return '/image/logos/jenjang/logo-uii dalwa.png';
    }

    return '/image/logos/logo-1.png';
};

const getEducationSubText = (row: any) => {
    const code = (
        row.jenjang?.code ||
        row.jenjang?.singkatan ||
        ''
    ).toUpperCase();
    const edu = row.education_data || {};
    const tipe = row.tipe_pendaftaran ? ` (${row.tipe_pendaftaran})` : '';

    if (code === 'MTS') {
        const rawTingkat = edu.tingkat_nama || edu.kelas_tingkat || edu.tingkat;
        if (rawTingkat) {
            return (String(rawTingkat).toLowerCase().includes('kelas') ? rawTingkat : `Kelas ${rawTingkat}`) + tipe;
        }

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : `Kelas 7${tipe}`;
    }

    if (code === 'MA') {
        const jurusan = edu.jurusan_nama || edu.jurusan_ma || edu.jurusan;
        const rawTingkat = edu.tingkat_nama || edu.kelas_tingkat || edu.tingkat;

        if (jurusan && rawTingkat) {
            const tk = String(rawTingkat).toLowerCase().includes('kelas') ? rawTingkat : `Kelas ${rawTingkat}`;
            return `${tk} | ${jurusan}${tipe}`;
        }
        if (jurusan) {
            return `Jurusan ${jurusan}${tipe}`;
        }
        if (rawTingkat) {
            return (String(rawTingkat).toLowerCase().includes('kelas') ? rawTingkat : `Kelas ${rawTingkat}`) + tipe;
        }

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : `Kelas 10${tipe}`;
    }

    // S1, S2, S3
    const prodi = edu.fakultas_utama_nama
        ? `${edu.fakultas_utama_nama} - ${edu.prodi_utama_nama || ''}`
        : (edu.fakultas_prodi_utama || edu.prodi_utama || edu.prodi);

    if (prodi) {
        return `Prodi: ${prodi}${tipe}`;
    }

    return row.tipe_pendaftaran ? `${row.tipe_pendaftaran}` : 'Reguler';
};

const handleLogoError = (event: Event) => {
    const target = event.target as HTMLImageElement;

    if (target) {
        target.src = '/image/logos/logo-1.png';
    }
};

const getJenjangScopeText = (code?: string) => {
    const c = (code || '').toUpperCase();

    if (c === 'MTS') {
return 'Tingkat Wustha / Menengah Pertama';
}

    if (c === 'MA') {
return 'Tingkat Ulya / Menengah Atas';
}

    if (c === 'S1') {
return 'Program Sarjana Terakreditasi';
}

    if (c === 'S2') {
return 'Program Magister Pascasarjana';
}

    if (c === 'S3') {
return 'Program Doktoral Pascasarjana';
}

    return 'Program Pendidikan Santri';
};

// Check if pendaftar doesn't have an active tagihan for the current cycle
const hasActiveTagihanForCycle = (p: any) => {
    if (!p.tagihans || p.tagihans.length === 0) return false;
    if (p.is_interview_ulang && p.interview_ulang_at) {
        const targetTime = new Date(p.interview_ulang_at).getTime();
        return p.tagihans.some((t: any) => t.created_at && new Date(t.created_at).getTime() >= targetTime);
    }
    return true;
};

// Initialize selected recipients (strictly status TAGIHAN and without tagihans in current cycle)
const selectedRecipients = ref<any[]>(
    Array.isArray(props.targetPendaftars)
        ? props.targetPendaftars.filter(
              (p: any) =>
                  !hasActiveTagihanForCycle(p) &&
                  (p.status || '').toUpperCase() === 'TAGIHAN' &&
                  (!currentJenjang.value ||
                      String(p.jenjang_id || p.jenjang?.id) ===
                          String(currentJenjang.value.id)),
          )
        : [],
);

// Form state for Informasi Tagihan
const formInfo = ref({
    jenis_tagihan_id: '',
    jenis_tagihan: '',
    total_amount: 0,
    nama_tagihan: '',
    published_at: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0],
});

// Formatting helper
const formatRupiah = (val: number | string) => {
    const num = parseFloat(String(val || 0));

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

// Category Selection Action
const selectCategory = (kat: KategoriBiayaItem) => {
    formInfo.value.jenis_tagihan_id = kat.id;
    formInfo.value.nama_tagihan = kat.name;
    formInfo.value.total_amount = kat.total_biaya || 0;
    formInfo.value.jenis_tagihan = kat.name;
};

// Currently Selected Kategori Details & Items Breakdown
const selectedKategori = computed(() => {
    if (!formInfo.value.jenis_tagihan_id) {
return props.kategoriBiayas?.[0] || null;
}

    return (
        (props.kategoriBiayas || []).find(
            (kat) => String(kat.id) === String(formInfo.value.jenis_tagihan_id),
        ) ||
        props.kategoriBiayas?.[0] ||
        null
    );
});

// Auto select first category if available
watch(
    () => props.kategoriBiayas,
    (kats) => {
        if (kats && kats.length > 0 && !formInfo.value.jenis_tagihan_id) {
            selectCategory(kats[0]);
        }
    },
    { immediate: true },
);

// Samaha items map: { [pendaftarId]: { amount: 0, notes: '' } }
const samahaItems = ref<Record<string, { amount: number; notes: string }>>({});

// Keep samahaItems synced with selectedRecipients
watch(
    selectedRecipients,
    (newVal) => {
        const nextMap: Record<string, { amount: number; notes: string }> = {};
        newVal.forEach((p) => {
            nextMap[p.id] = samahaItems.value[p.id] || { amount: 0, notes: '' };
        });
        samahaItems.value = nextMap;
    },
    { immediate: true, deep: true },
);

// Masa tenggat calculation (days between published_at and due_date)
const masaTenggatHari = computed(() => {
    if (!formInfo.value.published_at || !formInfo.value.due_date) {
return 14;
}

    const start = new Date(formInfo.value.published_at).getTime();
    const end = new Date(formInfo.value.due_date).getTime();
    const diffTime = end - start;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays > 0 ? diffDays : 0;
});

// Computed: Check if Informasi Tagihan is fully filled
const isInformasiTagihanComplete = computed(() => {
    return (
        Boolean(
            formInfo.value.jenis_tagihan_id || formInfo.value.jenis_tagihan,
        ) &&
        Boolean(formInfo.value.nama_tagihan?.trim()) &&
        Number(formInfo.value.total_amount || 0) > 0 &&
        Boolean(formInfo.value.published_at) &&
        Boolean(formInfo.value.due_date)
    );
});

const imageErrorMap = ref<Record<string, boolean>>({});

const getPendaftarPhoto = (pendaftar: any): string | null => {
    if (!pendaftar) {
return null;
}

    const raw =
        pendaftar.foto_url ||
        pendaftar.foto ||
        pendaftar.personal_data?.foto_url ||
        pendaftar.personal_data?.foto ||
        pendaftar.personal_data?.pas_foto ||
        pendaftar.dokumens?.find(
            (d: any) =>
                d.dokumen?.is_profile_photo ||
                d.dokumen?.name?.toLowerCase().includes('foto'),
        )?.file_path ||
        null;

    if (!raw) {
return null;
}

    if (
        raw.startsWith('http://') ||
        raw.startsWith('https://') ||
        raw.startsWith('data:image') ||
        raw.startsWith('/storage/') ||
        raw.startsWith('/')
    ) {
        return raw;
    }

    if (raw.startsWith('storage/')) {
        return `/${raw}`;
    }

    return `/storage/${raw.replace(/^\/+/, '')}`;
};

const getInitials = (name?: string) => {
    if (!name) {
return 'CS';
}

    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

// ==========================================
// MODAL: PILIH PENDAFTAR PENERIMA TAGIHAN
// ==========================================
const isAddModalOpen = ref(false);
const modalSearch = ref('');
const isCandidateFilterModalOpen = ref(false);

const candidateFilterForm = ref({
    cabang_id: '',
    gender: '',
});

const appliedCandidateFilters = ref({
    cabang_id: '',
    gender: '',
});

const isCandidateFilterActive = computed(() => {
    return Boolean(
        appliedCandidateFilters.value.cabang_id ||
        appliedCandidateFilters.value.gender,
    );
});

const applyCandidateFilters = () => {
    appliedCandidateFilters.value = { ...candidateFilterForm.value };
    modalCandidateCurrentPage.value = 1;
    isCandidateFilterModalOpen.value = false;
};

const resetCandidateFilters = () => {
    candidateFilterForm.value = {
        cabang_id: '',
        gender: '',
    };
    appliedCandidateFilters.value = {
        cabang_id: '',
        gender: '',
    };
    modalCandidateCurrentPage.value = 1;
    isCandidateFilterModalOpen.value = false;
};

const clearSingleCandidateFilter = (
    key: 'cabang_id' | 'gender',
) => {
    candidateFilterForm.value[key] = '';
    appliedCandidateFilters.value[key] = '';
    modalCandidateCurrentPage.value = 1;
};

const getJenjangName = (id: string) => {
    const j = props.jenjangs?.find(
        (item: any) => String(item.id) === String(id),
    );

    return j ? j.name || j.singkatan || 'Jenjang' : 'Jenjang';
};

const getCabangName = (id: string) => {
    const c = props.cabangs?.find(
        (item: any) => String(item.id) === String(id),
    );

    return c?.name || 'Cabang';
};

const modalTempSelectedIds = ref<string[]>([]);
const modalCandidateCurrentPage = ref(1);
const modalCandidatePerPage = ref(5);

const handleOpenAddModal = () => {
    if (!isInformasiTagihanComplete.value) {
return;
}

    modalSearch.value = '';
    candidateFilterForm.value = {
        cabang_id: '',
        gender: '',
    };
    appliedCandidateFilters.value = {
        cabang_id: '',
        gender: '',
    };
    modalCandidateCurrentPage.value = 1;
    modalTempSelectedIds.value = [];
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    isCandidateFilterModalOpen.value = false;
    modalTempSelectedIds.value = [];
};

// Filtered pendaftar strictly status TAGIHAN & belum_dibuat & active jenjang, excluding already selected recipients
const filteredAvailablePendaftars = computed(() => {
    let list = props.availablePendaftars || [];

    // Filter to only status TAGIHAN, without tagihans in current cycle, and strictly matching active jenjang
    list = list.filter((p: any) => {
        const isBelumDibuat = !hasActiveTagihanForCycle(p);
        const isStatusTagihan = (p.status || '').toUpperCase() === 'TAGIHAN';
        const isSameJenjang =
            !currentJenjang.value ||
            String(p.jenjang_id || p.jenjang?.id) ===
                String(currentJenjang.value.id);

        return isBelumDibuat && isStatusTagihan && isSameJenjang;
    });

    // Exclude those already selected in selectedRecipients
    const existingIds = selectedRecipients.value.map((r) => r.id);
    list = list.filter((p: any) => !existingIds.includes(p.id));

    if (appliedCandidateFilters.value.cabang_id) {
        list = list.filter(
            (p: any) =>
                String(p.cabang_id || p.cabang?.id) ===
                String(appliedCandidateFilters.value.cabang_id),
        );
    }

    if (appliedCandidateFilters.value.gender) {
        const targetGender = appliedCandidateFilters.value.gender.toLowerCase();
        list = list.filter((p: any) => {
            const g = (
                p.personal_data?.jenis_kelamin ||
                p.gender ||
                ''
            ).toLowerCase();

            if (targetGender.includes('laki')) {
                return g.includes('laki') || g === 'l';
            }

            if (targetGender.includes('perempuan')) {
                return g.includes('perempuan') || g === 'p';
            }

            return g === targetGender;
        });
    }

    const q = (modalSearch.value || '').trim().toLowerCase();

    if (q) {
        list = list.filter(
            (p: any) =>
                (p.nama && p.nama.toLowerCase().includes(q)) ||
                (p.nik && String(p.nik).toLowerCase().includes(q)) ||
                (p.nomor_pendaftaran &&
                    String(p.nomor_pendaftaran).toLowerCase().includes(q)) ||
                (p.cabang?.name && p.cabang.name.toLowerCase().includes(q)) ||
                (p.jenjang?.nama && p.jenjang.nama.toLowerCase().includes(q)) ||
                (p.jenjang?.name && p.jenjang.name.toLowerCase().includes(q)),
        );
    }

    return list;
});

const modalCandidateTotalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(
            filteredAvailablePendaftars.value.length /
                modalCandidatePerPage.value,
        ),
    );
});

const paginatedModalCandidates = computed(() => {
    const start =
        (modalCandidateCurrentPage.value - 1) * modalCandidatePerPage.value;

    return filteredAvailablePendaftars.value.slice(
        start,
        start + modalCandidatePerPage.value,
    );
});

const modalCandidatePagination = computed(() => {
    const total = filteredAvailablePendaftars.value.length;

    if (total === 0) {
        return { from: 0, to: 0, total: 0 };
    }

    const from =
        (modalCandidateCurrentPage.value - 1) * modalCandidatePerPage.value + 1;
    const to = Math.min(
        modalCandidateCurrentPage.value * modalCandidatePerPage.value,
        total,
    );

    return { from, to, total };
});

watch(
    () => [
        modalSearch.value,
        appliedCandidateFilters.value.jenjang_id,
        appliedCandidateFilters.value.cabang_id,
        appliedCandidateFilters.value.gender,
    ],
    () => {
        modalCandidateCurrentPage.value = 1;
    },
);

const isAllCandidatePageSelected = computed(() => {
    if (paginatedModalCandidates.value.length === 0) {
return false;
}

    return paginatedModalCandidates.value.every((p: any) =>
        modalTempSelectedIds.value.includes(p.id),
    );
});

const toggleSelectAllCandidatePage = (checked: boolean) => {
    if (checked) {
        const toAdd = paginatedModalCandidates.value
            .map((p: any) => p.id)
            .filter((id) => !modalTempSelectedIds.value.includes(id));
        modalTempSelectedIds.value.push(...toAdd);
    } else {
        const pageIds = paginatedModalCandidates.value.map((p: any) => p.id);
        modalTempSelectedIds.value = modalTempSelectedIds.value.filter(
            (id) => !pageIds.includes(id),
        );
    }
};

const toggleModalPendaftar = (id: string, checked?: boolean) => {
    const shouldSelect =
        typeof checked === 'boolean'
            ? checked
            : !modalTempSelectedIds.value.includes(id);

    if (shouldSelect) {
        if (!modalTempSelectedIds.value.includes(id)) {
            modalTempSelectedIds.value.push(id);
        }
    } else {
        modalTempSelectedIds.value = modalTempSelectedIds.value.filter(
            (pId) => pId !== id,
        );
    }
};

const confirmAddRecipients = () => {
    const candidatesToAdd = (props.availablePendaftars || []).filter((p: any) =>
        modalTempSelectedIds.value.includes(p.id),
    );

    selectedRecipients.value.push(...candidatesToAdd);
    closeAddModal();
};

const removeRecipient = (id: string) => {
    selectedRecipients.value = selectedRecipients.value.filter(
        (p) => p.id !== id,
    );
};

// Form Submission
const submitForm = useForm({
    pendaftar_ids: [] as string[],
    kategori_id: '',
    nama_tagihan: '',
    total_amount: 0,
    due_date: '',
    published_at: '',
    samaha_items: [] as any[],
});

const handleSubmit = () => {
    if (!isInformasiTagihanComplete.value) {
return;
}

    if (selectedRecipients.value.length === 0) {
return;
}

    submitForm.pendaftar_ids = selectedRecipients.value.map((p) => p.id);
    submitForm.kategori_id = formInfo.value.jenis_tagihan_id;
    submitForm.nama_tagihan = formInfo.value.nama_tagihan;
    submitForm.total_amount = formInfo.value.total_amount;
    submitForm.due_date = formInfo.value.due_date;
    submitForm.published_at = formInfo.value.published_at;
    submitForm.samaha_items = selectedRecipients.value.map((p) => ({
        pendaftar_id: p.id,
        amount: samahaItems.value[p.id]?.amount || 0,
        notes: '',
    }));

    submitForm.post(create_bill.url());
};
</script>

<template>
    <div class="relative min-h-screen w-full pb-16">
        <Head title="Tambah Tagihan Pendaftaran" />

        <!-- RIGHT DRAWER: JENJANG SELECTOR -->
        <Teleport to="body">
            <div
                v-if="isJenjangDrawerOpen"
                class="fixed inset-0 z-50 overflow-hidden"
            >
                <div
                    @click="isJenjangDrawerOpen = false"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300"
                ></div>

                <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div
                        class="w-screen max-w-md bg-white shadow-2xl transition-all duration-300 dark:bg-slate-900"
                    >
                        <!-- Drawer Header -->
                        <div
                            class="flex items-center justify-between border-b border-gray-100 bg-gray-50 p-5 dark:border-slate-800 dark:bg-slate-800/80"
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
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
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
                                        Pilih jenjang penerbitan tagihan
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="isJenjangDrawerOpen = false"
                                class="cursor-pointer rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
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
                                @click="switchJenjang(j.id)"
                                class="group flex w-full items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="[
                                    currentJenjang?.id === j.id
                                        ? 'border-primary bg-primary text-white shadow-lg ring-2 shadow-primary/25 ring-primary/30 dark:bg-blue-600'
                                        : 'border-gray-100 bg-white hover:border-primary/40 hover:bg-primary/5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/50',
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <img
                                        :src="getJenjangLogo(j.code)"
                                        :alt="j.name"
                                        class="h-9 w-9 shrink-0 object-contain"
                                    />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-black tracking-wide"
                                                :class="
                                                    currentJenjang?.id === j.id
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
                                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px] font-black leading-none tracking-tight shadow-xs"
                                                :class="
                                                    currentJenjang?.id === j.id
                                                        ? 'bg-white text-primary dark:bg-white dark:text-blue-600'
                                                        : 'bg-rose-500 text-white'
                                                "
                                            >
                                                {{
                                                    formatBadgeCount(
                                                        props.jenjangCounts?.[
                                                            j.id
                                                        ]?.belum_dibuat ?? 0,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <span
                                            class="mt-0.5 text-xs font-bold"
                                            :class="
                                                currentJenjang?.id === j.id
                                                    ? 'text-white/90'
                                                    : 'text-gray-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ j.name }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[11px]"
                                            :class="
                                                currentJenjang?.id === j.id
                                                    ? 'text-white/75'
                                                    : 'text-gray-400 dark:text-slate-400'
                                            "
                                        >
                                            {{ getJenjangScopeText(j.code) }}
                                        </span>
                                    </div>
                                </div>
                                <svg
                                    class="h-5 w-5 shrink-0 transition-transform group-hover:translate-x-1"
                                    :class="
                                        currentJenjang?.id === j.id
                                            ? 'text-white'
                                            : 'text-gray-400 dark:text-slate-400'
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

                        <div
                            class="border-t border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800/50"
                        >
                            <span
                                class="text-[11px] font-medium text-gray-400 dark:text-slate-500"
                            >
                                Urutan Jenjang: MTs, MA, S1, S2, S3
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Header Page & Back Button (Preserves Jenjang Tab on click) -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <h1
                        class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                    >
                        Form Penerbitan Tagihan {{ props.tagihanType === 'interview' ? 'Interview Ulang' : 'Pendaftaran' }}
                    </h1>
                    <span
                        v-if="props.tagihanType === 'interview'"
                        class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300 uppercase"
                    >
                        Khusus Interview Ulang
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-black text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300 uppercase"
                    >
                        Pendaftaran Baru
                    </span>
                </div>
                <p
                    class="mt-1 text-xs text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    {{
                        props.tagihanType === 'interview'
                            ? 'Terbitkan rincian tagihan biaya tes seleksi & interview ulang bagi calon santri yang dijadwalkan wawancara ulang'
                            : 'Terbitkan rincian tagihan biaya pendaftaran & formulir masuk untuk calon santri per jenjang pendidikan'
                    }}
                </p>
            </div>
            <!-- Back Button on far top-right end (Preserving Tab) -->
            <div class="flex shrink-0 items-center justify-end">
                <BackButton :href="backUrl">Kembali</BackButton>
            </div>
        </div>

        <!-- CARD JENJANG (FULL DETAILED CARD FROM DATABASE) -->
        <div
            class="relative mb-6 flex flex-col gap-5 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-4 sm:gap-5">
                <img
                    :src="getJenjangLogo(currentJenjang?.code)"
                    :alt="currentJenjang?.name"
                    @error="handleLogoError"
                    class="h-12 w-auto max-w-[70px] shrink-0 object-contain sm:h-16 sm:max-w-[80px]"
                />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-xs font-black tracking-widest text-primary uppercase dark:text-blue-400"
                        >
                            KODE: {{ currentJenjang?.code || 'MTS' }}
                        </span>
                        <span
                            class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Jenjang Pendidikan
                        </span>
                    </div>
                    <h2
                        class="mt-1 text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                    >
                        {{ currentJenjang?.name || 'Madrasah Tsanawiyah' }}
                    </h2>
                    <p
                        class="mt-0.5 text-xs font-medium text-gray-500 dark:text-slate-400"
                    >
                        {{ getJenjangScopeText(currentJenjang?.code) }}
                    </p>
                </div>
            </div>

            <!-- Badge Penerima Santri & Ganti Jenjang Docked Button -->
            <div
                class="flex items-center gap-3 border-t border-gray-100 pt-3 sm:border-t-0 sm:pt-0 dark:border-slate-800"
            >
                <div
                    class="flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3.5 py-2 dark:border-emerald-900/50 dark:bg-emerald-950/40"
                >
                    <span
                        class="text-xs font-extrabold text-emerald-800 dark:text-emerald-300"
                        >Penerima:</span
                    >
                    <span
                        class="font-mono text-sm font-black text-emerald-700 dark:text-emerald-400"
                    >
                        {{ selectedRecipients.length }} Santri
                    </span>
                </div>

                <!-- Floating Docked Jenjang Switch Button -->
                <button
                    type="button"
                    @click="isJenjangDrawerOpen = true"
                    class="group -mr-5 -my-5 flex shrink-0 cursor-pointer items-center gap-2.5 rounded-l-2xl bg-[#1a2e4a] px-3.5 py-3 text-white shadow-xl shadow-slate-900/20 transition-all duration-300 hover:bg-[#15253d] hover:pr-5 focus:outline-none sm:-mr-6 sm:-my-6 dark:bg-slate-800 dark:hover:bg-slate-700"
                    title="Klik untuk memilih / mengganti Jenjang Pendidikan"
                >
                    <img
                        :src="getJenjangLogo(currentJenjang?.code)"
                        :alt="currentJenjang?.name"
                        class="h-7 w-auto shrink-0 object-contain"
                    />
                    <div class="flex flex-col text-left">
                        <span
                            class="text-[9px] leading-none font-black tracking-wider text-slate-300 uppercase"
                            >JENJANG</span
                        >
                        <span
                            class="mt-1 text-xs leading-none font-black text-white uppercase"
                            >{{ currentJenjang?.code || 'MTS' }}</span
                        >
                    </div>
                </button>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- CARD 1: INFORMASI TAGIHAN (PROFESSIONAL BILLING INTERFACE) -->
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Card Header -->
                <div
                    class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3.5">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            <svg
                                class="h-5.5 w-5.5"
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
                            <h2
                                class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                            >
                                Informasi Tagihan
                            </h2>
                            <p
                                class="text-xs font-medium text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    props.tagihanType === 'interview'
                                        ? 'Pilih kategori biaya interview untuk mengisikan komponen rincian & nominal biaya otomatis'
                                        : 'Pilih kategori biaya pendaftaran untuk mengisikan komponen rincian & nominal biaya otomatis'
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="hidden items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 sm:flex dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <span>Jenjang:</span>
                        <strong
                            class="font-black text-primary dark:text-blue-400"
                            >{{ currentJenjang?.code || 'MTS' }}</strong
                        >
                    </div>
                </div>

                <!-- 1. KATEGORI TAGIHAN SELECTION CARDS (AUTO-ADAPTIVE & RESPONSIVE GRID) -->
                <div class="mb-6">
                    <div class="mb-2.5 flex items-center justify-between">
                        <label
                            class="block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                        >
                            KATEGORI BIAYA {{ props.tagihanType === 'interview' ? 'INTERVIEW ULANG' : 'PENDAFTARAN' }}
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            v-if="(props.kategoriBiayas || []).length > 0"
                            class="text-[11px] font-bold text-slate-400 dark:text-slate-500"
                        >
                            {{ props.kategoriBiayas?.length }} Kategori Tersedia
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4"
                        :class="[
                            (props.kategoriBiayas || []).length > 2
                                ? 'sm:grid-cols-2 lg:grid-cols-3'
                                : 'sm:grid-cols-2',
                            (props.kategoriBiayas || []).length > 6
                                ? 'max-h-[420px] overflow-y-auto pr-1'
                                : '',
                        ]"
                    >
                        <button
                            v-for="kat in props.kategoriBiayas || []"
                            :key="kat.id"
                            type="button"
                            @click="selectCategory(kat)"
                            class="group relative flex h-full cursor-pointer flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200 focus:outline-none"
                            :class="
                                formInfo.jenis_tagihan_id === kat.id
                                    ? 'border-primary bg-primary/5 shadow-sm ring-2 ring-primary/20 dark:border-blue-500 dark:bg-blue-950/30 dark:ring-blue-500/20'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800'
                            "
                        >
                            <!-- Top: Header with Icon, Name & Radio Check -->
                            <div
                                class="flex w-full items-start justify-between gap-3"
                            >
                                <div class="flex min-w-0 items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors"
                                        :class="
                                            formInfo.jenis_tagihan_id === kat.id
                                                ? 'bg-primary text-white shadow-xs dark:bg-blue-600'
                                                : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-slate-700'
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
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4
                                                class="line-clamp-2 text-xs font-black tracking-tight transition-colors"
                                                :class="
                                                    formInfo.jenis_tagihan_id ===
                                                    kat.id
                                                        ? 'text-primary dark:text-blue-400'
                                                        : 'text-slate-900 dark:text-slate-100'
                                                "
                                                :title="kat.name"
                                            >
                                                {{ kat.name }}
                                            </h4>
                                            <span
                                                v-if="kat.jenis === 'interview'"
                                                class="inline-flex shrink-0 items-center rounded-md border border-indigo-200 bg-indigo-50 px-1.5 py-0.2 text-[9px] font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300 uppercase"
                                            >
                                                Interview
                                            </span>
                                            <span
                                                v-else-if="kat.jenis === 'pendaftaran'"
                                                class="inline-flex shrink-0 items-center rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.2 text-[9px] font-black text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300 uppercase"
                                            >
                                                Pendaftaran
                                            </span>
                                        </div>
                                        <p
                                            class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400"
                                        >
                                            {{ kat.items?.length || 0 }}
                                            Komponen Biaya
                                        </p>
                                    </div>
                                </div>

                                <!-- Radio Circle Indicator -->
                                <div
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition-all"
                                    :class="
                                        formInfo.jenis_tagihan_id === kat.id
                                            ? 'bg-primary text-white ring-2 ring-primary/30 dark:bg-blue-600'
                                            : 'border border-slate-300 bg-white group-hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800'
                                    "
                                >
                                    <svg
                                        v-if="
                                            formInfo.jenis_tagihan_id === kat.id
                                        "
                                        class="h-3 w-3"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Bottom: Nominal Total Price -->
                            <div
                                class="mt-3.5 flex w-full items-center justify-between border-t border-slate-100/80 pt-2.5 dark:border-slate-800"
                            >
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase dark:text-slate-500"
                                    >Total Biaya:</span
                                >
                                <span
                                    class="font-mono text-sm font-black transition-colors"
                                    :class="
                                        formInfo.jenis_tagihan_id === kat.id
                                            ? 'text-primary dark:text-blue-400'
                                            : 'text-slate-800 dark:text-slate-200'
                                    "
                                >
                                    {{ formatRupiah(kat.total_biaya) }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. FORM PARAMETER PENERBITAN INVOICE (BALANCED 3-COLUMN GRID) -->
                <div
                    class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/50 p-4.5 sm:p-5 dark:border-slate-700/90 dark:border-slate-800 dark:bg-slate-800"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-wider text-slate-700 uppercase dark:text-slate-300"
                    >
                        Parameter Penerbitan Tagihan
                    </h3>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <!-- NAMA TAGIHAN (Spans 2 cols on lg) -->
                        <div class="lg:col-span-2">
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                NAMA TAGIHAN
                                <span class="text-rose-500">*</span>
                            </label>
                            <TextInput
                                type="text"
                                v-model="formInfo.nama_tagihan"
                                placeholder="Aa Contoh: Biaya Pendaftaran & Formulir"
                                class="w-full"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                Nama yang akan tercetak pada rincian invoice /
                                tagihan santri
                            </p>
                        </div>

                        <!-- NOMINAL TAGIHAN (Col 3) -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                NOMINAL TAGIHAN (RP)
                                <span class="text-rose-500">*</span>
                            </label>
                            <CurrencyInput
                                v-model="formInfo.total_amount"
                                placeholder="Rp 0"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                Otomatis terisi dari total komponen biaya
                            </p>
                        </div>

                        <!-- TANGGAL TERBIT -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                TANGGAL TERBIT
                                <span class="text-rose-500">*</span>
                            </label>
                            <CustomDatePicker v-model="formInfo.published_at" />
                        </div>

                        <!-- TANGGAL JATUH TEMPO -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                TANGGAL JATUH TEMPO
                                <span class="text-rose-500">*</span>
                            </label>
                            <CustomDatePicker v-model="formInfo.due_date" />
                        </div>

                        <!-- STATUS ESTIMASI TENGGAT WAKTU (MATCHING EXACT INPUT HEIGHT & LABEL) -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                MASA TENGGAT
                            </label>
                            <div
                                class="flex h-[42px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-xs text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                <svg
                                    class="h-4 w-4 shrink-0 text-primary dark:text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span
                                    >Masa berlaku:
                                    <strong
                                        class="font-mono font-bold text-primary dark:text-blue-400"
                                        >{{ masaTenggatHari }} Hari</strong
                                    ></span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. RINCIAN KOMPONEN BIAYA (FINANCIAL DATA TABLE VIEW) -->
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700/90 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/80 px-5 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-2.5">
                            <svg
                                class="h-4.5 w-4.5 text-primary dark:text-blue-400"
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
                            <span
                                class="text-xs font-black tracking-wider text-slate-800 uppercase dark:text-slate-100 dark:text-slate-200"
                            >
                                Rincian Komponen Biaya:
                                {{ selectedKategori?.name || 'Tagihan' }}
                            </span>
                            <span
                                class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                            >
                                {{ selectedKategori?.items?.length || 0 }}
                                Komponen
                            </span>
                        </div>
                        <span
                            class="text-[11px] font-medium text-slate-500 dark:text-slate-400"
                        >
                            Standar Master Keuangan
                            {{
                                currentJenjang?.code
                                    ? `Jenjang ${currentJenjang.code}`
                                    : ''
                            }}
                        </span>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="border-b border-slate-100 bg-slate-50/40 text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400"
                            >
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-12 px-4 py-3 text-center"
                                    >
                                        NO
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        NAMA KOMPONEN BIAYA
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-4 py-3 text-right"
                                    >
                                        NOMINAL (RP)
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900"
                            >
                                <tr
                                    v-for="(
                                        item, idx
                                    ) in selectedKategori?.items || []"
                                    :key="item.id"
                                    class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800"
                                >
                                    <td
                                        class="px-4 py-3.5 text-center font-bold text-slate-400 dark:text-slate-500"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="px-4 py-3.5 font-semibold text-slate-800 dark:text-slate-100 dark:text-slate-200"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100"
                                    >
                                        {{ formatRupiah(item.nominal) }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !selectedKategori?.items ||
                                        selectedKategori.items.length === 0
                                    "
                                >
                                    <td
                                        colspan="3"
                                        class="py-6 text-center text-xs font-medium text-slate-400 dark:text-slate-500"
                                    >
                                        Tidak ada rincian komponen untuk
                                        kategori ini.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot
                                class="border-t-2 border-slate-200 bg-slate-50/90 font-bold dark:border-slate-700 dark:bg-slate-800"
                            >
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-4 py-3.5 text-right text-xs tracking-wider text-slate-600 uppercase dark:text-slate-300"
                                    >
                                        Total Biaya Komponen:
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-right font-mono text-sm font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{
                                            formatRupiah(
                                                selectedKategori?.total_biaya ||
                                                    formInfo.total_amount,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CARD 2: DAFTAR PENERIMA TAGIHAN (Clean Table matching Virtual Account Import style) -->
            <div
                class="overflow-hidden rounded-4xl border border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Header Card -->
                <div
                    class="flex flex-col gap-3 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between md:p-7 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3.5">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-blue-100 bg-blue-50 text-blue-600 shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-400"
                        >
                            <svg
                                class="h-5.5 w-5.5"
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
                            <h2
                                class="text-base font-bold text-gray-900 sm:text-lg dark:text-slate-100"
                            >
                                Daftar Penerima Tagihan
                            </h2>
                            <p
                                class="text-xs font-medium text-gray-500 dark:text-slate-400"
                            >
                                {{ selectedRecipients.length }} pendaftar
                                terpilih
                            </p>
                        </div>
                    </div>

                    <!-- Button Tambahkan Pendaftar (Standard Size) -->
                    <PrimaryButton
                        type="button"
                        @click="handleOpenAddModal"
                        class="inline-flex items-center gap-2"
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
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                            />
                        </svg>
                        <span>Tambahkan Pendaftar</span>
                    </PrimaryButton>
                </div>

                <!-- TABLE CONTENT WITH STATES (Matching /admin/keuangan/va/import table styling) -->
                <div class="relative min-h-[220px] overflow-hidden">
                    <!-- STATE 3: Pendaftar Data List -->
                    <div
                        v-if="
                            isInformasiTagihanComplete &&
                            selectedRecipients.length > 0
                        "
                        class="overflow-x-auto"
                    >
                        <table
                            class="w-full min-w-max divide-y divide-gray-200 text-left text-xs dark:divide-slate-800"
                        >
                            <thead
                                class="border-b border-gray-100 bg-gray-50 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/75"
                            >
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-14 px-4 py-3.5 text-center text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >
                                        NO
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-64 px-4 py-3.5 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >
                                        PROFIL PENDAFTAR
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-44 px-4 py-3.5 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >
                                        JENJANG & CABANG
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-56 px-4 py-3.5 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >
                                        POTONGAN SAMAHA / DISKON
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-20 px-4 py-3.5 text-center text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >
                                        AKSI
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900"
                            >
                                <tr
                                    v-for="(p, idx) in selectedRecipients"
                                    :key="p.id"
                                    class="transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <!-- NO -->
                                    <td
                                        class="px-4 py-4 text-center align-middle font-bold text-gray-400 dark:text-slate-500"
                                    >
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- PROFIL PENDAFTAR -->
                                    <td class="px-4 py-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <img
                                                v-if="getPendaftarPhoto(p) && !imageErrorMap[p.id]"
                                                :src="getPendaftarPhoto(p)!"
                                                @error="imageErrorMap[p.id] = true"
                                                class="h-10 w-10 shrink-0 rounded-full border border-gray-100 object-cover shadow-xs dark:border-slate-700 dark:border-slate-800"
                                            />
                                            <div
                                                v-else
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-xs font-bold text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
                                            >
                                                {{ getInitials(p.nama) }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4
                                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                                    >
                                                        {{ p.nama }}
                                                    </h4>
                                                    <span
                                                        v-if="p.is_interview_ulang"
                                                        class="inline-flex shrink-0 items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-1.5 py-0.5 text-[9px] font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300"
                                                        title="Calon santri mengikuti sesi interview ulang"
                                                    >
                                                        <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Interview Ulang
                                                    </span>
                                                </div>
                                                <p
                                                    class="mt-0.5 font-mono text-xs text-gray-400 dark:text-slate-500"
                                                >
                                                    {{
                                                        p.nomor_pendaftaran ||
                                                        '-'
                                                    }}
                                                    <span v-if="p.nik"
                                                        >| NIK:
                                                        {{ p.nik }}</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- JENJANG & CABANG -->
                                    <td class="px-4 py-4 align-middle">
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-slate-200"
                                                >{{
                                                    p.jenjang?.name ||
                                                    currentJenjang?.name ||
                                                    '-'
                                                }}</span
                                            >
                                            <span
                                                class="text-xs text-gray-500 dark:text-slate-400"
                                                >{{
                                                    p.cabang?.name || '-'
                                                }}</span
                                            >
                                        </div>
                                    </td>

                                    <!-- POTONGAN SAMAHA / DISKON (Consistent Standard CurrencyInput) -->
                                    <td class="px-4 py-4 align-middle">
                                        <div class="max-w-[200px]">
                                            <CurrencyInput
                                                v-model="
                                                    samahaItems[p.id].amount
                                                "
                                                placeholder="Rp 0"
                                            />
                                        </div>
                                    </td>

                                    <!-- AKSI -->
                                    <td
                                        class="px-4 py-4 text-center align-middle"
                                    >
                                        <button
                                            type="button"
                                            @click="removeRecipient(p.id)"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-rose-100 bg-rose-50 p-2.5 text-rose-600 transition-colors hover:bg-rose-100 hover:text-rose-700 focus:outline-none dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-900/50"
                                            title="Hapus dari daftar penerima"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- STATE 1: Form Belum Lengkap Overlay Card -->
                    <div
                        v-if="!isInformasiTagihanComplete"
                        class="my-10 flex flex-col items-center justify-center px-4 py-8 text-center"
                    >
                        <div
                            class="flex w-full max-w-md flex-col items-center rounded-3xl border border-amber-100 bg-white p-8 shadow-xl shadow-amber-500/5 dark:border-amber-900/40 dark:bg-slate-900"
                        >
                            <div
                                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-500 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-400"
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
                                        stroke-width="2.5"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="text-base font-bold text-gray-900 dark:text-slate-100"
                            >
                                Form Belum Lengkap
                            </h3>
                            <p
                                class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-slate-400"
                            >
                                Silakan lengkapi Informasi Tagihan di atas
                                terlebih dahulu untuk menambahkan pendaftar.
                            </p>
                        </div>
                    </div>

                    <!-- STATE 2: Belum Ada Pendaftar -->
                    <div
                        v-else-if="selectedRecipients.length === 0"
                        class="my-10 flex flex-col items-center justify-center px-4 py-8 text-center"
                    >
                        <div
                            class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800 dark:bg-slate-800/80 dark:text-slate-500 dark:text-slate-600"
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
                                    stroke-width="1.8"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-sm font-semibold text-gray-500 dark:text-slate-400"
                        >
                            Belum ada pendaftar yang ditambahkan.
                        </p>
                        <p
                            class="mt-1 text-xs text-gray-400 dark:text-slate-500"
                        >
                            Klik tombol "Tambahkan Pendaftar" di pojok kanan
                            atas untuk memilih santri penerima tagihan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS (MATCHING /admin/pengaturan/pegawai/Form.vue STYLE) -->
            <div class="flex items-center justify-end gap-3 pt-4 pb-12">
                <SecondaryButton
                    type="button"
                    @click="router.get(backUrl)"
                    :disabled="submitForm.processing"
                >
                    Batal
                </SecondaryButton>

                <PrimaryButton
                    type="submit"
                    :disabled="
                        submitForm.processing ||
                        !isInformasiTagihanComplete ||
                        selectedRecipients.length === 0
                    "
                    :class="{ 'opacity-25': submitForm.processing }"
                >
                    <svg
                        v-if="submitForm.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>Terbitkan Tagihan</span>
                </PrimaryButton>
            </div>
        </form>

        <!-- MODAL: PILIH PENDAFTAR PENERIMA TAGIHAN -->
        <Modal
            :show="isAddModalOpen"
            @close="closeAddModal"
            maxWidth="2xl"
            title="Pilih Pendaftar Penerima Tagihan"
            :description="`Pilih calon santri ${currentJenjang?.name ? `jenjang ${currentJenjang.name}` : ''} untuk diterbitkan tagihan`"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50"
                >
                    <svg
                        class="h-6 w-6 text-blue-600 dark:text-blue-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                </div>
            </template>
            <div class="space-y-4">
                <!-- Calon Santri DataTable -->
                <div
                    class="flex flex-col rounded-2xl border border-gray-100 bg-white shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900"
                >
                    <!-- Header Action Bar (Search & Filter) -->
                    <div
                        class="relative z-20 flex flex-col gap-3 rounded-t-2xl border-b border-gray-100 bg-white p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="flex w-full items-center justify-between gap-3"
                        >
                            <!-- Search Input (Kiri) -->
                            <div class="group relative flex-1 sm:max-w-xs">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-colors group-focus-within:text-primary dark:text-slate-500 dark:group-focus-within:text-blue-400"
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
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="modalSearch"
                                    placeholder="Cari data..."
                                    class="block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 py-2 pr-3 pl-8.5 text-xs font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20"
                                />
                            </div>

                            <!-- Filter Trigger Button (Kanan) -->
                            <button
                                type="button"
                                @click="isCandidateFilterModalOpen = true"
                                class="group inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 shadow-2xs transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                :class="
                                    isCandidateFilterActive
                                        ? 'border-primary/40 bg-primary/5 text-primary dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-400'
                                        : ''
                                "
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500"
                                    :class="
                                        isCandidateFilterActive
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
                                <span>Filter</span>
                                <span
                                    v-if="isCandidateFilterActive"
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary dark:bg-blue-400"
                                ></span>
                            </button>
                        </div>
                    </div>

                    <!-- Table with horizontal scroll & spacious columns -->
                    <div class="relative overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-xs">
                            <thead
                                class="sticky top-0 z-10 border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="w-10 px-4 py-3">
                                        <div class="flex items-center">
                                            <Checkbox
                                                :checked="
                                                    isAllCandidatePageSelected
                                                "
                                                @update:checked="
                                                    toggleSelectAllCandidatePage
                                                "
                                            />
                                        </div>
                                    </th>
                                    <th
                                        class="min-w-[240px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        PENDAFTAR
                                    </th>
                                    <th
                                        class="min-w-[140px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        CABANG
                                    </th>
                                    <th
                                        class="min-w-[160px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        JENJANG
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 bg-white font-medium text-slate-700 dark:divide-slate-800 dark:bg-slate-900 dark:text-slate-300"
                            >
                                <tr
                                    v-for="p in paginatedModalCandidates"
                                    :key="p.id"
                                    @click="toggleModalPendaftar(p.id)"
                                    class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
                                    :class="
                                        modalTempSelectedIds.includes(p.id)
                                            ? 'bg-blue-50/50 dark:bg-blue-950/20'
                                            : ''
                                    "
                                >
                                    <!-- Checkbox -->
                                    <td
                                        class="w-10 px-4 py-3 text-center"
                                        @click.stop
                                    >
                                        <div class="flex items-center">
                                            <Checkbox
                                                :checked="
                                                    modalTempSelectedIds.includes(
                                                        p.id,
                                                    )
                                                "
                                                @update:checked="
                                                    (val) =>
                                                        toggleModalPendaftar(
                                                            p.id,
                                                            val,
                                                        )
                                                "
                                            />
                                        </div>
                                    </td>

                                    <!-- Profil Santri -->
                                    <td class="px-4 py-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p
                                                    class="text-xs font-bold text-gray-900 dark:text-slate-100"
                                                >
                                                    {{ p.nama }}
                                                </p>
                                                <span
                                                    v-if="p.is_interview_ulang"
                                                    class="inline-flex shrink-0 items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-1 py-0.2 text-[8.5px] font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300"
                                                    title="Calon santri mengikuti sesi interview ulang"
                                                >
                                                    <svg class="h-2 w-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Interview Ulang
                                                </span>
                                            </div>
                                            <p
                                                class="font-mono text-[11px] text-gray-400 dark:text-slate-500"
                                            >
                                                No:
                                                {{
                                                    p.nomor_pendaftaran ||
                                                    '-'
                                                }}
                                            </p>
                                            <p
                                                v-if="p.nik"
                                                class="font-mono text-[11px] text-gray-400 dark:text-slate-500"
                                            >
                                                NIK: {{ p.nik }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Cabang -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{
                                                p.cabang?.name ||
                                                p.personal_data
                                                    ?.cabang_pendaftaran ||
                                                '-'
                                            }}
                                        </span>
                                    </td>

                                    <!-- Jenjang -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <img
                                                :src="getJenjangLogo(p.jenjang)"
                                                :alt="
                                                    p.jenjang?.name ||
                                                    p.jenjang?.nama
                                                "
                                                class="h-6 w-6 shrink-0 object-contain"
                                                @error="handleLogoError"
                                            />
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-xs font-bold text-slate-800 dark:text-slate-100"
                                                >
                                                    {{
                                                        p.jenjang?.name ||
                                                        p.jenjang?.nama ||
                                                        '-'
                                                    }}
                                                </span>
                                                <span
                                                    v-if="
                                                        getEducationSubText(p)
                                                    "
                                                    class="max-w-[160px] truncate text-[11px] text-slate-400 dark:text-slate-500"
                                                    :title="
                                                        getEducationSubText(p)
                                                    "
                                                >
                                                    {{ getEducationSubText(p) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        filteredAvailablePendaftars.length === 0
                                    "
                                >
                                    <td
                                        colspan="4"
                                        class="py-8 text-center text-xs font-medium text-gray-400 dark:text-slate-500"
                                    >
                                        Tidak ada calon santri tersedia untuk
                                        dipilih.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer for Candidates -->
                    <div
                        v-if="filteredAvailablePendaftars.length > 0"
                        class="mt-auto flex flex-col items-center justify-between gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-3 sm:flex-row sm:p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                    >
                        <!-- Info -->
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            Menampilkan
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalCandidatePagination.from }}</span
                            >
                            sampai
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalCandidatePagination.to }}</span
                            >
                            dari
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalCandidatePagination.total }}</span
                            >
                            entri
                        </div>

                        <!-- Page Buttons -->
                        <div class="flex items-center gap-1">
                            <!-- Prev -->
                            <button
                                type="button"
                                :disabled="modalCandidateCurrentPage <= 1"
                                @click="modalCandidateCurrentPage--"
                                class="flex min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                &laquo;
                            </button>

                            <!-- Page Numbers -->
                            <button
                                v-for="p in modalCandidateTotalPages"
                                :key="'cand-page-' + p"
                                type="button"
                                @click="modalCandidateCurrentPage = p"
                                class="flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors"
                                :class="
                                    modalCandidateCurrentPage === p
                                        ? 'border-primary bg-primary text-white shadow-2xs dark:border-blue-600 dark:bg-blue-600'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                                "
                            >
                                {{ p }}
                            </button>

                            <!-- Next -->
                            <button
                                type="button"
                                :disabled="
                                    modalCandidateCurrentPage >=
                                    modalCandidateTotalPages
                                "
                                @click="modalCandidateCurrentPage++"
                                class="flex min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                &raquo;
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <SecondaryButton
                    type="button"
                    @click="closeAddModal"
                    class="w-full justify-center sm:w-auto"
                >
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    @click="confirmAddRecipients"
                    :disabled="modalTempSelectedIds.length === 0"
                    class="w-full justify-center sm:w-auto"
                >
                    Tambahkan
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL FILTER CALON SANTRI -->
        <FilterModal
            :show="isCandidateFilterModalOpen"
            title="Filter Calon Santri"
            description="Saring daftar calon santri berdasarkan kriteria pendaftaran"
            zIndexClass="z-[130]"
            @close="isCandidateFilterModalOpen = false"
            @reset="resetCandidateFilters"
            @apply="applyCandidateFilters"
        >
            <!-- 1. Cabang Pendaftaran -->
            <div>
                <label
                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                >
                    Cabang Pendaftaran
                </label>
                <CustomSelect
                    v-model="candidateFilterForm.cabang_id"
                    :options="
                        (props.cabangs || []).map((c: any) => ({
                            value: c.id,
                            label: c.name,
                        }))
                    "
                    placeholder="Semua Cabang"
                />
            </div>

            <!-- 3. Jenis Kelamin -->
            <div>
                <label
                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                >
                    Jenis Kelamin
                </label>
                <CustomSelect
                    v-model="candidateFilterForm.gender"
                    :options="[
                        { value: 'Laki-Laki', label: 'Laki-Laki' },
                        { value: 'Perempuan', label: 'Perempuan' },
                    ]"
                    placeholder="Semua Jenis Kelamin"
                />
            </div>
        </FilterModal>
    </div>
</template>
