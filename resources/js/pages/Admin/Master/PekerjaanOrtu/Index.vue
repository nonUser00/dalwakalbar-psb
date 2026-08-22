<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
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
} from '@/routes/admin/master/pekerjaan-orang-tua';

const props = defineProps<{
    pekerjaanOrtu: any;
    hasLainnya: string | null;
    filters: any;
}>();

const columns = [
    { key: 'name', label: 'NAMA PEKERJAAN' },
    { key: 'is_lainnya', label: 'TIPE PEKERJAAN' },
];

const handleSearch = (search: string) => {
    router.get(
        index.url(),
        { search, limit: props.filters?.limit || 5 },
        { preserveState: true, replace: true },
    );
};

const handleLimit = (limit: number) => {
    router.get(
        index.url(),
        { search: props.filters?.search, limit },
        { preserveState: true, replace: true },
    );
};

// ==========================================
// Modal Form State
// ==========================================
const showModal = ref(false);
const isEditing = ref(false);
const itemToDelete = ref<any>(null);
const showDeleteModal = ref(false);

const form = useForm({
    id: '',
    name: '',
    is_lainnya: false,
});

// Lock Checkbox if Pekerjaan Lainnya already exists on another record
const isCheckboxDisabled = computed(() => {
    if (!props.hasLainnya) {
        return false;
    }

    if (isEditing.value && form.id === props.hasLainnya) {
        return false;
    }

    return true;
});

watch(
    () => form.is_lainnya,
    (newVal) => {
        if (newVal) {
            form.name = 'Pekerjaan Lainnya';
        } else if (form.name === 'Pekerjaan Lainnya') {
            form.name = '';
        }
    },
);

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (item: any) => {
    isEditing.value = true;
    form.id = item.id;
    form.name = item.name;
    form.is_lainnya = Boolean(item.is_lainnya);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(update.url({ pekerjaanOrtu: form.id }), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const openDeleteModal = (item: any) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        router.delete(destroy.url({ pekerjaanOrtu: itemToDelete.value.id }), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Master Pekerjaan Orang Tua" />

        <!-- Header Page -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Master Pekerjaan Orang Tua
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola daftar pekerjaan orang tua/wali calon santri untuk
                    formulir pendaftaran.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton @click="openAddModal">
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
                    Tambah Pekerjaan
                </PrimaryButton>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :columns="columns"
            :data="pekerjaanOrtu.data"
            :pagination="pekerjaanOrtu"
            @search="handleSearch"
            @limit="handleLimit"
        >
            <!-- Custom Cell: Nama Pekerjaan -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            row.is_lainnya
                                ? 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400'
                                : 'border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/50 dark:text-indigo-400',
                            'flex h-10 w-10 items-center justify-center rounded-xl border text-xs font-bold shadow-sm',
                        ]"
                    >
                        <svg
                            v-if="row.is_lainnya"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 13.255A2.36 2.36 0 0119 11H5a2.36 2.36 0 01-2 2.255V19a2 2 0 002 2h14a2 2 0 002-2v-5.745z M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ row.name }}
                        </p>
                        <p
                            class="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400"
                            v-if="row.is_lainnya"
                        >
                            Dapat dipilih pendaftar yang memiliki pekerjaan
                            khusus
                        </p>
                    </div>
                </div>
            </template>

            <!-- Custom Cell: Status Tipe Pekerjaan -->
            <template #cell-is_lainnya="{ row }">
                <span
                    v-if="row.is_lainnya"
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Pekerjaan Lainnya
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Standar
                </span>
            </template>

            <!-- Row Actions -->
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
                            @click="openEditModal(row)"
                            class="flex w-full items-center px-4 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
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
                            Edit
                        </button>
                        <button
                            @click="openDeleteModal(row)"
                            class="flex w-full items-center px-4 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
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
            </template>
        </DataTable>

        <!-- Modal Form (Tambah/Edit) -->
        <Modal
            :show="showModal"
            @close="closeModal"
            maxWidth="md"
            :title="isEditing ? 'Edit Pekerjaan' : 'Tambah Pekerjaan'"
            :description="
                isEditing ? 'Perbarui data pekerjaan' : 'Tambah pekerjaan baru'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/50"
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
                            d="M21 13.255A2.36 2.36 0 0119 11H5a2.36 2.36 0 01-2 2.255V19a2 2 0 002 2h14a2 2 0 002-2v-5.745z M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="pekerjaanForm"
                @submit.prevent="submitForm"
                class="space-y-5"
            >
                <!-- Checkbox Pekerjaan Lainnya -->
                <div
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 transition-colors dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex h-6 items-center">
                            <input
                                id="is_lainnya"
                                type="checkbox"
                                v-model="form.is_lainnya"
                                :disabled="isCheckboxDisabled"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-500 dark:focus:ring-indigo-500/30"
                            />
                        </div>
                        <div class="text-sm">
                            <label
                                for="is_lainnya"
                                :class="[
                                    isCheckboxDisabled
                                        ? 'cursor-not-allowed text-gray-400 dark:text-slate-500'
                                        : 'cursor-pointer text-gray-900 dark:text-slate-100',
                                    'font-bold select-none',
                                ]"
                            >
                                Jadikan sebagai "Pekerjaan Lainnya"
                            </label>
                            <p
                                class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-slate-400"
                            >
                                Centang untuk menampung input manual pekerjaan
                                lain oleh pendaftar. Hanya boleh ada 1 opsi
                                Pekerjaan Lainnya dalam sistem.
                            </p>
                            <p
                                v-if="isCheckboxDisabled && hasLainnya"
                                class="mt-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400"
                            >
                                * Opsi Pekerjaan Lainnya sudah ada di database.
                            </p>
                        </div>
                    </div>
                </div>

                <TextInput
                    id="pekerjaan_name"
                    label="Nama Pekerjaan Orang Tua *"
                    v-model="form.name"
                    :error="form.errors.name"
                    placeholder="Contoh: PNS, Wiraswasta, Pegawai Swasta"
                    :disabled="form.is_lainnya"
                    required
                />
            </form>

            <template #footer>
                <SecondaryButton
                    @click="closeModal"
                    class="w-full justify-center sm:w-auto"
                >
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    form="pekerjaanForm"
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="w-full justify-center sm:w-auto"
                >
                    {{ isEditing ? 'Simpan Data' : 'Tambah Pekerjaan' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="sm"
            title="Hapus Pekerjaan?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>

            <div
                class="p-5 text-sm leading-relaxed text-gray-600 dark:text-slate-300"
            >
                Apakah Anda yakin ingin menghapus pekerjaan
                <strong class="text-gray-900 dark:text-slate-100"
                    >"{{ itemToDelete?.name }}"</strong
                >? Tindakan ini tidak dapat dibatalkan.
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteModal = false"
                    class="w-full justify-center sm:w-auto"
                >
                    Batal
                </SecondaryButton>
                <DangerButton
                    @click="deleteItem"
                    class="w-full justify-center sm:w-auto"
                >
                    Ya, Hapus
                </DangerButton>
            </template>
        </Modal>
    </div>
</template>
