<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index, update_kelompok } from '@/routes/admin/pendaftar/penilaian_interview';

defineOptions({ layout: AdminLayout });

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

interface UserItem {
    id: string;
    name: string;
    email?: string;
    nik?: string;
    nip?: string;
    foto?: string;
    roles?: Array<{ id: string; name: string }>;
}

const props = defineProps<{
    kelompokUjian: {
        id: string;
        nama_kelompok: string;
        tanggal_ujian: string;
        waktu_mulai: string;
        waktu_selesai: string;
        lokasi: string;
        status: string;
        interview_penguji_ids: string[];
        tes_membaca_penguji_ids: string[];
        tes_menulis_penguji_ids: string[];
        tes_hafalan_penguji_ids: string[];
        koordinator_ids?: string[];
        pengawas_ids?: string[];
    };
    assignedPendaftars: any[];
    availablePendaftars: any[];
    jenjangs: JenjangItem[];
    cabangs: CabangItem[];
    pengujis: UserItem[];
    koordinator?: UserItem[];
    pengawas?: UserItem[];
}>();

const backUrl = index.url();

// Selected recipient list
const selectedRecipients = ref<any[]>(
    Array.isArray(props.assignedPendaftars) ? [...props.assignedPendaftars] : [],
);

// Form state
const scheduleForm = useForm({
    nama_kelompok: props.kelompokUjian.nama_kelompok || '',
    interview_penguji_ids: [...(props.kelompokUjian.interview_penguji_ids || [])],
    tes_membaca_penguji_ids: [...(props.kelompokUjian.tes_membaca_penguji_ids || [])],
    tes_menulis_penguji_ids: [...(props.kelompokUjian.tes_menulis_penguji_ids || [])],
    tes_hafalan_penguji_ids: [...(props.kelompokUjian.tes_hafalan_penguji_ids || [])],
    koordinator_ids: [...(props.kelompokUjian.koordinator_ids || props.kelompokUjian.pengawas_ids || [])],
    pengawas_ids: [...(props.kelompokUjian.koordinator_ids || props.kelompokUjian.pengawas_ids || [])],
    tanggal_ujian: props.kelompokUjian.tanggal_ujian || new Date().toISOString().split('T')[0],
    waktu_mulai: props.kelompokUjian.waktu_mulai || '08:00',
    waktu_selesai: props.kelompokUjian.waktu_selesai || '12:00',
    lokasi: props.kelompokUjian.lokasi || 'Ruang Ujian Pondok Pesantren Dalwa Kalbar',
    pendaftar_ids: [] as string[],
});

// ==========================================
// ROLE CONFIGURATIONS & EXAMINER SELECTION
// ==========================================
type RoleKey =
    | 'interview'
    | 'tes_membaca'
    | 'tes_menulis'
    | 'tes_hafalan'
    | 'koordinator';

interface RoleConfig {
    key: RoleKey;
    formKey:
        | 'interview_penguji_ids'
        | 'tes_membaca_penguji_ids'
        | 'tes_menulis_penguji_ids'
        | 'tes_hafalan_penguji_ids'
        | 'koordinator_ids';
    title: string;
    shortTitle: string;
    subtitle: string;
    isRequired: boolean;
    badgeColor: string;
    badgeText: string;
    themeIconBg: string;
    themeIconColor: string;
    themeHeaderBorder: string;
}

const roleConfigs: Record<RoleKey, RoleConfig> = {
    interview: {
        key: 'interview',
        formKey: 'interview_penguji_ids',
        title: 'Pewawancara (Interview)',
        shortTitle: 'Pewawancara',
        subtitle: 'Wawancara motivasi, kesiapan, & kepribadian santri',
        isRequired: true,
        badgeColor:
            'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800',
        badgeText: 'Wajib (1 Orang)',
        themeIconBg:
            'bg-indigo-50 border-indigo-100 text-indigo-600 dark:bg-indigo-950/40 dark:border-indigo-900/40 dark:text-indigo-400',
        themeIconColor: 'text-indigo-600 dark:text-indigo-400',
        themeHeaderBorder: 'border-indigo-100 dark:border-indigo-900/30',
    },
    tes_membaca: {
        key: 'tes_membaca',
        formKey: 'tes_membaca_penguji_ids',
        title: 'Penguji Tes Membaca',
        shortTitle: 'Penguji Membaca',
        subtitle: "Kefasihan membaca Al-Qur'an, kaidah tajwid, & kitab",
        isRequired: true,
        badgeColor:
            'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
        badgeText: 'Wajib (1 Orang)',
        themeIconBg:
            'bg-emerald-50 border-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:border-emerald-900/40 dark:text-emerald-400',
        themeIconColor: 'text-emerald-600 dark:text-emerald-400',
        themeHeaderBorder: 'border-emerald-100 dark:border-emerald-900/30',
    },
    tes_menulis: {
        key: 'tes_menulis',
        formKey: 'tes_menulis_penguji_ids',
        title: 'Penguji Tes Menulis',
        shortTitle: 'Penguji Menulis',
        subtitle: 'Kerapian & ketepatan penulisan Arab / Pegon',
        isRequired: true,
        badgeColor:
            'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-800',
        badgeText: 'Wajib (1 Orang)',
        themeIconBg:
            'bg-sky-50 border-sky-100 text-sky-600 dark:bg-sky-950/40 dark:border-sky-900/40 dark:text-sky-400',
        themeIconColor: 'text-sky-600 dark:text-sky-400',
        themeHeaderBorder: 'border-sky-100 dark:border-sky-900/30',
    },
    tes_hafalan: {
        key: 'tes_hafalan',
        formKey: 'tes_hafalan_penguji_ids',
        title: 'Penguji Tes Hafalan',
        shortTitle: 'Penguji Hafalan',
        subtitle: "Kelancaran hafalan surat Al-Qur'an & matan dasar",
        isRequired: true,
        badgeColor:
            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
        badgeText: 'Wajib (1 Orang)',
        themeIconBg:
            'bg-amber-50 border-amber-100 text-amber-600 dark:bg-amber-950/40 dark:border-amber-900/40 dark:text-amber-400',
        themeIconColor: 'text-amber-600 dark:text-amber-400',
        themeHeaderBorder: 'border-amber-100 dark:border-amber-900/30',
    },
    koordinator: {
        key: 'koordinator',
        formKey: 'koordinator_ids',
        title: 'Koordinator PSB',
        shortTitle: 'Koordinator PSB',
        subtitle: 'Penanggung jawab pelaksanaan seleksi PSB dan ketertiban ujian',
        isRequired: true,
        badgeColor:
            'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
        badgeText: 'Wajib (1 Orang)',
        themeIconBg:
            'bg-blue-50 border-blue-100 text-blue-600 dark:bg-blue-950/40 dark:border-blue-900/40 dark:text-blue-400',
        themeIconColor: 'text-blue-600 dark:text-blue-400',
        themeHeaderBorder: 'border-blue-100 dark:border-blue-900/30',
    },
};

const selectedInterviewPengujiList = computed(() => {
    return (props.pengujis || []).filter((p) =>
        scheduleForm.interview_penguji_ids.includes(p.id),
    );
});

const selectedMembacaPengujiList = computed(() => {
    return (props.pengujis || []).filter((p) =>
        scheduleForm.tes_membaca_penguji_ids.includes(p.id),
    );
});

const selectedMenulisPengujiList = computed(() => {
    return (props.pengujis || []).filter((p) =>
        scheduleForm.tes_menulis_penguji_ids.includes(p.id),
    );
});

const selectedHafalanPengujiList = computed(() => {
    return (props.pengujis || []).filter((p) =>
        scheduleForm.tes_hafalan_penguji_ids.includes(p.id),
    );
});

const selectedKoordinatorList = computed(() => {
    const pool = props.koordinator || props.pengawas || [];
    return pool.filter((p) =>
        scheduleForm.koordinator_ids.includes(p.id) ||
        scheduleForm.pengawas_ids.includes(p.id),
    );
});

const isAllRequiredRolesFilled = computed(() => {
    return (
        scheduleForm.interview_penguji_ids.length > 0 &&
        scheduleForm.tes_membaca_penguji_ids.length > 0 &&
        scheduleForm.tes_menulis_penguji_ids.length > 0 &&
        scheduleForm.tes_hafalan_penguji_ids.length > 0 &&
        (scheduleForm.koordinator_ids.length > 0 ||
            scheduleForm.pengawas_ids.length > 0)
    );
});

const missingRequiredRoles = computed(() => {
    const missing: (typeof roleConfigs)[RoleKey][] = [];

    if (scheduleForm.interview_penguji_ids.length === 0) {
        missing.push(roleConfigs.interview);
    }

    if (scheduleForm.tes_membaca_penguji_ids.length === 0) {
        missing.push(roleConfigs.tes_membaca);
    }

    if (scheduleForm.tes_menulis_penguji_ids.length === 0) {
        missing.push(roleConfigs.tes_menulis);
    }

    if (scheduleForm.tes_hafalan_penguji_ids.length === 0) {
        missing.push(roleConfigs.tes_hafalan);
    }

    if (
        scheduleForm.koordinator_ids.length === 0 &&
        scheduleForm.pengawas_ids.length === 0
    ) {
        missing.push(roleConfigs.koordinator);
    }

    return missing;
});

const staffErrorMessages = computed(() => {
    const errors: string[] = [];

    if (scheduleForm.errors.interview_penguji_ids) {
        errors.push(scheduleForm.errors.interview_penguji_ids);
    }

    if (scheduleForm.errors.tes_membaca_penguji_ids) {
        errors.push(scheduleForm.errors.tes_membaca_penguji_ids);
    }

    if (scheduleForm.errors.tes_menulis_penguji_ids) {
        errors.push(scheduleForm.errors.tes_menulis_penguji_ids);
    }

    if (scheduleForm.errors.tes_hafalan_penguji_ids) {
        errors.push(scheduleForm.errors.tes_hafalan_penguji_ids);
    }

    if (scheduleForm.errors.koordinator_ids) {
        errors.push(scheduleForm.errors.koordinator_ids);
    }

    if (scheduleForm.errors.pengawas_ids) {
        errors.push(scheduleForm.errors.pengawas_ids);
    }

    return errors;
});

const hasStaffErrors = computed(() => staffErrorMessages.value.length > 0);

const removePengujiFromRole = (role: RoleKey, id: string) => {
    const config = roleConfigs[role];
    (scheduleForm[config.formKey] as string[]) = (
        scheduleForm[config.formKey] as string[]
    ).filter((pId) => pId !== id);
};

// ==========================================
// UNIFIED STAFF LIST (PENGUJI + PENGAWAS)
// ==========================================
interface AssignedStaffItem {
    id: string;
    userId: string;
    name: string;
    email?: string;
    user?: any;
    roleKey: RoleKey;
    roleTitle: string;
    shortTitle: string;
    roleBadgeColor: string;
    themeIconBg: string;
    themeIconColor: string;
    isRequired: boolean;
}

const allAssignedStaff = computed<AssignedStaffItem[]>(() => {
    const list: AssignedStaffItem[] = [];

    // 1. Pewawancara
    (selectedInterviewPengujiList.value || []).forEach((u) => {
        list.push({
            id: `interview-${u.id}`,
            userId: u.id,
            name: u.name,
            email: u.email,
            user: u,
            roleKey: 'interview',
            roleTitle: roleConfigs.interview.title,
            shortTitle: roleConfigs.interview.shortTitle,
            roleBadgeColor: roleConfigs.interview.badgeColor,
            themeIconBg: roleConfigs.interview.themeIconBg,
            themeIconColor: roleConfigs.interview.themeIconColor,
            isRequired: true,
        });
    });

    // 2. Tes Membaca
    (selectedMembacaPengujiList.value || []).forEach((u) => {
        list.push({
            id: `tes_membaca-${u.id}`,
            userId: u.id,
            name: u.name,
            email: u.email,
            user: u,
            roleKey: 'tes_membaca',
            roleTitle: roleConfigs.tes_membaca.title,
            shortTitle: roleConfigs.tes_membaca.shortTitle,
            roleBadgeColor: roleConfigs.tes_membaca.badgeColor,
            themeIconBg: roleConfigs.tes_membaca.themeIconBg,
            themeIconColor: roleConfigs.tes_membaca.themeIconColor,
            isRequired: true,
        });
    });

    // 3. Tes Menulis
    (selectedMenulisPengujiList.value || []).forEach((u) => {
        list.push({
            id: `tes_menulis-${u.id}`,
            userId: u.id,
            name: u.name,
            email: u.email,
            user: u,
            roleKey: 'tes_menulis',
            roleTitle: roleConfigs.tes_menulis.title,
            shortTitle: roleConfigs.tes_menulis.shortTitle,
            roleBadgeColor: roleConfigs.tes_menulis.badgeColor,
            themeIconBg: roleConfigs.tes_menulis.themeIconBg,
            themeIconColor: roleConfigs.tes_menulis.themeIconColor,
            isRequired: true,
        });
    });

    // 4. Tes Hafalan
    (selectedHafalanPengujiList.value || []).forEach((u) => {
        list.push({
            id: `tes_hafalan-${u.id}`,
            userId: u.id,
            name: u.name,
            email: u.email,
            user: u,
            roleKey: 'tes_hafalan',
            roleTitle: roleConfigs.tes_hafalan.title,
            shortTitle: roleConfigs.tes_hafalan.shortTitle,
            roleBadgeColor: roleConfigs.tes_hafalan.badgeColor,
            themeIconBg: roleConfigs.tes_hafalan.themeIconBg,
            themeIconColor: roleConfigs.tes_hafalan.themeIconColor,
            isRequired: true,
        });
    });

    // 5. Koordinator PSB
    (selectedKoordinatorList.value || []).forEach((u) => {
        list.push({
            id: `koordinator-${u.id}`,
            userId: u.id,
            name: u.name,
            email: u.email,
            user: u,
            roleKey: 'koordinator',
            roleTitle: roleConfigs.koordinator.title,
            shortTitle: roleConfigs.koordinator.shortTitle,
            roleBadgeColor: roleConfigs.koordinator.badgeColor,
            themeIconBg: roleConfigs.koordinator.themeIconBg,
            themeIconColor: roleConfigs.koordinator.themeIconColor,
            isRequired: true,
        });
    });

    return list;
});

// Staff Pagination
const staffCurrentPage = ref(1);
const staffPerPage = ref(10);

const staffTotalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(allAssignedStaff.value.length / staffPerPage.value),
    );
});

const paginatedStaff = computed(() => {
    const start = (staffCurrentPage.value - 1) * staffPerPage.value;

    return allAssignedStaff.value.slice(start, start + staffPerPage.value);
});

const staffPagination = computed(() => {
    const total = allAssignedStaff.value.length;

    if (total === 0) {
        return { from: 0, to: 0, total: 0 };
    }

    const from = (staffCurrentPage.value - 1) * staffPerPage.value + 1;
    const to = Math.min(staffCurrentPage.value * staffPerPage.value, total);

    return { from, to, total };
});

watch(
    () => allAssignedStaff.value.length,
    () => {
        if (staffCurrentPage.value > staffTotalPages.value) {
            staffCurrentPage.value = staffTotalPages.value;
        }
    },
);

// ==========================================
// ROLE ASSIGNMENT MODAL (PEGAWAI SELECTION)
// ==========================================
const isRoleModalOpen = ref(false);
const activeRoleForModal = ref<RoleKey>('interview');
const roleUserSearch = ref('');
const selectedRoleUsersToAdd = ref<string[]>([]);
const modalStaffCurrentPage = ref(1);
const modalStaffPerPage = ref(10);

const availableRoleUsersPool = computed(() => {
    const role = activeRoleForModal.value;

    if (role === 'koordinator') {
        return props.koordinator || props.pengawas || [];
    }

    return props.pengujis || [];
});

const filteredAvailableRoleUsers = computed(() => {
    const pool = availableRoleUsersPool.value;
    const q = (roleUserSearch.value || '').trim().toLowerCase();

    if (!q) {
return pool;
}

    return pool.filter((u) => {
        const nameMatch = (u.name || '').toLowerCase().includes(q);
        const emailMatch = (u.email || '').toLowerCase().includes(q);
        const nikMatch = (u.nik || '').toLowerCase().includes(q);

        return nameMatch || emailMatch || nikMatch;
    });
});

const modalStaffTotalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(
            filteredAvailableRoleUsers.value.length / modalStaffPerPage.value,
        ),
    );
});

const paginatedModalStaff = computed(() => {
    const start = (modalStaffCurrentPage.value - 1) * modalStaffPerPage.value;

    return filteredAvailableRoleUsers.value.slice(
        start,
        start + modalStaffPerPage.value,
    );
});

const modalStaffPagination = computed(() => {
    const total = filteredAvailableRoleUsers.value.length;

    if (total === 0) {
        return { from: 0, to: 0, total: 0 };
    }

    const from =
        (modalStaffCurrentPage.value - 1) * modalStaffPerPage.value + 1;
    const to = Math.min(
        modalStaffCurrentPage.value * modalStaffPerPage.value,
        total,
    );

    return { from, to, total };
});

watch(
    () => [roleUserSearch.value, activeRoleForModal.value],
    () => {
        modalStaffCurrentPage.value = 1;
    },
);

const isAllRoleUsersSelected = computed(() => {
    if (paginatedModalStaff.value.length === 0) {
return false;
}

    return paginatedModalStaff.value.every((u) =>
        selectedRoleUsersToAdd.value.includes(u.id),
    );
});

const toggleSelectAllRoleUsers = (checked: boolean) => {
    if (checked) {
        const toAdd = paginatedModalStaff.value
            .map((u) => u.id)
            .filter((id) => !selectedRoleUsersToAdd.value.includes(id));
        selectedRoleUsersToAdd.value.push(...toAdd);
    } else {
        const pageIds = paginatedModalStaff.value.map((u) => u.id);
        selectedRoleUsersToAdd.value = selectedRoleUsersToAdd.value.filter(
            (id) => !pageIds.includes(id),
        );
    }
};

const toggleRoleUserSelection = (id: string, checked: boolean) => {
    if (checked) {
        selectedRoleUsersToAdd.value = [id];
    } else {
        selectedRoleUsersToAdd.value = selectedRoleUsersToAdd.value.filter(
            (item) => item !== id,
        );
    }
};

const openRoleModal = (roleKey: RoleKey) => {
    activeRoleForModal.value = roleKey;
    const config = roleConfigs[roleKey];
    selectedRoleUsersToAdd.value = [
        ...((scheduleForm[config.formKey] as string[]) || []).slice(0, 1),
    ];
    roleUserSearch.value = '';
    modalStaffCurrentPage.value = 1;
    isRoleModalOpen.value = true;
};

const switchModalRoleTab = (roleKey: RoleKey) => {
    activeRoleForModal.value = roleKey;
    const config = roleConfigs[roleKey];
    selectedRoleUsersToAdd.value = [
        ...((scheduleForm[config.formKey] as string[]) || []).slice(0, 1),
    ];
    roleUserSearch.value = '';
    modalStaffCurrentPage.value = 1;
};

const closeRoleModal = () => {
    isRoleModalOpen.value = false;
    selectedRoleUsersToAdd.value = [];
};

const confirmAddRoleUsers = () => {
    const config = roleConfigs[activeRoleForModal.value];
    (scheduleForm[config.formKey] as string[]) = [
        ...selectedRoleUsersToAdd.value.slice(0, 1),
    ];
    closeRoleModal();
};

// ==========================================
// CANDIDATE SANTRI TABLE PAGINATION
// ==========================================
const santriCurrentPage = ref(1);
const santriPerPage = ref(10);

const santriTotalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(selectedRecipients.value.length / santriPerPage.value),
    );
});

const paginatedSantri = computed(() => {
    const start = (santriCurrentPage.value - 1) * santriPerPage.value;

    return selectedRecipients.value.slice(start, start + santriPerPage.value);
});

const santriPagination = computed(() => {
    const total = selectedRecipients.value.length;

    if (total === 0) {
        return { from: 0, to: 0, total: 0 };
    }

    const from = (santriCurrentPage.value - 1) * santriPerPage.value + 1;
    const to = Math.min(santriCurrentPage.value * santriPerPage.value, total);

    return { from, to, total };
});

const removeSantriFromRecipients = (id: string) => {
    selectedRecipients.value = selectedRecipients.value.filter(
        (r) => r.id !== id,
    );
};

// ==========================================
// MODAL: ADD CANDIDATES (SANTRI PICKER)
// ==========================================
const isAddCandidatesModalOpen = ref(false);
const isCandidateFilterModalOpen = ref(false);
const candidateSearch = ref('');
const candidateFilterForm = ref({
    jenjang_id: '',
    cabang_id: '',
    gender: '',
});
const appliedCandidateFilters = ref({
    jenjang_id: '',
    cabang_id: '',
    gender: '',
});
const selectedCandidatesToAdd = ref<string[]>([]);
const modalCandidateCurrentPage = ref(1);
const modalCandidatePerPage = ref(10);

const isCandidateFilterActive = computed(() => {
    return Boolean(
        appliedCandidateFilters.value.jenjang_id ||
            appliedCandidateFilters.value.cabang_id ||
            appliedCandidateFilters.value.gender,
    );
});

const applyCandidateFilters = () => {
    appliedCandidateFilters.value = { ...candidateFilterForm.value };
    isCandidateFilterModalOpen.value = false;
    modalCandidateCurrentPage.value = 1;
};

const resetCandidateFilters = () => {
    candidateFilterForm.value = {
        jenjang_id: '',
        cabang_id: '',
        gender: '',
    };
    appliedCandidateFilters.value = {
        jenjang_id: '',
        cabang_id: '',
        gender: '',
    };
    modalCandidateCurrentPage.value = 1;
};

const existingRecipientIds = computed(() => {
    return selectedRecipients.value.map((r) => r.id);
});

const filteredAvailableCandidates = computed(() => {
    const list = props.availablePendaftars || [];
    const q = (candidateSearch.value || '').trim().toLowerCase();

    return list.filter((p) => {
        if (existingRecipientIds.value.includes(p.id)) {
            return false;
        }

        if (
            appliedCandidateFilters.value.jenjang_id &&
            p.jenjang_id !== appliedCandidateFilters.value.jenjang_id
        ) {
            return false;
        }

        if (
            appliedCandidateFilters.value.cabang_id &&
            p.cabang_id !== appliedCandidateFilters.value.cabang_id
        ) {
            return false;
        }

        if (appliedCandidateFilters.value.gender) {
            const g = (
                p.personal_data?.jenis_kelamin ||
                p.gender ||
                ''
            ).toLowerCase();

            if (
                appliedCandidateFilters.value.gender === 'Laki-Laki' &&
                !g.includes('laki') &&
                g !== 'l'
            ) {
                return false;
            }

            if (
                appliedCandidateFilters.value.gender === 'Perempuan' &&
                !g.includes('perempuan') &&
                g !== 'p'
            ) {
                return false;
            }
        }

        if (q) {
            const nameMatch = (p.nama || '').toLowerCase().includes(q);
            const noMatch = (p.nomor_pendaftaran || '')
                .toLowerCase()
                .includes(q);
            const nikMatch = (p.nik || '').toLowerCase().includes(q);

            return nameMatch || noMatch || nikMatch;
        }

        return true;
    });
});

const modalCandidateTotalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(
            filteredAvailableCandidates.value.length /
                modalCandidatePerPage.value,
        ),
    );
});

const paginatedModalCandidates = computed(() => {
    const start =
        (modalCandidateCurrentPage.value - 1) * modalCandidatePerPage.value;

    return filteredAvailableCandidates.value.slice(
        start,
        start + modalCandidatePerPage.value,
    );
});

const modalCandidatePagination = computed(() => {
    const total = filteredAvailableCandidates.value.length;

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
        candidateSearch.value,
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

    return paginatedModalCandidates.value.every((p) =>
        selectedCandidatesToAdd.value.includes(p.id),
    );
});

const toggleSelectAllCandidatePage = (checked: boolean) => {
    if (checked) {
        const toAdd = paginatedModalCandidates.value
            .map((p) => p.id)
            .filter((id) => !selectedCandidatesToAdd.value.includes(id));
        selectedCandidatesToAdd.value.push(...toAdd);
    } else {
        const pageIds = paginatedModalCandidates.value.map((p) => p.id);
        selectedCandidatesToAdd.value = selectedCandidatesToAdd.value.filter(
            (id) => !pageIds.includes(id),
        );
    }
};

const toggleCandidateSelection = (id: string, checked: boolean) => {
    if (checked) {
        if (!selectedCandidatesToAdd.value.includes(id)) {
            selectedCandidatesToAdd.value.push(id);
        }
    } else {
        selectedCandidatesToAdd.value = selectedCandidatesToAdd.value.filter(
            (item) => item !== id,
        );
    }
};

const openAddCandidatesModal = () => {
    candidateSearch.value = '';
    candidateFilterForm.value = {
        jenjang_id: '',
        cabang_id: '',
        gender: '',
    };
    appliedCandidateFilters.value = {
        jenjang_id: '',
        cabang_id: '',
        gender: '',
    };
    selectedCandidatesToAdd.value = [];
    modalCandidateCurrentPage.value = 1;
    isAddCandidatesModalOpen.value = true;
};

const closeAddCandidatesModal = () => {
    isAddCandidatesModalOpen.value = false;
    isCandidateFilterModalOpen.value = false;
    selectedCandidatesToAdd.value = [];
};

const confirmAddCandidates = () => {
    const candidatesToAdd = (props.availablePendaftars || []).filter((p) =>
        selectedCandidatesToAdd.value.includes(p.id),
    );

    selectedRecipients.value.push(...candidatesToAdd);
    closeAddCandidatesModal();
};

// ==========================================
// NOTIFY MODAL STATE & METHODS
// ==========================================
interface NotifyItem {
    category: string;
    text: string;
    targetSection?: string;
}

const isNotifyModalOpen = ref(false);
const notifyModalData = ref<{
    title: string;
    subtitle: string;
    items: NotifyItem[];
    type: 'warning' | 'error' | 'info';
    firstTargetSection?: string;
}>({
    title: '',
    subtitle: '',
    items: [],
    type: 'warning',
});

const showNotifyModal = (data: {
    title: string;
    subtitle: string;
    items: (NotifyItem | string)[];
    type?: 'warning' | 'error' | 'info';
    firstTargetSection?: string;
}) => {
    const formattedItems: NotifyItem[] = data.items.map((item) => {
        if (typeof item === 'string') {
            return { category: 'Pemberitahuan', text: item };
        }
        return item;
    });

    notifyModalData.value = {
        title: data.title,
        subtitle: data.subtitle,
        items: formattedItems,
        type: data.type || 'warning',
        firstTargetSection:
            data.firstTargetSection || formattedItems[0]?.targetSection,
    };
    isNotifyModalOpen.value = true;
};

const closeNotifyModal = () => {
    const target = notifyModalData.value.firstTargetSection;
    isNotifyModalOpen.value = false;

    if (target) {
        setTimeout(() => {
            const el = document.getElementById(target);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 250);
    }
};

// ==========================================
// SUBMIT FORM
// ==========================================
const submitForm = () => {
    const missingItems: NotifyItem[] = [];

    // 1. Validasi Calon Santri
    if (selectedRecipients.value.length === 0) {
        missingItems.push({
            category: 'Calon Santri',
            text: 'Belum ada calon santri dalam kelompok ini (Minimal 1 calon santri).',
            targetSection: 'section-card-santri',
        });
    }

    // 2. Validasi Info Kelompok
    if (!scheduleForm.nama_kelompok?.trim()) {
        missingItems.push({
            category: 'Informasi Jadwal',
            text: 'Nama Kelompok Ujian wajib diisi.',
            targetSection: 'section-card-info',
        });
    }

    if (!scheduleForm.tanggal_ujian) {
        missingItems.push({
            category: 'Informasi Jadwal',
            text: 'Tanggal Pelaksanaan Ujian belum ditentukan.',
            targetSection: 'section-card-info',
        });
    }

    if (!scheduleForm.waktu_mulai || !scheduleForm.waktu_selesai) {
        missingItems.push({
            category: 'Informasi Jadwal',
            text: 'Waktu Mulai dan Waktu Selesai Ujian wajib ditentukan.',
            targetSection: 'section-card-info',
        });
    }

    if (!scheduleForm.lokasi?.trim()) {
        missingItems.push({
            category: 'Informasi Jadwal',
            text: 'Lokasi / Ruangan Ujian wajib diisi.',
            targetSection: 'section-card-info',
        });
    }

    // Roles Penguji & Koordinator
    if (scheduleForm.interview_penguji_ids.length === 0) {
        missingItems.push({
            category: 'Tim Penguji',
            text: 'Pewawancara (Sesi Wawancara Santri & Wali) belum dipilih (Wajib 1 orang).',
            targetSection: 'section-card-penguji',
        });
    }

    if (scheduleForm.tes_membaca_penguji_ids.length === 0) {
        missingItems.push({
            category: 'Tim Penguji',
            text: 'Penguji Tes Membaca Al-Qur\'an / Kitab belum dipilih (Wajib 1 orang).',
            targetSection: 'section-card-penguji',
        });
    }

    if (scheduleForm.tes_menulis_penguji_ids.length === 0) {
        missingItems.push({
            category: 'Tim Penguji',
            text: 'Penguji Tes Menulis Arab / Pegon belum dipilih (Wajib 1 orang).',
            targetSection: 'section-card-penguji',
        });
    }

    if (scheduleForm.tes_hafalan_penguji_ids.length === 0) {
        missingItems.push({
            category: 'Tim Penguji',
            text: 'Penguji Tes Hafalan Doa / Surat Pendek belum dipilih (Wajib 1 orang).',
            targetSection: 'section-card-penguji',
        });
    }

    if (
        scheduleForm.koordinator_ids.length === 0 &&
        scheduleForm.pengawas_ids.length === 0
    ) {
        missingItems.push({
            category: 'Koordinator PSB',
            text: 'Koordinator PSB belum dipilih (Wajib 1 orang).',
            targetSection: 'section-card-penguji',
        });
    }

    if (missingItems.length > 0) {
        showNotifyModal({
            title: 'Kelengkapan Jadwal Ujian Belum Lengkap',
            subtitle:
                'Mohon lengkapi beberapa informasi berikut sebelum perubahan dapat disimpan:',
            items: missingItems,
            type: 'warning',
            firstTargetSection: missingItems[0].targetSection,
        });

        return;
    }

    scheduleForm.pendaftar_ids = selectedRecipients.value.map((r) => r.id);

    scheduleForm.put(update_kelompok.url(props.kelompokUjian.id), {
        preserveScroll: true,
        onError: (errors) => {
            const errorList = Object.values(errors);
            if (errorList.length > 0) {
                showNotifyModal({
                    title: 'Gagal Memperbarui Jadwal',
                    subtitle:
                        'Terjadi kesalahan validasi saat memproses jadwal ujian:',
                    items: errorList.map((err) => ({
                        category: 'Validasi Server',
                        text: String(err),
                    })),
                    type: 'error',
                });
            }
        },
    });
};

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

const getUserPhoto = (user: any): string | null => {
    if (!user) {
        return null;
    }

    const raw =
        user.foto_url ||
        user.foto ||
        user.avatar ||
        user.profile_photo_url ||
        user.pegawai?.foto ||
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
</script>

<template>
    <div class="relative min-h-screen w-full pb-16">
        <Head title="Edit Kelompok Interview & Seleksi - PSB Dalwa Kalbar" />

        <!-- PAGE HEADER -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                >
                    Form Edit Kelompok Interview & Seleksi
                </h1>
                <p
                    class="mt-1 text-xs text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Perbarui kelompok ujian, waktu, ruang, tim penguji, dan
                    calon santri peserta
                </p>
            </div>
            <div class="flex shrink-0 items-center justify-end">
                <BackButton :href="backUrl">Kembali</BackButton>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- CARD 1: INFORMASI JADWAL & KELOMPOK UJIAN -->
            <div
                id="section-card-info"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
            >
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
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h2
                                class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                            >
                                Informasi Jadwal & Kelompok Ujian
                            </h2>
                            <p
                                class="text-xs font-medium text-slate-500 dark:text-slate-400"
                            >
                                Tentukan nama kelompok ujian, ruangan
                                pelaksanaan, serta tanggal dan waktu ujian
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PARAMETER KELOMPOK -->
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4.5 sm:p-5 dark:border-slate-700/90 dark:border-slate-800 dark:bg-slate-800"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-wider text-slate-700 uppercase dark:text-slate-300"
                    >
                        Parameter Kelompok & Waktu Pelaksanaan
                    </h3>
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <div class="lg:col-span-2">
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                NAMA KELOMPOK UJIAN
                                <span class="text-rose-500">*</span>
                            </label>
                            <TextInput
                                type="text"
                                v-model="scheduleForm.nama_kelompok"
                                placeholder="Aa Contoh: Kelompok Ujian Seleksi - Gelombang 1"
                                :error="scheduleForm.errors.nama_kelompok"
                                class="w-full"
                                required
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                Nama kelompok yang akan tercetak pada jadwal
                                dan kartu ujian peserta
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                LOKASI / RUANG UJIAN
                            </label>
                            <TextInput
                                type="text"
                                v-model="scheduleForm.lokasi"
                                placeholder="Aa Contoh: Ruang Ujian A - Gedung Utama"
                                :error="scheduleForm.errors.lokasi"
                                class="w-full"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                Lokasi ruang ujian fisik atau tautan online
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                TANGGAL UJIAN
                                <span class="text-rose-500">*</span>
                            </label>
                            <CustomDatePicker
                                v-model="scheduleForm.tanggal_ujian"
                                :error="scheduleForm.errors.tanggal_ujian"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                WAKTU MULAI
                                <span class="text-rose-500">*</span>
                            </label>
                            <TextInput
                                type="time"
                                v-model="scheduleForm.waktu_mulai"
                                :error="scheduleForm.errors.waktu_mulai"
                                class="w-full"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-wider text-slate-700 uppercase dark:text-slate-200"
                            >
                                WAKTU SELESAI
                                <span class="text-rose-500">*</span>
                            </label>
                            <TextInput
                                type="time"
                                v-model="scheduleForm.waktu_selesai"
                                :error="scheduleForm.errors.waktu_selesai"
                                class="w-full"
                                required
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: TIM PENGUJI & KOORDINATOR PSB (1 UNIFIED TABLE) -->
            <div
                id="section-card-penguji"
                class="flex flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Card Header -->
                <div
                    class="flex flex-col gap-4 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-6 dark:border-slate-800"
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
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                                >
                                    Tim Penguji & Koordinator PSB
                                </h2>
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-extrabold"
                                    :class="
                                        isAllRequiredRolesFilled
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300'
                                    "
                                >
                                    {{
                                        isAllRequiredRolesFilled
                                            ? '5/5 Lengkap'
                                            : `${5 - missingRequiredRoles.length}/5 Belum Lengkap`
                                    }}
                                </span>
                            </div>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Daftar pewawancara, penguji akademik, dan
                                Koordinator PSB
                            </p>
                        </div>
                    </div>

                    <PrimaryButton
                        type="button"
                        @click="openRoleModal('interview')"
                        class="inline-flex shrink-0 items-center gap-2"
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
                        <span>Tambah</span>
                    </PrimaryButton>
                </div>

                <!-- Invalid Feedback Alert Banner -->
                <div
                    v-if="!isAllRequiredRolesFilled || hasStaffErrors"
                    class="border-b border-amber-100 bg-amber-50/70 p-4 dark:border-amber-900/40 dark:bg-amber-950/20"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-5 w-5 shrink-0 items-center justify-center text-amber-600 dark:text-amber-400"
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
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-xs font-bold text-amber-900 dark:text-amber-200"
                            >
                                Peran Wajib Belum Lengkap ({{
                                    5 - missingRequiredRoles.length
                                }}/5 Terpenuhi)
                            </p>
                            <p
                                class="mt-0.5 text-xs text-amber-700 dark:text-amber-300"
                            >
                                Setiap sesi ujian seleksi wajib menugaskan 1
                                personil untuk masing-masing peran:
                            </p>
                            <div
                                class="mt-2.5 flex flex-wrap items-center gap-2"
                            >
                                <button
                                    v-for="mRole in missingRequiredRoles"
                                    :key="'missing-role-' + mRole.key"
                                    type="button"
                                    @click="openRoleModal(mRole.key)"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-2.5 py-1 text-xs font-bold text-amber-800 shadow-2xs transition-all hover:border-amber-400 hover:bg-amber-100/80 active:scale-95 dark:border-amber-800 dark:bg-slate-900 dark:text-amber-300 dark:hover:bg-amber-950/60"
                                >
                                    <svg
                                        class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
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
                                    <span>{{ mRole.title }}</span>
                                </button>
                            </div>
                            <!-- Server Validation Errors -->
                            <div
                                v-if="staffErrorMessages.length > 0"
                                class="mt-2.5 space-y-1"
                            >
                                <p
                                    v-for="(err, eIdx) in staffErrorMessages"
                                    :key="'staff-err-' + eIdx"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400"
                                >
                                    <svg
                                        class="h-3.5 w-3.5 shrink-0"
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
                                    <span>{{ err }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="allAssignedStaff.length === 0"
                    class="p-10 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500"
                    >
                        <svg
                            class="h-7 w-7"
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
                    <h4
                        class="mt-4 text-base font-bold text-gray-800 dark:text-slate-200"
                    >
                        Belum Ada Tim Penguji / Koordinator PSB Ditugaskan
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Wajib menugaskan 1 personil untuk masing-masing peran
                        (Interview, Membaca, Menulis, Hafalan, dan Koordinator PSB).
                    </p>
                    <div class="mt-4">
                        <PrimaryButton
                            type="button"
                            @click="openRoleModal('interview')"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>Tambah</span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead>
                            <tr
                                class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                            >
                                <th class="w-14 px-6 py-3.5 text-center">NO</th>
                                <th class="px-6 py-3.5">
                                    PROFIL PENGUJI / KOORDINATOR PSB
                                </th>
                                <th class="px-6 py-3.5">ROLE / PERAN UJIAN</th>
                                <th class="px-6 py-3.5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                        >
                            <tr
                                v-for="(staff, sIdx) in paginatedStaff"
                                :key="staff.id"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
                            >
                                <!-- NO -->
                                <td
                                    class="w-14 px-6 py-4 text-center align-middle font-bold text-gray-400 dark:text-slate-500"
                                >
                                    {{
                                        (staffCurrentPage - 1) * staffPerPage +
                                        sIdx +
                                        1
                                    }}
                                </td>

                                <!-- PROFIL PENGUJI -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="getUserPhoto(staff.user || staff) && !imageErrorMap[staff.userId]"
                                            :src="getUserPhoto(staff.user || staff)!"
                                            @error="imageErrorMap[staff.userId] = true"
                                            class="h-9 w-9 shrink-0 rounded-full border border-gray-100 object-cover shadow-2xs dark:border-slate-700"
                                            alt="Foto Penguji"
                                        />
                                        <div
                                            v-else
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-100 font-mono text-xs font-bold text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                        >
                                            {{
                                                (staff.name || 'P')
                                                    .substring(0, 2)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                            >
                                                {{ staff.name }}
                                            </p>
                                            <p
                                                class="mt-0.5 font-mono text-xs text-gray-400 dark:text-slate-500"
                                            >
                                                {{ staff.email || '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- ROLE / PERAN UJIAN -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p
                                            class="text-sm font-bold text-slate-800 dark:text-slate-100"
                                        >
                                            {{ staff.roleTitle }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs font-normal text-slate-400 dark:text-slate-500"
                                        >
                                            {{
                                                roleConfigs[staff.roleKey]
                                                    ?.subtitle
                                            }}
                                        </p>
                                    </div>
                                </td>

                                <!-- AKSI -->
                                <td
                                    class="px-6 py-4 text-right whitespace-nowrap"
                                >
                                    <button
                                        type="button"
                                        @click="
                                            removePengujiFromRole(
                                                staff.roleKey,
                                                staff.userId,
                                            )
                                        "
                                        class="inline-flex cursor-pointer items-center justify-center rounded-full p-2 text-gray-400 transition-colors hover:bg-rose-50 hover:text-rose-600 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:text-slate-400 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                        title="Hapus Penugasan"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer for Staff -->
                <div
                    v-if="allAssignedStaff.length > 0"
                    class="mt-auto flex flex-col items-center justify-between gap-4 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800/50"
                >
                    <div class="text-sm text-gray-500 dark:text-slate-400">
                        Menampilkan
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ staffPagination.from }}</span
                        >
                        sampai
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ staffPagination.to }}</span
                        >
                        dari
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ staffPagination.total }}</span
                        >
                        entri
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="staffCurrentPage > 1 && staffCurrentPage--"
                            :disabled="staffCurrentPage === 1"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                staffCurrentPage === 1
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            &laquo;
                        </button>
                        <button
                            v-for="page in staffTotalPages"
                            :key="'staff-page-' + page"
                            type="button"
                            @click="staffCurrentPage = page"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                page === staffCurrentPage
                                    ? 'border-primary bg-primary text-white dark:border-primary dark:bg-primary'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            {{ page }}
                        </button>
                        <button
                            type="button"
                            @click="
                                staffCurrentPage < staffTotalPages &&
                                staffCurrentPage++
                            "
                            :disabled="staffCurrentPage >= staffTotalPages"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                staffCurrentPage >= staffTotalPages
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            &raquo;
                        </button>
                    </div>
                </div>
            </div>

            <!-- CARD 3: DAFTAR CALON SANTRI UJIAN -->
            <div
                id="section-card-santri"
                class="flex flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Card Header -->
                <div
                    class="flex flex-col gap-3 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-6 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400"
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
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                            >
                                Daftar Calon Santri Ujian
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Total {{ selectedRecipients.length }} calon
                                santri yang terdaftar dalam kelompok ujian ini
                            </p>
                        </div>
                    </div>
                    <PrimaryButton
                        type="button"
                        @click="openAddCandidatesModal"
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
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>Tambah</span>
                    </PrimaryButton>
                </div>

                <!-- Empty State -->
                <div
                    v-if="selectedRecipients.length === 0"
                    class="p-10 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500"
                    >
                        <svg
                            class="h-7 w-7"
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
                    <h4
                        class="mt-4 text-base font-bold text-gray-800 dark:text-slate-200"
                    >
                        Belum Ada Calon Santri Ditambahkan
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Silakan klik tombol "Tambah" di pojok kanan atas
                        untuk memilih calon santri yang akan diuji.
                    </p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead>
                            <tr
                                class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                            >
                                <th class="w-14 px-6 py-3.5 text-center">NO</th>
                                <th class="px-6 py-3.5">PROFIL SANTRI</th>
                                <th class="px-6 py-3.5">CABANG</th>
                                <th class="px-6 py-3.5">JENJANG</th>
                                <th class="px-6 py-3.5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                        >
                            <tr
                                v-for="(santri, rIdx) in paginatedSantri"
                                :key="santri.id"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
                            >
                                <!-- NO -->
                                <td
                                    class="w-14 px-6 py-4 text-center align-middle font-bold text-gray-400 dark:text-slate-500"
                                >
                                    {{
                                        (santriCurrentPage - 1) *
                                            santriPerPage +
                                        rIdx +
                                        1
                                    }}
                                </td>

                                <!-- PROFIL SANTRI -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="getPendaftarPhoto(santri) && !imageErrorMap[santri.id]"
                                            :src="getPendaftarPhoto(santri)!"
                                            @error="imageErrorMap[santri.id] = true"
                                            class="h-9 w-9 shrink-0 rounded-full border border-gray-200 object-cover dark:border-slate-700"
                                        />
                                        <div
                                            v-else
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-100 font-mono text-xs font-bold text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300"
                                        >
                                            {{
                                                (santri.nama || 'S')
                                                    .substring(0, 2)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                            >
                                                {{ santri.nama }}
                                            </p>
                                            <p
                                                class="mt-0.5 font-mono text-xs text-gray-400 dark:text-slate-500"
                                            >
                                                NIK: {{ santri.nik || '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- CABANG -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-sm font-bold text-slate-800 dark:text-slate-100"
                                    >
                                        {{ santri.cabang?.name || '-' }}
                                    </span>
                                </td>

                                <!-- JENJANG -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{
                                            santri.jenjang?.name ||
                                            santri.jenjang?.singkatan ||
                                            '-'
                                        }}
                                    </span>
                                </td>

                                <!-- AKSI -->
                                <td
                                    class="px-6 py-4 text-right whitespace-nowrap"
                                >
                                    <button
                                        type="button"
                                        @click="
                                            removeSantriFromRecipients(
                                                santri.id,
                                            )
                                        "
                                        class="inline-flex cursor-pointer items-center justify-center rounded-full p-2 text-gray-400 transition-colors hover:bg-rose-50 hover:text-rose-600 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:text-slate-400 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                        title="Hapus dari kelompok"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer for Santri -->
                <div
                    v-if="selectedRecipients.length > 0"
                    class="mt-auto flex flex-col items-center justify-between gap-4 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800/50"
                >
                    <div class="text-sm text-gray-500 dark:text-slate-400">
                        Menampilkan
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ santriPagination.from }}</span
                        >
                        sampai
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ santriPagination.to }}</span
                        >
                        dari
                        <span
                            class="font-semibold text-gray-900 dark:text-slate-100"
                            >{{ santriPagination.total }}</span
                        >
                        entri
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="
                                santriCurrentPage > 1 && santriCurrentPage--
                            "
                            :disabled="santriCurrentPage === 1"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                santriCurrentPage === 1
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            &laquo;
                        </button>
                        <button
                            v-for="page in santriTotalPages"
                            :key="'santri-page-' + page"
                            type="button"
                            @click="santriCurrentPage = page"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                page === santriCurrentPage
                                    ? 'border-primary bg-primary text-white dark:border-primary dark:bg-primary'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            {{ page }}
                        </button>
                        <button
                            type="button"
                            @click="
                                santriCurrentPage < santriTotalPages &&
                                santriCurrentPage++
                            "
                            :disabled="santriCurrentPage >= santriTotalPages"
                            class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                            :class="
                                santriCurrentPage >= santriTotalPages
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
                                    : 'cursor-pointer border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                            "
                        >
                            &raquo;
                        </button>
                    </div>
                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-3 pt-4 pb-12">
                <SecondaryButton type="button" @click="router.get(backUrl)"
                    >Batal</SecondaryButton
                >
                <PrimaryButton
                    type="submit"
                    :class="{ 'opacity-25': scheduleForm.processing }"
                    :disabled="
                        scheduleForm.processing ||
                        selectedRecipients.length === 0
                    "
                >
                    <svg
                        v-if="scheduleForm.processing"
                        class="mr-2 h-4 w-4 animate-spin"
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
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        ></path>
                    </svg>
                    Simpan Perubahan
                </PrimaryButton>
            </div>
        </form>

        <!-- MODAL PILIH SANTRI -->
        <Modal
            :show="isAddCandidatesModalOpen"
            @close="closeAddCandidatesModal"
            maxWidth="2xl"
            title="Pilih Calon Santri"
            description="Pilih calon santri untuk dimasukkan ke dalam kelompok ujian"
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
                            <!-- Search Input -->
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
                                    v-model="candidateSearch"
                                    placeholder="Cari data..."
                                    class="block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 py-2 pr-3 pl-8.5 text-xs font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-800 dark:focus:ring-blue-500/20"
                                />
                            </div>

                            <!-- Filter Trigger Button -->
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

                    <!-- Table with horizontal scroll -->
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
                                        class="min-w-[140px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        JENJANG
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                            >
                                <tr
                                    v-for="c in paginatedModalCandidates"
                                    :key="c.id"
                                    @click="
                                        toggleCandidateSelection(
                                            c.id,
                                            !selectedCandidatesToAdd.includes(
                                                c.id,
                                            ),
                                        )
                                    "
                                    class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50"
                                    :class="{
                                        'bg-primary/5 dark:bg-primary/15':
                                            selectedCandidatesToAdd.includes(
                                                c.id,
                                            ),
                                    }"
                                >
                                    <!-- Checkbox -->
                                    <td class="w-10 px-4 py-3" @click.stop>
                                        <div class="flex items-center">
                                            <Checkbox
                                                :checked="
                                                    selectedCandidatesToAdd.includes(
                                                        c.id,
                                                    )
                                                "
                                                @update:checked="
                                                    (val) =>
                                                        toggleCandidateSelection(
                                                            c.id,
                                                            val as boolean,
                                                        )
                                                "
                                            />
                                        </div>
                                    </td>

                                    <!-- PENDAFTAR -->
                                    <td class="min-w-[240px] px-4 py-3">
                                        <div class="min-w-0">
                                            <div
                                                class="text-xs font-bold text-slate-800 dark:text-slate-100"
                                            >
                                                {{ c.nama }}
                                            </div>
                                            <div
                                                class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-slate-400 dark:text-slate-500"
                                            >
                                                <span
                                                    v-if="
                                                        c.nomor_pendaftaran
                                                    "
                                                    class="font-mono"
                                                >
                                                    No:
                                                    {{
                                                        c.nomor_pendaftaran
                                                    }}
                                                </span>
                                                <span
                                                    v-if="c.nik"
                                                    class="font-mono"
                                                >
                                                    NIK: {{ c.nik }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- CABANG -->
                                    <td
                                        class="min-w-[140px] px-4 py-3 whitespace-nowrap"
                                    >
                                        <span
                                            class="text-xs font-medium text-slate-700 dark:text-slate-300"
                                        >
                                            {{ c.cabang?.name || '-' }}
                                        </span>
                                    </td>

                                    <!-- JENJANG -->
                                    <td
                                        class="min-w-[140px] px-4 py-3 whitespace-nowrap"
                                    >
                                        <span
                                            class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{
                                                c.jenjang?.name ||
                                                c.jenjang?.singkatan ||
                                                '-'
                                            }}
                                        </span>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        filteredAvailableCandidates.length === 0
                                    "
                                >
                                    <td colspan="4" class="px-4 py-10">
                                        <div
                                            class="flex flex-col items-center justify-center text-gray-500 dark:text-slate-400"
                                        >
                                            <svg
                                                class="mb-3 h-10 w-10 text-gray-300 dark:text-slate-600"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                                />
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-slate-200"
                                                >Tidak ada data yang
                                                ditemukan.</span
                                            >
                                            <p
                                                class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500"
                                            >
                                                Coba ubah kata kunci atau filter
                                                Anda.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div
                        v-if="filteredAvailableCandidates.length > 0"
                        class="mt-auto flex flex-col items-center justify-between gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-3 sm:flex-row sm:p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                    >
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

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="modalCandidateCurrentPage <= 1"
                                @click="modalCandidateCurrentPage--"
                                class="flex min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                &laquo;
                            </button>
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
                    @click="closeAddCandidatesModal"
                    class="w-full justify-center sm:w-auto"
                >
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    @click="confirmAddCandidates"
                    :disabled="selectedCandidatesToAdd.length === 0"
                    class="w-full justify-center sm:w-auto"
                >
                    Tambahkan ({{ selectedCandidatesToAdd.length }} Santri)
                </PrimaryButton>
            </template>
        </Modal>

        <!-- MODAL PILIH TIM PENGUJI / KOORDINATOR PSB -->
        <Modal
            :show="isRoleModalOpen"
            @close="closeRoleModal"
            maxWidth="2xl"
            :title="`Pilih ${roleConfigs[activeRoleForModal].title}`"
            :description="roleConfigs[activeRoleForModal].subtitle"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl"
                    :class="roleConfigs[activeRoleForModal].themeIconBg"
                >
                    <svg
                        class="h-6 w-6"
                        :class="roleConfigs[activeRoleForModal].themeIconColor"
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
            </template>

            <div class="space-y-4">
                <!-- Role Tabs inside Modal -->
                <div
                    class="flex items-center gap-1.5 overflow-x-auto border-b border-gray-100 pb-2 dark:border-slate-800"
                >
                    <button
                        v-for="rKey in (['interview', 'tes_membaca', 'tes_menulis', 'tes_hafalan', 'koordinator'] as RoleKey[])"
                        :key="'modal-tab-' + rKey"
                        type="button"
                        @click="switchModalRoleTab(rKey)"
                        class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold whitespace-nowrap transition-all"
                        :class="
                            activeRoleForModal === rKey
                                ? 'bg-primary text-white shadow-2xs dark:bg-blue-600'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-800'
                        "
                    >
                        <span>{{ roleConfigs[rKey].shortTitle }}</span>
                        <span
                            class="flex h-4.5 w-4.5 items-center justify-center rounded-full text-[10px] font-extrabold"
                            :class="
                                activeRoleForModal === rKey
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-200 text-gray-700 dark:bg-slate-700 dark:text-slate-300'
                            "
                        >
                            {{
                                (scheduleForm[roleConfigs[rKey].formKey] || [])
                                    .length
                            }}
                        </span>
                    </button>
                </div>

                <!-- Pegawai DataTable -->
                <div
                    class="flex flex-col rounded-2xl border border-gray-100 bg-white shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900"
                >
                    <!-- Header Action Bar (Search) -->
                    <div
                        class="relative z-20 flex flex-col items-center justify-between gap-3 rounded-t-2xl border-b border-gray-100 bg-white p-3.5 sm:flex-row sm:p-4 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="flex w-full flex-row items-center gap-3 sm:w-auto"
                        >
                            <div
                                class="group relative flex-1 sm:w-60 sm:flex-none lg:w-68"
                            >
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
                                    v-model="roleUserSearch"
                                    placeholder="Cari data..."
                                    class="block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 py-2 pr-3 pl-8.5 text-xs font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-800 dark:focus:ring-blue-500/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Table with horizontal scroll -->
                    <div class="relative overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-xs">
                            <thead
                                class="sticky top-0 z-10 border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="w-12 px-4 py-3 text-center">
                                        PILIH
                                    </th>
                                    <th
                                        class="min-w-[260px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        PEGAWAI
                                    </th>
                                    <th
                                        class="min-w-[160px] px-4 py-3 font-bold tracking-wider"
                                    >
                                        JABATAN
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-slate-800"
                            >
                                <tr
                                    v-for="row in paginatedModalStaff"
                                    :key="'modal-pegawai-' + row.id"
                                    class="group cursor-pointer bg-white transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                                    :class="{
                                        'bg-primary/5 hover:bg-primary/10 dark:bg-primary/15':
                                            selectedRoleUsersToAdd.includes(
                                                row.id,
                                            ),
                                    }"
                                    @click="
                                        toggleRoleUserSelection(
                                            row.id,
                                            !selectedRoleUsersToAdd.includes(
                                                row.id,
                                            ),
                                        )
                                    "
                                >
                                    <!-- Radio / Single Selection -->
                                    <td
                                        class="w-12 px-4 py-3 text-center whitespace-nowrap"
                                        @click.stop
                                    >
                                        <div class="flex items-center justify-center">
                                            <div
                                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full border transition-all"
                                                :class="
                                                    selectedRoleUsersToAdd.includes(row.id)
                                                        ? 'border-primary bg-primary text-white dark:border-blue-500 dark:bg-blue-600'
                                                        : 'border-gray-300 bg-white group-hover:border-gray-400 dark:border-slate-600 dark:bg-slate-800'
                                                "
                                                @click="
                                                    toggleRoleUserSelection(
                                                        row.id,
                                                        !selectedRoleUsersToAdd.includes(row.id),
                                                    )
                                                "
                                            >
                                                <div
                                                    v-if="selectedRoleUsersToAdd.includes(row.id)"
                                                    class="h-2 w-2 rounded-full bg-white"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- PEGAWAI -->
                                    <td
                                        class="min-w-[260px] px-4 py-3 whitespace-nowrap"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-xs font-bold text-slate-800 dark:text-slate-100"
                                            >
                                                {{ row.name }}
                                            </p>
                                            <p
                                                class="mt-0.5 font-mono text-[11px] text-slate-400 dark:text-slate-500"
                                            >
                                                {{ row.email || row.nik || '-' }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- JABATAN -->
                                    <td
                                        class="min-w-[160px] px-4 py-3 whitespace-nowrap text-gray-700 dark:text-slate-200"
                                    >
                                        <div
                                            class="text-xs font-medium text-slate-700 dark:text-slate-300"
                                        >
                                            {{
                                                row.roles && row.roles.length
                                                    ? row.roles[0].name
                                                    : '-'
                                            }}
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        filteredAvailableRoleUsers.length === 0
                                    "
                                >
                                    <td colspan="3" class="px-4 py-10">
                                        <div
                                            class="flex flex-col items-center justify-center text-gray-500 dark:text-slate-400"
                                        >
                                            <svg
                                                class="mb-3 h-10 w-10 text-gray-300 dark:text-slate-600"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                                />
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-slate-200"
                                                >Tidak ada data yang
                                                ditemukan.</span
                                            >
                                            <p
                                                class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500"
                                            >
                                                Coba ubah kata kunci pencarian
                                                Anda.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div
                        v-if="filteredAvailableRoleUsers.length > 0"
                        class="mt-auto flex flex-col items-center justify-between gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-3 sm:flex-row sm:p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                    >
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            Menampilkan
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalStaffPagination.from }}</span
                            >
                            sampai
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalStaffPagination.to }}</span
                            >
                            dari
                            <span
                                class="font-semibold text-gray-900 dark:text-slate-100"
                                >{{ modalStaffPagination.total }}</span
                            >
                            entri
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="modalStaffCurrentPage <= 1"
                                @click="modalStaffCurrentPage--"
                                class="flex min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                &laquo;
                            </button>
                            <button
                                v-for="p in modalStaffTotalPages"
                                :key="'staff-page-' + p"
                                type="button"
                                @click="modalStaffCurrentPage = p"
                                class="flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors"
                                :class="
                                    modalStaffCurrentPage === p
                                        ? 'border-primary bg-primary text-white shadow-2xs dark:border-blue-600 dark:bg-blue-600'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                                "
                            >
                                {{ p }}
                            </button>
                            <button
                                type="button"
                                :disabled="
                                    modalStaffCurrentPage >=
                                    modalStaffTotalPages
                                "
                                @click="modalStaffCurrentPage++"
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
                    @click="closeRoleModal"
                    class="w-full justify-center sm:w-auto"
                >
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    @click="confirmAddRoleUsers"
                    :disabled="selectedRoleUsersToAdd.length === 0"
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
            <!-- 1. Jenjang Pendidikan -->
            <div>
                <label
                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                >
                    Jenjang Pendidikan
                </label>
                <CustomSelect
                    v-model="candidateFilterForm.jenjang_id"
                    :options="
                        (props.jenjangs || []).map((j) => ({
                            value: j.id,
                            label: `${j.name} (${j.code || j.singkatan || ''})`,
                        }))
                    "
                    placeholder="Semua Jenjang"
                />
            </div>

            <!-- 2. Cabang Pendaftaran -->
            <div>
                <label
                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                >
                    Cabang Pendaftaran
                </label>
                <CustomSelect
                    v-model="candidateFilterForm.cabang_id"
                    :options="
                        (props.cabangs || []).map((c) => ({
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

        <!-- NOTIFY / VALIDATION MODAL -->
        <Modal
            :show="isNotifyModalOpen"
            @close="closeNotifyModal"
            maxWidth="lg"
            :title="notifyModalData.title"
            :description="notifyModalData.subtitle"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl"
                    :class="
                        notifyModalData.type === 'error'
                            ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400'
                            : 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400'
                    "
                >
                    <svg
                        v-if="notifyModalData.type === 'error'"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <svg
                        v-else
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

            <div class="space-y-2.5">
                <div
                    v-for="(item, idx) in notifyModalData.items"
                    :key="'notify-item-' + idx"
                    class="flex items-start gap-3 rounded-2xl border p-3.5 transition-colors"
                    :class="
                        notifyModalData.type === 'error'
                            ? 'border-rose-100 bg-rose-50/50 dark:border-rose-950/60 dark:bg-rose-950/20'
                            : 'border-amber-100 bg-amber-50/50 dark:border-amber-950/60 dark:bg-amber-950/20'
                    "
                >
                    <div
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                        :class="
                            notifyModalData.type === 'error'
                                ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-400'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                        "
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
                                stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span
                            v-if="item.category"
                            class="inline-block rounded-md border px-2 py-0.5 text-[10px] font-extrabold tracking-wider uppercase"
                            :class="
                                notifyModalData.type === 'error'
                                    ? 'border-rose-200 bg-white text-rose-700 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-300'
                                    : 'border-amber-200 bg-white text-amber-800 dark:border-amber-800 dark:bg-slate-900 dark:text-amber-300'
                            "
                        >
                            {{ item.category }}
                        </span>
                        <p
                            class="text-xs font-semibold text-slate-800 dark:text-slate-200"
                            :class="item.category ? 'mt-1' : ''"
                        >
                            {{ item.text }}
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex w-full justify-end">
                    <PrimaryButton
                        type="button"
                        @click="closeNotifyModal"
                        class="w-full sm:w-auto font-bold"
                    >
                        <span>Saya Mengerti, Lengkapi Data</span>
                    </PrimaryButton>
                </div>
            </template>
        </Modal>
    </div>
</template>
