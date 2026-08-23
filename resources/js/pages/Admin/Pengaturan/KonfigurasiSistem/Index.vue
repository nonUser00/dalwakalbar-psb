<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { general, kopSurat } from '@/routes/admin/pengaturan/konfigurasi';
import SequenceModal from './Partials/SequenceModal.vue';

const props = defineProps<{
    settings: Record<string, any>;
    sequences: any[];
}>();

const parseHariKerja = (val: any): string[] => {
    if (!val) return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    if (Array.isArray(val)) return val;
    try {
        const parsed = JSON.parse(val);
        return Array.isArray(parsed) ? parsed : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    } catch {
        return typeof val === 'string' ? val.split(',').map((s) => s.trim()) : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    }
};

// Form: Pengaturan Umum
const generalForm = useForm({
    nama_contact: props.settings.nama_contact?.value || '',
    kontak_darurat_wa: props.settings.kontak_darurat_wa?.value || '',
    hari_kerja: parseHariKerja(props.settings.hari_kerja?.value),
    jam_kerja_mulai: props.settings.jam_kerja_mulai?.value || '08:00',
    jam_kerja_selesai: props.settings.jam_kerja_selesai?.value || '17:00',
});

const listHari = [
    { key: 'Senin', label: 'Senin' },
    { key: 'Selasa', label: 'Selasa' },
    { key: 'Rabu', label: 'Rabu' },
    { key: 'Kamis', label: 'Kamis' },
    { key: 'Jumat', label: 'Jumat' },
    { key: 'Sabtu', label: 'Sabtu' },
    { key: 'Minggu', label: 'Minggu' },
];

const toggleHari = (hari: string) => {
    if (generalForm.hari_kerja.includes(hari)) {
        generalForm.hari_kerja = generalForm.hari_kerja.filter((h) => h !== hari);
    } else {
        generalForm.hari_kerja.push(hari);
    }
};

const submitGeneral = () => {
    generalForm.post(general.url(), {
        preserveScroll: true,
    });
};

// Form: Kop Surat
const kopSuratForm = useForm({
    kop_surat: null as File | null,
});

const kopSuratPreview = ref(
    props.settings.kop_surat_path?.value
        ? `/storage/${props.settings.kop_surat_path.value}`
        : null,
);

const fileInput = ref<HTMLInputElement | null>(null);

const handleKopSuratChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        kopSuratForm.kop_surat = target.files[0];
        kopSuratPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const cancelKopSurat = () => {
    kopSuratForm.reset();

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    kopSuratPreview.value = props.settings.kop_surat_path?.value
        ? `/storage/${props.settings.kop_surat_path.value}`
        : null;
};

const submitKopSurat = () => {
    kopSuratForm.post(kopSurat.url(), {
        preserveScroll: true,
        onSuccess: () => {
            kopSuratForm.reset();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

// Sequences
const showSequenceModal = ref(false);
const selectedSequence = ref<any | null>(null);

const sequenceColumns = [
    { key: 'name', label: 'NAMA' },
    { key: 'prefix', label: 'PREFIX' },
    { key: 'pattern', label: 'POLA (PATTERN)' },
    { key: 'padding', label: 'PADDING' },
];

const openSequenceModal = (seq: any) => {
    selectedSequence.value = seq;
    showSequenceModal.value = true;
};

const closeSequenceModal = () => {
    showSequenceModal.value = false;
    selectedSequence.value = null;
};

const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);

        return params.get('tab') || 'umum';
    }

    return 'umum';
};

const activeTab = ref(getInitialTab());

watch(activeTab, (newTab: string) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
    }
});
const tabs = [
    {
        id: 'umum',
        name: 'Umum',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    },
    {
        id: 'kop',
        name: 'Kop Surat',
        icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        id: 'penomoran',
        name: 'Penomoran',
        icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    },
];
</script>

<template>
    <div class="w-full">
        <Head title="Konfigurasi Sistem" />
        <!-- Page Header & Actions -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Konfigurasi Sistem
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Atur pengaturan global aplikasi seperti nama, kontak, dan
                    penomoran.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-8 md:flex-row">
            <!-- Sidebar Navigation -->
            <div class="w-full flex-shrink-0 md:w-64">
                <nav class="flex flex-col space-y-2" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'bg-primary font-bold text-white shadow-md dark:bg-blue-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
                            'group flex w-full cursor-pointer items-center rounded-xl px-4 py-3 text-sm transition-all duration-200',
                        ]"
                    >
                        <svg
                            :class="[
                                activeTab === tab.id
                                    ? 'text-white'
                                    : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300',
                                'mr-3 -ml-1 h-5 w-5 flex-shrink-0 transition-colors',
                            ]"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                v-if="tab.id === 'umum'"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="tab.icon"
                            />
                        </svg>
                        <span class="truncate">{{ tab.name }}</span>
                    </button>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="min-w-0 flex-1 space-y-6">
                <!-- Pengaturan Umum -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                    mode="out-in"
                >
                    <div
                        v-if="activeTab === 'umum'"
                        key="umum"
                        class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm transition-all dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="border-b border-slate-100 bg-slate-50/50 px-6 py-6 sm:px-8 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-600/10 dark:bg-blue-950/50 dark:text-blue-400 dark:ring-blue-500/20"
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
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-base leading-7 font-bold text-slate-900 dark:text-slate-100"
                                    >
                                        Pengaturan Umum
                                    </h3>
                                    <p
                                        class="text-sm leading-6 text-slate-500 dark:text-slate-400"
                                    >
                                        Informasi utama institusi yang akan
                                        digunakan di berbagai tempat.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-8 sm:px-8">
                            <form
                                @submit.prevent="submitGeneral"
                                class="max-w-2xl space-y-8"
                            >
                                <div
                                    class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 md:grid-cols-3 dark:border-slate-800"
                                >
                                    <div>
                                        <h2
                                            class="text-base leading-7 font-semibold text-gray-900 dark:text-slate-100"
                                        >
                                            Informasi Dasar
                                        </h2>
                                        <p
                                            class="mt-1 text-sm leading-6 text-gray-600 dark:text-slate-300 dark:text-slate-400"
                                        >
                                            Pengaturan nama dan kontak darurat
                                            untuk sistem.
                                        </p>
                                    </div>
                                    <div
                                        class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 md:col-span-2"
                                    >
                                        <div class="sm:col-span-4">
                                            <TextInput
                                                id="nama_contact"
                                                v-model="
                                                    generalForm.nama_contact
                                                "
                                                label="Nama Contact"
                                                required
                                                :error="
                                                    generalForm.errors
                                                        .nama_contact
                                                "
                                            />
                                        </div>
                                        <div class="sm:col-span-4">
                                            <TextInput
                                                id="kontak_darurat_wa"
                                                label="Nomor WhatsApp Layanan"
                                                v-model="
                                                    generalForm.kontak_darurat_wa
                                                "
                                                :error="
                                                    generalForm.errors
                                                        .kontak_darurat_wa
                                                "
                                                placeholder="Contoh: 081234567890"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Pengaturan Jam Kerja Layanan -->
                                <div
                                    class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 md:grid-cols-3 dark:border-slate-800"
                                >
                                    <div>
                                        <h2
                                            class="text-base leading-7 font-semibold text-gray-900 dark:text-slate-100"
                                        >
                                            Jam Kerja & Pelayanan
                                        </h2>
                                        <p
                                            class="mt-1 text-sm leading-6 text-gray-600 dark:text-slate-300 dark:text-slate-400"
                                        >
                                            Konfigurasi hari dan jam kerja operasional panitia/admin yang akan ditampilkan pada dashboard santri dan modal bantuan.
                                        </p>
                                    </div>
                                    <div
                                        class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6 md:col-span-2"
                                    >
                                        <!-- Hari Kerja Checkboxes -->
                                        <div class="sm:col-span-6">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                                Hari Kerja Aktif
                                            </label>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="hari in listHari"
                                                    :key="hari.key"
                                                    type="button"
                                                    @click="toggleHari(hari.key)"
                                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all"
                                                    :class="
                                                        generalForm.hari_kerja.includes(hari.key)
                                                            ? 'border-primary bg-primary/10 text-primary ring-2 ring-primary/20 dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                                    "
                                                >
                                                    <svg
                                                        v-if="generalForm.hari_kerja.includes(hari.key)"
                                                        class="h-3.5 w-3.5"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    <span>{{ hari.label }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Jam Mulai -->
                                        <div class="sm:col-span-3">
                                            <TextInput
                                                id="jam_kerja_mulai"
                                                label="Jam Mulai"
                                                type="time"
                                                v-model="generalForm.jam_kerja_mulai"
                                                :error="generalForm.errors.jam_kerja_mulai"
                                            />
                                        </div>

                                        <!-- Jam Selesai -->
                                        <div class="sm:col-span-3">
                                            <TextInput
                                                id="jam_kerja_selesai"
                                                label="Jam Selesai"
                                                type="time"
                                                v-model="generalForm.jam_kerja_selesai"
                                                :error="generalForm.errors.jam_kerja_selesai"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-6">
                                    <PrimaryButton
                                        :class="{
                                            'cursor-not-allowed opacity-50':
                                                generalForm.processing,
                                        }"
                                        :disabled="generalForm.processing"
                                    >
                                        <svg
                                            v-if="generalForm.processing"
                                            class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
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
                                        Simpan Pengaturan
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Kop Surat -->
                    <div
                        v-else-if="activeTab === 'kop'"
                        key="kop"
                        class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm transition-all dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="border-b border-slate-100 bg-slate-50/50 px-6 py-6 sm:px-8 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-600/10 dark:bg-purple-950/50 dark:text-purple-400 dark:ring-purple-500/20"
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
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-base leading-7 font-bold text-slate-900 dark:text-slate-100"
                                    >
                                        Logo Kop Surat
                                    </h3>
                                    <p
                                        class="text-sm leading-6 text-slate-500 dark:text-slate-400"
                                    >
                                        Logo ini akan digunakan pada hasil
                                        cetakan seperti PDF dan laporan.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-8 sm:px-8">
                            <form
                                @submit.prevent="submitKopSurat"
                                class="max-w-2xl space-y-8"
                            >
                                <div
                                    class="hover:border-primary-400 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-8 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-800/50"
                                >
                                    <div
                                        class="flex flex-col gap-8 sm:flex-row sm:items-center"
                                    >
                                        <div class="flex-shrink-0">
                                            <div
                                                v-if="kopSuratPreview"
                                                class="group hover:border-primary-300 relative flex h-36 w-36 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition-all dark:border-slate-700 dark:bg-slate-800"
                                            >
                                                <img
                                                    :src="kopSuratPreview"
                                                    alt="Kop Surat"
                                                    class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="flex h-36 w-36 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white transition-colors hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700"
                                            >
                                                <svg
                                                    class="mx-auto mb-2 h-10 w-10 text-gray-300 dark:text-slate-600"
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
                                                <span
                                                    class="block text-xs font-medium text-gray-400 dark:text-slate-500"
                                                    >Belum ada logo</span
                                                >
                                            </div>
                                        </div>

                                        <div class="flex-grow">
                                            <label
                                                class="mb-3 block text-sm font-bold text-gray-900 dark:text-slate-100"
                                                >Pilih File Logo Baru</label
                                            >
                                            <div
                                                class="mt-2 flex items-center gap-x-3"
                                            >
                                                <input
                                                    ref="fileInput"
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/jpg"
                                                    @change="
                                                        handleKopSuratChange
                                                    "
                                                    class="block w-full text-sm text-gray-500 transition-colors file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-primary file:px-5 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-dark focus:outline-none dark:text-slate-400 dark:file:bg-blue-600 dark:hover:file:bg-blue-500"
                                                />
                                            </div>
                                            <div
                                                class="mt-4 flex flex-col gap-1 text-xs leading-5 text-gray-500 dark:text-slate-400"
                                            >
                                                <p>
                                                    • Maksimal ukuran file:
                                                    <span class="font-semibold"
                                                        >2MB</span
                                                    >
                                                </p>
                                                <p>
                                                    • Format yang didukung:
                                                    <span class="font-semibold"
                                                        >JPG, PNG</span
                                                    >
                                                </p>
                                                <p>
                                                    • Disarankan menggunakan
                                                    resolusi tinggi dengan latar
                                                    transparan.
                                                </p>
                                            </div>
                                            <div
                                                v-if="
                                                    kopSuratForm.errors
                                                        .kop_surat
                                                "
                                                class="mt-3 rounded-xl bg-red-50 p-2 text-sm font-medium text-red-600 dark:bg-rose-950/40 dark:text-rose-400"
                                            >
                                                {{
                                                    kopSuratForm.errors
                                                        .kop_surat
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-end gap-x-4 border-t border-gray-900/10 pt-6 dark:border-slate-800"
                                >
                                    <button
                                        v-if="kopSuratForm.kop_surat"
                                        type="button"
                                        @click="cancelKopSurat"
                                        class="text-sm leading-6 font-semibold text-gray-900 transition-colors hover:text-gray-700 dark:text-slate-100 dark:text-slate-200 dark:text-slate-300 dark:hover:text-slate-100"
                                        :disabled="kopSuratForm.processing"
                                    >
                                        Batal
                                    </button>
                                    <PrimaryButton
                                        :class="{
                                            'cursor-not-allowed opacity-50':
                                                kopSuratForm.processing ||
                                                !kopSuratForm.kop_surat,
                                        }"
                                        :disabled="
                                            kopSuratForm.processing ||
                                            !kopSuratForm.kop_surat
                                        "
                                    >
                                        <svg
                                            v-if="kopSuratForm.processing"
                                            class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
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
                                        Simpan Data
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Format Penomoran -->
                    <div
                        v-else-if="activeTab === 'penomoran'"
                        key="penomoran"
                        class="transition-all"
                    >
                        <div class="mb-6 px-2">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 ring-1 ring-orange-600/20 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-500/20"
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
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-base leading-7 font-bold text-slate-900 dark:text-slate-100"
                                    >
                                        Penomoran Otomatis
                                    </h3>
                                    <p
                                        class="text-sm leading-6 text-slate-500 dark:text-slate-400"
                                    >
                                        Konfigurasi format penomoran otomatis
                                        untuk pendaftaran dan invoice.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <DataTable
                                :columns="sequenceColumns"
                                :data="sequences"
                                :pagination="false"
                            >
                                <template #cell-name="{ row }">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-100 text-lg font-bold text-blue-700 shadow-xs dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
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
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                                            >
                                                {{
                                                    row.name ===
                                                    'nomor_pendaftaran'
                                                        ? 'Pendaftaran'
                                                        : 'Invoice'
                                                }}
                                            </p>
                                            <p
                                                class="text-[13px] text-slate-500 dark:text-slate-400"
                                            >
                                                {{ row.name }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <template #cell-pattern="{ row }">
                                    <code
                                        class="rounded-lg border border-indigo-100 bg-indigo-50 px-2 py-1 text-[13px] font-semibold text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/50 dark:text-indigo-300"
                                        >{{ row.pattern }}</code
                                    >
                                </template>
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
                                                @click="openSequenceModal(row)"
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
                                        </template>
                                    </ActionMenu>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </div>

    <!-- Modal Edit Sequence -->
    <SequenceModal
        :show="showSequenceModal"
        :sequence-data="selectedSequence"
        @close="closeSequenceModal"
    />
</template>
