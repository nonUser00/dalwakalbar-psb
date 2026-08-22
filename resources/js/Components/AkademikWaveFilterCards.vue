<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

export interface ActiveTahunAkademik {
    id: string;
    name: string;
    is_active: boolean;
}

export interface GelombangItem {
    id: string;
    periode_id?: string;
    name?: string;
    nama_gelombang?: string;
    periode_name?: string;
    periode_status?: string;
    is_open?: boolean;
    is_in_range?: boolean;
    is_currently_open?: boolean;
    start_date?: string;
    end_date?: string;
    start_date_raw?: string;
    end_date_raw?: string;
    periode?: {
        id?: string;
        name?: string;
        status?: string;
    };
}

const props = withDefaults(
    defineProps<{
        activeTahunAkademik?: ActiveTahunAkademik | null;
        hasActiveTahunAkademik?: boolean;
        gelombangs?: GelombangItem[];
        selectedGelombangId?: string;
        showOverlay?: boolean;
    }>(),
    {
        activeTahunAkademik: null,
        hasActiveTahunAkademik: true,
        gelombangs: () => [],
        selectedGelombangId: '',
        showOverlay: true,
    },
);

const emit = defineEmits<{
    (e: 'select-gelombang', gelombangId: string): void;
}>();

const isDropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedGelombang = computed(() => {
    if (!props.selectedGelombangId) return null;
    return props.gelombangs.find((g) => g.id === props.selectedGelombangId) || null;
});

const openGelombangsCount = computed(() => {
    return (props.gelombangs || []).filter(
        (g) => g.is_currently_open || g.is_open || g.periode_status === 'buka',
    ).length;
});

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const handleSelect = (gelombangId: string) => {
    isDropdownOpen.value = false;
    emit('select-gelombang', gelombangId);
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative w-full">
        <!-- Overlay when no active Tahun Akademik -->
        <div
            v-if="showOverlay && !props.hasActiveTahunAkademik"
            class="absolute inset-0 z-50 flex items-center justify-center rounded-2xl bg-white/80 p-6 backdrop-blur-md dark:bg-slate-900/80"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-rose-200 bg-white p-8 text-center shadow-2xl dark:border-rose-900/50 dark:bg-slate-800"
            >
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-500 dark:bg-rose-950/50"
                >
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
                <h2 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                    Pendaftaran Tidak Ada yang Dibuka
                </h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    Saat ini tidak ada Tahun Akademik yang aktif.<br />
                    Silakan aktifkan Tahun Akademik melalui menu pengaturan akademik.
                </p>
                <div class="mt-6">
                    <Link
                        href="/admin/akademik/tahun-akademik"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-primary/20 transition-all hover:bg-primary-dark focus:ring-2 focus:ring-primary/20 dark:bg-blue-600 dark:hover:bg-blue-700"
                    >
                        Ke Pengaturan Akademik
                    </Link>
                </div>
            </div>
        </div>

        <!-- Academic Year & Wave Cards Grid -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Card 1: Active Academic Year Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/10 dark:bg-blue-950/50 dark:text-blue-400 dark:ring-blue-500/20"
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
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <span
                                class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-slate-400"
                            >
                                Tahun Akademik
                            </span>
                            <div class="mt-0.5 flex items-center gap-2">
                                <h3
                                    class="text-lg font-black tracking-tight text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.activeTahunAkademik?.name || '-' }}
                                </h3>
                                <span
                                    v-if="props.activeTahunAkademik?.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                    ></span>
                                    Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-[11px] text-gray-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span>Tahun ajaran operasional penerimaan santri</span>
                    <Link
                        href="/admin/akademik/tahun-akademik"
                        class="inline-flex items-center gap-1 font-semibold text-primary transition-colors hover:text-primary-dark hover:underline dark:text-blue-400"
                    >
                        <span>Kelola</span>
                        <svg
                            class="h-3 w-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Card 2: Interactive Gelombang Selector Card with Popover Dropdown -->
            <div
                ref="dropdownRef"
                class="relative flex flex-col justify-between overflow-visible rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/10 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/20"
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
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span
                                class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-slate-400"
                            >
                                Gelombang Pendaftaran
                            </span>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                <h3
                                    class="truncate text-lg font-black tracking-tight text-gray-900 dark:text-slate-100"
                                    :title="selectedGelombang?.name || 'Semua Gelombang'"
                                >
                                    {{ selectedGelombang?.name || 'Semua Gelombang' }}
                                </h3>
                                <span
                                    v-if="selectedGelombang?.is_currently_open"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                    ></span>
                                    Sedang Dibuka
                                </span>
                                <span
                                    v-else-if="selectedGelombang?.is_open"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300"
                                >
                                    Dibuka
                                </span>
                                <span
                                    v-else-if="
                                        selectedGelombang &&
                                        selectedGelombang.periode_status === 'draft'
                                    "
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                >
                                    Draft
                                </span>
                                <span
                                    v-else-if="selectedGelombang"
                                    class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400"
                                >
                                    Tutup
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Trigger Button -->
                    <button
                        type="button"
                        @click="toggleDropdown"
                        class="shadow-2xs inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50/80 px-3.5 py-2 text-xs font-bold text-gray-700 transition-all hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-emerald-600 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                        :class="{
                            'border-emerald-500 bg-emerald-50 text-emerald-800 shadow-xs ring-2 ring-emerald-500/20 dark:border-emerald-500 dark:bg-emerald-950/60 dark:text-emerald-200':
                                isDropdownOpen,
                        }"
                    >
                        <span>{{
                            props.selectedGelombangId ? 'Ganti Gelombang' : 'Pilih Gelombang'
                        }}</span>
                        <svg
                            class="h-4 w-4 transition-transform duration-200"
                            :class="{
                                'rotate-180 text-emerald-600 dark:text-emerald-400': isDropdownOpen,
                            }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Footer line with periode & date range -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-[11px] text-gray-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <div class="truncate">
                        <template v-if="selectedGelombang">
                            <span class="font-medium text-gray-700 dark:text-slate-300">{{
                                selectedGelombang.periode_name || 'Periode Aktif'
                            }}</span>
                            <template
                                v-if="selectedGelombang.start_date && selectedGelombang.end_date"
                            >
                                <span class="mx-1">•</span>
                                <span
                                    >{{ selectedGelombang.start_date }} s/d
                                    {{ selectedGelombang.end_date }}</span
                                >
                            </template>
                        </template>
                        <template v-else>
                            <span
                                >Menampilkan data seluruh gelombang pada TA
                                {{ props.activeTahunAkademik?.name }}</span
                            >
                        </template>
                    </div>
                    <span class="shrink-0 text-gray-400 dark:text-slate-500">
                        {{ openGelombangsCount }} Gelombang Buka
                    </span>
                </div>

                <!-- Floating Dropdown Menu -->
                <div
                    v-if="isDropdownOpen"
                    class="animate-fade-in absolute top-full right-0 z-50 mt-2 w-full max-w-md origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl ring-1 ring-slate-900/5 sm:w-96 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/5"
                >
                    <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                Pilih Gelombang (TA {{ props.activeTahunAkademik?.name }})
                            </span>
                            <span
                                class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                {{ openGelombangsCount }} Terbuka
                            </span>
                        </div>
                    </div>

                    <div class="custom-scrollbar max-h-64 space-y-1.5 overflow-y-auto p-1.5">
                        <!-- All Waves Option -->
                        <button
                            type="button"
                            @click="handleSelect('')"
                            class="flex w-full cursor-pointer items-center justify-between rounded-xl px-3 py-2.5 text-left text-xs transition-all hover:bg-slate-50 dark:hover:bg-slate-800/80"
                            :class="[
                                !props.selectedGelombangId
                                    ? 'bg-primary/10 font-bold text-primary ring-1 ring-primary/20 dark:bg-primary/20 dark:text-blue-300 dark:ring-blue-500/30'
                                    : 'font-medium text-slate-700 dark:text-slate-300',
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-xl"
                                    :class="
                                        !props.selectedGelombangId
                                            ? 'bg-primary text-white shadow-xs dark:bg-blue-600'
                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
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
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold">Semua Gelombang</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                        Tampilkan seluruh pendaftar pada tahun ajaran ini
                                    </div>
                                </div>
                            </div>
                            <svg
                                v-if="!props.selectedGelombangId"
                                class="h-4 w-4 text-primary dark:text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </button>

                        <!-- Gelombang List -->
                        <template v-if="props.gelombangs && props.gelombangs.length > 0">
                            <button
                                v-for="g in props.gelombangs"
                                :key="g.id"
                                type="button"
                                @click="handleSelect(g.id)"
                                class="flex w-full cursor-pointer items-center justify-between rounded-xl px-3 py-2.5 text-left text-xs transition-all hover:bg-slate-50 dark:hover:bg-slate-800/80"
                                :class="[
                                    props.selectedGelombangId === g.id
                                        ? 'bg-emerald-50/90 font-bold text-emerald-900 ring-1 ring-emerald-600/30 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-500/40'
                                        : 'font-medium text-slate-700 dark:text-slate-300',
                                ]"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                                        :class="
                                            props.selectedGelombangId === g.id
                                                ? 'bg-emerald-600 text-white shadow-xs dark:bg-emerald-500'
                                                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400'
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
                                                d="M13 10V3L4 14h7v7l9-11h-7z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span
                                                class="truncate font-bold text-slate-900 dark:text-slate-100"
                                            >
                                                {{ g.name || g.nama_gelombang || 'Gelombang' }}
                                            </span>
                                            <span
                                                v-if="g.is_currently_open"
                                                class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-1.5 py-0.2 text-[9px] font-black text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                            >
                                                <span
                                                    class="h-1 w-1 animate-pulse rounded-full bg-emerald-500"
                                                ></span>
                                                Sedang Dibuka
                                            </span>
                                            <span
                                                v-else-if="g.is_open"
                                                class="rounded-md bg-emerald-100 px-1.5 py-0.2 text-[9px] font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                            >
                                                Dibuka
                                            </span>
                                            <span
                                                v-else-if="g.periode_status === 'draft'"
                                                class="rounded-md bg-slate-100 px-1.5 py-0.2 text-[9px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                            >
                                                Draft
                                            </span>
                                            <span
                                                v-else
                                                class="rounded-md bg-rose-100 px-1.5 py-0.2 text-[9px] font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400"
                                            >
                                                Tutup
                                            </span>
                                        </div>
                                        <div
                                            class="mt-0.5 truncate text-[10px] text-slate-400 dark:text-slate-500"
                                        >
                                            {{ g.periode_name || g.periode?.name || 'Periode Aktif' }}
                                            <template v-if="g.start_date && g.end_date">
                                                • {{ g.start_date }} s/d {{ g.end_date }}
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <svg
                                    v-if="props.selectedGelombangId === g.id"
                                    class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>
                        </template>
                        <div
                            v-else
                            class="py-4 text-center text-xs text-slate-400 dark:text-slate-500"
                        >
                            Tidak ada gelombang pada tahun akademik ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
