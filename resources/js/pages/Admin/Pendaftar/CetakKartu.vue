<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    pendaftar: any;
}>();

const handlePrint = () => {
    window.print();
};

const handleClose = () => {
    window.close();
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return dateStr;
}

        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const formatDateTime = (dateStr?: string) => {
    if (!dateStr) {
return { date: '-', time: '-' };
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return { date: dateStr, time: '-' };
}

        const date = d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
        const time = d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        return { date, time };
    } catch {
        return { date: dateStr, time: '-' };
    }
};

const normalizePhotoUrl = (raw?: string | null): string | null => {
    if (!raw) {
return null;
}

    if (
        raw.startsWith('http://') ||
        raw.startsWith('https://') ||
        raw.startsWith('data:image') ||
        raw.startsWith('/storage/') ||
        raw.startsWith('/')
    ) {
        return raw;
    }

    if (raw.startsWith('storage/')) {
        return `/${raw}`;
    }

    return `/storage/${raw.replace(/^\/+/, '')}`;
};

const getPendaftarPhoto = (pendaftar: any) => {
    if (!pendaftar) {
return '';
}

    const raw =
        pendaftar.foto_url ||
        pendaftar.foto ||
        pendaftar.pas_foto ||
        pendaftar.personal_data?.foto_url ||
        pendaftar.personal_data?.foto ||
        pendaftar.personal_data?.pas_foto ||
        pendaftar.dokumens?.find(
            (d: any) =>
                d.is_profile_photo ||
                d.dokumen?.is_profile_photo ||
                (d.dokumen?.name || d.dokumen?.nama_dokumen || '')
                    .toLowerCase()
                    .includes('foto'),
        )?.file_path ||
        null;

    const normalized = normalizePhotoUrl(raw);

    if (normalized) {
        return normalized;
    }

    // Avatar with participant name & theme green background
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(pendaftar.nama || 'Peserta')}&background=1b5e20&color=fff&size=256&bold=true`;
};

const getPilihanJenjangLanjutanText = (pendaftar: any) => {
    const jenjangName =
        pendaftar.jenjang?.name || pendaftar.program_studi || '-';
    const edu = pendaftar.education_data || {};
    const code = (pendaftar.jenjang?.code || '').toUpperCase();

    if (code === 'MTS') {
        const rawTingkat = edu.tingkat_nama || edu.kelas_tingkat || edu.tingkat;
        const tingkat = rawTingkat
            ? String(rawTingkat).toLowerCase().includes('kelas')
                ? rawTingkat
                : `Kelas ${rawTingkat}`
            : pendaftar.tipe_pendaftaran === 'Pindahan'
              ? 'Pindahan'
              : 'Kelas 7';

        return `${jenjangName} ${tingkat}`;
    }

    if (code === 'MA') {
        const rawTingkat = edu.tingkat_nama || edu.kelas_tingkat || edu.tingkat;
        const tingkat = rawTingkat
            ? String(rawTingkat).toLowerCase().includes('kelas')
                ? rawTingkat
                : `Kelas ${rawTingkat}`
            : pendaftar.tipe_pendaftaran === 'Pindahan'
              ? 'Pindahan'
              : 'Kelas 10';
        const jurusan =
            edu.jurusan_nama || edu.jurusan_ma || edu.jurusan || '';

        return jurusan ? `${jenjangName} ${tingkat} | ${jurusan}` : `${jenjangName} ${tingkat}`;
    }

    const prodiUtama = edu.fakultas_utama_nama
        ? `${edu.fakultas_utama_nama} - ${edu.prodi_utama_nama || ''}`
        : edu.fakultas_prodi_utama || edu.prodi_utama || edu.prodi;

    if (prodiUtama) {
        return `${jenjangName} | ${prodiUtama}`;
    }

    return `${jenjangName}`;
};

const kelompokUjian = computed(() => {
    if (
        props.pendaftar.kelompok_ujians &&
        props.pendaftar.kelompok_ujians.length > 0
    ) {
        return props.pendaftar.kelompok_ujians[0];
    }

    return null;
});

const interviewDate = computed(() => {
    if (kelompokUjian.value && kelompokUjian.value.tanggal_ujian) {
        return formatDate(kelompokUjian.value.tanggal_ujian);
    }

    return 'Belum Dijadwalkan';
});

const interviewTime = computed(() => {
    if (kelompokUjian.value && kelompokUjian.value.waktu_mulai) {
        return `${kelompokUjian.value.waktu_mulai.substring(0, 5)} - ${kelompokUjian.value.waktu_selesai ? kelompokUjian.value.waktu_selesai.substring(0, 5) : 'Selesai'}`;
    }

    return '-';
});

const interviewLocation = computed(() => {
    if (kelompokUjian.value && kelompokUjian.value.lokasi) {
        return kelompokUjian.value.lokasi;
    }

    return 'Belum Ditentukan';
});

interface SignatureSlot {
    key: string;
    roleTitle: string;
    roleSub: string;
    officerName: string | null;
    isKoordinator?: boolean;
}

const signatureSlots = computed<SignatureSlot[]>(() => {
    const ku = kelompokUjian.value;
    const pengujis = (ku?.pengujis || []) as any[];
    const koordinators = (ku?.koordinator || ku?.pengawas || []) as any[];

    const findByRole = (role: string) => {
        return pengujis.find((p) => p.pivot?.peran === role);
    };

    const pewawancara = findByRole('interview');
    const membaca = findByRole('tes_membaca');
    const menulis = findByRole('tes_menulis');
    const hafalan = findByRole('tes_hafalan');
    const koordinator = koordinators.length > 0 ? koordinators[0] : null;

    // Fallbacks if pivot peran was not explicitly set in legacy records
    const fallbackPengujis = pengujis.filter((p) => !p.pivot?.peran);

    return [
        {
            key: 'interview',
            roleTitle: 'PEWAWANCARA',
            roleSub: 'Interview / Kepribadian',
            officerName:
                pewawancara?.name ||
                (fallbackPengujis[0] ? fallbackPengujis[0].name : null),
        },
        {
            key: 'tes_membaca',
            roleTitle: 'PENGUJI MEMBACA',
            roleSub: "Al-Qur'an & Kitab",
            officerName:
                membaca?.name ||
                (fallbackPengujis[1] ? fallbackPengujis[1].name : null),
        },
        {
            key: 'tes_menulis',
            roleTitle: 'PENGUJI MENULIS',
            roleSub: "Imla' & Khath",
            officerName:
                menulis?.name ||
                (fallbackPengujis[2] ? fallbackPengujis[2].name : null),
        },
        {
            key: 'tes_hafalan',
            roleTitle: 'PENGUJI HAFALAN',
            roleSub: "Juz 'Amma & Doa",
            officerName:
                hafalan?.name ||
                (fallbackPengujis[3] ? fallbackPengujis[3].name : null),
        },
        {
            key: 'koordinator',
            roleTitle: 'KOORDINATOR PSB',
            roleSub: 'Penanggung Jawab',
            officerName: koordinator?.name || null,
            isKoordinator: true,
        },
    ];
});
</script>

<template>
    <div
        class="min-h-screen bg-slate-100 p-4 font-sans text-gray-900 sm:p-6 dark:bg-slate-950 dark:text-slate-100 print:bg-white print:p-0"
    >
        <Head :title="`Kartu Peserta - ${props.pendaftar.nama}`" />

        <!-- TOP ACTION BAR (Screen Only) -->
        <div
            class="mx-auto mb-4 flex max-w-[794px] justify-end gap-3 print:hidden"
        >
            <button
                type="button"
                @click="handleClose"
                class="cursor-pointer rounded-full border border-gray-300 bg-white px-5 py-2 text-xs font-bold text-gray-800 shadow-xs transition-colors hover:bg-gray-50 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                Tutup
            </button>
            <button
                type="button"
                @click="handlePrint"
                class="flex cursor-pointer items-center gap-2 rounded-full bg-[#1b5e20] px-5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-[#14532d] sm:text-sm dark:bg-emerald-700 dark:hover:bg-emerald-600"
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
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                <span>Cetak Kartu</span>
            </button>
        </div>

        <!-- PRINTABLE CARD CONTAINER (STRICT 1 PAGE A4) -->
        <div
            class="printable-card mx-auto max-w-[794px] rounded-xs border-[3px] border-[#1b5e20] bg-white p-2.5 font-sans shadow-xl sm:p-3.5 dark:bg-slate-900 print:shadow-none print:p-2"
        >
            <div class="space-y-2 border border-[#1b5e20] p-2 sm:p-2.5">
                <!-- HEADER BOX -->
                <div
                    class="rounded-xs bg-[#1b5e20] px-4 py-2.5 text-center text-white"
                >
                    <h1
                        class="text-xl leading-tight font-black tracking-wider uppercase sm:text-2xl"
                    >
                        KARTU PESERTA
                    </h1>
                    <h2
                        class="mt-0.5 text-sm leading-tight font-bold tracking-wide sm:text-base text-emerald-100"
                    >
                        Tes Seleksi & Wawancara
                    </h2>
                    <h3
                        class="mt-1 text-xs font-extrabold tracking-wider uppercase sm:text-sm"
                    >
                        PSB PON-PES DARULLUGHAH WADDA'WAH
                    </h3>
                    <h4
                        class="mt-0.5 text-[11px] font-bold tracking-widest text-emerald-200 uppercase"
                    >
                        WILAYAH KALIMANTAN BARAT
                    </h4>
                </div>

                <!-- BIODATA SECTION -->
                <div
                    class="grid grid-cols-12 items-stretch gap-2.5 rounded-xs border border-gray-300 p-2"
                >
                    <!-- PHOTO BOX (LEFT) -->
                    <div
                        class="col-span-4 flex flex-col items-center justify-center border border-gray-300 bg-gray-50 p-1 sm:col-span-3 dark:bg-slate-800"
                    >
                        <div
                            class="flex h-full max-h-[175px] min-h-[150px] w-full items-center justify-center overflow-hidden border border-gray-300 bg-gray-200"
                        >
                            <img
                                :src="getPendaftarPhoto(props.pendaftar)"
                                :alt="props.pendaftar.nama"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    </div>

                    <!-- DETAILS TABLE (RIGHT) -->
                    <div
                        class="col-span-8 flex flex-col justify-center text-xs sm:col-span-9"
                    >
                        <table class="w-full border-collapse table-fixed">
                            <colgroup>
                                <col style="width: 135px" />
                                <col style="width: 14px" />
                                <col />
                            </colgroup>
                            <tbody>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        NO. PENDAFTARAN
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-mono font-bold text-gray-900 dark:text-slate-100 text-[12px]"
                                    >
                                        {{
                                            props.pendaftar.nomor_pendaftaran ||
                                            '-'
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        NAMA PESERTA
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-black text-gray-900 uppercase dark:text-slate-100 text-[12px]"
                                    >
                                        {{ props.pendaftar.nama }}
                                    </td>
                                </tr>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        NO NIK
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-mono font-bold text-gray-900 dark:text-slate-100 text-[12px]"
                                    >
                                        {{ props.pendaftar.nik || '-' }}
                                    </td>
                                </tr>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        JENIS KELAMIN
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-semibold text-gray-900 dark:text-slate-100 text-[11.5px]"
                                    >
                                        {{
                                            props.pendaftar.personal_data
                                                ?.jenis_kelamin || '-'
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        TEMPAT, TGL LAHIR
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-semibold text-gray-900 dark:text-slate-100 text-[11.5px]"
                                    >
                                        {{
                                            props.pendaftar.personal_data
                                                ?.tempat_lahir || '-'
                                        }},
                                        {{
                                            formatDate(
                                                props.pendaftar.personal_data
                                                    ?.tanggal_lahir,
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    class="border-b border-gray-200 dark:border-slate-700/70"
                                >
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        NISN
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-mono font-semibold text-gray-900 dark:text-slate-100 text-[11.5px]"
                                    >
                                        {{
                                            props.pendaftar.education_data
                                                ?.pendidikan_sebelumnya?.nisn ||
                                            props.pendaftar.education_data
                                                ?.nisn ||
                                            '-'
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-2 py-0.5 font-extrabold text-[#1b5e20] uppercase text-[11.5px] whitespace-nowrap"
                                    >
                                        ASAL SEKOLAH
                                    </td>
                                    <td
                                        class="py-0.5 text-center font-bold text-gray-700 dark:text-slate-300 text-[11.5px]"
                                    >
                                        :
                                    </td>
                                    <td
                                        class="px-1 py-0.5 font-semibold text-gray-900 dark:text-slate-100 text-[11.5px]"
                                    >
                                        {{
                                            props.pendaftar.education_data
                                                ?.pendidikan_sebelumnya
                                                ?.nama_sekolah ||
                                            props.pendaftar.education_data
                                                ?.asal_sekolah ||
                                            '-'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECTION 2: INFORMASI PENDAFTARAN (3 COLS) -->
                <div class="overflow-hidden rounded-xs border border-[#1b5e20]">
                    <div
                        class="grid grid-cols-12 bg-[#1b5e20] px-2 py-1 text-center text-[10.5px] font-extrabold tracking-wider text-white uppercase"
                    >
                        <div class="col-span-3 border-r border-emerald-700">
                            TANGGAL PENDAFTARAN
                        </div>
                        <div class="col-span-6 border-r border-emerald-700">
                            PILIHAN JENJANG LANJUTAN
                        </div>
                        <div class="col-span-3">STATUS</div>
                    </div>
                    <div
                        class="grid min-h-[46px] grid-cols-12 items-center bg-[#fffde7]/30 text-center text-xs"
                    >
                        <div
                            class="col-span-3 border-r border-gray-300 px-2 py-1 font-bold text-gray-900 dark:text-slate-100"
                        >
                            <div>
                                {{
                                    formatDateTime(props.pendaftar.created_at)
                                        .date
                                }}
                            </div>
                            <div
                                class="text-[10px] font-normal text-gray-400 dark:text-slate-500"
                            >
                                {{
                                    formatDateTime(props.pendaftar.created_at)
                                        .time
                                }}
                            </div>
                        </div>
                        <div
                            class="col-span-6 border-r border-gray-300 px-3 py-1 font-bold text-[#1b5e20]"
                        >
                            {{ getPilihanJenjangLanjutanText(props.pendaftar) }}
                        </div>
                        <div
                            class="col-span-3 px-2 py-1 font-bold text-gray-800 dark:text-slate-200"
                        >
                            {{ props.pendaftar.status || '-' }}
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: JADWAL UJIAN (3 COLS) -->
                <div class="overflow-hidden rounded-xs border border-[#1b5e20]">
                    <div
                        class="grid grid-cols-12 bg-[#1b5e20] px-2 py-1 text-center text-[10.5px] font-extrabold tracking-wider text-white uppercase"
                    >
                        <div class="col-span-4 border-r border-emerald-700">
                            TANGGAL UJIAN
                        </div>
                        <div class="col-span-3 border-r border-emerald-700">
                            WAKTU
                        </div>
                        <div class="col-span-5">LOKASI / RUANGAN</div>
                    </div>
                    <div
                        class="grid min-h-[42px] grid-cols-12 items-center bg-[#fffde7]/30 text-center text-xs"
                    >
                        <div
                            class="col-span-4 border-r border-gray-300 px-2 py-1 font-bold text-gray-900 dark:text-slate-100"
                        >
                            {{ interviewDate }}
                        </div>
                        <div
                            class="col-span-3 border-r border-gray-300 px-2 py-1 font-bold text-gray-900 dark:text-slate-100"
                        >
                            {{ interviewTime }}
                        </div>
                        <div
                            class="col-span-5 px-2 py-1 font-bold text-gray-800 dark:text-slate-200"
                        >
                            {{ interviewLocation }}
                        </div>
                    </div>
                </div>

                <!-- INFORMASI PENTING PILL -->
                <div
                    class="rounded-xs border border-[#fef3c7] bg-[#fffbeb] px-3 py-1.5 text-center text-[10.5px] leading-snug font-bold text-[#92400e]"
                >
                    <span class="font-extrabold tracking-wide uppercase"
                        >INFORMASI PENTING :</span
                    >
                    Kartu ini wajib dibawa saat pelaksanaan ujian & wawancara.
                    Harap hadir 15 menit sebelum jadwal dimulai.
                </div>

                <!-- SECTION 4: TANDA TANGAN 5 PENGUJI / KOORDINATOR PSB -->
                <div class="overflow-hidden rounded-xs border border-[#1b5e20]">
                    <div
                        class="flex items-center justify-center gap-2 bg-[#1b5e20] px-2 py-1 text-center text-[10.5px] font-extrabold tracking-wider text-white uppercase"
                    >
                        TANDA TANGAN PENGUJI & KOORDINATOR PSB
                    </div>
                    <div class="grid grid-cols-5 gap-px bg-[#1b5e20]/30">
                        <div
                            v-for="slot in signatureSlots"
                            :key="slot.key"
                            class="relative flex min-h-[88px] flex-col justify-between bg-white p-1.5 dark:bg-slate-900"
                        >
                            <!-- Role Title & Subtitle -->
                            <div class="text-center">
                                <div
                                    class="text-[9px] font-black tracking-tight uppercase leading-tight"
                                    :class="
                                        slot.isKoordinator
                                            ? 'text-amber-800 dark:text-amber-400'
                                            : 'text-[#1b5e20] dark:text-emerald-400'
                                    "
                                >
                                    {{ slot.roleTitle }}
                                </div>
                                <div
                                    class="mt-0.5 text-[7.5px] font-medium text-gray-500 dark:text-slate-400"
                                >
                                    {{ slot.roleSub }}
                                </div>
                            </div>

                            <!-- Watermark TTD Space -->
                            <div
                                class="my-1 flex items-center justify-center"
                            >
                                <span
                                    class="pointer-events-none text-xs font-black tracking-widest text-gray-200 select-none uppercase dark:text-slate-800"
                                >
                                    TTD
                                </span>
                            </div>

                            <!-- Assigned Name or Empty Line -->
                            <div class="mt-auto text-center">
                                <div
                                    v-if="slot.officerName"
                                    class="line-clamp-2 px-0.5 text-[8.5px] font-bold leading-tight text-gray-900 dark:text-slate-100"
                                    :title="slot.officerName"
                                >
                                    ( {{ slot.officerName }} )
                                </div>
                                <div
                                    v-else
                                    class="text-[8px] font-medium leading-tight text-gray-400 dark:text-slate-500"
                                >
                                    ( ............................ )
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER BAR -->
                <div
                    class="rounded-xs bg-[#1b5e20] py-1 text-center text-[10px] font-black tracking-widest text-white uppercase"
                >
                    PSB PON-PES DARULLUGHAH WADDA'WAH • KALIMANTAN BARAT
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 5mm 8mm;
    }
    html,
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .printable-card {
        max-height: 275mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
