export enum PendaftarStatus {
    DRAFT = 'DRAFT',
    SUBMITTED = 'SUBMITTED',
    TAGIHAN = 'TAGIHAN',
    INTERVIEW = 'INTERVIEW',
    LULUS = 'LULUS',
    TIDAK_LULUS = 'TIDAK_LULUS',
    KEDATANGAN = 'KEDATANGAN',
    AKTIF = 'AKTIF',
    DITOLAK = 'DITOLAK',
}

export enum StatusTagihan {
    BELUM_BAYAR = 'BELUM_BAYAR',
    BELUM_LUNAS = 'BELUM_LUNAS',
    LUNAS = 'LUNAS',
    SAMAHA = 'SAMAHA',
    BATAL = 'BATAL',
}

export enum StatusPembayaran {
    MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI',
    DITERIMA = 'DITERIMA',
    DITOLAK = 'DITOLAK',
}

export enum MetodePembayaran {
    TUNAI = 'TUNAI',
    TRANSFER = 'TRANSFER',
    SAMAHA = 'SAMAHA',
}

export enum StatusKelulusan {
    LULUS = 'lulus',
    TIDAK_LULUS = 'tidak_lulus',
    CADANGAN = 'cadangan',
    PERTIMBANGAN = 'pertimbangan',
    PENDING = 'pending',
}

export enum StatusKelompokUjian {
    SCHEDULED = 'scheduled',
    IN_PROGRESS = 'in_progress',
    COMPLETED = 'completed',
    CANCELLED = 'cancelled',
}

export enum StatusInterview {
    BELUM_DIJADWALKAN = 'belum_dijadwalkan',
    TERJADWAL = 'terjadwal',
    SELESAI = 'selesai',
}

export enum GenderAllowed {
    L = 'L',
    P = 'P',
    ALL = 'ALL',
}

export enum JalurKeberangkatan {
    ROMBONGAN = 'ROMBONGAN',
    MANDIRI = 'MANDIRI',
}

export enum JenisRombongan {
    PESAWAT = 'PESAWAT',
    KAPAL = 'KAPAL',
    BUS = 'BUS',
}

export enum TipePendaftaran {
    BARU = 'Baru',
    PINDAHAN = 'Pindahan',
    LANJUTAN = 'Lanjutan',
}

export enum JalurPendaftaran {
    SEMUA = 'Semua',
    REGULER = 'Reguler',
    PRESTASI = 'Prestasi',
    BEASISWA = 'Beasiswa',
    PINDAHAN = 'Pindahan',
}

export enum JenisKategoriBiaya {
    PENDAFTARAN = 'pendaftaran',
    ROMBONGAN = 'rombongan',
    INTERVIEW = 'interview',
    LAINNYA = 'lainnya',
}

export interface StatusBadgeInfo {
    label: string;
    classes: string;
}

export function getPendaftarStatusBadge(
    status?: string | null,
): StatusBadgeInfo {
    const s = (status || 'DRAFT').toUpperCase();

    switch (s) {
        case PendaftarStatus.DRAFT:
            return {
                label: 'DRAFT',
                classes: 'bg-slate-100 text-slate-700 border-slate-200',
            };
        case PendaftarStatus.SUBMITTED:
        case 'SUBMIT':
            return {
                label: 'SUBMITTED',
                classes: 'bg-blue-100 text-blue-700 border-blue-200',
            };
        case PendaftarStatus.TAGIHAN:
            return {
                label: 'TAGIHAN',
                classes: 'bg-amber-100 text-amber-700 border-amber-200',
            };
        case PendaftarStatus.INTERVIEW:
            return {
                label: 'INTERVIEW',
                classes: 'bg-indigo-100 text-indigo-700 border-indigo-200',
            };
        case PendaftarStatus.LULUS:
            return {
                label: 'LULUS',
                classes: 'bg-emerald-100 text-emerald-700 border-emerald-200',
            };
        case PendaftarStatus.TIDAK_LULUS:
        case 'GAGAL':
            return {
                label: 'TIDAK LULUS',
                classes: 'bg-rose-100 text-rose-700 border-rose-200',
            };
        case PendaftarStatus.KEDATANGAN:
            return {
                label: 'KEDATANGAN',
                classes: 'bg-purple-100 text-purple-700 border-purple-200',
            };
        case PendaftarStatus.AKTIF:
            return {
                label: 'SANTRI AKTIF',
                classes: 'bg-teal-100 text-teal-700 border-teal-200',
            };
        case PendaftarStatus.DITOLAK:
            return {
                label: 'DITOLAK',
                classes: 'bg-red-100 text-red-700 border-red-200',
            };
        default:
            return {
                label: s,
                classes: 'bg-gray-100 text-gray-700 border-gray-200',
            };
    }
}

export function getStatusTagihanBadge(status?: string | null): StatusBadgeInfo {
    const s = (status || 'BELUM_BAYAR').toUpperCase();

    switch (s) {
        case StatusTagihan.LUNAS:
            return {
                label: 'LUNAS',
                classes: 'bg-emerald-100 text-emerald-700 border-emerald-200',
            };
        case StatusTagihan.BELUM_LUNAS:
            return {
                label: 'BELUM LUNAS',
                classes: 'bg-amber-100 text-amber-700 border-amber-200',
            };
        case StatusTagihan.SAMAHA:
            return {
                label: 'SAMAHA',
                classes: 'bg-sky-100 text-sky-700 border-sky-200',
            };
        case StatusTagihan.BELUM_BAYAR:
            return {
                label: 'BELUM BAYAR',
                classes: 'bg-rose-100 text-rose-700 border-rose-200',
            };
        case StatusTagihan.BATAL:
            return {
                label: 'DIBATALKAN',
                classes: 'bg-slate-100 text-slate-700 border-slate-200',
            };
        default:
            return {
                label: s,
                classes: 'bg-gray-100 text-gray-700 border-gray-200',
            };
    }
}

export function getStatusPembayaranBadge(
    status?: string | null,
): StatusBadgeInfo {
    const s = (status || 'MENUNGGU_VERIFIKASI').toUpperCase();

    switch (s) {
        case StatusPembayaran.DITERIMA:
        case 'TERVERIFIKASI':
            return {
                label: 'DITERIMA',
                classes: 'bg-emerald-100 text-emerald-700 border-emerald-200',
            };
        case StatusPembayaran.MENUNGGU_VERIFIKASI:
        case 'PENDING':
            return {
                label: 'MENUNGGU VERIFIKASI',
                classes: 'bg-amber-100 text-amber-700 border-amber-200',
            };
        case StatusPembayaran.DITOLAK:
            return {
                label: 'DITOLAK',
                classes: 'bg-rose-100 text-rose-700 border-rose-200',
            };
        default:
            return {
                label: s,
                classes: 'bg-gray-100 text-gray-700 border-gray-200',
            };
    }
}

export function getStatusInterviewBadge(
    isScheduled: boolean,
    isCompleted: boolean = false,
): StatusBadgeInfo {
    if (isCompleted) {
        return {
            label: 'SELESAI INTERVIEW',
            classes: 'bg-blue-50 text-blue-800 border-blue-200',
        };
    }

    if (isScheduled) {
        return {
            label: 'SUDAH DIJADWALKAN',
            classes: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        };
    }

    return {
        label: 'BELUM DIJADWALKAN',
        classes: 'bg-amber-50 text-amber-800 border-amber-200',
    };
}
