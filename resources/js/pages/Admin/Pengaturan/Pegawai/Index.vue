<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    index,
    destroy,
    bulkDelete,
    resetPassword,
    updateRole,
    toggleStatus,
    importPage,
    exportTemplate,
    create,
    edit,
    show,
} from '@/routes/admin/pengaturan/pegawai';

const props = defineProps<{
    pegawais: any;
    roles: Array<{ id: string; name: string }>;
    cabangs?: Array<{ id: string; name: string; singkatan?: string }>;
    jenjangs?: Array<{ id: string; name: string; code?: string; singkatan?: string }>;
    filters: Record<string, string>;
}>();

const columns = [
    { key: 'name', label: 'PEGAWAI' },
    { key: 'kontak', label: 'KONTAK' },
    { key: 'gender', label: 'GENDER' },
    { key: 'roles', label: 'JABATAN' },
    { key: 'status', label: 'STATUS' },
];

const selectedRows = ref<string[]>([]);
const deleteModal = ref(false);
const bulkDeleteModal = ref(false);
const resetPasswordModal = ref(false);
const roleModal = ref(false);
const dataPermissionModal = ref(false);

const selectedPegawai = ref<any>(null);

const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const roleForm = useForm({
    role: '',
});

const dataPermissionForm = useForm({
    allowed_gender: 'ALL',
    allowed_cabang_ids: [] as string[],
    allowed_jenjang_ids: [] as string[],
});

const filterForm = useForm({
    role: props.filters.role || '',
    status: props.filters.status || '',
    gender: props.filters.gender || '',
});

const isFilterModalOpen = ref(false);

const isFilterActive = computed(() => {
    return filterForm.role || filterForm.status || filterForm.gender;
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: props.filters.search || '',
            limit: props.pegawais?.per_page || 10,
            role: filterForm.role,
            status: filterForm.status,
            gender: filterForm.gender,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.role = '';
    filterForm.status = '';
    filterForm.gender = '';
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: props.filters.search || '',
            limit: props.pegawais?.per_page || 10,
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
            index.url(),
            {
                search,
                status: props.filters.status,
                role: props.filters.role,
                gender: props.filters.gender,
                limit: props.pegawais?.per_page || 10,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
};

const handleLimit = (limit: number) => {
    router.get(
        index.url(),
        {
            limit,
            search: props.filters.search,
            status: props.filters.status,
            role: props.filters.role,
            gender: props.filters.gender,
        },
        { preserveState: true, replace: true },
    );
};

const handleSelection = (ids: string[]) => {
    selectedRows.value = ids;
};

const confirmDelete = (pegawai: any) => {
    selectedPegawai.value = pegawai;
    deleteModal.value = true;
};

const confirmBulkDelete = () => {
    if (selectedRows.value.length === 0) {
        return;
    }

    bulkDeleteModal.value = true;
};

const submitDelete = () => {
    router.delete(destroy.url(selectedPegawai.value.id), {
        onSuccess: () => (deleteModal.value = false),
    });
};

const submitBulkDelete = () => {
    router.post(
        bulkDelete.url(),
        { ids: selectedRows.value },
        {
            onSuccess: () => {
                bulkDeleteModal.value = false;
                selectedRows.value = [];
            },
        },
    );
};

const openResetPassword = (pegawai: any) => {
    selectedPegawai.value = pegawai;
    // Default password = TGL Lahir (DDMMYYYY) if exists, else default 12345678
    let defaultPass = '12345678';

    if (pegawai.tanggal_lahir) {
        const d = new Date(pegawai.tanggal_lahir);
        defaultPass =
            String(d.getDate()).padStart(2, '0') +
            String(d.getMonth() + 1).padStart(2, '0') +
            d.getFullYear();
    }

    resetForm.clearErrors();
    resetForm.password = defaultPass;
    resetForm.password_confirmation = defaultPass;
    resetPasswordModal.value = true;
};

const submitResetPassword = () => {
    resetForm.post(resetPassword.url(selectedPegawai.value.id), {
        onSuccess: () => (resetPasswordModal.value = false),
    });
};

const openRoleModal = (pegawai: any) => {
    selectedPegawai.value = pegawai;
    roleForm.clearErrors();
    roleForm.role = pegawai.roles?.[0]?.name || '';
    roleModal.value = true;
};

const submitRole = () => {
    roleForm.post(updateRole.url(selectedPegawai.value.id), {
        onSuccess: () => (roleModal.value = false),
    });
};

const openDataPermissionModal = (pegawai: any) => {
    selectedPegawai.value = pegawai;
    dataPermissionForm.clearErrors();
    dataPermissionForm.allowed_gender = pegawai.allowed_gender || 'ALL';
    dataPermissionForm.allowed_cabang_ids = Array.isArray(pegawai.allowed_cabang_ids) ? [...pegawai.allowed_cabang_ids] : [];
    dataPermissionForm.allowed_jenjang_ids = Array.isArray(pegawai.allowed_jenjang_ids) ? [...pegawai.allowed_jenjang_ids] : [];
    dataPermissionModal.value = true;
};

const toggleCabangPermission = (cabangId: string) => {
    const idx = dataPermissionForm.allowed_cabang_ids.indexOf(cabangId);
    if (idx > -1) {
        dataPermissionForm.allowed_cabang_ids.splice(idx, 1);
    } else {
        dataPermissionForm.allowed_cabang_ids.push(cabangId);
    }
};

const toggleAllCabangPermission = () => {
    const all = (props.cabangs || []).map((c) => c.id);
    if (dataPermissionForm.allowed_cabang_ids.length === all.length) {
        dataPermissionForm.allowed_cabang_ids = [];
    } else {
        dataPermissionForm.allowed_cabang_ids = [...all];
    }
};

const toggleJenjangPermission = (jenjangId: string) => {
    const idx = dataPermissionForm.allowed_jenjang_ids.indexOf(jenjangId);
    if (idx > -1) {
        dataPermissionForm.allowed_jenjang_ids.splice(idx, 1);
    } else {
        dataPermissionForm.allowed_jenjang_ids.push(jenjangId);
    }
};

const toggleAllJenjangPermission = () => {
    const all = (props.jenjangs || []).map((j) => j.id);
    if (dataPermissionForm.allowed_jenjang_ids.length === all.length) {
        dataPermissionForm.allowed_jenjang_ids = [];
    } else {
        dataPermissionForm.allowed_jenjang_ids = [...all];
    }
};

const submitDataPermission = () => {
    dataPermissionForm.post(`/admin/pengaturan/pegawai/${selectedPegawai.value.id}/permission-data`, {
        onSuccess: () => {
            dataPermissionModal.value = false;
        },
    });
};

const handleToggleStatus = (pegawai: any) => {
    router.post(toggleStatus.url(pegawai.id));
};

const executeExport = (ids: string[] = []) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/pengaturan/pegawai/export';

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

const isSuperAdmin = (row: any) => {
    return row.roles?.some((r: any) => r.name === 'Super Admin');
};

const getDetailUrl = (id: string, isEdit = false) => {
    let currentUrl = '/admin/pengaturan/pegawai';

    if (typeof window !== 'undefined') {
        currentUrl += window.location.search;
    }

    const targetUrl = isEdit ? edit.url(id) : show.url(id);

    return `${targetUrl}?from=${encodeURIComponent(currentUrl)}`;
};
</script>

<template>
    <div class="w-full">
        <Head title="Manajemen Pegawai" />

        <!-- Page Header & Actions -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Pegawai & Staf
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola data pegawai, hak akses, dan status keaktifan.
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
                        <DropdownLink :href="importPage.url()">
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
                            :href="exportTemplate.url()"
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
                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
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

                <Link :href="create.url()">
                    <PrimaryButton class="h-full">
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
                        Tambah Pegawai
                    </PrimaryButton>
                </Link>
            </div>
        </div>

        <div class="mt-6">
            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="pegawais.data"
                :pagination="pegawais"
                :selectable="true"
                :disable-selection="isSuperAdmin"
                @search="handleSearch"
                @limit="handleLimit"
                @selection-change="handleSelection"
            >
                <template #bulk-actions="{ selectedIds }">
                    <div v-if="selectedIds.length > 0" class="flex gap-2">
                        <button
                            @click="handleExportSelected(selectedIds)"
                            class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                            @click="confirmBulkDelete"
                            class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-bold text-rose-700 shadow-sm transition-all hover:bg-rose-100 focus:ring-2 focus:ring-rose-500/20 focus:outline-none sm:px-4 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/50"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4"
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

                <template #filters>
                    <!-- Trigger Button -->
                    <button
                        @click="isFilterModalOpen = true"
                        class="group inline-flex h-full items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                            class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2 dark:bg-blue-400"
                        ></span>
                    </button>

                    <!-- Filter Modal -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Data Pegawai"
                        description="Saring data berdasarkan jabatan atau status"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Jabatan (Role)</label
                            >
                            <CustomSelect
                                v-model="filterForm.role"
                                :options="roles.map((r) => r.name)"
                                placeholder="Semua Jabatan"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Gender</label
                            >
                            <CustomSelect
                                v-model="filterForm.gender"
                                :options="['Laki-laki', 'Perempuan']"
                                placeholder="Semua Gender"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Status</label
                            >
                            <CustomSelect
                                v-model="filterForm.status"
                                :options="['aktif', 'nonaktif']"
                                placeholder="Semua Status"
                            />
                        </div>
                    </FilterModal>
                </template>
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="row.foto"
                            :src="`/storage/${row.foto}`"
                            class="h-11 w-11 rounded-full border border-gray-100 object-cover shadow-sm dark:border-slate-700 dark:border-slate-800"
                        />
                        <div
                            v-else
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-blue-200 bg-blue-100 text-lg font-bold text-blue-700 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <p
                                class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ row.name }}
                            </p>
                            <p
                                class="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400"
                            >
                                NIK: {{ row.nik || '-' }}
                            </p>
                        </div>
                    </div>
                </template>

                <template #cell-kontak="{ row }">
                    <div class="flex flex-col gap-1.5 text-[13px]">
                        <div
                            class="flex items-center text-slate-600 dark:text-slate-300"
                        >
                            <svg
                                class="mr-2 h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            <span class="max-w-50 truncate">{{
                                row.email
                            }}</span>
                        </div>
                        <div
                            class="flex items-center text-slate-600 dark:text-slate-300"
                        >
                            <svg
                                class="mr-2 h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                />
                            </svg>
                            {{ row.nomor_hp || '-' }}
                        </div>
                    </div>
                </template>

                <template #cell-gender="{ row }">
                    <div
                        v-if="row.gender === 'Laki-Laki'"
                        class="flex items-center text-[13px] font-medium text-blue-600 dark:text-blue-400"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 8v-4h-4M16 4l-5 5"
                            />
                            <circle cx="9" cy="13" r="5" stroke-width="2" />
                        </svg>
                        Laki-Laki
                    </div>
                    <div
                        v-else-if="row.gender === 'Perempuan'"
                        class="flex items-center text-[13px] font-medium text-pink-600 dark:text-pink-400"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v6m-3-3h6"
                            />
                            <circle cx="12" cy="9" r="6" stroke-width="2" />
                        </svg>
                        Perempuan
                    </div>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                        >-</span
                    >
                </template>

                <template #cell-roles="{ row }">
                    <div
                        class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                    >
                        {{ row.roles?.length ? row.roles[0].name : '-' }}
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span
                        v-if="row.is_active"
                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                    >
                        <span
                            class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"
                        ></span>
                        Aktif
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                    >
                        <span
                            class="mr-1.5 h-1.5 w-1.5 rounded-full bg-slate-400"
                        ></span>
                        Nonaktif
                    </span>
                </template>

                <!-- Row Actions -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
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
                                <Link
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    :href="getDetailUrl(row.id, true)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-emerald-500 dark:text-emerald-400"
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
                                </Link>
                                <Link
                                    :href="getDetailUrl(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-sky-500 dark:text-sky-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    Detail
                                </Link>
                                <button
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    @click="openRoleModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                        />
                                    </svg>
                                    Akses
                                </button>
                                <button
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    @click="openDataPermissionModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-purple-500 dark:text-purple-400"
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
                                    Izin Data
                                </button>
                                <button
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    @click="openResetPassword(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-orange-500 dark:text-orange-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                        />
                                    </svg>
                                    Sandi
                                </button>
                                <button
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    @click="handleToggleStatus(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        v-if="!row.is_active"
                                        class="mr-3 h-4 w-4 text-emerald-500 dark:text-emerald-400"
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
                                    <svg
                                        v-else
                                        class="mr-3 h-4 w-4 text-gray-400 dark:text-slate-500"
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
                                    {{
                                        row.is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'
                                    }}
                                </button>
                                <button
                                    v-if="
                                        !row.roles?.some(
                                            (r: any) =>
                                                r.name === 'Super Admin',
                                        )
                                    "
                                    @click="confirmDelete(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
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

        <!-- Role Modal Form -->
        <Modal
            :show="roleModal"
            @close="roleModal = false"
            maxWidth="sm"
            title="Akses Pegawai"
            description="Ubah hak akses"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950/50"
                >
                    <svg
                        class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </div>
            </template>

            <div
                class="mb-5 rounded-xl border border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800"
            >
                <p class="mb-1 text-xs text-gray-500 dark:text-slate-400">
                    Pegawai Terpilih:
                </p>
                <p
                    class="text-base font-bold text-gray-900 dark:text-slate-100"
                >
                    {{ selectedPegawai?.name }}
                </p>
            </div>

            <form @submit.prevent="submitRole" class="space-y-6">
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300"
                        >Pilih Akses / Role</label
                    >
                    <CustomSelect
                        v-model="roleForm.role"
                        :options="
                            roles
                                .map((r) => r.name)
                                .filter((n) => n !== 'Super Admin')
                        "
                        placeholder="-- Tanpa Akses --"
                    />
                    <p
                        v-if="roleForm.errors.role"
                        class="mt-1.5 text-sm text-rose-500 dark:text-rose-400"
                    >
                        {{ roleForm.errors.role }}
                    </p>
                </div>
            </form>

            <template #footer>
                <SecondaryButton @click="roleModal = false"
                    >Batal</SecondaryButton
                >
                <PrimaryButton
                    @click="submitRole"
                    :class="{ 'opacity-25': roleForm.processing }"
                    :disabled="roleForm.processing"
                >
                    Simpan Akses
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Izin Manajemen Data -->
        <Modal
            :show="dataPermissionModal"
            @close="dataPermissionModal = false"
            maxWidth="xl"
            title="Izin Manajemen Data"
            description="Batasi akses data pendaftar yang dapat dilihat & dikelola oleh staf ini."
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
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                </div>
            </template>

            <div
                class="mb-5 rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/50"
            >
                <p class="text-xs text-gray-500 dark:text-slate-400">
                    Pegawai: <strong class="text-gray-900 dark:text-slate-100">{{ selectedPegawai?.name }}</strong>
                    <span v-if="selectedPegawai?.nik"> • NIK: {{ selectedPegawai.nik }}</span>
                </p>
            </div>

            <form @submit.prevent="submitDataPermission" class="space-y-6">
                <!-- 1. Filter Gender Pendaftar -->
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-slate-200">
                        1. Izin Berdasarkan Jenis Kelamin Santri
                    </label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <button
                            type="button"
                            @click="dataPermissionForm.allowed_gender = 'ALL'"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-bold transition-all"
                            :class="
                                dataPermissionForm.allowed_gender === 'ALL' || !dataPermissionForm.allowed_gender
                                    ? 'border-primary/50 bg-primary/10 text-primary dark:border-blue-500 dark:bg-blue-950/50 dark:text-blue-300 shadow-2xs'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            "
                        >
                            <span>Semua Gender</span>
                        </button>
                        <button
                            type="button"
                            @click="dataPermissionForm.allowed_gender = 'Laki-Laki'"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-bold transition-all"
                            :class="
                                dataPermissionForm.allowed_gender === 'Laki-Laki'
                                    ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-950/60 dark:text-blue-300 shadow-2xs'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            "
                        >
                            <span>Hanya Laki-Laki</span>
                        </button>
                        <button
                            type="button"
                            @click="dataPermissionForm.allowed_gender = 'Perempuan'"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-bold transition-all"
                            :class="
                                dataPermissionForm.allowed_gender === 'Perempuan'
                                    ? 'border-rose-500 bg-rose-50 text-rose-700 dark:border-rose-500 dark:bg-rose-950/60 dark:text-rose-300 shadow-2xs'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            "
                        >
                            <span>Hanya Perempuan</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Filter Cabang Pendaftar -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-slate-200">
                            2. Izin Berdasarkan Cabang Pondok
                        </label>
                        <button
                            type="button"
                            @click="toggleAllCabangPermission"
                            class="text-xs font-bold text-primary hover:underline dark:text-blue-400"
                        >
                            {{ dataPermissionForm.allowed_cabang_ids.length === (props.cabangs || []).length ? 'Hapus Semua' : 'Pilih Semua' }}
                        </button>
                    </div>
                    <p class="mb-2 text-[11px] text-gray-500 dark:text-slate-400">
                        *Jika tidak ada yang dipilih, maka staf diizinkan mengakses semua cabang.
                    </p>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <label
                            v-for="cab in (props.cabangs || [])"
                            :key="cab.id"
                            @click.prevent="toggleCabangPermission(cab.id)"
                            class="flex cursor-pointer items-center space-x-2.5 rounded-xl border p-2.5 transition-all select-none text-xs font-bold"
                            :class="
                                dataPermissionForm.allowed_cabang_ids.includes(cab.id)
                                    ? 'border-primary/50 bg-primary/5 text-primary dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            "
                        >
                            <Checkbox :checked="dataPermissionForm.allowed_cabang_ids.includes(cab.id)" @update:checked="() => toggleCabangPermission(cab.id)" />
                            <span class="truncate">{{ cab.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- 3. Filter Jenjang Pendaftar -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-slate-200">
                            3. Izin Berdasarkan Jenjang Pendidikan
                        </label>
                        <button
                            type="button"
                            @click="toggleAllJenjangPermission"
                            class="text-xs font-bold text-primary hover:underline dark:text-blue-400"
                        >
                            {{ dataPermissionForm.allowed_jenjang_ids.length === (props.jenjangs || []).length ? 'Hapus Semua' : 'Pilih Semua' }}
                        </button>
                    </div>
                    <p class="mb-2 text-[11px] text-gray-500 dark:text-slate-400">
                        *Jika tidak ada yang dipilih, maka staf diizinkan mengakses semua jenjang.
                    </p>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <label
                            v-for="jjg in (props.jenjangs || [])"
                            :key="jjg.id"
                            @click.prevent="toggleJenjangPermission(jjg.id)"
                            class="flex cursor-pointer items-center space-x-2.5 rounded-xl border p-2.5 transition-all select-none text-xs font-bold"
                            :class="
                                dataPermissionForm.allowed_jenjang_ids.includes(jjg.id)
                                    ? 'border-primary/50 bg-primary/5 text-primary dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            "
                        >
                            <Checkbox :checked="dataPermissionForm.allowed_jenjang_ids.includes(jjg.id)" @update:checked="() => toggleJenjangPermission(jjg.id)" />
                            <span class="truncate">{{ jjg.name }}</span>
                        </label>
                    </div>
                </div>
            </form>

            <template #footer>
                <SecondaryButton @click="dataPermissionModal = false">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    @click="submitDataPermission"
                    :class="{ 'opacity-25': dataPermissionForm.processing }"
                    :disabled="dataPermissionForm.processing"
                >
                    Simpan Izin Data
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Reset Password Modal Form -->
        <Modal
            :show="resetPasswordModal"
            @close="resetPasswordModal = false"
            maxWidth="sm"
            title="Reset Password"
            description="Atur ulang sandi"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/50"
                >
                    <svg
                        class="h-6 w-6 text-amber-600 dark:text-amber-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                        />
                    </svg>
                </div>
            </template>

            <div
                class="mb-5 rounded-xl border border-amber-100 bg-amber-50/50 p-4 text-center dark:border-amber-900/50 dark:bg-amber-950/30"
            >
                <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">
                    Pegawai Terpilih:
                </p>
                <p
                    class="text-base font-bold text-slate-700 dark:text-slate-200"
                >
                    {{ selectedPegawai?.name }}
                </p>
            </div>

            <form @submit.prevent="submitResetPassword" class="space-y-4">
                <PasswordInput
                    v-model="resetForm.password"
                    label="Password Baru"
                    :error="resetForm.errors.password"
                    required
                />
                <PasswordInput
                    v-model="resetForm.password_confirmation"
                    label="Konfirmasi Password"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton @click="resetPasswordModal = false"
                    >Batal</SecondaryButton
                >
                <PrimaryButton
                    @click="submitResetPassword"
                    :class="{ 'opacity-25': resetForm.processing }"
                    :disabled="resetForm.processing"
                >
                    Reset Password
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Bulk Delete Modal -->
        <Modal
            :show="bulkDeleteModal"
            @close="bulkDeleteModal = false"
            maxWidth="sm"
            title="Hapus Terpilih?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-950/50"
                >
                    <svg
                        class="h-6 w-6 text-rose-600 dark:text-rose-400"
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
            <p class="mb-2 text-sm text-slate-600 dark:text-slate-300">
                Semua data pegawai yang dipilih beserta hak aksesnya akan
                dihapus permanen. Tindakan ini tidak dapat dibatalkan.
            </p>
            <template #footer>
                <SecondaryButton @click="bulkDeleteModal = false"
                    >Batal</SecondaryButton
                >
                <DangerButton @click="submitBulkDelete"
                    >Ya, Hapus Semua</DangerButton
                >
            </template>
        </Modal>

        <!-- Single Delete Modal -->
        <Modal
            :show="deleteModal"
            @close="deleteModal = false"
            maxWidth="sm"
            title="Hapus Pegawai?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-950/50"
                >
                    <svg
                        class="h-6 w-6 text-rose-600 dark:text-rose-400"
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
            <p class="mb-2 text-sm text-slate-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus
                <span class="font-bold text-slate-900 dark:text-slate-100"
                    >"{{ selectedPegawai?.name }}"</span
                >? Tindakan ini tidak dapat dibatalkan.
            </p>
            <template #footer>
                <SecondaryButton @click="deleteModal = false"
                    >Batal</SecondaryButton
                >
                <DangerButton @click="submitDelete">Ya, Hapus</DangerButton>
            </template>
        </Modal>
    </div>
</template>
