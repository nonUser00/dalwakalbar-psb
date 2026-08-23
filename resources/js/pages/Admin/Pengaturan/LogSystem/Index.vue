<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    index,
    destroy,
    bulkDelete as bulkDeleteAction,
    clear,
    exportMethod,
    show,
} from '@/routes/admin/pengaturan/log';

const props = defineProps<{
    logs: any;
    filters: any;
    modules: string[];
    events: string[];
    roles: string[];
}>();

const columns = [
    { key: 'created_at', label: 'Waktu' },
    { key: 'causer', label: 'Pengguna' },
    { key: 'role', label: 'Role' },
    { key: 'log_name', label: 'Modul' },
    { key: 'event', label: 'Aktivitas' },
    { key: 'description', label: 'Deskripsi' },
];

const selectedLogIds = ref<string[]>([]);
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const showClearModal = ref(false);
const logToDelete = ref<any>(null);

const isFilterModalOpen = ref(false);
const search = ref(props.filters.search || '');
const limit = ref(props.filters.limit || 10);

const filterForm = useForm({
    module: props.filters.module || '',
    action: props.filters.action || '',
    role: props.filters.role || '',
    date_start: props.filters.date_start || '',
    date_end: props.filters.date_end || '',
});

const isFilterActive = computed(() => {
    return (
        filterForm.module ||
        filterForm.action ||
        filterForm.role ||
        filterForm.date_start ||
        filterForm.date_end
    );
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: search.value,
            limit: limit.value,
            module: filterForm.module,
            action: filterForm.action,
            role: filterForm.role,
            date_start: filterForm.date_start,
            date_end: filterForm.date_end,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.module = '';
    filterForm.action = '';
    filterForm.role = '';
    filterForm.date_start = '';
    filterForm.date_end = '';
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: search.value,
            limit: limit.value,
        },
        { preserveState: true, replace: true },
    );
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const handleSearch = (val: string) => {
    search.value = val;

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const handleLimit = (val: number) => {
    limit.value = val;
    applyFilters();
};

const formatDate = (dateString: string) => {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const confirmDelete = (log: any) => {
    logToDelete.value = log;
    showDeleteModal.value = true;
};

const deleteLog = () => {
    if (logToDelete.value) {
        router.delete(destroy.url(logToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                logToDelete.value = null;
            },
        });
    }
};

const bulkDelete = () => {
    router.post(
        bulkDeleteAction.url(),
        { ids: selectedLogIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showBulkDeleteModal.value = false;
                selectedLogIds.value = [];
            },
        },
    );
};

const clearAll = () => {
    router.post(
        clear.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showClearModal.value = false;
            },
        },
    );
};

const exportLogs = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = exportMethod.url();

    // Add CSRF token
    const csrfToken = document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);
    }

    // Add filter parameters
    const addInput = (name: string, value: any) => {
        if (value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }
    };

    addInput('search', search.value);
    addInput('module', filterForm.module);
    addInput('action', filterForm.action);
    addInput('role', filterForm.role);
    addInput('date_start', filterForm.date_start);
    addInput('date_end', filterForm.date_end);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const exportSelected = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = exportMethod.url();

    // Add CSRF token
    const csrfToken = document.head
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);
    }

    selectedLogIds.value.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const getDetailUrl = (id: string) => {
    let currentUrl = '/admin/pengaturan/log';

    if (typeof window !== 'undefined') {
        currentUrl += window.location.search;
    }

    return `${show.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};
</script>

<template>
    <div class="w-full">
        <Head title="Log Aktivitas - Admin" />

        <!-- Page Header & Actions -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Log Aktivitas Sistem
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Pantau semua perubahan dan tindakan pengguna secara
                    realtime.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <SecondaryButton @click="exportLogs" class="h-full">
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
                    Export
                </SecondaryButton>
                <DangerButton @click="showClearModal = true" class="h-full">
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                    Kosongkan
                </DangerButton>
            </div>
        </div>

        <div class="mt-6">
            <!-- Data Table with Premium UI -->
            <DataTable
                :data="logs.data"
                :columns="columns"
                :pagination="logs"
                :selectable="true"
                @selection-change="selectedLogIds = $event"
                @search="handleSearch"
                @limit="handleLimit"
            >
                <template #bulk-actions="{ selectedIds }">
                    <div v-if="selectedIds.length > 0" class="flex gap-2">
                        <button
                            @click="exportSelected"
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
                            Ekspor
                        </button>
                        <button
                            @click="showBulkDeleteModal = true"
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
                        class="group inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                        title="Filter Log Aktivitas"
                        description="Saring data aktivitas"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >Tgl Mulai</label
                                >
                                <CustomDatePicker
                                    v-model="filterForm.date_start"
                                    placeholder="dd/mm/yyyy"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                    >Tgl Akhir</label
                                >
                                <CustomDatePicker
                                    v-model="filterForm.date_end"
                                    placeholder="dd/mm/yyyy"
                                />
                            </div>
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Modul</label
                            >
                            <CustomSelect
                                v-model="filterForm.module"
                                :options="modules"
                                placeholder="Semua Modul"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Aksi (Event)</label
                            >
                            <CustomSelect
                                v-model="filterForm.action"
                                :options="events"
                                placeholder="Semua Aksi"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >Role Pengguna</label
                            >
                            <CustomSelect
                                v-model="filterForm.role"
                                :options="roles"
                                placeholder="Semua Role"
                            />
                        </div>
                    </FilterModal>
                </template>

                <template #cell-created_at="{ row }">
                    <span
                        class="text-[13px] font-medium whitespace-nowrap text-slate-600 dark:text-slate-300"
                        >{{ formatDate(row.created_at) }}</span
                    >
                </template>
                <template #cell-causer="{ row }">
                    <div class="flex min-w-30 items-center">
                        <div v-if="row.causer" class="flex flex-col">
                            <p
                                class="text-[15px] leading-tight font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ row.causer.name || row.causer.nama }}
                            </p>
                            <span
                                class="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    row.causer.email ||
                                    row.causer.nomor_pendaftaran ||
                                    (row.causer_type && row.causer_type.includes('Pendaftar')
                                        ? 'Pendaftar'
                                        : 'Admin')
                                }}
                            </span>
                        </div>
                        <div
                            v-else-if="
                                row.causer_type &&
                                row.causer_type.includes('Pendaftar')
                            "
                            class="flex flex-col"
                        >
                            <p
                                class="text-[14px] leading-tight font-bold text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    row.properties?.nama ||
                                    row.properties?.pendaftar_nama ||
                                    'Pendaftar'
                                }}
                            </p>
                            <span
                                class="mt-0.5 text-[12px] text-slate-400 dark:text-slate-500"
                            >
                                {{
                                    row.properties?.nomor_pendaftaran ||
                                    '(Data Akun Terhapus)'
                                }}
                            </span>
                        </div>
                        <div
                            v-else-if="
                                row.causer_type &&
                                row.causer_type.includes('User')
                            "
                            class="flex flex-col"
                        >
                            <p
                                class="text-[14px] leading-tight font-bold text-slate-700 dark:text-slate-200"
                            >
                                {{ row.properties?.name || 'Pengguna / Admin' }}
                            </p>
                            <span
                                class="mt-0.5 text-[12px] text-slate-400 dark:text-slate-500"
                            >
                                (Akun Telah Dihapus)
                            </span>
                        </div>
                        <div v-else class="flex flex-col">
                            <span
                                class="text-[13px] font-semibold text-slate-600 dark:text-slate-300"
                                >Sistem Otomatis</span
                            >
                            <span
                                class="text-[11px] text-slate-400 dark:text-slate-500"
                                >Proses Latar / Cron</span
                            >
                        </div>
                    </div>
                </template>
                <template #cell-role="{ row }">
                    <div class="flex flex-wrap gap-1">
                        <template
                            v-if="
                                row.causer &&
                                row.causer.roles &&
                                row.causer.roles.length
                            "
                        >
                            <span
                                v-for="role in row.causer.roles"
                                :key="role.id"
                                class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                            >
                                {{ role.name
                                }}<span
                                    v-if="
                                        role.id !==
                                        row.causer.roles[
                                            row.causer.roles.length - 1
                                        ].id
                                    "
                                    >,
                                </span>
                            </span>
                        </template>
                        <template
                            v-else-if="
                                row.causer_type &&
                                row.causer_type.includes('Pendaftar')
                            "
                        >
                            <span
                                class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                            >
                                Pendaftar
                            </span>
                        </template>
                        <template
                            v-else-if="
                                row.causer_type &&
                                row.causer_type.includes('User')
                            "
                        >
                            <span
                                class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                            >
                                Pegawai
                            </span>
                        </template>
                        <span
                            v-else
                            class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                        >
                            Sistem
                        </span>
                    </div>
                </template>
                <template #cell-log_name="{ row }">
                    <span
                        class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                        >{{ row.log_name || '-' }}</span
                    >
                </template>
                <template #cell-event="{ row }">
                    <span
                        class="text-[13px] font-medium text-slate-700 capitalize dark:text-slate-300"
                    >
                        {{ row.event || 'Unknown' }}
                    </span>
                </template>
                <template #cell-description="{ row }">
                    <div
                        class="max-w-50 truncate text-[13px] font-medium text-slate-800 dark:text-slate-200"
                        :title="row.description"
                    >
                        {{ row.description }}
                    </div>
                </template>
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

        <!-- Delete Modal -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="sm"
            title="Hapus Log?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
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
                Apakah Anda yakin ingin menghapus log aktivitas ini secara
                permanen? Data yang dihapus tidak dapat dikembalikan.
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteModal = false"
                    class="w-full justify-center sm:w-auto"
                    >Batal
                </SecondaryButton>
                <DangerButton
                    @click="deleteLog"
                    class="w-full justify-center sm:w-auto"
                    >Ya, Hapus</DangerButton
                >
            </template>
        </Modal>

        <!-- Bulk Delete Modal -->
        <Modal
            :show="showBulkDeleteModal"
            @close="showBulkDeleteModal = false"
            maxWidth="sm"
            title="Hapus Terpilih?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
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
                Apakah Anda yakin ingin menghapus
                <strong class="text-rose-600 dark:text-rose-400">{{
                    selectedLogIds.length
                }}</strong>
                log yang dipilih secara permanen? Data yang dihapus tidak dapat
                dikembalikan.
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showBulkDeleteModal = false"
                    class="w-full justify-center sm:w-auto"
                    >Batal
                </SecondaryButton>
                <DangerButton
                    @click="bulkDelete"
                    class="w-full justify-center sm:w-auto"
                    >Ya, Hapus Terpilih
                </DangerButton>
            </template>
        </Modal>

        <!-- Clear All Modal -->
        <Modal
            :show="showClearModal"
            @close="showClearModal = false"
            maxWidth="sm"
            title="Kosongkan Log?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
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

            <div class="p-5">
                <div
                    class="mb-2 rounded-xl border border-rose-100 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/50"
                >
                    <p
                        class="text-sm leading-relaxed text-rose-800 dark:text-rose-300"
                    >
                        <strong>PERINGATAN KRITIS:</strong> Anda akan menghapus
                        <strong>SELURUH</strong> riwayat log aktivitas yang
                        tersimpan di sistem. Tindakan ini akan menghilangkan
                        jejak audit secara permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showClearModal = false"
                    class="w-full justify-center sm:w-auto"
                    >Batal
                </SecondaryButton>
                <DangerButton
                    @click="clearAll"
                    class="w-full justify-center sm:w-auto"
                    >Ya, Kosongkan Log</DangerButton
                >
            </template>
        </Modal>
    </div>
</template>
