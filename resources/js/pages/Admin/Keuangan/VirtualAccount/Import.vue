<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm } from '@inertiajs/vue3';
import readXlsxFile from 'read-excel-file/browser';
import { ref, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const backUrl = computed(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const from = params.get('from');

        if (from) {
            try {
                return decodeURIComponent(from);
            } catch {
                return from;
            }
        }
    }

    return '/admin/keuangan/va';
});

interface BankItem {
    id: string;
    kode_bank?: string;
    singkatan?: string;
    name: string;
    logo_path?: string;
}

interface PendaftarItem {
    id: string;
    nik: string;
    nama: string;
    cabang?: string;
    jenjang?: string;
}

interface ImportRow {
    id: string;
    nik: string;
    name: string;
    cabang: string;
    jenjang: string;
    pendaftar_id?: string;
    is_verified: boolean;
    errors: Record<string, string>;
    vas: Record<string, string>; // bank_id -> nomor_va
}

const props = defineProps<{
    banks: BankItem[];
    cabangs?: string[];
    jenjangs?: string[];
    pendaftars?: PendaftarItem[];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const isParsed = ref(false);
const parsedData = ref<ImportRow[]>([]);
const isImporting = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const uploadedFile = ref<File | null>(null);

const cabangOptions = computed(() => {
    return (props.cabangs || ['Kalimantan Barat', 'Kalimantan Timur']).map(
        (c) => ({
            value: c,
            label: c,
        }),
    );
});

const jenjangOptions = computed(() => {
    return (
        props.jenjangs || [
            'Madrasah Tsanawiyah',
            'Madrasah Aliyah',
            'Strata 1 (Sarjana)',
            'Pasca Sarjana (Magister)',
            'Doktor (S3)',
        ]
    ).map((j) => ({
        value: j,
        label: j,
    }));
});

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        processFile(target.files[0]);
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;

    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        processFile(e.dataTransfer.files[0]);
    }
};

// Verifikasi NIK terhadap database santri/pendaftar
const verifyRowByNik = (row: ImportRow) => {
    const cleanNik = (row.nik || '').trim();

    if (!cleanNik) {
        row.errors.nik = 'NIK wajib diisi';
        row.pendaftar_id = '';
        row.is_verified = false;

        return;
    }

    const found = (props.pendaftars || []).find(
        (p) => (p.nik || '').trim() === cleanNik,
    );

    if (found) {
        row.name = found.nama;

        if (found.cabang) {
            row.cabang = found.cabang;
        }

        if (found.jenjang) {
            row.jenjang = found.jenjang;
        }

        row.pendaftar_id = found.id;
        row.is_verified = true;
        delete row.errors.nik;
    } else {
        row.pendaftar_id = '';
        row.is_verified = false;
        row.errors.nik = 'NIK tidak terdaftar dalam database pendaftar';
    }
};

const processFile = async (file: File) => {
    if (!file) {
        return;
    }

    errorMsg.value = '';
    uploadedFile.value = file;

    const isExcel = file.name.endsWith('.xlsx') || file.name.endsWith('.xls');

    if (isExcel) {
        try {
            const rawResult = await readXlsxFile(file);
            let rawRows: any[] = rawResult as any[];

            if (
                Array.isArray(rawRows) &&
                rawRows.length > 0 &&
                'data' in rawRows[0]
            ) {
                rawRows = (rawRows[0] as any).data;
            }

            if (!rawRows || rawRows.length < 2) {
                errorMsg.value =
                    'File kosong atau format header tidak sesuai template.';

                return;
            }

            const header = (rawRows[0] || []).map((h: any) =>
                String(h || '').trim(),
            );
            const bankIndices: { bank_id: string; index: number }[] = [];

            props.banks.forEach((bank) => {
                const idx = header.findIndex(
                    (h: string) =>
                        h.toLowerCase().includes(bank.name.toLowerCase()) ||
                        (bank.kode_bank && h.includes(bank.kode_bank)) ||
                        (bank.singkatan &&
                            h
                                .toLowerCase()
                                .includes(bank.singkatan.toLowerCase())),
                );

                if (idx !== -1) {
                    bankIndices.push({ bank_id: bank.id, index: idx });
                }
            });

            const items: ImportRow[] = [];

            for (let i = 1; i < rawRows.length; i++) {
                const rowCells = rawRows[i] || [];
                const nik = String(rowCells[0] || '').trim();
                const name = String(rowCells[1] || '').trim();
                const cabang = String(rowCells[2] || '').trim();
                const jenjang = String(rowCells[3] || '').trim();

                if (!nik && !name) {
                    continue;
                }

                const vaMap: Record<string, string> = {};
                props.banks.forEach((bank) => {
                    const bMatch = bankIndices.find(
                        (b) => b.bank_id === bank.id,
                    );

                    if (
                        bMatch &&
                        rowCells[bMatch.index] !== undefined &&
                        rowCells[bMatch.index] !== null
                    ) {
                        vaMap[bank.id] = String(rowCells[bMatch.index]).trim();
                    } else {
                        vaMap[bank.id] = '';
                    }
                });

                const newRow: ImportRow = {
                    id: `row_${i}_${Date.now()}`,
                    nik,
                    name,
                    cabang,
                    jenjang,
                    is_verified: false,
                    errors: {},
                    vas: vaMap,
                };

                verifyRowByNik(newRow);
                items.push(newRow);
            }

            if (items.length === 0) {
                errorMsg.value = 'Tidak ada data baris yang dapat dibaca.';

                return;
            }

            parsedData.value = items;
            isParsed.value = true;
        } catch (err: any) {
            console.error('Error reading excel file:', err);
            errorMsg.value =
                'Gagal membaca file Excel. Pastikan format file sesuai template resmi.';
        }
    } else {
        parseCSVFile(file);
    }
};

const parseCSVFile = (file: File) => {
    const reader = new FileReader();
    reader.onload = (evt) => {
        const text = evt.target?.result as string;

        if (!text) {
            errorMsg.value = 'File CSV kosong.';

            return;
        }

        const lines = text.split(/\r\n|\n/);

        if (lines.length < 2) {
            errorMsg.value = 'Header file CSV tidak ditemukan.';

            return;
        }

        const header = lines[0]
            .split(',')
            .map((h) => h.replace(/^"(.*)"$/, '$1').trim());
        const bankIndices: { bank_id: string; index: number }[] = [];

        props.banks.forEach((bank) => {
            const idx = header.findIndex(
                (h) =>
                    h.toLowerCase().includes(bank.name.toLowerCase()) ||
                    (bank.kode_bank && h.includes(bank.kode_bank)) ||
                    (bank.singkatan &&
                        h.toLowerCase().includes(bank.singkatan.toLowerCase())),
            );

            if (idx !== -1) {
                bankIndices.push({ bank_id: bank.id, index: idx });
            }
        });

        const items: ImportRow[] = [];

        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();

            if (!line) {
                continue;
            }

            const cells = line
                .split(',')
                .map((c) => c.replace(/^"(.*)"$/, '$1').trim());
            const nik = cells[0] || '';
            const name = cells[1] || '';
            const cabang = cells[2] || '';
            const jenjang = cells[3] || '';

            if (!nik && !name) {
                continue;
            }

            const vaMap: Record<string, string> = {};
            props.banks.forEach((bank) => {
                const bMatch = bankIndices.find((b) => b.bank_id === bank.id);

                if (bMatch && cells[bMatch.index]) {
                    vaMap[bank.id] = cells[bMatch.index];
                } else {
                    vaMap[bank.id] = '';
                }
            });

            const newRow: ImportRow = {
                id: `row_${i}_${Date.now()}`,
                nik,
                name,
                cabang,
                jenjang,
                is_verified: false,
                errors: {},
                vas: vaMap,
            };

            verifyRowByNik(newRow);
            items.push(newRow);
        }

        if (items.length === 0) {
            errorMsg.value = 'Tidak ada data baris yang dapat dibaca.';

            return;
        }

        parsedData.value = items;
        isParsed.value = true;
    };

    reader.readAsText(file);
};

const removeRow = (index: number) => {
    parsedData.value.splice(index, 1);
};

const addRow = () => {
    const vaMap: Record<string, string> = {};
    props.banks.forEach((bank) => {
        vaMap[bank.id] = '';
    });

    const newRow: ImportRow = {
        id: `row_new_${Date.now()}`,
        nik: '',
        name: '',
        cabang: cabangOptions.value[0]?.value || '',
        jenjang: jenjangOptions.value[0]?.value || '',
        is_verified: false,
        errors: {},
        vas: vaMap,
    };

    parsedData.value.push(newRow);
};

const formatBytes = (bytes: number, decimals = 2) => {
    if (!+bytes) {
        return '0 Bytes';
    }

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

const downloadTemplate = () => {
    window.location.href = '/admin/keuangan/va/template';
};

const hasErrors = computed(() => {
    return parsedData.value.some(
        (r) => !r.is_verified || Object.keys(r.errors).length > 0,
    );
});

const form = useForm({
    rows: [] as any[],
});

const submitImport = () => {
    if (parsedData.value.length === 0) {
        errorMsg.value = 'Tidak ada data untuk diimpor.';

        return;
    }

    // Pastikan semua data terverifikasi
    let hasInvalid = false;
    parsedData.value.forEach((row) => {
        verifyRowByNik(row);

        if (!row.is_verified) {
            hasInvalid = true;
        }
    });

    if (hasInvalid) {
        errorMsg.value =
            'Terdapat NIK yang tidak terdaftar dalam database pendaftar. Harap periksa kolom NIK.';

        return;
    }

    form.rows = parsedData.value.map((row) => ({
        pendaftar_id: row.pendaftar_id || row.nik,
        vas: Object.entries(row.vas).map(([bId, nVa]) => ({
            bank_id: bId,
            nomor_va: nVa,
        })),
    }));

    isImporting.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    form.post('/admin/keuangan/va/import', {
        onSuccess: () => {
            isImporting.value = false;
        },
        onError: (err) => {
            isImporting.value = false;

            if (Object.keys(err).length > 0) {
                errorMsg.value = 'Terdapat kesalahan validasi saat menyimpan.';
            }
        },
    });
};

const resetImport = () => {
    isParsed.value = false;
    parsedData.value = [];
    errorMsg.value = '';
    successMsg.value = '';
    uploadedFile.value = null;
    form.clearErrors();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <div class="w-full">
        <Head title="Import Virtual Account" />

        <!-- Page Header (Matching Pegawai Import) -->
        <div
            class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Import Virtual Account
                </h1>
                <p
                    class="mt-1 max-w-xl text-sm text-gray-500 dark:text-slate-400"
                >
                    Import data nomor Virtual Account pendaftar secara massal
                    menggunakan file Excel (.xlsx).
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="w-full">
            <!-- Step 1: Upload File Area (Matching Pegawai Import Style) -->
            <div
                v-if="!isParsed"
                class="overflow-hidden rounded-4xl border border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="p-6 md:p-8">
                    <h3
                        class="mb-4 text-base font-bold text-gray-900 dark:text-slate-100"
                    >
                        Pilih File
                    </h3>

                    <div
                        @dragenter.prevent="isDragging = true"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        class="mb-6 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-12 text-center transition-colors duration-200"
                        :class="
                            isDragging
                                ? 'border-primary bg-primary/5 dark:border-blue-500 dark:bg-blue-500/10'
                                : 'border-gray-200 bg-gray-50 hover:bg-gray-50 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800'
                        "
                        @click="triggerFileInput"
                    >
                        <template v-if="!uploadedFile">
                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900"
                            >
                                <svg
                                    class="h-8 w-8 text-primary dark:text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="mb-1 text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Tarik & Lepas File Kesini
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                atau klik untuk menelusuri file dari komputer
                                Anda (.xlsx, .xls, .csv)
                            </p>
                        </template>
                        <template v-else>
                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/50"
                            >
                                <svg
                                    class="h-8 w-8 text-emerald-500 dark:text-emerald-400"
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
                            </div>
                            <h3
                                class="mb-1 text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                {{ uploadedFile.name }}
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                {{ formatBytes(uploadedFile.size) }}
                            </p>
                        </template>

                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFileUpload"
                            accept=".xlsx, .xls, .csv"
                            class="hidden"
                        />
                    </div>

                    <div
                        v-if="errorMsg"
                        class="mb-6 flex items-center gap-2 rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm font-medium text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400"
                    >
                        <span>{{ errorMsg }}</span>
                    </div>

                    <!-- Warning Box Format Wajib (Matching Pegawai Import) -->
                    <div
                        class="rounded-xl border border-[#FEF08A] bg-[#FEF9C3]/40 p-6 dark:border-amber-900/40 dark:bg-amber-950/30"
                    >
                        <div
                            class="mb-3 flex items-center gap-2 text-[#9A3412] dark:text-amber-400"
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
                            <h4 class="text-sm font-bold">
                                Format & Ketentuan Wajib
                            </h4>
                        </div>
                        <ul
                            class="ml-1 list-inside list-disc space-y-1.5 text-sm text-[#9A3412]/80 dark:text-amber-300/80"
                        >
                            <li>
                                Baris pertama file
                                <span class="font-bold"
                                    >harus berupa header resmi</span
                                >
                                hasil unduhan template.
                            </li>
                            <li>
                                Kolom identitas:
                                <span
                                    class="rounded bg-[#FEF08A] px-1 font-bold dark:bg-amber-900/60 dark:text-amber-200"
                                    >NIK</span
                                >
                                adalah kunci verifikasi data pendaftar.
                            </li>
                            <li>
                                <span class="font-bold"
                                    >Verifikasi Otomatis:</span
                                >
                                Nama, Cabang, dan Jenjang akan disinkronkan
                                langsung dari data pendaftar resmi berdasarkan
                                NIK.
                            </li>
                            <li>
                                Kolom Nomor VA bank dapat diisi sesuai nomor
                                rekening virtual masing-masing bank.
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton
                            @click="downloadTemplate"
                            type="button"
                            class="border-dashed"
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
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Template Resmi (.xlsx)
                        </SecondaryButton>
                    </div>
                </div>
            </div>

            <!-- Step 2: Review & Validation Table (Matching Pegawai Import Style) -->
            <div
                v-else
                class="flex flex-col overflow-hidden rounded-4xl border border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex flex-col justify-between gap-4 border-b border-gray-100 p-6 sm:flex-row sm:items-center md:p-8 dark:border-slate-800"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/50"
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
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Preview Data & Validasi
                            </h3>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                            >
                                {{ parsedData.length }} data terdeteksi. Data
                                santri otomatis dicocokkan dengan NIK.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            v-if="!hasErrors"
                            class="flex items-center rounded-full bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Semua Data Terverifikasi
                        </div>
                        <div
                            v-else
                            class="flex items-center rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"
                        >
                            Terdapat NIK Belum Terdaftar
                        </div>

                        <SecondaryButton
                            @click="resetImport"
                            :disabled="isImporting"
                            type="button"
                        >
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            @click="submitImport"
                            :disabled="isImporting || hasErrors"
                            :class="{
                                'cursor-not-allowed opacity-50': hasErrors,
                            }"
                            type="button"
                        >
                            <svg
                                v-if="isImporting"
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
                            {{
                                isImporting
                                    ? 'Menyimpan...'
                                    : 'Simpan ke Database'
                            }}
                        </PrimaryButton>
                    </div>
                </div>

                <div
                    v-if="errorMsg"
                    class="mx-6 mt-6 flex items-start gap-3 rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm font-medium text-rose-600 md:mx-8 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0"
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
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Review Table -->
                <div class="overflow-x-auto p-6 md:p-8">
                    <table
                        class="w-full min-w-max divide-y divide-gray-200 border-b border-gray-100 dark:divide-slate-800 dark:border-slate-800"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="min-w-48 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NIK PENDAFTAR
                                    <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-56 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NAMA SANTRI (OTOMATIS)
                                </th>
                                <th
                                    class="min-w-48 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    CABANG
                                </th>
                                <th
                                    class="min-w-48 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    JENJANG
                                </th>
                                <th
                                    v-for="bank in props.banks"
                                    :key="bank.id"
                                    class="min-w-44 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    VA
                                    {{
                                        (
                                            bank.singkatan || bank.name
                                        ).toUpperCase()
                                    }}
                                </th>
                                <th
                                    class="min-w-20 px-3 py-3 text-center text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    AKSI
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-slate-800/60"
                        >
                            <tr
                                v-for="(row, index) in parsedData"
                                :key="row.id"
                                class="hover:bg-gray-50 dark:bg-slate-800 dark:hover:bg-slate-800/40 dark:hover:bg-slate-800/50"
                            >
                                <!-- NIK Input with Live Verification -->
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.nik"
                                        :error="row.errors.nik"
                                        @input="verifyRowByNik(row)"
                                        placeholder="16 Digit NIK"
                                    />
                                </td>

                                <!-- Nama Santri (Auto-filled by NIK) -->
                                <td class="px-3 py-4 align-top">
                                    <div class="space-y-1">
                                        <TextInput
                                            v-model="row.name"
                                            placeholder="Nama Pendaftar"
                                        />
                                        <div
                                            v-if="row.is_verified"
                                            class="flex items-center text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                                        >
                                            <svg
                                                class="mr-1 h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Terverifikasi
                                        </div>
                                    </div>
                                </td>

                                <!-- Cabang (CustomSelect) -->
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.cabang"
                                        :options="cabangOptions"
                                        placeholder="Pilih Cabang"
                                    />
                                </td>

                                <!-- Jenjang (CustomSelect) -->
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.jenjang"
                                        :options="jenjangOptions"
                                        placeholder="Pilih Jenjang"
                                    />
                                </td>

                                <!-- Bank VA Columns -->
                                <td
                                    v-for="bank in props.banks"
                                    :key="bank.id"
                                    class="px-3 py-4 align-top"
                                >
                                    <TextInput
                                        v-model="row.vas[bank.id]"
                                        :placeholder="'Nomor VA...'"
                                    />
                                </td>

                                <!-- Aksi Hapus Baris -->
                                <td class="px-3 py-4 text-center align-middle">
                                    <button
                                        type="button"
                                        @click="removeRow(index)"
                                        class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition-colors hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 focus:outline-none dark:border-slate-700 dark:text-slate-500 dark:hover:border-rose-800 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                        title="Hapus Baris"
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

                    <!-- Tombol Tambah Baris Manual -->
                    <div class="mt-4">
                        <button
                            type="button"
                            @click="addRow"
                            class="inline-flex cursor-pointer items-center text-sm font-bold text-primary transition-colors hover:underline dark:text-blue-400"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Tambah Baris
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
