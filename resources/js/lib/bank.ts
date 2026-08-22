export interface BankLogoSource {
    logo_path?: string | null;
    logo_url?: string | null;
    name?: string | null;
    nama_bank?: string | null;
    singkatan?: string | null;
    kode_bank?: string | null;
}

/**
 * Returns a valid URL for bank logo.
 * Falls back to default assets in /image/bank/ if not uploaded or missing.
 */
export function getBankLogo(bank?: BankLogoSource | null): string {
    if (!bank) {
        return '/image/bank/bri.png';
    }

    // 1. Direct logo_url from backend accessor if available
    if (bank.logo_url) {
        return bank.logo_url;
    }

    // 2. Resolve logo_path
    if (bank.logo_path) {
        const path = bank.logo_path.trim();

        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        if (path.startsWith('/image/') || path.startsWith('image/')) {
            return path.startsWith('/') ? path : `/${path}`;
        }

        if (path.startsWith('/storage/') || path.startsWith('storage/')) {
            return path.startsWith('/') ? path : `/${path}`;
        }

        return `/storage/${path}`;
    }

    // 3. Fallback to default local asset by name, abbreviation, or bank code
    const nameLower = (bank.name || bank.nama_bank || '').toLowerCase();
    const singkatanLower = (bank.singkatan || '').toLowerCase();
    const code = (bank.kode_bank || '').trim();
    const searchStr = `${nameLower} ${singkatanLower} ${code}`;

    if (searchStr.includes('bca') || code === '014') {
        return '/image/bank/bca.png';
    }

    if (searchStr.includes('bni') || code === '009') {
        return '/image/bank/bni.png';
    }

    if (searchStr.includes('bri') || code === '002') {
        return '/image/bank/bri.png';
    }

    if (
        searchStr.includes('bsi') ||
        searchStr.includes('syariah') ||
        code === '451'
    ) {
        return '/image/bank/bsi.png';
    }

    if (searchStr.includes('mandiri') || code === '008') {
        return '/image/bank/mandiri.png';
    }

    if (searchStr.includes('kalbar') || code === '123') {
        return '/image/bank/kalbar.png';
    }

    return '/image/bank/bri.png';
}

/**
 * Image error handler fallback
 */
export function handleBankLogoError(
    event: Event,
    fallback = '/image/bank/bri.png',
) {
    const target = event.target as HTMLImageElement;

    if (target && target.src && !target.src.endsWith(fallback)) {
        target.src = fallback;
    }
}
