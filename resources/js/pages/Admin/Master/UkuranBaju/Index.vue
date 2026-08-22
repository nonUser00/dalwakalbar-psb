<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
} from '@/routes/admin/master/ukuran-baju';

const props = defineProps<{
    ukuranBaju: any;
    filters: any;
}>();

const columns = [{ key: 'name', label: 'UKURAN BAJU' }];

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
});

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
        form.put(update.url({ ukuranBaju: form.id }), {
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
        router.delete(destroy.url({ ukuranBaju: itemToDelete.value.id }), {
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
        <Head title="Master Ukuran Baju" />

        <!-- Header Page -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Master Ukuran Baju
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola daftar ukuran baju/seragam calon santri untuk seragam
                    pondok & sekolah.
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
                    Tambah Ukuran
                </PrimaryButton>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :columns="columns"
            :data="ukuranBaju.data"
            :pagination="ukuranBaju"
            @search="handleSearch"
            @limit="handleLimit"
        >
            <!-- Custom Cell: Ukuran Baju -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-violet-100 bg-violet-50 text-xs font-extrabold text-violet-600 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/50 dark:text-violet-400"
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
                                d="M7 7h10l2 3v10a1 1 0 01-1 1H6a1 1 0 01-1-1V10l2-3zM9 7V5a3 3 0 016 0v2"
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
                        >
                            Ukuran seragam santri
                        </p>
                    </div>
                </div>
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
            :title="isEditing ? 'Edit Ukuran' : 'Tambah Ukuran'"
            :description="
                isEditing ? 'Perbarui data ukuran' : 'Tambah ukuran baru'
            "
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 dark:bg-violet-950/50"
                >
                    <svg
                        class="h-6 w-6 text-violet-600 dark:text-violet-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 7h10l2 3v10a1 1 0 01-1 1H6a1 1 0 01-1-1V10l2-3zM9 7V5a3 3 0 016 0v2"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="ukuranForm"
                @submit.prevent="submitForm"
                class="space-y-4"
            >
                <TextInput
                    id="ukuran_name"
                    label="Nama Ukuran Baju *"
                    v-model="form.name"
                    :error="form.errors.name"
                    placeholder="Contoh: S, M, L, XL, XXL"
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
                    form="ukuranForm"
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="w-full justify-center sm:w-auto"
                >
                    {{ isEditing ? 'Simpan Data' : 'Tambah Ukuran' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="sm"
            title="Hapus Ukuran?"
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
                Apakah Anda yakin ingin menghapus ukuran baju
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
