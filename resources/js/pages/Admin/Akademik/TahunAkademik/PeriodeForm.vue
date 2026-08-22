<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import { store, update, index } from '@/routes/admin/akademik/tahun_akademik';

interface PivotJenjang {
    kuota?: number | null;
}

interface JenjangItem {
    id: string;
    name: string;
    code?: string;
    pivot?: PivotJenjang;
}

interface PeriodeItem {
    id: string;
    tahun_akademik_id: string;
    name: string;
    jalur_pendaftaran?: string;
    status: 'buka' | 'tutup' | 'draft';
    kuota?: number | null;
    start_date?: string;
    end_date?: string;
    jenjangs?: JenjangItem[];
}

interface TahunAkademikItem {
    id: string;
    name: string;
    is_active: boolean;
}

interface JenjangQuotaConfig {
    jenjang_id: string;
    jenjang_name: string;
    is_selected: boolean;
    is_unlimited: boolean;
    kuota: number | null;
}

const props = defineProps<{
    periode?: PeriodeItem;
    tahunAkademiks: TahunAkademikItem[];
    jenjangs: JenjangItem[];
    selectedTahunAkademikId?: string;
}>();

const isEditing = !!props.periode;

// Order Priority helper for Jenjang: MTs -> MA -> S1 -> S2 -> S3
const getJenjangOrderPriority = (name: string, code?: string): number => {
    const str = (name + ' ' + (code || '')).toLowerCase();

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

// Build initial jenjang items array sorted by MTs, MA, S1, S2, S3
const initialJenjangItems = computed<JenjangQuotaConfig[]>(() => {
    const attachedMap = new Map<string, number | null | undefined>();

    if (props.periode?.jenjangs) {
        props.periode.jenjangs.forEach((j) => {
            attachedMap.set(j.id, j.pivot ? j.pivot.kuota : null);
        });
    }

    if (!props.jenjangs) {
        return [];
    }

    // Clone and sort jenjangs array
    const sortedJenjangs = [...props.jenjangs].sort((a, b) => {
        return (
            getJenjangOrderPriority(a.name, a.code) -
            getJenjangOrderPriority(b.name, b.code)
        );
    });

    return sortedJenjangs.map((j) => {
        const isAttached = isEditing
            ? props.periode?.jenjangs
                ? props.periode.jenjangs.some((pj) => pj.id === j.id)
                : false
            : true; // Default selected on new create
        const kuotaVal = attachedMap.get(j.id);

        return {
            jenjang_id: j.id,
            jenjang_name: j.name,
            is_selected: isAttached,
            is_unlimited: isEditing
                ? isAttached
                    ? kuotaVal === null || kuotaVal === undefined
                    : false
                : false,
            kuota: isEditing
                ? isAttached && kuotaVal !== undefined
                    ? kuotaVal
                    : 100
                : 100,
        };
    });
});

const form = useForm({
    tahun_akademik_id:
        props.periode?.tahun_akademik_id ||
        props.selectedTahunAkademikId ||
        (props.tahunAkademiks && props.tahunAkademiks.length > 0
            ? props.tahunAkademiks[0].id
            : ''),
    name: props.periode?.name || '',
    jalur_pendaftaran: props.periode?.jalur_pendaftaran || 'Semua',
    status: props.periode?.status || 'buka',
    start_date: props.periode?.start_date
        ? props.periode.start_date.substring(0, 10)
        : '',
    end_date: props.periode?.end_date
        ? props.periode.end_date.substring(0, 10)
        : '',
    jenjang_items: initialJenjangItems.value,
});

const toggleAllJenjang = () => {
    const allSelected = form.jenjang_items.every((item) => item.is_selected);
    form.jenjang_items.forEach((item) => {
        item.is_selected = !allSelected;
    });
};

const submitForm = () => {
    // Process quota items before submit
    form.jenjang_items.forEach((item) => {
        if (item.is_unlimited || !item.is_selected) {
            item.kuota = null;
        }
    });

    if (isEditing && props.periode) {
        form.put(update.url({ model: 'periode', id: props.periode.id }));
    } else {
        form.post(store.url({ model: 'periode' }));
    }
};
</script>

<template>
    <div class="mx-auto w-full max-w-6xl">
        <Head
            :title="
                isEditing
                    ? 'Edit Periode Pendaftaran'
                    : 'Tambah Periode Pendaftaran'
            "
        />

        <!-- Page Header -->
        <div
            class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    {{
                        isEditing
                            ? 'Edit Periode Pendaftaran'
                            : 'Tambah Periode Pendaftaran'
                    }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{
                        isEditing
                            ? 'Perbarui konfigurasi gelombang pendaftaran, status, dan kuota santri.'
                            : 'Konfigurasi gelombang pendaftaran baru, peruntukan jenjang, serta kuota santri.'
                    }}
                </p>
            </div>
            <BackButton :href="index.url({ query: { tab: 'periode' } })"
                >Kembali</BackButton
            >
        </div>

        <!-- Main Form Container -->
        <form @submit.prevent="submitForm" class="space-y-6">
            <div
                class="space-y-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs sm:p-8 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Section 1: Informasi Utama Periode -->
                <div class="space-y-6">
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-slate-800"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 font-bold text-primary dark:bg-blue-950/60 dark:text-blue-400"
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
                        </div>
                        <div>
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-slate-100"
                            >
                                Informasi Utama Periode
                            </h2>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Tentukan Tahun Akademik, nama gelombang, jalur
                                pendaftaran, dan tanggal pembukaan.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <CustomSelect
                            id="tahun_akademik_id"
                            label="Tahun Akademik *"
                            v-model="form.tahun_akademik_id"
                            :options="
                                tahunAkademiks.map((ta) => ({
                                    value: ta.id,
                                    label:
                                        'TA ' +
                                        ta.name +
                                        (ta.is_active
                                            ? ' (Aktif Saat Ini)'
                                            : ''),
                                }))
                            "
                            :error="form.errors.tahun_akademik_id"
                            required
                        />

                        <TextInput
                            id="name"
                            label="Nama Periode / Gelombang *"
                            v-model="form.name"
                            :error="form.errors.name"
                            placeholder="Contoh: Gelombang 1 TA 2026/2027"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <CustomSelect
                            id="jalur_pendaftaran"
                            label="Jalur Pendaftaran *"
                            v-model="form.jalur_pendaftaran"
                            :options="[
                                {
                                    value: 'Semua',
                                    label: 'Semua Jalur (Reguler & Pindahan)',
                                },
                                { value: 'Reguler', label: 'Jalur Reguler' },
                                { value: 'Pindahan', label: 'Jalur Pindahan' },
                            ]"
                            :error="form.errors.jalur_pendaftaran"
                            required
                        />

                        <CustomSelect
                            id="status"
                            label="Status Periode *"
                            v-model="form.status"
                            :options="[
                                {
                                    value: 'buka',
                                    label: 'Buka (Aktif Pendaftaran)',
                                },
                                { value: 'tutup', label: 'Tutup (Selesai)' },
                                {
                                    value: 'draft',
                                    label: 'Draft (Belum Dibuka)',
                                },
                            ]"
                            :error="form.errors.status"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <TextInput
                            id="start_date"
                            label="Tanggal Buka Pendaftaran"
                            type="date"
                            v-model="form.start_date"
                            :error="form.errors.start_date"
                        />
                        <TextInput
                            id="end_date"
                            label="Tanggal Tutup Pendaftaran"
                            type="date"
                            v-model="form.end_date"
                            :error="form.errors.end_date"
                        />
                    </div>
                </div>

                <!-- Section 2: Peruntukan Jenjang & Konfigurasi Kuota (Sorted: MTs, MA, S1, S2, S3) -->
                <div class="space-y-6 pt-2">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
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
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-slate-100"
                                >
                                    Peruntukan Jenjang & Konfigurasi Kuota
                                    Santri
                                </h2>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Pilih jenjang yang dibuka pada periode ini
                                    (MTs, MA, S1, S2, S3) dan tentukan kuotanya.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="toggleAllJenjang"
                            class="cursor-pointer rounded-xl border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-bold text-primary shadow-xs transition-all hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-blue-400 dark:hover:bg-slate-700 dark:hover:bg-slate-800"
                        >
                            Pilih Semua
                        </button>
                    </div>

                    <!-- Sorted Cards Grid (MTs, MA, S1, S2, S3) -->
                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="item in form.jenjang_items"
                            :key="item.jenjang_id"
                            class="flex flex-col justify-between space-y-4 rounded-2xl border p-5 transition-all duration-200"
                            :class="
                                item.is_selected
                                    ? 'border-primary/40 bg-white shadow-xs ring-1 ring-primary/10 dark:border-blue-500/50 dark:bg-slate-800 dark:bg-slate-900 dark:ring-blue-500/20'
                                    : 'border-gray-200 bg-gray-50 opacity-65 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800/70 dark:bg-slate-900/50'
                            "
                        >
                            <!-- Header Card Jenjang -->
                            <div class="flex items-center justify-between">
                                <label
                                    class="flex cursor-pointer items-center space-x-3 select-none"
                                >
                                    <Checkbox
                                        v-model:checked="item.is_selected"
                                    />
                                    <span
                                        class="text-base font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                                        >{{ item.jenjang_name }}</span
                                    >
                                </label>

                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="
                                        item.is_selected
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400'
                                            : 'border-gray-200 bg-gray-100 text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            item.is_selected
                                                ? 'bg-emerald-500'
                                                : 'bg-gray-400 dark:bg-slate-500'
                                        "
                                    ></span>
                                    {{
                                        item.is_selected ? 'Dibuka' : 'Ditutup'
                                    }}
                                </span>
                            </div>

                            <!-- Controls Kuota saat Aktif -->
                            <div
                                v-if="item.is_selected"
                                class="space-y-3 border-t border-gray-100 pt-3 dark:border-slate-800"
                            >
                                <label
                                    class="flex cursor-pointer items-center space-x-2.5 text-xs font-bold text-gray-700 select-none dark:text-slate-200 dark:text-slate-300"
                                >
                                    <Checkbox
                                        v-model:checked="item.is_unlimited"
                                        @change="
                                            if (item.is_unlimited)
                                                item.kuota = null;
                                        "
                                    />
                                    <span>Kuota Tanpa Batas (Unlimited)</span>
                                </label>

                                <div
                                    v-if="!item.is_unlimited"
                                    class="space-y-1"
                                >
                                    <label
                                        class="block text-xs font-semibold text-gray-600 dark:text-slate-300 dark:text-slate-400"
                                        >Jumlah Kuota Santri *</label
                                    >
                                    <div class="relative rounded-xl">
                                        <TextInput
                                            :id="'kuota_' + item.jenjang_id"
                                            type="number"
                                            v-model="item.kuota"
                                            placeholder="Contoh: 150"
                                            class="w-full pr-16 text-sm"
                                            min="1"
                                            required
                                        />
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5"
                                        >
                                            <span
                                                class="text-xs font-bold text-gray-400 dark:text-slate-500"
                                                >Santri</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-3 text-center dark:border-emerald-900/50 dark:bg-emerald-950/40"
                                >
                                    <span
                                        class="flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400"
                                    >
                                        <svg
                                            class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
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
                                        Pendaftaran Tanpa Batas Kuota
                                    </span>
                                </div>
                            </div>

                            <div
                                v-else
                                class="border-t border-gray-100 pt-3 text-center dark:border-slate-800"
                            >
                                <span
                                    class="text-xs font-medium text-gray-400 italic dark:text-slate-500"
                                    >Jenjang ini tidak dibuka pada periode
                                    ini</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div
                    class="flex items-center justify-end space-x-3 border-t border-gray-100 pt-6 dark:border-slate-800"
                >
                    <Link :href="index.url({ query: { tab: 'periode' } })">
                        <SecondaryButton type="button" class="font-bold"
                            >Batal</SecondaryButton
                        >
                    </Link>
                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-sm font-bold"
                    >
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>
