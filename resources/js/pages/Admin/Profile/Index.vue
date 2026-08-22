<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
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
    update,
    password as updatePasswordRoute,
} from '@/routes/admin/profile';

defineOptions({ layout: AdminLayout });

interface RoleItem {
    id: string;
    name: string;
}

const props = defineProps<{
    user: any;
    roles: RoleItem[];
    tab?: string;
}>();

// ==========================================
// TAB STATE WITH URL SYNC
// ==========================================
const activeTab = ref<'biodata' | 'security'>(
    props.tab === 'security' ? 'security' : 'biodata',
);

const setTab = (tab: 'biodata' | 'security') => {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
};

// ==========================================
// MODAL: AVATAR / FOTO PROFIL
// ==========================================
const showAvatarModal = ref(false);
const tempAvatarData = ref<string | null>(null);

const avatarForm = useForm({
    foto: null as string | null,
});

const openAvatarModal = () => {
    tempAvatarData.value = null;
    avatarForm.clearErrors();
    showAvatarModal.value = true;
};

const closeAvatarModal = () => {
    showAvatarModal.value = false;
    tempAvatarData.value = null;
};

const saveAvatarCrop = () => {
    if (tempAvatarData.value) {
        avatarForm.foto = tempAvatarData.value;
        avatarForm.put(update.url({ query: { tab: activeTab.value } }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAvatarModal();
            },
        });
    }
};

const removeAvatar = () => {
    avatarForm.foto = '';
    avatarForm.put(update.url({ query: { tab: activeTab.value } }), {
        preserveScroll: true,
        onSuccess: () => {
            closeAvatarModal();
        },
    });
};

// ==========================================
// MODAL: EDIT BIODATA
// ==========================================
const modalEditBiodataOpen = ref(false);

const biodataForm = useForm({
    name: '',
    email: '',
    nik: '',
    nip: '',
    gender: 'Laki-Laki',
    nomor_hp: '',
    tempat_lahir: '',
    tanggal_lahir: '',
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
});

const openEditBiodataModal = () => {
    biodataForm.clearErrors();
    biodataForm.name = props.user?.name || '';
    biodataForm.email = props.user?.email || '';
    biodataForm.nik = props.user?.nik || '';
    biodataForm.nip = props.user?.nip || '';
    biodataForm.gender = props.user?.gender || 'Laki-Laki';
    biodataForm.nomor_hp = props.user?.nomor_hp || '';
    biodataForm.tempat_lahir = props.user?.tempat_lahir || '';
    biodataForm.tanggal_lahir = props.user?.tanggal_lahir || '';
    biodataForm.no_kk = props.user?.no_kk || '';
    biodataForm.no_akta_lahir = props.user?.no_akta_lahir || '';
    biodataForm.alamat_lengkap = props.user?.alamat_lengkap || '';
    biodataForm.rt = props.user?.rt || '';
    biodataForm.rw = props.user?.rw || '';
    biodataForm.kode_pos = props.user?.kode_pos || '';
    biodataForm.provinsi = props.user?.provinsi || '';
    biodataForm.kabupaten_kota = props.user?.kabupaten_kota || '';
    biodataForm.kecamatan = props.user?.kecamatan || '';
    biodataForm.kelurahan_desa = props.user?.kelurahan_desa || '';

    // Preload cascading region lists
    if (biodataForm.provinsi) {
        fetchCities(biodataForm.provinsi, false);

        if (biodataForm.kabupaten_kota) {
            fetchDistricts(biodataForm.kabupaten_kota, false);

            if (biodataForm.kecamatan) {
                fetchVillages(biodataForm.kecamatan, false);
            }
        }
    }

    modalEditBiodataOpen.value = true;
};

const closeEditBiodataModal = () => {
    modalEditBiodataOpen.value = false;
    biodataForm.clearErrors();
};

const submitBiodata = () => {
    biodataForm.put(update.url({ query: { tab: 'biodata' } }), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditBiodataModal();
        },
    });
};

// ==========================================
// FORM: KEAMANAN & PASSWORD
// ==========================================
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put(updatePasswordRoute.url({ query: { tab: 'security' } }), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordForm.clearErrors();
        },
    });
};

const passwordChecks = computed(() => {
    const p = passwordForm.password;

    return {
        length: p.length >= 8,
        hasLetter: /[a-zA-Z]/.test(p),
        hasNumber: /[0-9]/.test(p),
        matchesConfirm:
            p.length > 0 && p === passwordForm.password_confirmation,
    };
});

const isPasswordFormValid = computed(() => {
    return (
        passwordForm.current_password.length > 0 &&
        passwordChecks.value.length &&
        passwordChecks.value.hasLetter &&
        passwordChecks.value.hasNumber &&
        passwordChecks.value.matchesConfirm
    );
});

// ==========================================
// INDONESIA REGIONAL CASCADING LOGIC
// ==========================================
const provinces = ref<{ value: string; label: string }[]>([]);
const cities = ref<{ value: string; label: string }[]>([]);
const districts = ref<{ value: string; label: string }[]>([]);
const villages = ref<{ value: string; label: string }[]>([]);

const fetchProvinces = async () => {
    try {
        const res = await fetch('/api/indonesia/provinces');
        const data = await res.json();
        provinces.value = data.map((p: any) => ({
            value: p.code,
            label: p.name,
        }));
    } catch (e) {
        console.error('Error fetching provinces:', e);
    }
};

const fetchCities = async (provinceCode: string, resetLower = true) => {
    if (!provinceCode) {
        cities.value = [];
        districts.value = [];
        villages.value = [];

        return;
    }

    try {
        const res = await fetch(
            `/api/indonesia/cities?province_code=${provinceCode}`,
        );
        const data = await res.json();
        cities.value = data.map((c: any) => ({
            value: c.code,
            label: c.name,
        }));

        if (resetLower) {
            biodataForm.kabupaten_kota = '';
            biodataForm.kecamatan = '';
            biodataForm.kelurahan_desa = '';
            districts.value = [];
            villages.value = [];
        }
    } catch (e) {
        console.error('Error fetching cities:', e);
    }
};

const fetchDistricts = async (cityCode: string, resetLower = true) => {
    if (!cityCode) {
        districts.value = [];
        villages.value = [];

        return;
    }

    try {
        const res = await fetch(
            `/api/indonesia/districts?city_code=${cityCode}`,
        );
        const data = await res.json();
        districts.value = data.map((d: any) => ({
            value: d.code,
            label: d.name,
        }));

        if (resetLower) {
            biodataForm.kecamatan = '';
            biodataForm.kelurahan_desa = '';
            villages.value = [];
        }
    } catch (e) {
        console.error('Error fetching districts:', e);
    }
};

const fetchVillages = async (districtCode: string, resetLower = true) => {
    if (!districtCode) {
        villages.value = [];

        return;
    }

    try {
        const res = await fetch(
            `/api/indonesia/villages?district_code=${districtCode}`,
        );
        const data = await res.json();
        villages.value = data.map((v: any) => ({
            value: v.code,
            label: v.name,
        }));

        if (resetLower) {
            biodataForm.kelurahan_desa = '';
        }
    } catch (e) {
        console.error('Error fetching villages:', e);
    }
};

const onProvinceChange = (val: string) => {
    fetchCities(val, true);
};

const onCityChange = (val: string) => {
    fetchDistricts(val, true);
};

const onDistrictChange = (val: string) => {
    fetchVillages(val, true);
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    if (tabParam === 'security' || tabParam === 'biodata') {
        activeTab.value = tabParam;
    }

    fetchProvinces();
});

// Format date helper
const formatDate = (dateString: string) => {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Profil Saya" />

        <!-- Header Page Bar -->
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
            >
                Profil Saya
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Informasi detail akun dan pengaturan data personal Anda.
            </p>
        </div>

        <!-- MAIN PROFILE HERO CARD -->
        <div
            class="relative mt-4 flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
        >
            <!-- Cover Banner -->
            <div class="relative h-64 sm:h-72">
                <img
                    src="/image/hero.jpg"
                    alt="Poster Background"
                    class="h-full w-full object-cover object-[center_30%]"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-gray-900/85 via-gray-900/20 to-gray-900/30 dark:from-slate-950/90 dark:via-slate-950/40"
                ></div>
            </div>

            <!-- Profile Info Row -->
            <div
                class="relative z-10 -mt-16 flex flex-grow flex-col items-center px-8 md:items-start"
            >
                <!-- Avatar with Camera Trigger -->
                <div
                    class="group relative mb-6 h-32 w-32 shrink-0 overflow-hidden rounded-full border-4 border-white bg-white shadow-xl dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900"
                >
                    <img
                        v-if="props.user.foto_url || props.user.foto"
                        :src="
                            props.user.foto_url || `/storage/${props.user.foto}`
                        "
                        class="h-full w-full object-cover"
                        alt="Foto Profil"
                    />
                    <img
                        v-else
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.user.name)}&background=0D8ABC&color=fff&size=256`"
                        class="h-full w-full object-cover"
                        alt="Foto Profil"
                    />

                    <!-- Camera Trigger Overlay on Avatar -->
                    <button
                        @click="openAvatarModal"
                        type="button"
                        class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center bg-black/40 text-white opacity-0 transition-opacity group-hover:opacity-100"
                        title="Ubah Foto Profil"
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
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <span class="mt-1 text-[10px] font-bold"
                            >Ubah Foto</span
                        >
                    </button>
                </div>

                <!-- Name, Email & Circular Action Buttons Row (Baseline & Edge-to-Edge) -->
                <div
                    class="mb-4 flex w-full flex-col gap-4 sm:flex-row sm:items-baseline sm:justify-between"
                >
                    <!-- Left: Name & Email -->
                    <div>
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-slate-100"
                        >
                            {{ props.user.name }}
                        </h2>
                        <p
                            class="mt-1 font-medium text-gray-500 dark:text-slate-400"
                        >
                            {{ props.user.email }}
                        </p>
                    </div>

                    <!-- Right: Circular Action Buttons (Edit Foto, Edit Biodata) -->
                    <div class="flex items-center gap-2.5">
                        <!-- Tombol Edit Foto Profil (Bulat) -->
                        <button
                            @click="openAvatarModal"
                            type="button"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all duration-200 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:bg-slate-700 dark:hover:text-blue-400"
                            title="Edit Foto Profil"
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
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </button>

                        <!-- Tombol Edit Biodata Diri (Bulat) -->
                        <button
                            @click="openEditBiodataModal"
                            type="button"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:text-primary hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:bg-slate-700 dark:hover:bg-slate-800/50 dark:hover:text-blue-400"
                            title="Edit Biodata Diri"
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
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Badges Row -->
                <div
                    class="mt-auto mb-6 flex w-full flex-wrap items-center justify-center gap-3 border-t border-gray-100 pt-6 md:justify-start dark:border-slate-800"
                >
                    <span
                        v-if="props.user.is_active"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-600 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500 shadow-sm"
                        ></span>
                        Status Aktif
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-rose-500 shadow-sm"
                        ></span>
                        Nonaktif
                    </span>

                    <span
                        v-for="role in props.roles"
                        :key="role.id"
                        class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-600 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-300"
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
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        {{ role.name }}
                    </span>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <svg
                            class="h-4 w-4 text-slate-400"
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
                        NIP: {{ props.user.nip || '-' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- NAV TABS BAR (Layout Hero/Tab Grid dengan Icon di atas dan Label di bawah) -->
        <div
            class="rounded-2xl border border-gray-200/90 bg-white p-2 sm:p-2.5 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    @click="setTab('biodata')"
                    :class="[
                        activeTab === 'biodata'
                            ? 'bg-primary text-white shadow-sm ring-1 ring-primary dark:bg-blue-600 dark:ring-blue-500'
                            : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                        'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-3 text-center transition-all duration-150',
                    ]"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                        :class="
                            activeTab === 'biodata'
                                ? 'bg-white/20 text-white'
                                : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                        "
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
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <span class="text-xs font-bold leading-tight">Biodata Diri</span>
                </button>

                <button
                    type="button"
                    @click="setTab('security')"
                    :class="[
                        activeTab === 'security'
                            ? 'bg-primary text-white shadow-sm ring-1 ring-primary dark:bg-blue-600 dark:ring-blue-500'
                            : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                        'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-3 text-center transition-all duration-150',
                    ]"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                        :class="
                            activeTab === 'security'
                                ? 'bg-white/20 text-white'
                                : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                        "
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
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>
                    <span class="text-xs font-bold leading-tight">Keamanan & Password</span>
                </button>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 1: BIODATA DIRI (CLEAN READ-ONLY CARDS)               -->
        <!-- ========================================================= -->
        <div v-show="activeTab === 'biodata'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Card 1: Identitas Diri (2 cols) -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex-grow p-6 sm:p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 dark:border-indigo-900/40 dark:bg-indigo-950/40"
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
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Identitas Diri
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Informasi personal dasar pegawai
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NIK
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.nik || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NIP
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.nip || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tempat Lahir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.tempat_lahir || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tanggal Lahir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ formatDate(props.user.tanggal_lahir) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jenis Kelamin
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.gender || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-indigo-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nomor HP / WA
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.nomor_hp || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Berkas & Legalitas (1 col) -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex-grow p-6 sm:p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/40"
                                >
                                    <svg
                                        class="h-6 w-6 text-emerald-500 dark:text-emerald-400"
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
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Dokumen
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Berkas & Legalitas
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-emerald-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    No Kartu Keluarga
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.no_kk || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-emerald-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    No Akta Kelahiran
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.no_akta_lahir || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-emerald-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tanggal Terdaftar
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ formatDate(props.user.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Alamat Tempat Tinggal (3 cols full width) -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl lg:col-span-3 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/40"
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
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Alamat Tempat Tinggal
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Informasi domisili saat ini
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-5 transition-colors hover:bg-rose-50/30 md:col-span-2 xl:col-span-4 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jalan / Detail Alamat
                                </p>
                                <p
                                    class="text-sm leading-relaxed font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.alamat_lengkap || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    RT / RW
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.rt || '-' }} /
                                    {{ props.user.rw || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kelurahan / Desa
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.user.village?.name ||
                                        props.user.kelurahan_desa ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kecamatan
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.user.district?.name ||
                                        props.user.kecamatan ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kabupaten / Kota
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.user.city?.name ||
                                        props.user.kabupaten_kota ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Provinsi
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.user.province?.name ||
                                        props.user.provinsi ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800 dark:bg-slate-800/50 dark:hover:bg-rose-950/20"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kode Pos
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.user.kode_pos || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 2: KEAMANAN & PASSWORD                                -->
        <!-- ========================================================= -->
        <div
            v-show="activeTab === 'security'"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        >
            <!-- Form Ganti Password -->
            <div
                class="flex flex-col overflow-hidden border border-gray-100 bg-white p-6 shadow-sm sm:rounded-4xl sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-blue-100 bg-blue-50 text-blue-600 dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
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
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-xl font-bold text-gray-900 dark:text-slate-100"
                            >
                                Perbarui Kata Sandi
                            </h3>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                            >
                                Amankan akun Anda dengan kombinasi kata sandi
                                yang kuat
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitPassword" class="space-y-5">
                    <PasswordInput
                        label="Kata Sandi Saat Ini"
                        v-model="passwordForm.current_password"
                        :error="passwordForm.errors.current_password"
                        placeholder="Masukkan kata sandi lama"
                        required
                    />

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <PasswordInput
                            label="Kata Sandi Baru"
                            v-model="passwordForm.password"
                            :error="passwordForm.errors.password"
                            placeholder="Minimal 8 karakter"
                            required
                        />

                        <PasswordInput
                            label="Konfirmasi Kata Sandi Baru"
                            v-model="passwordForm.password_confirmation"
                            :error="passwordForm.errors.password_confirmation"
                            placeholder="Ulangi kata sandi baru"
                            required
                        />
                    </div>

                    <!-- Password Requirement Checklist -->
                    <div
                        class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/70"
                    >
                        <p
                            class="text-xs font-bold text-gray-700 dark:text-slate-200"
                        >
                            Persyaratan Kata Sandi:
                        </p>
                        <div
                            class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2"
                        >
                            <div
                                class="flex items-center gap-2"
                                :class="
                                    passwordChecks.length
                                        ? 'font-bold text-emerald-600 dark:text-emerald-400'
                                        : 'text-gray-400 dark:text-slate-500'
                                "
                            >
                                <span>{{
                                    passwordChecks.length ? '✓' : '○'
                                }}</span>
                                <span>Minimal 8 karakter</span>
                            </div>
                            <div
                                class="flex items-center gap-2"
                                :class="
                                    passwordChecks.hasLetter
                                        ? 'font-bold text-emerald-600 dark:text-emerald-400'
                                        : 'text-gray-400 dark:text-slate-500'
                                "
                            >
                                <span>{{
                                    passwordChecks.hasLetter ? '✓' : '○'
                                }}</span>
                                <span>Mengandung huruf</span>
                            </div>
                            <div
                                class="flex items-center gap-2"
                                :class="
                                    passwordChecks.hasNumber
                                        ? 'font-bold text-emerald-600 dark:text-emerald-400'
                                        : 'text-gray-400 dark:text-slate-500'
                                "
                            >
                                <span>{{
                                    passwordChecks.hasNumber ? '✓' : '○'
                                }}</span>
                                <span>Mengandung angka</span>
                            </div>
                            <div
                                class="flex items-center gap-2"
                                :class="
                                    passwordChecks.matchesConfirm
                                        ? 'font-bold text-emerald-600 dark:text-emerald-400'
                                        : 'text-gray-400 dark:text-slate-500'
                                "
                            >
                                <span>{{
                                    passwordChecks.matchesConfirm ? '✓' : '○'
                                }}</span>
                                <span>Konfirmasi kata sandi cocok</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <PrimaryButton
                            type="submit"
                            :loading="passwordForm.processing"
                            :disabled="!isPasswordFormValid"
                            class="font-bold"
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
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                            Ubah Kata Sandi
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Security Info Side Card -->
            <div class="space-y-6">
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white p-6 shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center gap-3 text-sm font-bold text-gray-900 dark:text-slate-100"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-400"
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
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <span>Tips Keamanan Akun</span>
                    </div>

                    <ul
                        class="mt-4 space-y-3 text-xs leading-relaxed text-gray-500 dark:text-slate-400"
                    >
                        <li class="flex items-start gap-2">
                            <span
                                class="font-bold text-primary dark:text-blue-400"
                                >•</span
                            >
                            <span
                                >Jangan membagikan kata sandi akun Anda kepada
                                siapapun.</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="font-bold text-primary dark:text-blue-400"
                                >•</span
                            >
                            <span
                                >Ganti kata sandi secara berkala minimal 3-6
                                bulan sekali.</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="font-bold text-primary dark:text-blue-400"
                                >•</span
                            >
                            <span
                                >Selalu klik <strong>Logout</strong> setelah
                                selesai menggunakan komputer umum.</span
                            >
                        </li>
                    </ul>
                </div>

                <div
                    class="flex flex-col overflow-hidden border border-gray-900 bg-gray-900 p-6 text-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-950"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-400"
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
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-emerald-400">
                                Enkripsi BCRYPT
                            </p>
                            <p class="text-sm font-bold">Data Terproteksi</p>
                        </div>
                    </div>
                    <p
                        class="mt-3 text-xs leading-relaxed text-gray-400 dark:text-slate-500"
                    >
                        Kredensial akun terlindungi dengan enkripsi hash standar
                        Laravel.
                    </p>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- MODAL: EDIT BIODATA DIRI (Standard App Modal UI)          -->
        <!-- ========================================================= -->
        <Modal
            :show="modalEditBiodataOpen"
            @close="closeEditBiodataModal"
            maxWidth="2xl"
            title="Edit Biodata Diri"
            description="Perbarui informasi data pribadi dan domisili Anda."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400"
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
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="editBiodataFormSubmit"
                @submit.prevent="submitBiodata"
                class="space-y-6"
            >
                <!-- Section 1: Identitas Pokok -->
                <div>
                    <h4
                        class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                    >
                        Identitas Utama
                    </h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextInput
                            label="Nama Lengkap"
                            v-model="biodataForm.name"
                            :error="biodataForm.errors.name"
                            placeholder="Nama Lengkap"
                            required
                        />

                        <TextInput
                            label="Alamat Email"
                            v-model="biodataForm.email"
                            type="email"
                            :error="biodataForm.errors.email"
                            placeholder="email@domain.com"
                            required
                        />

                        <TextInput
                            label="Nomor WhatsApp / HP"
                            v-model="biodataForm.nomor_hp"
                            :error="biodataForm.errors.nomor_hp"
                            placeholder="081234567890"
                        />

                        <CustomSelect
                            label="Jenis Kelamin"
                            v-model="biodataForm.gender"
                            :options="[
                                { value: 'Laki-Laki', label: 'Laki-Laki' },
                                { value: 'Perempuan', label: 'Perempuan' },
                            ]"
                            :error="biodataForm.errors.gender"
                            required
                        />

                        <TextInput
                            label="NIP"
                            v-model="biodataForm.nip"
                            :error="biodataForm.errors.nip"
                            placeholder="NIP Pegawai"
                        />

                        <TextInput
                            label="NIK"
                            v-model="biodataForm.nik"
                            :error="biodataForm.errors.nik"
                            placeholder="16 Digit NIK"
                            maxlength="16"
                        />

                        <TextInput
                            label="Tempat Lahir"
                            v-model="biodataForm.tempat_lahir"
                            :error="biodataForm.errors.tempat_lahir"
                            placeholder="Kota Kelahiran"
                            required
                        />

                        <CustomDatePicker
                            label="Tanggal Lahir"
                            v-model="biodataForm.tanggal_lahir"
                            :error="biodataForm.errors.tanggal_lahir"
                            required
                        />

                        <TextInput
                            label="No Kartu Keluarga (KK)"
                            v-model="biodataForm.no_kk"
                            :error="biodataForm.errors.no_kk"
                            placeholder="16 Digit No KK"
                            maxlength="16"
                        />

                        <TextInput
                            label="No Akta Kelahiran"
                            v-model="biodataForm.no_akta_lahir"
                            :error="biodataForm.errors.no_akta_lahir"
                            placeholder="No Akta Kelahiran"
                        />
                    </div>
                </div>

                <!-- Section 2: Alamat Domisili -->
                <div
                    class="border-t border-gray-100 pt-5 dark:border-slate-800"
                >
                    <h4
                        class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                    >
                        Alamat Domisili
                    </h4>
                    <div class="space-y-4">
                        <TextareaInput
                            label="Alamat Lengkap"
                            v-model="biodataForm.alamat_lengkap"
                            :error="biodataForm.errors.alamat_lengkap"
                            placeholder="Jalan, Gang, No Rumah"
                            rows="2"
                        />

                        <div class="grid grid-cols-3 gap-3">
                            <TextInput
                                label="RT"
                                v-model="biodataForm.rt"
                                :error="biodataForm.errors.rt"
                                placeholder="001"
                            />
                            <TextInput
                                label="RW"
                                v-model="biodataForm.rw"
                                :error="biodataForm.errors.rw"
                                placeholder="002"
                            />
                            <TextInput
                                label="Kode Pos"
                                v-model="biodataForm.kode_pos"
                                :error="biodataForm.errors.kode_pos"
                                placeholder="78123"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <CustomSelect
                                label="Provinsi"
                                v-model="biodataForm.provinsi"
                                :options="provinces"
                                :error="biodataForm.errors.provinsi"
                                placeholder="Pilih Provinsi"
                                @update:modelValue="onProvinceChange"
                            />

                            <CustomSelect
                                label="Kabupaten / Kota"
                                v-model="biodataForm.kabupaten_kota"
                                :options="cities"
                                :error="biodataForm.errors.kabupaten_kota"
                                placeholder="Pilih Kabupaten/Kota"
                                :disabled="!biodataForm.provinsi"
                                @update:modelValue="onCityChange"
                            />

                            <CustomSelect
                                label="Kecamatan"
                                v-model="biodataForm.kecamatan"
                                :options="districts"
                                :error="biodataForm.errors.kecamatan"
                                placeholder="Pilih Kecamatan"
                                :disabled="!biodataForm.kabupaten_kota"
                                @update:modelValue="onDistrictChange"
                            />

                            <CustomSelect
                                label="Kelurahan / Desa"
                                v-model="biodataForm.kelurahan_desa"
                                :options="villages"
                                :error="biodataForm.errors.kelurahan_desa"
                                placeholder="Pilih Kelurahan/Desa"
                                :disabled="!biodataForm.kecamatan"
                            />
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <SecondaryButton @click="closeEditBiodataModal" type="button">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    form="editBiodataFormSubmit"
                    :loading="biodataForm.processing"
                    class="font-bold"
                >
                    Simpan Perubahan
                </PrimaryButton>
            </template>
        </Modal>

        <!-- ========================================================= -->
        <!-- MODAL: UBAH FOTO PROFIL (Standard App Modal UI)           -->
        <!-- ========================================================= -->
        <Modal
            :show="showAvatarModal"
            @close="closeAvatarModal"
            maxWidth="md"
            title="Ubah Foto Profil"
            description="Unggah dan sesuaikan foto profil persegi (1:1)."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400"
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
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </div>
            </template>

            <div class="space-y-4">
                <ImageCropper
                    v-model="tempAvatarData"
                    :aspectRatio="1"
                    label="Pilih Foto Baru (JPG / PNG)"
                    helpText="Foto akan dipotong secara proporsional persegi (1:1)."
                />
            </div>

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <button
                        v-if="props.user.foto_url || props.user.foto"
                        @click="removeAvatar"
                        type="button"
                        class="cursor-pointer text-xs font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                    >
                        Hapus Foto
                    </button>
                    <div v-else></div>

                    <div class="flex gap-2">
                        <SecondaryButton
                            @click="closeAvatarModal"
                            type="button"
                        >
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            @click="saveAvatarCrop"
                            type="button"
                            :disabled="!tempAvatarData || avatarForm.processing"
                            :loading="avatarForm.processing"
                            class="font-bold"
                        >
                            Terapkan Foto
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Modal>
    </div>
</template>
