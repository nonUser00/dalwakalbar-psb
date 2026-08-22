<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import ImageCropper from '@/Components/Form/ImageCropper.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    index,
    store,
    update,
    resetPassword,
} from '@/routes/admin/pengaturan/pegawai';

const props = defineProps<{
    pegawai?: any;
}>();

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

const isEditing = !!props.pegawai;

const existingFotoUrl =
    props.pegawai?.foto_url ||
    (props.pegawai?.foto ? `/storage/${props.pegawai.foto}` : null);

const form = useForm({
    name: props.pegawai?.name || '',
    email: props.pegawai?.email || '',
    nik: props.pegawai?.nik || '',
    nip: props.pegawai?.nip || '',
    gender: props.pegawai?.gender || '',
    nomor_hp: props.pegawai?.nomor_hp || '',
    foto: existingFotoUrl as string | null,
    tempat_lahir: props.pegawai?.tempat_lahir || '',
    tanggal_lahir: props.pegawai?.tanggal_lahir || '',
    no_kk: props.pegawai?.no_kk || '',
    no_akta_lahir: props.pegawai?.no_akta_lahir || '',
    alamat_lengkap: props.pegawai?.alamat_lengkap || '',
    rt: props.pegawai?.rt || '',
    rw: props.pegawai?.rw || '',
    kode_pos: props.pegawai?.kode_pos || '',
    provinsi: props.pegawai?.provinsi || '',
    kabupaten_kota: props.pegawai?.kabupaten_kota || '',
    kecamatan: props.pegawai?.kecamatan || '',
    kelurahan_desa: props.pegawai?.kelurahan_desa || '',
});

// Modal Logic
const resetPasswordModal = ref(false);
const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetPassword = () => {
    let defaultPass = '12345678';

    if (props.pegawai?.tanggal_lahir) {
        const d = new Date(props.pegawai.tanggal_lahir);
        defaultPass =
            String(d.getDate()).padStart(2, '0') +
            String(d.getMonth() + 1).padStart(2, '0') +
            d.getFullYear();
    }

    resetForm.password = defaultPass;
    resetForm.password_confirmation = defaultPass;
    resetPasswordModal.value = true;
};

const submitResetPassword = () => {
    resetForm.post(resetPassword.url(props.pegawai.id), {
        onSuccess: () => (resetPasswordModal.value = false),
    });
};

const submitForm = () => {
    if (isEditing) {
        form.put(update.url(props.pegawai.id));
    } else {
        form.post(store.url());
    }
};

const provinces = ref<any[]>([]);
const cities = ref<any[]>([]);
const districts = ref<any[]>([]);
const villages = ref<any[]>([]);

const fetchProvinces = async () => {
    try {
        const res = await fetch('/api/indonesia/provinces');
        const data = await res.json();
        provinces.value = data.map((p: any) => ({
            value: p.name,
            label: p.name,
            id: p.code,
        }));

        if (form.provinsi) {
            const p = provinces.value.find(
                (prov: any) => prov.value === form.provinsi,
            );

            if (p) {
                await fetchCities(p.id, true);
            }
        }
    } catch {}
};

const fetchCities = async (provinceCode: string, init = false) => {
    try {
        const res = await fetch(
            '/api/indonesia/cities?province_code=' + provinceCode,
        );
        const data = await res.json();
        cities.value = data.map((c: any) => ({
            value: c.name,
            label: c.name,
            id: c.code,
        }));

        if (init && form.kabupaten_kota) {
            const c = cities.value.find(
                (city: any) => city.value === form.kabupaten_kota,
            );

            if (c) {
                await fetchDistricts(c.id, true);
            }
        }
    } catch {}
};

const fetchDistricts = async (cityCode: string, init = false) => {
    try {
        const res = await fetch(
            '/api/indonesia/districts?city_code=' + cityCode,
        );
        const data = await res.json();
        districts.value = data.map((d: any) => ({
            value: d.name,
            label: d.name,
            id: d.code,
        }));

        if (init && form.kecamatan) {
            const d = districts.value.find(
                (dist: any) => dist.value === form.kecamatan,
            );

            if (d) {
                await fetchVillages(d.id);
            }
        }
    } catch {}
};

const fetchVillages = async (districtCode: string) => {
    try {
        const res = await fetch(
            '/api/indonesia/villages?district_code=' + districtCode,
        );
        const data = await res.json();
        villages.value = data.map((v: any) => ({
            value: v.name,
            label: v.name,
            id: v.code,
        }));
    } catch {}
};

onMounted(() => {
    fetchProvinces();
});

const onProvinceChange = (newVal: string) => {
    form.kabupaten_kota = '';
    form.kecamatan = '';
    form.kelurahan_desa = '';
    cities.value = [];
    districts.value = [];
    villages.value = [];
    const p = provinces.value.find((prov: any) => prov.value === newVal);

    if (p) {
        fetchCities((p as any).id);
    }
};

const onCityChange = (newVal: string) => {
    form.kecamatan = '';
    form.kelurahan_desa = '';
    districts.value = [];
    villages.value = [];
    const c = cities.value.find((city: any) => city.value === newVal);

    if (c) {
        fetchDistricts((c as any).id);
    }
};

const onDistrictChange = (newVal: string) => {
    form.kelurahan_desa = '';
    villages.value = [];
    const d = districts.value.find((dist: any) => dist.value === newVal);

    if (d) {
        fetchVillages((d as any).id);
    }
};

defineOptions({ layout: AdminLayout });

const genderOptions = [
    { value: 'Laki-Laki', label: 'Laki-Laki' },
    { value: 'Perempuan', label: 'Perempuan' },
];
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="isEditing ? 'Edit Pegawai' : 'Tambah Pegawai'" />

        <div
            class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    {{ isEditing ? 'Edit Pegawai' : 'Tambah Pegawai Baru' }}
                </h1>
                <p
                    class="mt-1 max-w-xl text-sm text-gray-500 dark:text-slate-400"
                >
                    {{
                        isEditing
                            ? 'Perbarui informasi profil dan biodata pegawai.'
                            : 'Lengkapi formulir di bawah ini untuk menambahkan pegawai baru ke dalam sistem.'
                    }}
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="max-w-6xl">
            <div
                v-if="!isEditing"
                class="mb-8 flex items-start gap-4 rounded-3xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/40"
            >
                <div
                    class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50"
                >
                    <svg
                        class="h-5 w-5 text-blue-600 dark:text-blue-400"
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
                    <h4
                        class="text-sm font-bold text-blue-900 dark:text-blue-200"
                    >
                        Informasi Penting
                    </h4>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">
                        Password untuk akun baru akan otomatis digenerate dari
                        <strong>Tanggal Lahir</strong> pegawai dengan format
                        <code
                            class="rounded bg-blue-100 px-1 font-mono dark:bg-blue-900/60 dark:text-blue-200"
                            >DDMMYYYY</code
                        >
                        (contoh: 15081995).
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Data Pribadi & Foto Card -->
                <div
                    class="relative rounded-4xl border border-gray-100 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="mb-8 flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50"
                        >
                            <svg
                                class="h-6 w-6 text-blue-500 dark:text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Identitas Diri & Foto
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                Kredensial login, foto profil, dan biodata
                                pegawai
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        <div class="space-y-6 lg:col-span-8">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <TextInput
                                    v-model="form.name"
                                    label="Nama Lengkap"
                                    :error="form.errors.name"
                                    required
                                    placeholder="Masukkan nama lengkap"
                                />
                                <TextInput
                                    v-model="form.email"
                                    type="email"
                                    label="Email"
                                    :error="form.errors.email"
                                    required
                                    placeholder="Masukkan email aktif"
                                />

                                <TextInput
                                    v-model="form.nik"
                                    label="Nomor Induk Kependudukan (NIK)"
                                    :error="form.errors.nik"
                                    placeholder="16 digit NIK"
                                />
                                <TextInput
                                    v-model="form.nip"
                                    label="Nomor Induk Pegawai (NIP)"
                                    :error="form.errors.nip"
                                    placeholder="Masukkan NIP (jika ada)"
                                />

                                <CustomSelect
                                    v-model="form.gender"
                                    label="Jenis Kelamin"
                                    required
                                    :options="genderOptions"
                                    :error="form.errors.gender"
                                    placeholder="-- Pilih --"
                                />
                                <TextInput
                                    v-model="form.nomor_hp"
                                    label="Nomor HP/WA Aktif"
                                    :error="form.errors.nomor_hp"
                                    placeholder="Contoh: 081234567890"
                                />

                                <TextInput
                                    v-model="form.tempat_lahir"
                                    label="Tempat Lahir"
                                    required
                                    :error="form.errors.tempat_lahir"
                                    placeholder="Kota kelahiran"
                                />
                                <div class="space-y-1.5">
                                    <label
                                        class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                        >Tanggal Lahir</label
                                    >
                                    <CustomDatePicker
                                        v-model="form.tanggal_lahir"
                                        placeholder="Pilih Tanggal Lahir"
                                    />
                                    <p
                                        v-if="form.errors.tanggal_lahir"
                                        class="text-xs font-medium text-red-500"
                                    >
                                        {{ form.errors.tanggal_lahir }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="isEditing"
                                class="border-t border-gray-100 pt-4 dark:border-slate-800"
                            >
                                <p
                                    class="mb-3 text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                >
                                    Keamanan Akun
                                </p>
                                <button
                                    type="button"
                                    @click="openResetPassword"
                                    class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-700 transition-colors hover:bg-amber-100 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:outline-none dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/50"
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
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                        />
                                    </svg>
                                    Reset Password Akun
                                </button>
                            </div>
                        </div>

                        <div
                            class="border-l-0 border-gray-100 pt-8 pl-0 lg:col-span-4 lg:border-l lg:pt-0 lg:pl-8 dark:border-slate-800"
                        >
                            <ImageCropper
                                v-model="form.foto"
                                :initialImage="existingFotoUrl"
                                :error="form.errors.foto"
                                label="Foto Profil"
                            />
                        </div>
                    </div>
                </div>

                <!-- Dokumen Card -->
                <div
                    class="relative rounded-4xl border border-gray-100 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="mb-8 flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/50"
                        >
                            <svg
                                class="h-6 w-6 text-indigo-500 dark:text-indigo-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Dokumen Pendukung
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                Informasi registrasi dokumen kependudukan
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <TextInput
                            v-model="form.no_kk"
                            label="Nomor Kartu Keluarga"
                            :error="form.errors.no_kk"
                            placeholder="16 digit Nomor KK"
                        />
                        <TextInput
                            v-model="form.no_akta_lahir"
                            label="Nomor Akta Kelahiran"
                            :error="form.errors.no_akta_lahir"
                            placeholder="Nomor registrasi akta kelahiran"
                        />
                    </div>
                </div>

                <!-- Alamat Card -->
                <div
                    class="relative rounded-4xl border border-gray-100 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="mb-8 flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
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
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Alamat Tempat Tinggal
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                Detail domisili saat ini
                            </p>
                        </div>
                    </div>

                    <!-- 12-Column Grid Layout for Address -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                        <div class="md:col-span-3">
                            <CustomSelect
                                v-model="form.provinsi"
                                label="Provinsi"
                                :options="provinces"
                                :error="form.errors.provinsi"
                                placeholder="Pilih Provinsi"
                                @update:modelValue="onProvinceChange"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <CustomSelect
                                v-model="form.kabupaten_kota"
                                label="Kabupaten / Kota"
                                :options="cities"
                                :error="form.errors.kabupaten_kota"
                                placeholder="Pilih Kabupaten / Kota"
                                @update:modelValue="onCityChange"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <CustomSelect
                                v-model="form.kecamatan"
                                label="Kecamatan"
                                :options="districts"
                                :error="form.errors.kecamatan"
                                placeholder="Pilih Kecamatan"
                                @update:modelValue="onDistrictChange"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <CustomSelect
                                v-model="form.kelurahan_desa"
                                label="Kelurahan / Desa"
                                :options="villages"
                                :error="form.errors.kelurahan_desa"
                                placeholder="Pilih Kelurahan / Desa"
                            />
                        </div>

                        <div class="md:col-span-12">
                            <TextareaInput
                                v-model="form.alamat_lengkap"
                                label="Alamat Lengkap (Jalan / Perumahan)"
                                :error="form.errors.alamat_lengkap"
                                placeholder="Contoh: Jl. Merdeka No. 123"
                                :rows="3"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <TextInput
                                v-model="form.kode_pos"
                                label="Kode Pos"
                                :error="form.errors.kode_pos"
                                placeholder="Contoh: 12345"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <div class="space-y-1.5">
                                <label
                                    class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                    >RT / RW</label
                                >
                                <div
                                    class="relative flex w-full items-center rounded-xl border border-gray-200 bg-white shadow-sm transition-all focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:focus-within:border-blue-500"
                                >
                                    <input
                                        v-model="form.rt"
                                        type="text"
                                        maxlength="3"
                                        class="w-full border-none bg-transparent px-2 py-2.5 text-center text-sm font-medium focus:ring-0 focus:outline-none dark:text-slate-200"
                                        placeholder="001"
                                    />
                                    <span
                                        class="-mt-1 text-lg leading-none font-medium text-gray-400 select-none dark:text-slate-500"
                                        >/</span
                                    >
                                    <input
                                        v-model="form.rw"
                                        type="text"
                                        maxlength="3"
                                        class="w-full border-none bg-transparent px-2 py-2.5 text-center text-sm font-medium focus:ring-0 focus:outline-none dark:text-slate-200"
                                        placeholder="002"
                                    />
                                </div>
                                <p
                                    v-if="form.errors.rt || form.errors.rw"
                                    class="text-xs font-medium text-red-500"
                                >
                                    {{ form.errors.rt || form.errors.rw }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 pb-12">
                    <Link :href="index.url()">
                        <SecondaryButton>Batal</SecondaryButton>
                    </Link>
                    <PrimaryButton
                        type="submit"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg
                            v-if="!form.processing"
                            class="mr-2 h-4 w-4"
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
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Pegawai' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <!-- Reset Password Modal Form -->
        <Modal
            :show="resetPasswordModal"
            @close="resetPasswordModal = false"
            maxWidth="sm"
            title="Reset Password"
            description="Atur ulang sandi akun"
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
                    {{ props.pegawai?.name }}
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
    </div>
</template>
