<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { index, store, update, destroy } from '@/routes/admin/akademik/dokumen';

interface JenjangItem {
    id: string;
    name: string;
}

interface DokumenItem {
    id: string;
    name: string;
    type: 'gambar' | 'pdf' | 'semua';
    jalur_pendaftaran: string;
    is_required: boolean;
    is_profile_photo: boolean;
    jenjangs?: JenjangItem[];
}

const props = defineProps<{
    dokumens: {
        data: DokumenItem[];
        from?: number;
        to?: number;
        total?: number;
        per_page?: number;
        current_page?: number;
        last_page?: number;
        links?: any[];
    };
    jenjangs: JenjangItem[];
    filters: {
        search?: string;
        limit?: number;
    };
}>();

// Sort jenjangs priority: MTs -> MA -> S1 -> S2 -> S3
const getJenjangOrderPriority = (name: string): number => {
    const str = name.toLowerCase();

    if (str.includes('mts')) {
        return 1;
    }

    if (str.includes('ma') && !str.includes('utama')) {
        return 2;
    }

    if (str.includes('s1')) {
        return 3;
    }

    if (str.includes('s2')) {
        return 4;
    }

    if (str.includes('s3')) {
        return 5;
    }

    return 99;
};

const sortedJenjangs = computed(() => {
    if (!props.jenjangs) {
        return [];
    }

    return [...props.jenjangs].sort(
        (a, b) =>
            getJenjangOrderPriority(a.name) - getJenjangOrderPriority(b.name),
    );
});

const columns = [
    { key: 'name', label: 'NAMA DOKUMEN' },
    { key: 'type', label: 'JENIS FILE' },
    { key: 'jalur_pendaftaran', label: 'JALUR PENDAFTARAN' },
    { key: 'is_required', label: 'SIFAT UPLOAD' },
    { key: 'jenjangs', label: 'JENJANG PENDAFTARAN' },
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
const itemToDelete = ref<DokumenItem | null>(null);
const showDeleteModal = ref(false);

const form = useForm({
    id: '',
    name: '',
    type: 'semua' as 'gambar' | 'pdf' | 'semua',
    jalur_pendaftaran: 'Semua',
    is_required: true,
    is_profile_photo: false,
    jenjang_ids: [] as string[],
});

// Otomatis uncheck foto profil jika tipe diubah ke PDF murni
watch(
    () => form.type,
    (newType) => {
        if (newType === 'pdf') {
            form.is_profile_photo = false;
        }
    },
);

const isJenjangSelected = (id: string) => {
    return form.jenjang_ids.includes(id);
};

const toggleJenjang = (id: string) => {
    const idx = form.jenjang_ids.indexOf(id);

    if (idx > -1) {
        form.jenjang_ids.splice(idx, 1);
    } else {
        form.jenjang_ids.push(id);
    }
};

const toggleAllJenjang = () => {
    if (form.jenjang_ids.length === sortedJenjangs.value.length) {
        form.jenjang_ids = [];
    } else {
        form.jenjang_ids = sortedJenjangs.value.map((j) => j.id);
    }
};

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.jenjang_ids = sortedJenjangs.value.map((j) => j.id);
    showModal.value = true;
};

const openEditModal = (item: DokumenItem) => {
    isEditing.value = true;
    form.id = item.id;
    form.name = item.name;
    form.type = item.type;
    form.jalur_pendaftaran = item.jalur_pendaftaran || 'Semua';
    form.is_required = Boolean(item.is_required);
    form.is_profile_photo = Boolean(item.is_profile_photo);
    form.jenjang_ids = item.jenjangs ? item.jenjangs.map((j) => j.id) : [];
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
        form.put(update.url(form.id), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const openDeleteModal = (item: DokumenItem) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        router.delete(destroy.url(itemToDelete.value.id), {
            preserveScroll: true,
            preserveState: true,
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
        <Head title="Dokumen Lampiran Pendaftaran" />

        <!-- Header Page -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Dokumen Lampiran Pendaftaran
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola berkas & dokumen yang wajib diunggah oleh calon
                    santri sesuai jenjang & jalur.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton @click="openAddModal" class="font-bold">
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
                    Tambah Dokumen
                </PrimaryButton>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :columns="columns"
            :data="dokumens.data"
            :pagination="dokumens"
            @search="handleSearch"
            @limit="handleLimit"
        >
            <!-- Custom Cell: Nama Dokumen -->
            <template #cell-name="{ row }">
                <div class="flex items-center gap-2.5">
                    <p
                        class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                    >
                        {{ row.name }}
                    </p>
                    <span
                        v-if="row.is_profile_photo"
                        class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <svg
                            class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Foto Profil
                    </span>
                </div>
            </template>

            <!-- Custom Cell: Jenis Dokumen -->
            <template #cell-type="{ row }">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold"
                    :class="
                        row.type === 'gambar'
                            ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400'
                            : row.type === 'semua'
                              ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400'
                              : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400'
                    "
                >
                    <svg
                        v-if="row.type === 'gambar'"
                        class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-else-if="row.type === 'semua'"
                        class="h-3.5 w-3.5"
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
                    <svg
                        v-else
                        class="h-3.5 w-3.5"
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
                    {{
                        row.type === 'gambar'
                            ? 'Gambar (JPG/PNG)'
                            : row.type === 'semua'
                              ? 'Dokumen & Gambar'
                              : 'Dokumen (PDF)'
                    }}
                </span>
            </template>

            <!-- Custom Cell: Jalur Pendaftaran -->
            <template #cell-jalur_pendaftaran="{ row }">
                <span
                    class="text-[13px] font-medium text-slate-700 dark:text-slate-300"
                >
                    {{ row.jalur_pendaftaran || 'Semua Jalur' }}
                </span>
            </template>

            <!-- Custom Cell: Sifat Upload -->
            <template #cell-is_required="{ row }">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold"
                    :class="
                        row.is_required
                            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400'
                            : 'border-gray-200 bg-gray-100 text-gray-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                    "
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="
                            row.is_required
                                ? 'bg-rose-500'
                                : 'bg-gray-400 dark:bg-slate-500'
                        "
                    ></span>
                    {{ row.is_required ? 'Wajib' : 'Opsional' }}
                </span>
            </template>

            <!-- Custom Cell: Jenjang Pendaftaran -->
            <template #cell-jenjangs="{ row }">
                <div
                    class="max-w-xs text-[13px] font-medium whitespace-normal text-slate-700 dark:text-slate-300"
                >
                    <span
                        v-if="
                            row.jenjangs &&
                            row.jenjangs.length === sortedJenjangs.length
                        "
                        class="font-extrabold text-emerald-700 dark:text-emerald-400"
                    >
                        Semua Jenjang
                    </span>
                    <span v-else-if="row.jenjangs && row.jenjangs.length > 0">
                        {{
                            [...row.jenjangs]
                                .sort(
                                    (a, b) =>
                                        getJenjangOrderPriority(a.name) -
                                        getJenjangOrderPriority(b.name),
                                )
                                .map((j) => j.name)
                                .join(', ')
                        }}
                    </span>
                    <span
                        v-else
                        class="font-normal text-gray-400 italic dark:text-slate-500"
                        >Belum diset</span
                    >
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
                            class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800"
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
                            class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        >
                            <svg
                                class="mr-2.5 h-4 w-4 text-rose-500"
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

        <!-- Modal Form (Tambah / Edit) -->
        <Modal
            :show="showModal"
            @close="closeModal"
            maxWidth="lg"
            :title="isEditing ? 'Edit Dokumen' : 'Tambah Dokumen'"
            :description="
                isEditing
                    ? 'Perbarui data dokumen lampiran'
                    : 'Tambah dokumen lampiran baru'
            "
        >
            <form
                id="dokumenForm"
                @submit.prevent="submitForm"
                class="space-y-5"
            >
                <!-- Nama Dokumen -->
                <TextInput
                    id="dokumen_name"
                    label="Nama Dokumen Lampiran *"
                    v-model="form.name"
                    :error="form.errors.name"
                    placeholder="Contoh: Pas Foto Santri, Kartu Keluarga (KK)"
                    required
                />

                <!-- Row 2 Column: Tipe Dokumen & Jalur Pendaftaran -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <CustomSelect
                        id="dokumen_type"
                        label="Jenis File Dokumen *"
                        v-model="form.type"
                        :options="[
                            { value: 'semua', label: 'Dokumen & Gambar (PDF, JPG, PNG)' },
                            { value: 'gambar', label: 'Hanya Gambar (JPG, PNG)' },
                            { value: 'pdf', label: 'Hanya Dokumen (PDF)' },
                        ]"
                        :error="form.errors.type"
                        required
                    />

                    <CustomSelect
                        id="dokumen_jalur"
                        label="Jalur Pendaftaran *"
                        v-model="form.jalur_pendaftaran"
                        :options="[
                            { value: 'Semua', label: 'Semua Jalur' },
                            { value: 'Reguler', label: 'Jalur Reguler' },
                            { value: 'Pindahan', label: 'Jalur Pindahan' },
                        ]"
                        :error="form.errors.jalur_pendaftaran"
                        required
                    />
                </div>

                <!-- Row 2 Column: Sifat Upload & Foto Profil Switch -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <CustomSelect
                        id="dokumen_sifat"
                        label="Sifat Unggah Berkas *"
                        :modelValue="form.is_required ? 'wajib' : 'optional'"
                        @update:modelValue="
                            (val: string) =>
                                (form.is_required = val === 'wajib')
                        "
                        :options="[
                            { value: 'wajib', label: 'Wajib (Harus Diunggah)' },
                            {
                                value: 'optional',
                                label: 'Opsional (Boleh Kosong)',
                            },
                        ]"
                        :error="form.errors.is_required"
                        required
                    />

                    <!-- Checkbox Foto Profil -->
                    <div
                        class="flex flex-col justify-center rounded-xl border border-gray-200 bg-gray-50 p-3.5 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/70"
                    >
                        <label
                            class="flex cursor-pointer items-center space-x-3 select-none"
                        >
                            <Checkbox
                                :checked="form.is_profile_photo"
                                @update:checked="
                                    (val: boolean) =>
                                        (form.is_profile_photo = val)
                                "
                                :disabled="form.type === 'pdf'"
                            />
                            <div class="flex flex-col">
                                <span
                                    class="text-xs font-bold text-gray-900 dark:text-slate-100"
                                    :class="{
                                        'opacity-50': form.type === 'pdf',
                                    }"
                                >
                                    Jadikan Foto Profil
                                </span>
                                <span
                                    class="text-[11px] text-gray-500 dark:text-slate-400"
                                >
                                    {{
                                        form.type !== 'pdf'
                                            ? 'Foto ini dipakai sebagai avatar santri'
                                            : 'Tidak aktif untuk jenis Hanya PDF'
                                    }}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Checkbox Jenjang Pendaftaran (Sorted MTs -> S3) -->
                <div class="space-y-3 pt-1">
                    <div class="flex items-center justify-between">
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                        >
                            Berlaku Untuk Jenjang Pendaftaran *
                        </label>
                        <button
                            type="button"
                            @click="toggleAllJenjang"
                            class="text-xs font-bold text-primary hover:underline dark:text-blue-400"
                        >
                            {{
                                form.jenjang_ids.length ===
                                sortedJenjangs.length
                                    ? 'Batal Pilih'
                                    : 'Pilih Semua'
                            }}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <label
                            v-for="j in sortedJenjangs"
                            :key="j.id"
                            @click.prevent="toggleJenjang(j.id)"
                            class="flex cursor-pointer items-center space-x-2.5 rounded-xl border p-2.5 transition-all select-none"
                            :class="
                                isJenjangSelected(j.id)
                                    ? 'border-primary/40 bg-primary/5 font-bold text-primary dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-400'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                            "
                        >
                            <Checkbox
                                :checked="isJenjangSelected(j.id)"
                                @update:checked="() => toggleJenjang(j.id)"
                            />
                            <span class="text-xs font-bold">{{ j.name }}</span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.jenjang_ids"
                        class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.jenjang_ids }}
                    </p>
                </div>
            </form>

            <template #footer>
                <SecondaryButton @click="closeModal" class="font-bold">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    form="dokumenForm"
                    type="submit"
                    :disabled="form.processing"
                    class="font-bold"
                >
                    {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Modal Delete -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="sm"
            title="Hapus Dokumen?"
            description="Konfirmasi penghapusan syarat berkas"
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
                Apakah Anda yakin ingin menghapus dokumen
                <strong class="text-gray-900 dark:text-slate-100"
                    >"{{ itemToDelete?.name }}"</strong
                >?
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteModal = false"
                    class="font-bold"
                >
                    Batal
                </SecondaryButton>
                <DangerButton @click="deleteItem" class="font-bold">
                    Ya, Hapus
                </DangerButton>
            </template>
        </Modal>
    </div>
</template>
