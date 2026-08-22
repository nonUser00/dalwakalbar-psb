<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { getBankLogo } from '@/lib/bank';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    virtualAccounts: any[];
    tagihans: any[];
}>();

const activeVirtualAccounts = computed(() => {
    const vas = props.virtualAccounts || [];
    return vas.filter((va: any) => {
        if (!va) return false;
        if (
            va.is_active === false ||
            va.is_active === 0 ||
            va.status === 'INACTIVE' ||
            va.status === 'TIDAK_AKTIF'
        ) {
            return false;
        }
        return true;
    });
});

const copiedVaId = ref<string | null>(null);
const copyToClipboard = (text: string, id: string) => {
    if (navigator.clipboard && text) {
        navigator.clipboard.writeText(text);
        copiedVaId.value = id;
        setTimeout(() => {
            if (copiedVaId.value === id) {
                copiedVaId.value = null;
            }
        }, 2000);
    }
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Virtual Account Pembayaran - PSB Dalwa Kalbar" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Virtual Account Bank Pembayaran
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Nomor rekening virtual account bank resmi untuk pembayaran otomatis calon santri.
                </p>
            </div>

            <span class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full border border-indigo-200 bg-indigo-50 px-3.5 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                {{ activeVirtualAccounts.length }} Channel Bank Aktif
            </span>
        </div>

        <!-- Empty State -->
        <div
            v-if="activeVirtualAccounts.length === 0"
            class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-gray-200 bg-white p-12 text-center shadow-xs dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-slate-100">
                Belum Ada Virtual Account Aktif
            </h3>
            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
                Nomor Virtual Account belum diterbitkan untuk akun Anda. Silakan selesaikan pengisian formulir dan verifikasi berkas terlebih dahulu.
            </p>
        </div>

        <!-- Centered Virtual Account Cards -->
        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="va in activeVirtualAccounts"
                :key="va.id"
                class="group relative flex flex-col items-center justify-between rounded-3xl border border-gray-200/90 bg-white p-6 sm:p-7 text-center shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500/40"
            >
                <!-- TOP SECTION: Bank Logo & Bank Name (Centered) -->
                <div class="flex flex-col items-center w-full">
                    <!-- Bank Logo Container -->
                    <div class="flex h-16 w-36 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50/80 p-2.5 shadow-2xs dark:border-slate-700/60 dark:bg-slate-800">
                        <img
                            :src="getBankLogo(va.bank || va)"
                            :alt="va.bank?.name || va.bank?.nama_bank || 'Bank'"
                            class="h-full w-auto max-h-11 object-contain transition-transform duration-200 group-hover:scale-105"
                        />
                    </div>

                    <!-- Bank Name -->
                    <h4 class="mt-3.5 text-base font-extrabold text-gray-900 leading-snug dark:text-slate-100">
                        {{ va.bank?.name || va.bank?.nama_bank || va.bank?.singkatan || 'Bank Pembayaran' }}
                    </h4>

                    <!-- Bank Code & Status Pill -->
                    <div class="mt-1 flex items-center justify-center gap-2">
                        <span v-if="va.bank?.kode_bank" class="font-mono text-xs font-semibold text-gray-400 dark:text-slate-500">
                            Kode Bank: {{ va.bank.kode_bank }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 uppercase dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Aktif
                        </span>
                    </div>
                </div>

                <!-- MIDDLE SECTION: Nomor VA (Centered) -->
                <div class="mt-6 w-full flex flex-col items-center">
                    <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                        Nomor Virtual Account
                    </span>

                    <!-- VA Highlight Box with Copy Button -->
                    <div class="mt-2 flex w-full items-center justify-between gap-2 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 dark:border-blue-500/30 dark:bg-blue-950/30">
                        <span class="font-mono text-lg sm:text-xl font-black tracking-wider text-primary dark:text-blue-300 select-all">
                            {{ va.nomor_va || va.va_number || '-' }}
                        </span>

                        <button
                            type="button"
                            @click="copyToClipboard(va.nomor_va || va.va_number, va.id)"
                            class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-white text-primary shadow-2xs transition-all hover:scale-105 hover:bg-primary hover:text-white dark:bg-slate-800 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white"
                            :title="copiedVaId === va.id ? 'Tersalin!' : 'Salin Nomor VA'"
                        >
                            <svg v-if="copiedVaId === va.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <span v-if="copiedVaId === va.id" class="mt-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        Nomor VA berhasil disalin!
                    </span>
                </div>

                <!-- BOTTOM SECTION: Meta Info (Centered) -->
                <div class="mt-5 w-full border-t border-gray-100 pt-3.5 text-center dark:border-slate-800">
                    <p class="text-xs font-bold text-gray-800 dark:text-slate-200">
                        a.n. {{ props.pendaftar.nama || 'Calon Santri' }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500">
                        Pembayaran akan otomatis terverifikasi sistem.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
