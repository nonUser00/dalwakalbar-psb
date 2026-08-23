<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index, edit } from '@/routes/admin/pengaturan/pegawai';

defineOptions({ layout: AdminLayout });

defineProps<{
    pegawai: any;
}>();

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
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Profil Pegawai" />

        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Profil Pegawai
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Detail informasi pegawai dan hak akses.
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="space-y-6">
            <div
                class="relative mt-4 flex min-h-150 flex-col overflow-hidden border border-gray-100 bg-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="relative h-64 sm:h-72">
                    <img
                        src="/image/hero.jpg"
                        alt="Poster Background"
                        class="h-full w-full object-cover object-[center_30%]"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-gray-900/20 to-gray-900/30"
                    ></div>

                    <div
                        class="absolute top-4 right-4 left-4 z-20 flex items-center justify-between px-4 py-2 md:top-6 md:right-8 md:left-8"
                    >
                        <Link
                            v-if="
                                !pegawai.roles?.some(
                                    (r: any) => r.name === 'Super Admin',
                                )
                            "
                            :href="edit.url(pegawai.id)"
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 text-white shadow-md backdrop-blur-md transition-all duration-200 hover:scale-105 hover:border-blue-400 hover:bg-blue-600 hover:text-white hover:shadow-blue-500/30 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-blue-400 dark:hover:border-blue-500 dark:hover:bg-blue-600 dark:hover:text-white"
                            title="Edit Profil"
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
                        </Link>
                    </div>
                </div>

                <div
                    class="relative z-10 -mt-16 flex flex-grow flex-col items-center px-8 md:items-start"
                >
                    <div
                        class="mb-6 h-32 w-32 shrink-0 overflow-hidden rounded-full border-4 border-white bg-white shadow-xl dark:border-slate-800 dark:bg-slate-800"
                    >
                        <img
                            v-if="pegawai.foto"
                            :src="`/storage/${pegawai.foto}`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(pegawai.name)}&background=0D8ABC&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <div class="mb-4 w-full text-center md:text-left">
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-slate-100"
                        >
                            {{ pegawai.name }}
                        </h2>
                        <p
                            class="mt-1 font-medium text-gray-500 dark:text-slate-400"
                        >
                            {{ pegawai.email }}
                        </p>
                    </div>

                    <div
                        class="mt-auto mb-10 flex w-full flex-wrap items-center justify-center gap-3 border-t border-gray-100 pt-6 md:justify-start dark:border-slate-800"
                    >
                        <span
                            v-if="pegawai.is_active"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-600 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-500 shadow-sm dark:bg-emerald-400"
                            ></span>
                            Status Aktif
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-rose-500 shadow-sm dark:bg-rose-400"
                            ></span>
                            Nonaktif
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-600 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
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
                            {{
                                pegawai.roles
                                    ?.map((r: any) => r.name)
                                    .join(', ') || 'Pegawai'
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex-grow p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-500 dark:border-indigo-900/50 dark:bg-indigo-950/50 dark:text-indigo-400"
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
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NIK
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.nik || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NIP
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.nip || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tempat Lahir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.tempat_lahir || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tanggal Lahir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ formatDate(pegawai.tanggal_lahir) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jenis Kelamin
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.gender || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nomor HP / WA
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.nomor_hp || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex-grow p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-emerald-500 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400"
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
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    No Kartu Keluarga
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.no_kk || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    No Akta Kelahiran
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.no_akta_lahir || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-emerald-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tanggal Terdaftar
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ formatDate(pegawai.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl lg:col-span-3 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-8">
                        <div
                            class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-rose-100 bg-rose-50 text-rose-500 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400"
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
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-5 transition-colors hover:bg-rose-50/30 md:col-span-2 xl:col-span-4 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jalan / Detail Alamat
                                </p>
                                <p
                                    class="text-sm leading-relaxed font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.alamat_lengkap || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    RT / RW
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.rt || '-' }} /
                                    {{ pegawai.rw || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kelurahan / Desa
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.kelurahan_desa || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kecamatan
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.kecamatan || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kabupaten / Kota
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.kabupaten_kota || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 md:col-span-1 xl:col-span-2 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Provinsi
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.provinsi || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-rose-50/30 md:col-span-1 xl:col-span-2 dark:border-slate-800 dark:border-slate-800/60 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 flex items-center gap-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kode Pos
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                >
                                    {{ pegawai.kode_pos || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
