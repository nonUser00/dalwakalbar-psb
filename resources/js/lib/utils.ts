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
