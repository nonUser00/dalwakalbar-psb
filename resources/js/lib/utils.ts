import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const ALL_DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

/**
 * Format contiguous days into range (e.g. ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] => 'Senin - Sabtu')
 * Non-consecutive: ['Senin', 'Selasa', 'Kamis', 'Jumat', 'Sabtu'] => 'Senin - Selasa, Kamis - Sabtu'
 */
export function formatHariKerja(selectedDays: string[] | undefined | null): string {
    if (!selectedDays || !Array.isArray(selectedDays) || selectedDays.length === 0) {
        return 'Senin - Sabtu';
    }

    // Filter and sort days according to standard weekly calendar order
    const validIndices = selectedDays
        .map((d) => ALL_DAYS.indexOf(d))
        .filter((idx) => idx !== -1)
        .sort((a, b) => a - b);

    if (validIndices.length === 0) {
        return selectedDays.join(', ');
    }

    if (validIndices.length === 7) {
        return 'Setiap Hari (Senin - Minggu)';
    }

    const ranges: string[] = [];
    let start = validIndices[0];
    let prev = validIndices[0];

    for (let i = 1; i < validIndices.length; i++) {
        const curr = validIndices[i];
        if (curr === prev + 1) {
            prev = curr;
        } else {
            if (start === prev) {
                ranges.push(ALL_DAYS[start]);
            } else if (prev === start + 1) {
                ranges.push(`${ALL_DAYS[start]} - ${ALL_DAYS[prev]}`);
            } else {
                ranges.push(`${ALL_DAYS[start]} - ${ALL_DAYS[prev]}`);
            }
            start = curr;
            prev = curr;
        }
    }

    if (start === prev) {
        ranges.push(ALL_DAYS[start]);
    } else {
        ranges.push(`${ALL_DAYS[start]} - ${ALL_DAYS[prev]}`);
    }

    return ranges.join(', ');
}

/**
 * Format nomor telepon / WhatsApp untuk link wa.me:
 * - Menghilangkan spasi, tanda minus, plus, dan karakter non-angka
 * - Jika berawalan '08...' atau '0...', diubah menjadi '628...' / '62...'
 * - Jika sudah berawalan '628...' atau '62...', tetap dibiarkan '62...'
 * - Jika berawalan '8...', ditambahkan '62' di depan menjadi '628...'
 */
export function formatWaNumber(phone?: string | number | null): string {
    if (!phone) {
        return '';
    }

    let clean = String(phone).replace(/[^0-9]/g, '');

    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('8')) {
        clean = '62' + clean;
    }

    return clean;
}

/**
 * Membuat tautan WhatsApp lengkap (https://wa.me/...) dengan nomor yang telah diformat standar
 */
export function formatWaUrl(phone?: string | number | null, text?: string): string {
    const num = formatWaNumber(phone);
    if (!num) {
        return '#';
    }

    if (text) {
        return `https://wa.me/${num}?text=${encodeURIComponent(text)}`;
    }

    return `https://wa.me/${num}`;
}
