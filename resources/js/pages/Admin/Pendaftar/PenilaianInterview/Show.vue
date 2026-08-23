<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index } from '@/routes/admin/pendaftar/penilaian_interview';
import { show as showWawancara } from '@/routes/admin/pendaftar/penilaian_interview/wawancara';
import PenentuanKelasModal from './Partials/PenentuanKelasModal.vue';

defineOptions({ layout: AdminLayout });

interface UserItem {
    id: string;
    name: string;
    email?: string;
    nik?: string;
    nip?: string;
    foto?: string;
    roles?: Array<{ id: string; name: string }>;
}

interface JenjangItem {
    id: string;
    code?: string;
    name: string;
    singkatan?: string;
    logo_path?: string;
}

interface CabangItem {
    id: string;
    name: string;
}

const props = defineProps<{
    kelompok: {
        id: string;
        nama_kelompok: string;
        tanggal_ujian: string;
        waktu_mulai: string;
        waktu_selesai: string;
        lokasi: string;
        status: string;
        created_at?: string;
    };
    timUjian: {
        pewawancara: UserItem[];
        tes_membaca: UserItem[];
        tes_menulis: UserItem[];
        tes_hafalan: UserItem[];
        koordinator?: UserItem[];
        pengawas?: UserItem[];
    };
    metrics: {
        total_santri: number;
        laki_count: number;
        perempuan_count: number;
        dinilai_count: number;
        locked_count: number;
        belum_dinilai_count: number;
    };
    pendaftars: any[];
    jenjangs?: JenjangItem[];
    cabangs?: CabangItem[];
    kategoriPenilaians?: any[];
}>();

const backUrl = index.url();

const isGroupLocked = computed(() => {
    const status = (props.kelompok.status || '').toLowerCase();
    return (
        status === 'completed' ||
        (props.pendaftars && props.pendaftars.length > 0 && props.pendaftars.every((p: any) => Boolean((p.hasil_ujian || p.hasilUjian)?.locked_at)))
    );
});

const isSantriLocked = (row: any): boolean => {
    const h = row.hasil_ujian || row.hasilUjian;
    return Boolean(h?.locked_at) || isGroupLocked.value;
};

const isEvaluationComplete = (row: any): boolean => {
    const h = row.hasil_ujian || row.hasilUjian;
    if (!h) {
        return false;
    }

    const hasWawancara = Boolean(h.hasil_wawancara && String(h.hasil_wawancara).trim() !== '');
    const hasBaca = Number(h.nilai_baca_kitab || 0) > 0 || Boolean(h.predikat_baca_kitab);
    const hasTulis = Number(h.nilai_menulis || 0) > 0 || Boolean(h.predikat_menulis);
    const hasHafalan = Number(h.nilai_hafalan || 0) > 0 || Boolean(h.predikat_hafalan);

    return hasWawancara && hasBaca && hasTulis && hasHafalan;
};

const getKelulusanStatus = (row: any): string => {
    const h = row.hasil_ujian || row.hasilUjian;
    if (!h) {
        return '';
    }
    const status = h.status_kelulusan?.value || h.status_kelulusan || '';
    return typeof status === 'string' ? status.toLowerCase() : '';
};

const isSantriReadyToLock = (row: any): boolean => {
    const status = getKelulusanStatus(row);
    return isEvaluationComplete(row) && (status === 'lulus' || status === 'tidak_lulus');
};

const lockedSantriCount = computed(() => {
    if (!props.pendaftars) {
        return 0;
    }
    return props.pendaftars.filter((p: any) => Boolean((p.hasil_ujian || p.hasilUjian)?.locked_at)).length;
});

const canEdit = computed(() => {
    return !isGroupLocked.value;
});

const isPenentuanKelasModalOpen = ref(false);
const selectedPendaftar = ref<any>(null);

const isLockModalOpen = ref(false);
const isLocking = ref(false);

const isSingleLockModalOpen = ref(false);
const isSingleLocking = ref(false);
const isSingleUnlockModalOpen = ref(false);
const isSingleUnlocking = ref(false);
const targetPendaftarForLock = ref<any>(null);

const readyToLockCount = computed(() => {
    if (!props.pendaftars) {
        return 0;
    }
    return props.pendaftars.filter((p: any) => {
        const status = getKelulusanStatus(p);
        return isEvaluationComplete(p) && (status === 'lulus' || status === 'tidak_lulus');
    }).length;
});

const canLockGroup = computed(() => {
    if (!props.pendaftars || props.pendaftars.length === 0) {
        return false;
    }
    if (isGroupLocked.value) {
        return false;
    }
    return readyToLockCount.value === props.pendaftars.length;
});

const openPenentuanKelasModal = (pendaftar: any) => {
    // If santri is marked tidak_lulus, disallow penentuan kelas
    if (getKelulusanStatus(pendaftar) === 'tidak_lulus') {
        return;
    }
    selectedPendaftar.value = pendaftar;
    isPenentuanKelasModalOpen.value = true;
};

const toggleKelulusan = (row: any, targetStatus: 'lulus' | 'tidak_lulus') => {
    if (!isEvaluationComplete(row) || isSantriLocked(row)) {
        return;
    }

    const currentStatus = getKelulusanStatus(row);
    const nextStatus = currentStatus === targetStatus ? null : targetStatus;

    router.post(
        '/admin/pendaftar/penilaian-interview/kelulusan',
        {
            pendaftar_id: row.id,
            kelompok_ujian_id: props.kelompok.id,
            status_kelulusan: nextStatus,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => refreshData(),
        }
    );
};

const openSingleLockModal = (pendaftar: any) => {
    if (!isSantriReadyToLock(pendaftar)) {
        return;
    }
    targetPendaftarForLock.value = pendaftar;
    isSingleLockModalOpen.value = true;
};

const handleSingleLock = () => {
    if (!targetPendaftarForLock.value || isSingleLocking.value) {
        return;
    }

    isSingleLocking.value = true;
    router.post(
        `/admin/pendaftar/penilaian-interview/${targetPendaftarForLock.value.id}/finalize`,
        {
            kelompok_ujian_id: props.kelompok.id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSingleLocking.value = false;
                isSingleLockModalOpen.value = false;
                targetPendaftarForLock.value = null;
                refreshData();
            },
        }
    );
};

const openSingleUnlockModal = (pendaftar: any) => {
    targetPendaftarForLock.value = pendaftar;
    isSingleUnlockModalOpen.value = true;
};

const handleSingleUnlock = () => {
    if (!targetPendaftarForLock.value || isSingleUnlocking.value) {
        return;
    }

    isSingleUnlocking.value = true;
    router.post(
        `/admin/pendaftar/penilaian-interview/${targetPendaftarForLock.value.id}/unlock`,
        {
            kelompok_ujian_id: props.kelompok.id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSingleUnlocking.value = false;
                isSingleUnlockModalOpen.value = false;
                targetPendaftarForLock.value = null;
                refreshData();
            },
        }
    );
};

const handleLockKelompok = () => {
    if (!canLockGroup.value || isLocking.value) {
        return;
    }

    isLocking.value = true;
    router.post(
        `/admin/pendaftar/penilaian-interview/kelompok/${props.kelompok.id}/lock-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isLocking.value = false;
                isLockModalOpen.value = false;
                refreshData();
            },
        }
    );
};

const refreshData = () => {
    router.reload({ only: ['pendaftars', 'metrics', 'kelompok'] });
};

// ==========================================
// TIM PENGUJI / KOORDINATOR PSB CONFIG (GROUPED PER PEGAWAI)
// ==========================================
interface StaffRoleBadge {
    key: string;
    title: string;
    shortTitle: string;
    badgeColor: string;
    dotColor: string;
}

interface GroupedStaffMember {
    id: string;
    name: string;
    email?: string;
    nik?: string;
    nip?: string;
    foto?: string;
    jabatan?: string;
    roles: StaffRoleBadge[];
}

const roleBadgeConfigs: Record<string, { title: string; shortTitle: string; badgeColor: string; dotColor: string }> = {
    pewawancara: {
        title: 'Pewawancara (Interview)',
        shortTitle: 'Pewawancara',
        badgeColor: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
        dotColor: 'bg-indigo-500 dark:bg-indigo-400',
    },
    interview: {
        title: 'Pewawancara (Interview)',
        shortTitle: 'Pewawancara',
        badgeColor: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
        dotColor: 'bg-indigo-500 dark:bg-indigo-400',
    },
    tes_membaca: {
        title: 'Penguji Tes Membaca',
        shortTitle: 'Tes Membaca',
        badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
        dotColor: 'bg-emerald-500 dark:bg-emerald-400',
    },
    tes_menulis: {
        title: 'Penguji Tes Menulis',
        shortTitle: 'Tes Menulis',
        badgeColor: 'bg-sky-50 text-sky-700 border-sky-200/80 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
        dotColor: 'bg-sky-500 dark:bg-sky-400',
    },
    tes_hafalan: {
        title: 'Penguji Tes Hafalan',
        shortTitle: 'Tes Hafalan',
        badgeColor: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
        dotColor: 'bg-amber-500 dark:bg-amber-400',
    },
    koordinator: {
        title: 'Koordinator PSB',
        shortTitle: 'Koordinator PSB',
        badgeColor: 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
        dotColor: 'bg-purple-500 dark:bg-purple-400',
    },
};

const groupedStaffList = computed<GroupedStaffMember[]>(() => {
    const map = new Map<string, GroupedStaffMember>();

    const addRole = (user: any, roleKey: string) => {
        if (!user || !user.id) {
            return;
        }
        const config = roleBadgeConfigs[roleKey] || {
            title: roleKey,
            shortTitle: roleKey,
            badgeColor: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            dotColor: 'bg-slate-500',
        };

        if (!map.has(user.id)) {
            const roleName = user.roles && user.roles.length > 0 ? user.roles[0].name : 'Pegawai / Guru';
            map.set(user.id, {
                id: user.id,
                name: user.name,
                email: user.email,
                nik: user.nik,
                nip: user.nip,
                foto: user.foto,
                jabatan: roleName,
                roles: [],
            });
        }

        const member = map.get(user.id)!;
        if (!member.roles.some((r) => r.key === roleKey)) {
            member.roles.push({
                key: roleKey,
                title: config.title,
                shortTitle: config.shortTitle,
                badgeColor: config.badgeColor,
                dotColor: config.dotColor,
            });
        }
    };

    (props.timUjian?.pewawancara || []).forEach((u) => addRole(u, 'pewawancara'));
    (props.timUjian?.tes_membaca || []).forEach((u) => addRole(u, 'tes_membaca'));
    (props.timUjian?.tes_menulis || []).forEach((u) => addRole(u, 'tes_menulis'));
    (props.timUjian?.tes_hafalan || []).forEach((u) => addRole(u, 'tes_hafalan'));
    (props.timUjian?.koordinator || props.timUjian?.pengawas || []).forEach((u) => addRole(u, 'koordinator'));

    return Array.from(map.values());
});

const getFotoPegawai = (foto?: string | null) => {
    if (!foto) {
        return null;
    }
    if (
        foto.startsWith('http://') ||
        foto.startsWith('https://') ||
        foto.startsWith('data:image') ||
        foto.startsWith('/storage/') ||
        foto.startsWith('/')
    ) {
        return foto;
    }
    if (foto.startsWith('storage/')) {
        return `/${foto}`;
    }
    return `/storage/${foto.replace(/^\/+/, '')}`;
};

// ==========================================
// CARD 3: SEARCH, FILTER, & PAGINATION STATE
// ==========================================
const searchQuery = ref('');
const limit = ref(10);
const isLimitDropdownOpen = ref(false);
const limitOptions = [5, 10, 25, 50, 100];

const isFilterModalOpen = ref(false);
const filterForm = ref({
    status_penilaian: '',
    status_wawancara: '',
    status_kelas: '',
    status_kelulusan: '',
});

const isFilterActive = computed(() => {
    return Boolean(
        filterForm.value.status_penilaian ||
        filterForm.value.status_wawancara ||
        filterForm.value.status_kelas ||
        filterForm.value.status_kelulusan,
    );
});

const resetFilters = () => {
    filterForm.value.status_penilaian = '';
    filterForm.value.status_wawancara = '';
    filterForm.value.status_kelas = '';
    filterForm.value.status_kelulusan = '';
    isFilterModalOpen.value = false;
    currentPage.value = 1;
};

const applyFilters = () => {
    isFilterModalOpen.value = false;
    currentPage.value = 1;
};

const isSantriDinilai = (santri: any): boolean => {
    const h = santri.hasil_ujian || santri.hasilUjian;
    if (!h) {
        return Boolean(santri.penilaians && santri.penilaians.length > 0);
    }
    const hasWawancara = Boolean(h.hasil_wawancara && h.hasil_wawancara !== '');
    const hasBaca = Number(h.nilai_baca_kitab || 0) > 0;
    const hasTulis = Number(h.nilai_menulis || 0) > 0;
    const hasHafalan = Number(h.nilai_hafalan || 0) > 0;
    const hasTotal = Number(h.total_nilai || 0) > 0;
    const hasKelas = Boolean(h.rekomendasi_kelas_pondok);
    const hasLocked = Boolean(h.locked_at);
    const hasPenilaians = Boolean(santri.penilaians && santri.penilaians.length > 0);

    return hasWawancara || hasBaca || hasTulis || hasHafalan || hasTotal || hasKelas || hasLocked || hasPenilaians;
};

const filteredSantriList = computed(() => {
    let list = props.pendaftars || [];
    const q = searchQuery.value.trim().toLowerCase();

    if (q) {
        list = list.filter((p) => {
            const noMatch = (p.nomor_pendaftaran || '').toLowerCase().includes(q);
            const namaMatch = (p.nama || '').toLowerCase().includes(q);
            const nikMatch = (p.nik || '').toLowerCase().includes(q);
            const emailMatch = (p.email || '').toLowerCase().includes(q);
            const hpMatch = (p.nomor_hp || p.personal_data?.nomor_hp || '').toLowerCase().includes(q);
            const cabangMatch = (p.cabang?.name || p.personal_data?.cabang_pendaftaran || '').toLowerCase().includes(q);
            const jenjangMatch = (p.jenjang?.name || p.jenjang?.singkatan || '').toLowerCase().includes(q);

            return noMatch || namaMatch || nikMatch || emailMatch || hpMatch || cabangMatch || jenjangMatch;
        });
    }

    if (filterForm.value.status_penilaian) {
        list = list.filter((p) => {
            const dinilai = isSantriDinilai(p);
            if (filterForm.value.status_penilaian === 'sudah_dinilai') {
                return dinilai;
            }
            if (filterForm.value.status_penilaian === 'belum_dinilai') {
                return !dinilai;
            }
            return true;
        });
    }

    if (filterForm.value.status_wawancara) {
        list = list.filter((p) => {
            const h = p.hasil_ujian || p.hasilUjian;
            const hasWawancara = Boolean(h?.hasil_wawancara && String(h.hasil_wawancara).trim() !== '');
            if (filterForm.value.status_wawancara === 'sudah_wawancara') {
                return hasWawancara;
            }
            if (filterForm.value.status_wawancara === 'belum_wawancara') {
                return !hasWawancara;
            }
            return true;
        });
    }

    if (filterForm.value.status_kelas) {
        list = list.filter((p) => {
            const h = p.hasil_ujian || p.hasilUjian;
            const hasKelas = Boolean(h?.rekomendasi_kelas_pondok && String(h.rekomendasi_kelas_pondok).trim() !== '');
            if (filterForm.value.status_kelas === 'sudah_ditentukan') {
                return hasKelas;
            }
            if (filterForm.value.status_kelas === 'belum_ditentukan') {
                return !hasKelas;
            }
            return true;
        });
    }

    if (filterForm.value.status_kelulusan) {
        list = list.filter((p) => {
            const status = getKelulusanStatus(p);
            if (filterForm.value.status_kelulusan === 'lulus') {
                return status === 'lulus';
            }
            if (filterForm.value.status_kelulusan === 'tidak_lulus') {
                return status === 'tidak_lulus';
            }

            return true;
        });
    }

    return list;
});

// Candidate Pagination
const currentPage = ref(1);

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredSantriList.value.length / limit.value));
});

const paginatedSantriList = computed(() => {
    const start = (currentPage.value - 1) * limit.value;

    return filteredSantriList.value.slice(start, start + limit.value);
});

const paginationInfo = computed(() => {
    const total = filteredSantriList.value.length;

    if (total === 0) {
return { from: 0, to: 0, total: 0 };
}

    const from = (currentPage.value - 1) * limit.value + 1;
    const to = Math.min(currentPage.value * limit.value, total);

    return { from, to, total };
});

watch([searchQuery, limit], () => {
    currentPage.value = 1;
});

const imageErrorMap = ref<Record<string, boolean>>({});
const staffImageErrorMap = ref<Record<string, boolean>>({});

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

// Helper Functions
const getInitials = (name?: string) => {
    if (!name) {
return 'PS';
}

    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};

const getJenjangLogo = (jenjangOrCode?: JenjangItem | string | any) => {
    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code = (
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || jenjangOrCode?.name || ''
    ).toUpperCase();

    const found = props.jenjangs?.find(
        (j) =>
            (j.code || j.singkatan || '').toUpperCase() === code ||
            (j.name || '').toUpperCase() === code,
    );

    if (found?.logo_path) {
        return found.logo_path.startsWith('/')
            ? found.logo_path
            : `/${found.logo_path}`;
    }

    if (code === 'MTS' || code.includes('TSANAWIYAH')) {
        return '/image/logos/jenjang/logo-mts.png';
    }

    if (code === 'MA' || code.includes('ALIYAH')) {
        return '/image/logos/jenjang/logo-ma.png';
    }

    if (
        code === 'S1' ||
        code === 'S2' ||
        code === 'S3' ||
        code.includes('UII') ||
        code.includes('UNI') ||
        code.includes('DALWA') ||
        code.includes('SARJANA') ||
        code.includes('MAGISTER') ||
        code.includes('DOKTOR') ||
        code.includes('PASCASARJANA')
    ) {
        return '/image/logos/jenjang/logo-uii dalwa.png';
    }

    return '/image/logos/logo-1.png';
};

const handleLogoError = (event: Event) => {
    const target = event.target as HTMLImageElement;

    if (target) {
        target.src = '/image/logos/logo-1.png';
    }
};

const getEducationSubText = (row: any) => {
    const code = (
        row.jenjang?.code ||
        row.jenjang?.singkatan ||
        row.jenjang?.name ||
        ''
    ).toUpperCase();
    const edu = row.education_data || {};
    const tipe = row.tipe_pendaftaran ? ` (${row.tipe_pendaftaran})` : '';

    if (code === 'MTS' || code.includes('TSANAWIYAH')) {
        const rawTingkat = edu.tingkat_nama || edu.kelas_tingkat || edu.tingkat;
        if (rawTingkat) {
            return (String(rawTingkat).toLowerCase().includes('kelas') ? rawTingkat : `Kelas ${rawTingkat}`) + tipe;
        }

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : `Kelas 7${tipe}`;
    }

    if (code === 'MA' || code.includes('ALIYAH')) {
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

const formatIndoDate = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return dateStr;
}

        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const formatFullDate = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return dateStr;
}

        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <div class="relative min-h-screen w-full pb-16">
        <Head :title="`Detail Kelompok: ${props.kelompok.nama_kelompok} - PSB Dalwa Kalbar`" />

        <!-- PAGE HEADER -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100">
                    Detail Kelompok Interview
                </h1>
                <p class="mt-1 text-xs text-gray-500 sm:text-sm dark:text-slate-400">
                    Informasi lengkap jadwal pelaksanaan, tim penguji & Koordinator PSB, serta daftar calon santri peserta ujian.
                </p>
            </div>
            <div class="flex shrink-0 items-center justify-end">
                <BackButton :href="backUrl">Kembali</BackButton>
            </div>
        </div>

        <div class="space-y-6">
            <!-- ======================================================= -->
            <!-- CARD 1: INFORMASI JADWAL & TIM PENGUJI (COMPACT CARD)   -->
            <!-- ======================================================= -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <!-- Card Header -->
                <div class="flex flex-col gap-2 border-b border-gray-100 bg-slate-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-slate-800 dark:bg-slate-800/30">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-primary shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xs font-black tracking-wider text-gray-900 uppercase dark:text-slate-100">
                                INFORMASI JADWAL & TIM PENGUJI UJIAN
                            </h2>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500">
                                Rincian jadwal pelaksanaan ujian dan penugasan tim penguji & Koordinator PSB
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-black tracking-wider text-blue-700 uppercase dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                        {{ props.kelompok.status || 'SCHEDULED' }}
                    </span>
                </div>

                <div class="p-4 sm:p-5 space-y-4">
                    <!-- SECTION A: PARAMETER JADWAL & RUANGAN (COMPACT 4-BOX GRID) -->
                    <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                        <!-- 1. Nama Kelompok -->
                        <div class="flex flex-col justify-between rounded-xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/40 dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <span class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-indigo-700 uppercase dark:bg-indigo-950/60 dark:text-indigo-300">
                                    KELOMPOK
                                </span>
                            </div>
                            <div class="mt-2.5">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider dark:text-slate-500">
                                    Nama Kelompok
                                </span>
                                <h4 class="mt-0.5 break-words text-xs font-black leading-snug text-gray-900 dark:text-slate-100" :title="props.kelompok.nama_kelompok">
                                    {{ props.kelompok.nama_kelompok }}
                                </h4>
                            </div>
                        </div>

                        <!-- 2. Tanggal Pelaksanaan -->
                        <div class="flex flex-col justify-between rounded-xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all hover:border-emerald-300 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-emerald-700 uppercase dark:bg-emerald-950/60 dark:text-emerald-300">
                                    TANGGAL
                                </span>
                            </div>
                            <div class="mt-2.5">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider dark:text-slate-500">
                                    Tanggal Ujian
                                </span>
                                <h4 class="mt-0.5 break-words text-xs font-black leading-snug text-gray-900 dark:text-slate-100" :title="formatFullDate(props.kelompok.tanggal_ujian)">
                                    {{ formatFullDate(props.kelompok.tanggal_ujian) }}
                                </h4>
                            </div>
                        </div>

                        <!-- 3. Waktu Pelaksanaan -->
                        <div class="flex flex-col justify-between rounded-xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all hover:border-amber-300 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/40 dark:bg-amber-950/50 dark:text-amber-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase dark:bg-amber-950/60 dark:text-amber-300">
                                    WAKTU
                                </span>
                            </div>
                            <div class="mt-2.5">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider dark:text-slate-500">
                                    Waktu Pelaksanaan
                                </span>
                                <h4 class="mt-0.5 font-mono text-xs font-black leading-snug text-gray-900 dark:text-slate-100">
                                    {{ props.kelompok.waktu_mulai }} - {{ props.kelompok.waktu_selesai }} WIB
                                </h4>
                            </div>
                        </div>

                        <!-- 4. Ruangan / Lokasi -->
                        <div class="flex flex-col justify-between rounded-xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all hover:border-sky-300 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-sky-100 bg-sky-50 text-sky-600 dark:border-sky-900/40 dark:bg-sky-950/50 dark:text-sky-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="rounded-md bg-sky-50 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-sky-700 uppercase dark:bg-sky-950/60 dark:text-sky-300">
                                    RUANGAN
                                </span>
                            </div>
                            <div class="mt-2.5">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider dark:text-slate-500">
                                    Ruangan / Lokasi
                                </span>
                                <h4 class="mt-0.5 break-words text-xs font-black leading-relaxed text-gray-900 dark:text-slate-100" :title="props.kelompok.lokasi || 'Ruang Utama'">
                                    {{ props.kelompok.lokasi || 'Ruang Utama' }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION B: TIM PENGUJI & KOORDINATOR PSB (PER PEGAWAI) -->
                    <div class="border-t border-gray-100 pt-3.5 dark:border-slate-800">
                        <div class="mb-2.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                <h3 class="text-[11px] font-black tracking-wider text-slate-700 uppercase dark:text-slate-300">
                                    TIM PENGUJI & KOORDINATOR PSB
                                </h3>
                            </div>
                            <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-black tracking-wider text-indigo-700 uppercase dark:bg-indigo-950/60 dark:text-indigo-300">
                                {{ groupedStaffList.length }} PEGAWAI DITUGASKAN
                            </span>
                        </div>

                        <!-- Staff Grid grouped per Pegawai -->
                        <div
                            v-if="groupedStaffList.length > 0"
                            class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                v-for="staff in groupedStaffList"
                                :key="staff.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-200/80 bg-white p-3 shadow-2xs transition-all hover:border-indigo-300 hover:shadow-xs dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900/60"
                            >
                                <!-- Avatar -->
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50 text-xs font-black text-indigo-600 shadow-2xs dark:border-indigo-900/40 dark:bg-indigo-950/60 dark:text-indigo-400"
                                >
                                    <img
                                        v-if="staff.foto"
                                        :src="getFotoPegawai(staff.foto) || undefined"
                                        :alt="staff.name"
                                        class="h-full w-full object-cover"
                                        @error="(e) => ((e.target as HTMLElement).style.display = 'none')"
                                    />
                                    <span v-else>
                                        {{ getInitials(staff.name) }}
                                    </span>
                                </div>

                                <!-- Staff Info & Roles -->
                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-xs font-black text-gray-900 dark:text-slate-100" :title="staff.name">
                                        {{ staff.name }}
                                    </h4>
                                    <p class="truncate text-[10px] text-gray-400 dark:text-slate-500">
                                        {{ staff.email || (staff.nip ? `NIP: ${staff.nip}` : (staff.nik ? `NIK: ${staff.nik}` : staff.jabatan || 'Pegawai')) }}
                                    </p>

                                    <!-- Tugas / Peran Badges -->
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                        <span
                                            v-for="r in staff.roles"
                                            :key="r.key"
                                            class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                            :class="r.badgeColor"
                                        >
                                            <span class="h-1 w-1 rounded-full" :class="r.dotColor"></span>
                                            {{ r.title }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 py-6 px-4 text-center dark:border-slate-800"
                        >
                            <p class="text-xs font-bold text-gray-600 dark:text-slate-400">
                                Belum ada personil yang ditugaskan
                            </p>
                            <p class="mt-0.5 text-[10px] text-gray-400 dark:text-slate-500">
                                Tidak ada penguji atau Koordinator PSB yang terdaftar pada kelompok interview ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- CARD 3: DAFTAR CALON SANTRI UJIAN                      -->
            <!-- ======================================================= -->
            <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <!-- Card Header -->
                <div class="flex flex-col gap-2 border-b border-gray-100 bg-slate-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-slate-800 dark:bg-slate-800/30">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-2xs dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xs font-black tracking-wider text-gray-900 uppercase dark:text-slate-100">
                                DAFTAR CALON SANTRI UJIAN
                            </h2>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500">
                                Total {{ filteredSantriList.length }} calon santri yang terdaftar dalam kelompok interview ini
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-black tracking-wider text-emerald-700 uppercase dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ filteredSantriList.length }} CALON SANTRI
                    </span>
                </div>

                <!-- Toolbar (Search, Limit, Filter Button matching DataTable.vue) -->
                <div class="relative z-20 flex flex-col items-center justify-between gap-3 border-b border-gray-100 bg-white p-3.5 sm:p-4 lg:flex-row dark:border-slate-800 dark:bg-slate-900">
                    <!-- Left: Search & Limit -->
                    <div class="flex w-full flex-row items-center gap-2.5 lg:w-auto">
                        <!-- Search -->
                        <div class="group relative flex-1 sm:w-64 sm:flex-none lg:w-72">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-colors group-focus-within:text-primary dark:text-slate-500 dark:group-focus-within:text-blue-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari data..."
                                class="block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 py-2 pr-3 pl-9 text-xs font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20"
                            />
                        </div>

                        <!-- Limit -->
                        <div class="relative flex w-auto shrink-0 items-center">
                            <button
                                type="button"
                                @click="isLimitDropdownOpen = !isLimitDropdownOpen"
                                class="group flex w-full min-w-16 items-center justify-between rounded-xl border border-neutral-warm/20 bg-surface/50 px-3 py-2 text-xs font-bold text-primary-dark shadow-xs transition-all duration-300 hover:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-blue-500/20"
                            >
                                <span>{{ limit }}</span>
                                <svg
                                    class="ml-2 h-4 w-4 transform text-gray-400 transition-transform duration-300 group-hover:text-primary dark:text-slate-400 dark:text-slate-500 dark:group-hover:text-blue-400"
                                    :class="isLimitDropdownOpen ? '-rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Full Screen Dropdown Overlay -->
                            <div
                                v-if="isLimitDropdownOpen"
                                class="fixed inset-0 z-40"
                                @click="isLimitDropdownOpen = false"
                            ></div>

                            <!-- Dropdown Menu -->
                            <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="transform opacity-0 scale-95 -translate-y-2"
                                enter-to-class="transform opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="transform opacity-100 scale-100 translate-y-0"
                                leave-to-class="transform opacity-0 scale-95 -translate-y-2"
                            >
                                <div
                                    v-if="isLimitDropdownOpen"
                                    class="absolute top-full right-0 z-50 mt-2 w-32 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl ring-1 ring-black/5 focus:outline-none sm:right-auto sm:left-0 dark:border-slate-800 dark:bg-slate-800 dark:ring-white/5"
                                >
                                    <div class="px-2 py-2">
                                        <p class="mb-2 px-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                            Per Halaman
                                        </p>
                                        <button
                                            v-for="option in limitOptions"
                                            :key="'limit-' + option"
                                            @click="
                                                limit = option;
                                                isLimitDropdownOpen = false;
                                            "
                                            type="button"
                                            class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold transition-colors last:mb-0"
                                            :class="
                                                limit === option
                                                    ? 'bg-primary/10 text-primary dark:bg-primary/25 dark:text-blue-300'
                                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:bg-slate-800 dark:text-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                            "
                                        >
                                            {{ option }}
                                            <svg
                                                v-if="limit === option"
                                                class="h-4 w-4 text-primary dark:text-blue-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <!-- Right: Kunci Hasil Button, Dropdown Nilai Tes & Filter Button -->
                    <div class="mt-2 flex w-full flex-wrap items-center justify-end gap-2.5 lg:mt-0 lg:w-auto">
                        <!-- Kunci Hasil Ujian Button -->
                        <button
                            v-if="!isGroupLocked"
                            type="button"
                            :disabled="!canLockGroup"
                            @click="isLockModalOpen = true"
                            class="inline-flex cursor-pointer items-center rounded-xl px-3.5 py-2.5 text-xs font-bold shadow-sm transition-all focus:ring-2 focus:ring-emerald-500/20 focus:outline-none sm:px-4"
                            :class="canLockGroup
                                ? 'bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98]'
                                : 'border border-gray-200 bg-gray-100 text-gray-400 opacity-60 cursor-not-allowed dark:border-slate-800 dark:bg-slate-800 dark:text-slate-500'"
                            :title="canLockGroup ? 'Kunci semua hasil ujian santri' : `Belum siap dikunci: ${readyToLockCount}/${props.pendaftars?.length || 0} santri telah dinilai lengkap dan diputuskan kelulusannya`"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span class="ml-2">Kunci Hasil ({{ readyToLockCount }}/{{ props.pendaftars?.length || 0 }})</span>
                        </button>
                        <div
                            v-else
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-bold text-emerald-700 shadow-2xs dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Hasil Terkunci (Selesai)</span>
                        </div>

                        <Dropdown align="right" width="48" content-classes="p-1.5 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-800">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="group inline-flex cursor-pointer items-center rounded-xl bg-primary px-3.5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-primary-dark focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:bg-primary dark:hover:bg-primary-dark"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span class="ml-2 font-bold">Nilai Tes</span>
                                    <svg class="ml-1.5 h-4 w-4 text-white/80 transition-transform group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="space-y-1">
                                    <Link
                                        :href="`/admin/pendaftar/penilaian-interview/kelompok/${kelompok.id}/tes/tes-membaca`"
                                        class="group flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-200 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-300"
                                    >
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <span>Tes Membaca</span>
                                    </Link>

                                    <Link
                                        :href="`/admin/pendaftar/penilaian-interview/kelompok/${kelompok.id}/tes/tes-menulis`"
                                        class="group flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-blue-50 hover:text-blue-700 dark:text-slate-200 dark:hover:bg-blue-950/50 dark:hover:text-blue-300"
                                    >
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </div>
                                        <span>Tes Menulis</span>
                                    </Link>

                                    <Link
                                        :href="`/admin/pendaftar/penilaian-interview/kelompok/${kelompok.id}/tes/tes-hafalan`"
                                        class="group flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-purple-50 hover:text-purple-700 dark:text-slate-200 dark:hover:bg-purple-950/50 dark:hover:text-purple-300"
                                    >
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-100 dark:bg-purple-950/60 dark:text-purple-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <span>Tes Hafalan</span>
                                    </Link>
                                </div>
                            </template>
                        </Dropdown>

                        <button
                            type="button"
                            @click="isFilterModalOpen = true"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                                :class="isFilterActive ? 'text-primary dark:text-blue-400' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Filter</span>
                            <span
                                v-if="isFilterActive"
                                class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2 dark:bg-blue-400"
                            ></span>
                        </button>
                    </div>
                </div>

                <!-- Filter Modal -->
                <FilterModal
                    :show="isFilterModalOpen"
                    title="Filter Data Calon Santri"
                    description="Saring data calon santri pada kelompok ini berdasarkan status penilaian, wawancara, penentuan kelas, dan keputusan kelulusan"
                    @close="isFilterModalOpen = false"
                    @reset="resetFilters"
                    @apply="applyFilters"
                >
                    <div class="space-y-4">
                        <!-- 1. Status Penilaian Nilai Tes -->
                        <div>
                            <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                Status Penilaian Nilai Tes
                            </label>
                            <CustomSelect
                                v-model="filterForm.status_penilaian"
                                :options="[
                                    { value: '', label: 'Semua Status Penilaian' },
                                    { value: 'sudah_dinilai', label: 'Sudah Dinilai' },
                                    { value: 'belum_dinilai', label: 'Belum Dinilai' },
                                ]"
                                placeholder="Pilih Status Penilaian"
                            />
                        </div>

                        <!-- 2. Status Wawancara -->
                        <div>
                            <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                Status Wawancara
                            </label>
                            <CustomSelect
                                v-model="filterForm.status_wawancara"
                                :options="[
                                    { value: '', label: 'Semua Status Wawancara' },
                                    { value: 'sudah_wawancara', label: 'Sudah Diwawancara' },
                                    { value: 'belum_wawancara', label: 'Belum Diwawancara' },
                                ]"
                                placeholder="Pilih Status Wawancara"
                            />
                        </div>

                        <!-- 3. Status Penentuan Kelas -->
                        <div>
                            <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                Status Penentuan Kelas Pondok
                            </label>
                            <CustomSelect
                                v-model="filterForm.status_kelas"
                                :options="[
                                    { value: '', label: 'Semua Status Kelas' },
                                    { value: 'sudah_ditentukan', label: 'Sudah Ditentukan' },
                                    { value: 'belum_ditentukan', label: 'Belum Ditentukan' },
                                ]"
                                placeholder="Pilih Status Penentuan Kelas"
                            />
                        </div>

                        <!-- 4. Status Keputusan Kelulusan -->
                        <div>
                            <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                Status Keputusan Kelulusan
                            </label>
                            <CustomSelect
                                v-model="filterForm.status_kelulusan"
                                :options="[
                                    { value: '', label: 'Semua Keputusan' },
                                    { value: 'lulus', label: 'Lulus' },
                                    { value: 'tidak_lulus', label: 'Tidak Lulus' },

                                ]"
                                placeholder="Pilih Status Kelulusan"
                            />
                        </div>
                    </div>
                </FilterModal>

                <!-- Modal Konfirmasi Kunci Hasil Ujian -->
                <Modal
                    :show="isLockModalOpen"
                    max-width="md"
                    title="Kunci Hasil Ujian Kelompok"
                    description="Finalisasi dan penguncian seluruh hasil wawancara, tes, dan keputusan kelulusan."
                    @close="isLockModalOpen = false"
                >
                    <div class="space-y-4">
                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                            <p class="font-bold">Perhatian sebelum mengunci hasil:</p>
                            <ul class="mt-1.5 list-disc pl-4 space-y-1 text-[11px]">
                                <li>Status pendaftaran calon santri akan diperbarui ke <strong>LULUS</strong> atau <strong>TIDAK LULUS</strong>.</li>
                                <li>Seluruh nilai tes dan form wawancara akan <strong>dikunci</strong> (hanya dapat dilihat / mode preview).</li>
                                <li>Status kelompok interview akan diubah menjadi <strong>Selesai (Completed)</strong>.</li>
                                <li>Khusus calon santri yang <strong>Lulus</strong>, penentuan kelas pondok tetap dapat diisi/diubah sewaktu-waktu.</li>
                            </ul>
                        </div>

                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                            Apakah Anda yakin ingin mengunci hasil ujian untuk kelompok <strong class="text-slate-900 dark:text-white">{{ props.kelompok.nama_kelompok }}</strong>?
                        </p>

                        <div class="mt-4 flex items-center justify-end gap-2.5">
                            <SecondaryButton :disabled="isLocking" @click="isLockModalOpen = false">
                                Batal
                            </SecondaryButton>
                            <button
                                type="button"
                                :disabled="isLocking"
                                @click="handleLockKelompok"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500/20 disabled:opacity-50"
                            >
                                <svg v-if="isLocking" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>{{ isLocking ? 'Mengunci...' : 'Ya, Kunci Hasil' }}</span>
                            </button>
                        </div>
                    </div>
                </Modal>

                <!-- Modal Konfirmasi Kunci Nilai Santri Per-Pendaftar -->
                <Modal
                    :show="isSingleLockModalOpen"
                    max-width="md"
                    title="Kunci Nilai Calon Santri"
                    description="Finalisasi dan penguncian nilai untuk calon santri yang dipilih."
                    @close="isSingleLockModalOpen = false"
                >
                    <div class="space-y-4">
                        <!-- Santri Info Summary Box -->
                        <div v-if="targetPendaftarForLock" class="rounded-2xl border border-gray-100 bg-slate-50/80 p-3.5 text-xs dark:border-slate-800 dark:bg-slate-800/60">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-black text-primary dark:bg-blue-950/60 dark:text-blue-400">
                                    {{ getInitials(targetPendaftarForLock.nama) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-black text-slate-900 truncate dark:text-white">{{ targetPendaftarForLock.nama }}</h4>
                                    <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ targetPendaftarForLock.nomor_pendaftaran }} &bull; {{ targetPendaftarForLock.jenjang?.name }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                    :class="getKelulusanStatus(targetPendaftarForLock) === 'lulus' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300'"
                                >
                                    {{ getKelulusanStatus(targetPendaftarForLock) === 'lulus' ? 'LULUS' : 'TIDAK LULUS' }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                            <p class="font-bold">Perhatian penguncian nilai santri:</p>
                            <ul class="mt-1.5 list-disc pl-4 space-y-1 text-[11px]">
                                <li>Status pendaftaran santri akan diperbarui ke <strong>{{ getKelulusanStatus(targetPendaftarForLock) === 'lulus' ? 'LULUS' : 'TIDAK LULUS' }}</strong>.</li>
                                <li>Seluruh nilai tes dan wawancara santri ini akan <strong>dikunci</strong> (mode pratinjau).</li>
                                <li>Jika seluruh calon santri dalam kelompok telah dikunci, status kelompok ujian otomatis menjadi <strong>Completed</strong>.</li>
                                <li v-if="getKelulusanStatus(targetPendaftarForLock) === 'lulus'">Penentuan kelas pondok tetap dapat diisi/diubah sewaktu-waktu.</li>
                            </ul>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-2.5">
                            <SecondaryButton :disabled="isSingleLocking" @click="isSingleLockModalOpen = false">
                                Batal
                            </SecondaryButton>
                            <button
                                type="button"
                                :disabled="isSingleLocking"
                                @click="handleSingleLock"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-amber-700 focus:ring-2 focus:ring-amber-500/20 disabled:opacity-50"
                            >
                                <svg v-if="isSingleLocking" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>{{ isSingleLocking ? 'Mengunci...' : 'Kunci Nilai Santri' }}</span>
                            </button>
                        </div>
                    </div>
                </Modal>

                <!-- Modal Konfirmasi Buka Kunci Nilai Santri -->
                <Modal
                    :show="isSingleUnlockModalOpen"
                    max-width="md"
                    title="Buka Kunci Nilai Santri"
                    description="Membuka kembali lembar penilaian dan wawancara agar dapat diedit."
                    @close="isSingleUnlockModalOpen = false"
                >
                    <div class="space-y-4">
                        <!-- Santri Info Summary Box -->
                        <div v-if="targetPendaftarForLock" class="rounded-2xl border border-gray-100 bg-slate-50/80 p-3.5 text-xs dark:border-slate-800 dark:bg-slate-800/60">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-black text-primary dark:bg-blue-950/60 dark:text-blue-400">
                                    {{ getInitials(targetPendaftarForLock.nama) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-black text-slate-900 truncate dark:text-white">{{ targetPendaftarForLock.nama }}</h4>
                                    <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ targetPendaftarForLock.nomor_pendaftaran }} &bull; {{ targetPendaftarForLock.jenjang?.name }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-3.5 text-xs text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300">
                            <p class="font-bold">Efek membuka kunci nilai:</p>
                            <ul class="mt-1.5 list-disc pl-4 space-y-1 text-[11px]">
                                <li>Status pendaftaran santri akan dikembalikan ke <strong>INTERVIEW</strong>.</li>
                                <li>Nilai tes dan wawancara dapat diedit kembali oleh penguji.</li>
                                <li>Status kelompok interview akan disesuaikan menjadi <strong>In Progress</strong> jika sebelumnya Selesai.</li>
                            </ul>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-2.5">
                            <SecondaryButton :disabled="isSingleUnlocking" @click="isSingleUnlockModalOpen = false">
                                Batal
                            </SecondaryButton>
                            <button
                                type="button"
                                :disabled="isSingleUnlocking"
                                @click="handleSingleUnlock"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-primary-dark focus:ring-2 focus:ring-primary/20 disabled:opacity-50"
                            >
                                <svg v-if="isSingleUnlocking" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                <span>{{ isSingleUnlocking ? 'Membuka...' : 'Buka Kunci Nilai' }}</span>
                            </button>
                        </div>
                    </div>
                </Modal>

                <!-- Table Content -->
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[1200px] text-left text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="min-w-[170px] px-5 py-4 font-bold tracking-wider whitespace-nowrap">NO REGISTRASI</th>
                                <th class="min-w-[240px] px-5 py-4 font-bold tracking-wider whitespace-nowrap">CALON SANTRI</th>
                                <th class="min-w-[140px] px-4 py-4 font-bold tracking-wider whitespace-nowrap text-center">HASIL WAWANCARA</th>
                                <th class="min-w-[110px] px-4 py-4 font-bold tracking-wider whitespace-nowrap text-center">TES MEMBACA</th>
                                <th class="min-w-[110px] px-4 py-4 font-bold tracking-wider whitespace-nowrap text-center">TES MENULIS</th>
                                <th class="min-w-[110px] px-4 py-4 font-bold tracking-wider whitespace-nowrap text-center">TES HAFALAN</th>
                                <th class="min-w-[90px] px-3 py-4 font-bold tracking-wider whitespace-nowrap text-center">LULUS</th>
                                <th class="min-w-[110px] px-3 py-4 font-bold tracking-wider whitespace-nowrap text-center">TIDAK LULUS</th>
                                <th class="min-w-[160px] px-5 py-4 font-bold tracking-wider whitespace-nowrap text-center">PENENTUAN KELAS</th>
                                <th class="min-w-[90px] px-5 py-4 text-right font-bold tracking-wider whitespace-nowrap">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <tr
                                v-for="row in paginatedSantriList"
                                :key="row.id"
                                class="bg-white transition-colors duration-150 hover:bg-gray-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                            >
                                <!-- 1. NO REGISTRASI & NIK -->
                                <td class="min-w-[170px] px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-sm font-bold text-primary-dark dark:text-blue-400">
                                            {{ row.nomor_pendaftaran || '-' }}
                                        </span>
                                        <span class="mt-0.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                                            NIK: {{ row.nik || row.personal_data?.nik || '-' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- 2. CALON SANTRI -->
                                <td class="min-w-[240px] px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="text-left text-sm font-bold whitespace-nowrap text-slate-800 dark:text-slate-100">
                                            {{ row.nama }}
                                        </div>
                                        <span
                                            v-if="isSantriLocked(row)"
                                            class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[9px] font-black text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300 shrink-0"
                                            title="Nilai dan status kelulusan santri ini telah dikunci"
                                        >
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Terkunci
                                        </span>
                                    </div>
                                    <div v-if="row.jenjang" class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">
                                        {{ row.jenjang.name }}
                                    </div>
                                </td>

                                <!-- 3. HASIL WAWANCARA -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <div v-if="(row.hasil_ujian || row.hasilUjian)?.hasil_wawancara" class="flex flex-col items-center gap-0.5">
                                        <span v-if="(row.hasil_ujian || row.hasilUjian)?.hasil_wawancara === 'A'" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30" title="A - Memenuhi">
                                            A
                                        </span>
                                        <span v-else-if="(row.hasil_ujian || row.hasilUjian)?.hasil_wawancara === 'C'" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-xs font-black text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30" title="C - Syarat Tertentu">
                                            C
                                        </span>
                                        <span v-else-if="(row.hasil_ujian || row.hasilUjian)?.hasil_wawancara === 'D'" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-xs font-black text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30" title="D - Tidak Memenuhi">
                                            D
                                        </span>
                                        <span v-else class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-700 ring-1 ring-slate-300 dark:bg-slate-800 dark:text-slate-200">
                                            {{ (row.hasil_ujian || row.hasilUjian).hasil_wawancara }}
                                        </span>
                                        <Link
                                            :href="showWawancara.url({ kelompokUjian: kelompok.id, pendaftar: row.id })"
                                            class="text-[11px] font-medium text-slate-400 underline decoration-dotted underline-offset-2 hover:text-primary dark:text-slate-500 dark:hover:text-blue-400"
                                        >
                                            {{ isSantriLocked(row) ? 'Lihat' : 'Lihat / Ubah' }}
                                        </Link>
                                    </div>
                                    <div v-else class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Belum Diisi
                                        </span>
                                        <Link
                                            v-if="!isSantriLocked(row)"
                                            :href="showWawancara.url({ kelompokUjian: kelompok.id, pendaftar: row.id })"
                                            class="text-[11px] font-bold text-primary underline decoration-dotted underline-offset-2 hover:text-primary-dark dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            Wawancara
                                        </Link>
                                    </div>
                                </td>

                                <!-- 4. TES MEMBACA -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span v-if="Number((row.hasil_ujian || row.hasilUjian)?.nilai_baca_kitab || 0) > 0" class="font-bold text-gray-900 dark:text-slate-100">
                                        {{ Number((row.hasil_ujian || row.hasilUjian).nilai_baca_kitab).toFixed(0) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 dark:text-slate-500 font-medium">-</span>
                                </td>
                                
                                <!-- 5. TES MENULIS -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span v-if="Number((row.hasil_ujian || row.hasilUjian)?.nilai_menulis || 0) > 0" class="font-bold text-gray-900 dark:text-slate-100">
                                        {{ Number((row.hasil_ujian || row.hasilUjian).nilai_menulis).toFixed(0) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 dark:text-slate-500 font-medium">-</span>
                                </td>

                                <!-- 6. TES HAFALAN -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span v-if="Number((row.hasil_ujian || row.hasilUjian)?.nilai_hafalan || 0) > 0" class="font-bold text-gray-900 dark:text-slate-100">
                                        {{ Number((row.hasil_ujian || row.hasilUjian).nilai_hafalan).toFixed(0) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400 dark:text-slate-500 font-medium">-</span>
                                </td>

                                <!-- 7. LULUS (CHECKBOX) -->
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    <div
                                        class="flex items-center justify-center"
                                        :title="!isEvaluationComplete(row) ? 'Selesaikan 4 tes terlebih dahulu untuk menentukan kelulusan' : (isSantriLocked(row) ? 'Nilai santri ini telah dikunci' : 'Tandai Lulus')"
                                    >
                                        <Checkbox
                                            :model-value="getKelulusanStatus(row) === 'lulus'"
                                            :disabled="!isEvaluationComplete(row) || isSantriLocked(row)"
                                            @update:model-value="() => toggleKelulusan(row, 'lulus')"
                                        />
                                    </div>
                                </td>

                                <!-- 8. TIDAK LULUS (CHECKBOX) -->
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    <div
                                        class="flex items-center justify-center"
                                        :title="!isEvaluationComplete(row) ? 'Selesaikan 4 tes terlebih dahulu untuk menentukan kelulusan' : (isSantriLocked(row) ? 'Nilai santri ini telah dikunci' : 'Tandai Tidak Lulus')"
                                    >
                                        <Checkbox
                                            :model-value="getKelulusanStatus(row) === 'tidak_lulus'"
                                            :disabled="!isEvaluationComplete(row) || isSantriLocked(row)"
                                            @update:model-value="() => toggleKelulusan(row, 'tidak_lulus')"
                                        />
                                    </div>
                                </td>

                                <!-- 9. PENENTUAN KELAS -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <!-- Jika santri Tidak Lulus: Nonaktifkan / Tampilkan Tidak Ada Kelas -->
                                    <div v-if="getKelulusanStatus(row) === 'tidak_lulus'" class="flex flex-col items-center gap-1" title="Calon santri tidak lulus, penentuan kelas dinonaktifkan">
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-rose-200/80 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-400">
                                            <svg class="h-3 w-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Tidak Ada Kelas
                                        </span>
                                    </div>

                                    <!-- Jika santri Lulus atau Belum Diputuskan -->
                                    <div v-else-if="(row.hasil_ujian || row.hasilUjian)?.rekomendasi_kelas_pondok" class="flex flex-col items-center gap-0.5">
                                        <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            {{ (row.hasil_ujian || row.hasilUjian).rekomendasi_kelas_pondok }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="openPenentuanKelasModal(row)"
                                            class="cursor-pointer text-[11px] font-medium text-slate-400 underline decoration-dotted underline-offset-2 hover:text-primary dark:text-slate-500 dark:hover:text-blue-400"
                                        >
                                            Ubah Kelas
                                        </button>
                                    </div>
                                    <div v-else class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Belum Ditentukan
                                        </span>
                                        <button
                                            type="button"
                                            @click="openPenentuanKelasModal(row)"
                                            class="cursor-pointer text-[11px] font-bold text-primary underline decoration-dotted underline-offset-2 hover:text-primary-dark dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            Tentukan Kelas
                                        </button>
                                    </div>
                                </td>

                                <!-- 10. AKSI (TITIK TIGA ACTIONMENU) -->
                                <td class="min-w-[90px] px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex justify-end">
                                        <ActionMenu>
                                            <template #trigger>
                                                <button
                                                    type="button"
                                                    class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                                    title="Opsi Aksi"
                                                >
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <Link
                                                    :href="showWawancara.url({ kelompokUjian: kelompok.id, pendaftar: row.id })"
                                                    class="flex w-full items-center px-4 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:text-slate-200 dark:hover:bg-slate-700"
                                                >
                                                    <svg class="mr-3 h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    <span>{{ isSantriLocked(row) ? 'Lihat Wawancara' : 'Wawancara' }}</span>
                                                </Link>

                                                <!-- Penentuan Kelas Action: Hanya jika santri tidak ditolak / bukan tidak lulus -->
                                                <button
                                                    v-if="getKelulusanStatus(row) !== 'tidak_lulus'"
                                                    type="button"
                                                    @click="openPenentuanKelasModal(row)"
                                                    class="flex w-full cursor-pointer items-center px-4 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:text-slate-200 dark:hover:bg-slate-700"
                                                >
                                                    <svg class="mr-3 h-4 w-4 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    Penentuan Kelas
                                                </button>

                                                <div class="my-1 border-t border-gray-100 dark:border-slate-800"></div>

                                                <!-- Opsi Kunci Nilai (Jika Belum Terkunci) -->
                                                <button
                                                    v-if="!isSantriLocked(row)"
                                                    type="button"
                                                    :disabled="!isSantriReadyToLock(row)"
                                                    @click="openSingleLockModal(row)"
                                                    class="flex w-full items-center px-4 py-2.5 text-left text-sm font-bold transition-colors"
                                                    :class="isSantriReadyToLock(row)
                                                        ? 'cursor-pointer text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40'
                                                        : 'cursor-not-allowed text-gray-400 opacity-50 dark:text-slate-600'"
                                                    :title="isSantriReadyToLock(row) ? 'Kunci nilai calon santri' : 'Lengkapi 4 tes dan tentukan kelulusan terlebih dahulu'"
                                                >
                                                    <svg class="mr-3 h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    <span>Kunci Nilai</span>
                                                </button>

                                                <!-- Opsi Buka Kunci Nilai (Jika Sudah Terkunci) -->
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openSingleUnlockModal(row)"
                                                    class="flex w-full cursor-pointer items-center px-4 py-2.5 text-left text-sm font-bold text-sky-700 transition-colors hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-950/40"
                                                    title="Buka kunci nilai calon santri"
                                                >
                                                    <svg class="mr-3 h-4 w-4 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Buka Kunci Nilai</span>
                                                </button>
                                            </template>
                                        </ActionMenu>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="paginatedSantriList.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-gray-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-3 h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-sm font-bold text-gray-600 dark:text-slate-400">Tidak ada calon santri ditemukan</p>
                                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Coba ubah kata kunci pencarian atau filter Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer (Matching DataTable.vue) -->
                <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-100 bg-gray-50 p-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        Menampilkan
                        <span class="font-bold text-gray-900 dark:text-slate-100">{{ paginationInfo.from }}</span>
                        sampai
                        <span class="font-bold text-gray-900 dark:text-slate-100">{{ paginationInfo.to }}</span>
                        dari
                        <span class="font-bold text-gray-900 dark:text-slate-100">{{ paginationInfo.total }}</span>
                        calon santri
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="currentPage > 1 && currentPage--"
                            :disabled="currentPage === 1"
                            class="flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors"
                            :class="currentPage === 1
                                ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                        >
                            &laquo;
                        </button>
                        <button
                            v-for="page in totalPages"
                            :key="'page-' + page"
                            type="button"
                            @click="currentPage = page"
                            class="flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors"
                            :class="page === currentPage
                                ? 'border-primary bg-primary text-white dark:border-primary dark:bg-primary'
                                : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                        >
                            {{ page }}
                        </button>
                        <button
                            type="button"
                            @click="currentPage < totalPages && currentPage++"
                            :disabled="currentPage >= totalPages"
                            class="flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors"
                            :class="currentPage >= totalPages
                                ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                        >
                            &raquo;
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <PenentuanKelasModal
            :show="isPenentuanKelasModalOpen"
            :pendaftar="selectedPendaftar"
            :kelompok-ujian-id="kelompok.id"
            @close="isPenentuanKelasModalOpen = false; selectedPendaftar = null"
            @success="refreshData"
        />
    </div>
</template>
