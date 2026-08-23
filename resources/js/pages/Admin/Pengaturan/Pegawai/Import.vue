<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm } from '@inertiajs/vue3';
import readXlsxFile from 'read-excel-file/browser';
import { ref, onMounted, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    index,
    importMethod,
    exportTemplate,
} from '@/routes/admin/pengaturan/pegawai';

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

    return index.url();
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const isParsed = ref(false);
const parsedData = ref<any[]>([]);
const isImporting = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const uploadedFile = ref<File | null>(null);

const form = useForm({
    items: [] as any[],
});

const provinces = ref<any[]>([]);

const fetchProvinces = async () => {
    try {
        const res = await fetch('/api/indonesia/provinces');
        const data = await res.json();
        provinces.value = data.map((p: any) => ({
            value: p.name,
            label: p.name,
            id: p.code,
        }));
    } catch {}
};

onMounted(() => {
    fetchProvinces();
});

const fetchCitiesForRow = async (
    row: any,
    provinceName: string,
    initialLoad = false,
) => {
    if (!initialLoad) {
        row.kabupaten_kota = '';
        row.kecamatan = '';
        row.kelurahan_desa = '';
        row.cities = [];
        row.districts = [];
        row.villages = [];
    }

    const p = provinces.value.find((prov: any) => prov.value === provinceName);

    if (p) {
        try {
            const res = await fetch(
                '/api/indonesia/cities?province_code=' + p.id,
            );
            const data = await res.json();
            row.cities = data.map((c: any) => ({
                value: c.name,
                label: c.name,
                id: c.code,
            }));
        } catch {}
    }
};

const fetchDistrictsForRow = async (
    row: any,
    cityName: string,
    initialLoad = false,
) => {
    if (!initialLoad) {
        row.kecamatan = '';
        row.kelurahan_desa = '';
        row.districts = [];
        row.villages = [];
    }

    const c = row.cities.find((city: any) => city.value === cityName);

    if (c) {
        try {
            const res = await fetch(
                '/api/indonesia/districts?city_code=' + c.id,
            );
            const data = await res.json();
            row.districts = data.map((d: any) => ({
                value: d.name,
                label: d.name,
                id: d.code,
            }));
        } catch {}
    }
};

const fetchVillagesForRow = async (
    row: any,
    districtName: string,
    initialLoad = false,
) => {
    if (!initialLoad) {
        row.kelurahan_desa = '';
        row.villages = [];
    }

    const d = row.districts.find((dist: any) => dist.value === districtName);

    if (d) {
        try {
            const res = await fetch(
                '/api/indonesia/villages?district_code=' + d.id,
            );
            const data = await res.json();
            row.villages = data.map((v: any) => ({
                value: v.name,
                label: v.name,
                id: v.code,
            }));
        } catch {}
    }
};

const processFile = async (file: File) => {
    if (!file) {
        return;
    }

    errorMsg.value = '';
    uploadedFile.value = file;

    try {
        const rawResult = await readXlsxFile(file);

        let rows: any[] = rawResult as any[];

        // Tangani jika library mengembalikan array of sheets [{ sheet: '...', data: [...] }]
        if (
            rawResult &&
            rawResult.length > 0 &&
            rawResult[0] &&
            typeof rawResult[0] === 'object' &&
            'data' in (rawResult[0] as any) &&
            Array.isArray((rawResult[0] as any).data)
        ) {
            rows = (rawResult[0] as any).data;
        }

        if (!rows || rows.length === 0) {
            errorMsg.value = 'File Excel kosong sama sekali.';

            return;
        }

        let headerRow: any = rows[0];

        if (!Array.isArray(headerRow)) {
            if (headerRow !== null && typeof headerRow === 'object') {
                headerRow = Object.values(headerRow);
            } else {
                headerRow = [headerRow];
            }
        }

        const expectedHeaders = [
            'Nama',
            'Email',
            'Gender',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIP',
            'NIK',
            'No KK',
            'No Akta',
            'Nomor Telepon',
            'Alamat',
            'RT/RW',
            'Kelurahan',
            'Kecamatan',
            'Kabupaten',
            'Provinsi',
            'Kode Pos',
        ];

        if (headerRow.length < expectedHeaders.length) {
            errorMsg.value =
                'Format kolom tidak sesuai dengan template resmi. Pastikan urutan dan nama kolom sama persis.';

            return;
        }

        for (let i = 0; i < expectedHeaders.length; i++) {
            const currentHeader = String(headerRow[i] || '')
                .trim()
                .toLowerCase();
            const expectedHeader = expectedHeaders[i].toLowerCase();

            // Allow some flexibility like "rt/rw" vs "rt rw" but strictly enforce order
            if (
                currentHeader !== expectedHeader &&
                !(expectedHeader === 'rt/rw' && currentHeader.includes('rt'))
            ) {
                errorMsg.value =
                    'Format kolom tidak sesuai dengan template resmi. Pastikan urutan dan nama kolom sama persis.';

                return;
            }
        }

        const temp = [];

        for (let i = 1; i < rows.length; i++) {
            let row: any = rows[i];

            if (!Array.isArray(row)) {
                if (row !== null && typeof row === 'object') {
                    row = Object.values(row);
                } else {
                    row = [row];
                }
            }

            if (!row.some((r: any) => r !== null && r !== '')) {
                continue;
            }

            let parsedDate = '';
            const rawDate = row[4]; // Tanggal Lahir

            if (rawDate instanceof Date) {
                const d = rawDate;
                parsedDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            } else if (rawDate) {
                parsedDate = String(rawDate);
            }

            let rt = '';
            let rw = '';
            const rawRtRw = row[11];

            if (rawRtRw) {
                const parts = String(rawRtRw).split('/');

                if (parts[0]) {
                    rt = parts[0].trim();
                }

                if (parts[1]) {
                    rw = parts[1].trim();
                }
            }

            const newRow = {
                id: Date.now() + i,
                name: row[0] ? String(row[0]) : '',
                email: row[1] ? String(row[1]) : '',
                gender: row[2] ? String(row[2]) : '',
                tempat_lahir: row[3] ? String(row[3]) : '',
                tanggal_lahir: parsedDate,
                nip: row[5] ? String(row[5]) : '',
                nik: row[6] ? String(row[6]) : '',
                no_kk: row[7] ? String(row[7]) : '',
                no_akta_lahir: row[8] ? String(row[8]) : '',
                nomor_hp: row[9] ? String(row[9]) : '',
                alamat_lengkap: row[10] ? String(row[10]) : '',
                rt: rt,
                rw: rw,
                kelurahan_desa: row[12] ? String(row[12]) : '',
                kecamatan: row[13] ? String(row[13]) : '',
                kabupaten_kota: row[14] ? String(row[14]) : '',
                provinsi: row[15] ? String(row[15]) : '',
                kode_pos: row[16] ? String(row[16]) : '',
                cities: [],
                districts: [],
                villages: [],
                errors: {},
            };

            temp.push(newRow);

            // Preload locations if exists
            if (newRow.provinsi) {
                fetchCitiesForRow(newRow, newRow.provinsi, true).then(() => {
                    if (newRow.kabupaten_kota) {
                        fetchDistrictsForRow(
                            newRow,
                            newRow.kabupaten_kota,
                            true,
                        ).then(() => {
                            if (newRow.kecamatan) {
                                fetchVillagesForRow(
                                    newRow,
                                    newRow.kecamatan,
                                    true,
                                );
                            }
                        });
                    }
                });
            }
        }

        parsedData.value = temp;
        isParsed.value = true;
    } catch (err: any) {
        errorMsg.value =
            'Gagal memproses file Excel: ' +
            (err.message || 'Format tidak dikenali.');
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleFileUpload = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (file) {
        processFile(file);
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];

    if (file) {
        if (!file.name.match(/\.(xlsx|xls)$/i)) {
            errorMsg.value = 'Harap upload file Excel (.xlsx)';

            return;
        }

        processFile(file);
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const removeRow = (index: number) => {
    parsedData.value.splice(index, 1);
};

const validateData = () => {
    let isValid = true;
    parsedData.value.forEach((row) => {
        row.errors = {};

        if (!row.name) {
            row.errors.name = 'Nama wajib diisi.';
            isValid = false;
        }

        if (!row.email) {
            row.errors.email = 'Email wajib diisi.';
            isValid = false;
        } else if (!/^\S+@\S+\.\S+$/.test(row.email)) {
            row.errors.email = 'Format email tidak valid.';
            isValid = false;
        }

        if (!row.gender) {
            row.errors.gender = 'Gender wajib diisi.';
            isValid = false;
        }

        if (!row.tempat_lahir) {
            row.errors.tempat_lahir = 'Tempat Lahir wajib diisi.';
            isValid = false;
        }

        if (!row.tanggal_lahir) {
            row.errors.tanggal_lahir = 'Tanggal Lahir wajib diisi.';
            isValid = false;
        }
    });

    return isValid;
};

const submitImport = () => {
    if (parsedData.value.length === 0) {
        errorMsg.value = 'Tidak ada data untuk diimpor.';

        return;
    }

    if (!validateData()) {
        errorMsg.value =
            'Terdapat kesalahan pengisian data pada tabel. Silakan periksa kolom yang bergaris merah.';

        return;
    }

    form.items = parsedData.value.map((row) => ({
        name: row.name,
        email: row.email,
        gender: row.gender,
        tempat_lahir: row.tempat_lahir,
        tanggal_lahir: row.tanggal_lahir,
        nik: row.nik,
        nip: row.nip,
        nomor_hp: row.nomor_hp,
        no_kk: row.no_kk,
        no_akta_lahir: row.no_akta_lahir,
        alamat_lengkap: row.alamat_lengkap,
        rt: row.rt,
        rw: row.rw,
        kode_pos: row.kode_pos,
        provinsi: row.provinsi,
        kabupaten_kota: row.kabupaten_kota,
        kecamatan: row.kecamatan,
        kelurahan_desa: row.kelurahan_desa,
    }));

    isImporting.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    form.transform((data) => ({
        data: data.items,
    })).post(importMethod.url(), {
        onSuccess: () => {
            isImporting.value = false;
        },
        onError: (err) => {
            isImporting.value = false;

            // Map backend errors
            if (Object.keys(err).length > 0) {
                errorMsg.value = 'Terdapat kesalahan validasi dari server.';

                for (const [key, msg] of Object.entries(err)) {
                    const match = key.match(/^data\.(\d+)\.(.+)$/);

                    if (match) {
                        const index = parseInt(match[1]);
                        const field = match[2];

                        if (parsedData.value[index]) {
                            parsedData.value[index].errors[field] = (
                                msg as string
                            ).replace(/data\.\d+\./g, '');
                        }
                    }
                }
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

const addRow = () => {
    parsedData.value.push({
        id: Date.now() + Math.random(),
        name: '',
        email: '',
        gender: '',
        tempat_lahir: '',
        tanggal_lahir: '',
        nik: '',
        nip: '',
        nomor_hp: '',
        no_kk: '',
        no_akta_lahir: '',
        alamat_lengkap: '',
        rt: '',
        rw: '',
        kode_pos: '',
        provinsi: '',
        kabupaten_kota: '',
        kecamatan: '',
        kelurahan_desa: '',
        cities: [],
        districts: [],
        villages: [],
        errors: {},
    });
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
    window.location.href = exportTemplate.url();
};

const genderOptions = [
    { value: 'Laki-Laki', label: 'Laki-Laki' },
    { value: 'Perempuan', label: 'Perempuan' },
];
</script>

<template>
    <div class="w-full">
        <Head title="Import Pegawai" />

        <div
            class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Import Pegawai
                </h1>
                <p
                    class="mt-1 max-w-xl text-sm text-gray-500 dark:text-slate-400"
                >
                    Import data pegawai secara massal menggunakan file Excel
                    (.xlsx).
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="max-w-400">
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
                                ? 'border-primary bg-primary/5 dark:border-blue-500 dark:bg-blue-950/20'
                                : 'border-gray-200 bg-gray-50 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800'
                        "
                        @click="triggerFileInput"
                    >
                        <template v-if="!uploadedFile">
                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-gray-100 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
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
                                Anda
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
                            accept=".xlsx"
                            class="hidden"
                        />
                    </div>

                    <div
                        v-if="errorMsg"
                        class="mb-6 flex items-center gap-2 rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm font-medium text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
                    >
                        <span>{{ errorMsg }}</span>
                    </div>

                    <div
                        class="rounded-xl border border-[#FEF08A] bg-[#FEF9C3]/40 p-6 dark:border-amber-900/50 dark:bg-amber-950/30"
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
                            <h4 class="text-sm font-bold">Format Wajib</h4>
                        </div>
                        <ul
                            class="ml-1 list-inside list-disc space-y-1.5 text-sm text-[#9A3412]/80 dark:text-amber-300/80"
                        >
                            <li>
                                Baris pertama file
                                <span class="font-bold"
                                    >harus berupa header</span
                                >
                                nama kolom.
                            </li>
                            <li>
                                Kolom wajib:
                                <span
                                    class="rounded bg-[#FEF08A] px-1 font-bold dark:bg-amber-900/60 dark:text-amber-200"
                                    >name</span
                                >,
                                <span
                                    class="rounded bg-[#FEF08A] px-1 font-bold dark:bg-amber-900/60 dark:text-amber-200"
                                    >email</span
                                >.
                            </li>
                            <li>
                                Kolom opsional:
                                <span
                                    class="rounded bg-[#FEF08A] px-1 font-bold dark:bg-amber-900/60 dark:text-amber-200"
                                    >nip</span
                                >,
                                <span
                                    class="rounded bg-[#FEF08A] px-1 font-bold dark:bg-amber-900/60 dark:text-amber-200"
                                    >nik</span
                                >, dll.
                            </li>
                            <li>
                                Email tidak boleh duplikat (sudah terdaftar).
                            </li>
                            <li>
                                Password default:
                                <span class="font-bold"
                                    >Tanggal Lahir (DDMMYYYY)</span
                                >
                                atau <span class="font-bold">dalwa123</span>.
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
                            Download Template Resmi
                        </SecondaryButton>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col overflow-hidden rounded-4xl border border-gray-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 p-6 md:p-8 dark:border-slate-800"
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
                                {{ parsedData.length }} data ditemukan. Anda
                                dapat memperbaiki data langsung di tabel.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            v-if="
                                Object.keys(form.errors).length === 0 &&
                                !errorMsg
                            "
                            class="flex items-center rounded-full bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            Semua Data Valid
                        </div>
                        <SecondaryButton
                            @click="resetImport"
                            :disabled="isImporting"
                            type="button"
                            >Batal
                        </SecondaryButton>
                        <PrimaryButton
                            @click="submitImport"
                            :disabled="isImporting"
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
                    class="mx-6 mt-6 flex items-start gap-3 rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm font-medium text-rose-600 md:mx-8 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
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

                <!-- THE MASSIVE HORIZONTAL TABLE WITH ALL INPUTS -->
                <div class="overflow-x-auto p-6 md:p-8">
                    <table
                        class="w-full min-w-max divide-y divide-gray-200 border-b border-gray-100 dark:divide-slate-800 dark:border-slate-800"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NAMA LENGKAP
                                    <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-55 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    EMAIL <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    GENDER <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    TEMPAT LAHIR
                                    <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    TGL LAHIR
                                    <span class="text-red-500">*</span>
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NIK
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NIP
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NO. HP
                                </th>
                                <th
                                    class="min-w-45 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NO. KK
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    NO. AKTA LAHIR
                                </th>
                                <th
                                    class="min-w-62.5 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    ALAMAT LENGKAP
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    PROVINSI
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    KABUPATEN
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    KECAMATAN
                                </th>
                                <th
                                    class="min-w-50 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    KELURAHAN
                                </th>
                                <th
                                    class="min-w-30 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    RT/RW
                                </th>
                                <th
                                    class="min-w-30 px-3 py-3 text-left text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    KODE POS
                                </th>
                                <th
                                    class="min-w-20 px-3 py-3 text-center text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    AKSI
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-slate-800/80"
                        >
                            <tr
                                v-for="(row, index) in parsedData"
                                :key="row.id"
                                class="hover:bg-gray-50 dark:bg-slate-800 dark:hover:bg-slate-800/50"
                            >
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.name"
                                        :error="row.errors.name"
                                        placeholder="Nama Lengkap"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.email"
                                        type="email"
                                        :error="row.errors.email"
                                        placeholder="contoh@email.com"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.gender"
                                        :options="genderOptions"
                                        :error="row.errors.gender"
                                        placeholder="Pilih Gender"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.tempat_lahir"
                                        :error="row.errors.tempat_lahir"
                                        placeholder="Tempat Lahir"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomDatePicker
                                        v-model="row.tanggal_lahir"
                                        :error="row.errors.tanggal_lahir"
                                        placeholder="YYYY-MM-DD"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.nik"
                                        :error="row.errors.nik"
                                        placeholder="16 digit NIK"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.nip"
                                        :error="row.errors.nip"
                                        placeholder="Nomor Induk Pegawai"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.nomor_hp"
                                        :error="row.errors.nomor_hp"
                                        placeholder="0812..."
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.no_kk"
                                        :error="row.errors.no_kk"
                                        placeholder="16 digit KK"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.no_akta_lahir"
                                        :error="row.errors.no_akta_lahir"
                                        placeholder="No Registrasi"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.alamat_lengkap"
                                        :error="row.errors.alamat_lengkap"
                                        placeholder="Contoh Alamat No. 123"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.provinsi"
                                        :options="provinces"
                                        :error="row.errors.provinsi"
                                        placeholder="Pilih Provinsi"
                                        @update:modelValue="
                                            (val) => fetchCitiesForRow(row, val)
                                        "
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.kabupaten_kota"
                                        :options="row.cities"
                                        :error="row.errors.kabupaten_kota"
                                        placeholder="Pilih Kabupaten"
                                        @update:modelValue="
                                            (val) =>
                                                fetchDistrictsForRow(row, val)
                                        "
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.kecamatan"
                                        :options="row.districts"
                                        :error="row.errors.kecamatan"
                                        placeholder="Pilih Kecamatan"
                                        @update:modelValue="
                                            (val) =>
                                                fetchVillagesForRow(row, val)
                                        "
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <CustomSelect
                                        v-model="row.kelurahan_desa"
                                        :options="row.villages"
                                        :error="row.errors.kelurahan_desa"
                                        placeholder="Pilih Kelurahan"
                                    />
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <div class="flex items-center gap-1">
                                        <TextInput
                                            v-model="row.rt"
                                            :error="row.errors.rt"
                                            placeholder="RT"
                                            class="w-14"
                                        />
                                        <span
                                            class="text-gray-400 dark:text-slate-500"
                                            >/</span
                                        >
                                        <TextInput
                                            v-model="row.rw"
                                            :error="row.errors.rw"
                                            placeholder="RW"
                                            class="w-14"
                                        />
                                    </div>
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <TextInput
                                        v-model="row.kode_pos"
                                        :error="row.errors.kode_pos"
                                        placeholder="Kode Pos"
                                    />
                                </td>
                                <td class="px-3 py-4 text-center align-top">
                                    <div
                                        class="flex h-10.5 items-center justify-center"
                                    >
                                        <button
                                            @click="removeRow(index)"
                                            type="button"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-50 text-rose-500 transition-colors hover:bg-rose-100 hover:text-rose-600 focus:ring-2 focus:ring-rose-500/30 focus:outline-none dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-900/60 dark:hover:text-rose-300"
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
                                                    stroke-width="2.5"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-6 flex justify-center">
                        <button
                            @click="addRow"
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:bg-blue-950/30 dark:hover:bg-slate-800/50 dark:hover:text-blue-400"
                        >
                            <svg
                                class="mr-2 h-5 w-5"
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
                            Tambah Baris Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
