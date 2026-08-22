<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { ref, watch } from 'vue';
import TextInput from '@/Components/Form/TextInput.vue';
import { store_score } from '@/routes/admin/pendaftar/penilaian_interview';

interface AspekPenilaian {
    id: string;
    kategori_id: string;
    nama_aspek: string;
    bobot: number;
}

interface KategoriPenilaian {
    id: string;
    nama_kategori: string;
    aspek_penilaians?: AspekPenilaian[];
}

const props = defineProps<{
    show: boolean;
    pendaftar: any | null;
    kategoriPenilaians: KategoriPenilaian[];
    kelompokUjianId?: string;
}>();

const emit = defineEmits(['close', 'success']);

const form = useForm({
    pendaftar_id: '',
    kelompok_ujian_id: '',
    scores: {} as Record<string, number | ''>,
    catatans: {} as Record<string, string>,
});

const isMounted = ref(false);
onMounted(() => isMounted.value = true);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.pendaftar) {
            form.clearErrors();
            form.pendaftar_id = props.pendaftar.id;
            form.kelompok_ujian_id = props.kelompokUjianId || (props.pendaftar.kelompokUjians?.[0]?.id ?? '');

            const newScores: Record<string, number | ''> = {};
            const newCatatans: Record<string, string> = {};

            // Initialize all aspects with empty values
            (Array.isArray(props.kategoriPenilaians) ? props.kategoriPenilaians : Object.values(props.kategoriPenilaians || {})).forEach(kategori => {
                (Array.isArray(kategori.aspek_penilaians) ? kategori.aspek_penilaians : Object.values(kategori.aspek_penilaians || {})).forEach(aspek => {
                    newScores[aspek.id] = '';
                    newCatatans[aspek.id] = '';
                });
            });

            // Fill existing data if any
            if (props.pendaftar.penilaians) {
                (Array.isArray(props.pendaftar.penilaians) ? props.pendaftar.penilaians : Object.values(props.pendaftar.penilaians || {})).forEach((penilaian: any) => {
                    if (penilaian.aspek_id) {
                        newScores[penilaian.aspek_id] = penilaian.nilai !== null ? penilaian.nilai : '';
                        newCatatans[penilaian.aspek_id] = penilaian.catatan || '';
                    }
                });
            }

            form.scores = newScores;
            form.catatans = newCatatans;
        } else if (!isOpen) {
            form.reset();
        }
    },
    { immediate: true }
);

const submit = () => {
    form.post(store_score.url(), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            closeModal();
        },
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <teleport to="body" v-if="isMounted">
    <div
        v-if="show"
        class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50 p-4 backdrop-blur-sm sm:p-0"
    >
        <div class="relative w-full max-w-3xl rounded-3xl bg-white shadow-2xl dark:bg-slate-900 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-800 shrink-0 bg-gray-50/50 dark:bg-slate-800/50 rounded-t-3xl">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/50">
                        <svg class="h-6 w-6 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                            Input Nilai Ujian Akademik
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            {{ pendaftar?.nama }} ({{ pendaftar?.nomor_pendaftaran || pendaftar?.nik }})
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="rounded-full p-2 text-gray-400 hover:bg-gray-200 hover:text-gray-900 transition-colors dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto p-8 flex-1 bg-slate-50/50 dark:bg-slate-900/50 custom-scrollbar">
                <form @submit.prevent="submit" class="space-y-8">
                    <template v-for="kategori in kategoriPenilaians" :key="kategori.id">
                        <!-- Only show categories that have aspects -->
                        <div v-if="kategori.aspek_penilaians && Object.keys(kategori.aspek_penilaians).length > 0" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-slate-800">
                                <h4 class="text-base font-bold text-gray-900 dark:text-slate-100">
                                    {{ kategori.nama_kategori }}
                                </h4>
                            </div>
                            
                            <div class="space-y-5">
                                <div v-for="aspek in kategori.aspek_penilaians" :key="aspek.id" class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-6 bg-gray-50/50 dark:bg-slate-800/30 p-4 rounded-xl border border-gray-50 dark:border-slate-700/50">
                                    <div class="sm:w-1/3 pt-1">
                                        <label class="block text-sm font-bold text-gray-700 dark:text-slate-300">
                                            {{ aspek.nama_aspek }}
                                        </label>
                                        <p class="text-[11px] font-medium mt-1 text-gray-500 dark:text-slate-500">Bobot Penilaian: <span class="text-primary dark:text-blue-400">{{ aspek.bobot }}%</span></p>
                                    </div>
                                    <div class="flex-1 space-y-3">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                            <div class="w-32 shrink-0">
                                                <TextInput
                                                    type="number"
                                                    v-model="form.scores[aspek.id]"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    placeholder="Nilai (0-100)"
                                                />
                                            </div>
                                            <div class="flex-1 w-full">
                                                <TextInput
                                                    v-model="form.catatans[aspek.id]"
                                                    placeholder="Catatan penguji (opsional)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </form>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 bg-gray-50/50 dark:bg-slate-800/50 dark:border-slate-800 shrink-0 rounded-b-3xl">
                <button
                    type="button"
                    @click="closeModal"
                    class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:ring-slate-700"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-primary-dark focus:ring-2 focus:ring-primary/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-500/30"
                >
                    <svg
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Simpan Nilai
                </button>
            </div>
        </div>
    </div>
    </teleport>
</template>
