<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    log: {
        type: Object,
        required: true,
    },
});

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

    return '/admin/pengaturan/log';
});

const formatDate = (dateString: string) => {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getEventBadgeClass = (event: string) => {
    switch (event?.toLowerCase()) {
        case 'created':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'updated':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'deleted':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'exported':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        default:
            return 'bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-700';
    }
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Detail Log Aktivitas" />

        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Detail Log Aktivitas
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Informasi lengkap mengenai rekaman aktivitas sistem.
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="space-y-6">
            <!-- Info Card -->
            <div
                class="overflow-hidden border border-gray-100 bg-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="p-8">
                    <div
                        class="mb-8 flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50"
                        >
                            <svg
                                class="h-6 w-6 text-blue-500 dark:text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Informasi Dasar
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                Konteks eksekusi log
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-x-12 gap-y-8 lg:grid-cols-2"
                    >
                        <!-- Left column -->
                        <div class="space-y-8">
                            <div>
                                <p
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Pengguna / Aktor
                                </p>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800"
                                    >
                                        <img
                                            v-if="log.causer?.foto_url"
                                            :src="log.causer.foto_url"
                                            class="h-full w-full object-cover"
                                            alt="Foto"
                                        />
                                        <span
                                            v-else-if="log.causer"
                                            class="text-sm font-extrabold text-gray-500 uppercase dark:text-slate-400"
                                            >{{
                                                log.causer.name.charAt(0)
                                            }}</span
                                        >
                                        <svg
                                            v-else
                                            class="h-5 w-5 text-gray-500 dark:text-slate-400"
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
                                    <div>
                                        <p
                                            class="text-base font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            {{
                                                log.causer?.name ||
                                                'Sistem/Anonim'
                                            }}
                                        </p>
                                        <p
                                            v-if="log.causer?.roles"
                                            class="mt-0.5 text-sm font-medium text-gray-500 dark:text-slate-400"
                                        >
                                            {{
                                                log.causer.roles
                                                    .map((r: any) => r.name)
                                                    .join(', ') || '-'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Waktu Aksi
                                </p>
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5 text-gray-400 dark:text-slate-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <p
                                        class="text-base font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ formatDate(log.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p
                                        class="mb-3 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Aksi
                                    </p>
                                    <div>
                                        <span
                                            :class="[
                                                'rounded-full border px-3 py-1 text-xs font-bold tracking-wider uppercase',
                                                getEventBadgeClass(log.event),
                                            ]"
                                        >
                                            {{ log.event }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Modul
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <svg
                                            class="h-5 w-5 text-gray-400 dark:text-slate-500"
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
                                        <p
                                            class="text-base font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            {{ log.log_name || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right column -->
                        <div class="space-y-8">
                            <div>
                                <p
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Deskripsi Singkat
                                </p>
                                <p
                                    class="text-base leading-relaxed font-medium text-gray-900 dark:text-slate-100"
                                >
                                    {{ log.description }}
                                </p>
                            </div>
                            <div
                                v-if="
                                    log.properties?.ip ||
                                    log.properties?.ip_address
                                "
                            >
                                <p
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    IP Address
                                </p>
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5 text-gray-400 dark:text-slate-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                                        />
                                    </svg>
                                    <p
                                        class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-1 font-mono text-base text-gray-800 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        {{
                                            log.properties.ip ||
                                            log.properties.ip_address
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="log.properties?.user_agent">
                                <p
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    User Agent
                                </p>
                                <p
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm leading-relaxed font-medium break-all text-gray-600 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ log.properties.user_agent }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Properties Card -->
            <div
                class="overflow-hidden border border-gray-100 bg-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="p-8">
                    <div
                        class="mb-8 flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/50"
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
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Perubahan Data
                            </h3>
                            <p
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                Payload properti log
                            </p>
                        </div>
                    </div>

                    <!-- Clean JSON Viewer -->
                    <div class="space-y-8">
                        <div
                            v-if="
                                log.properties?.old ||
                                log.properties?.attributes
                            "
                            :class="[
                                'grid items-stretch gap-8',
                                log.properties?.old &&
                                log.properties?.attributes
                                    ? 'grid-cols-1 lg:grid-cols-2'
                                    : 'grid-cols-1',
                            ]"
                        >
                            <div
                                v-if="log.properties.old"
                                class="flex flex-col"
                            >
                                <p
                                    class="mb-4 flex items-center gap-2 text-sm font-bold tracking-wider text-gray-700 uppercase dark:text-slate-200 dark:text-slate-300"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-rose-500"
                                    ></span>
                                    Data Sebelum Perubahan
                                </p>
                                <div
                                    class="flex-1 rounded-2xl bg-gray-900 p-6 shadow-inner dark:bg-slate-950"
                                >
                                    <pre
                                        class="word-break font-mono text-[13px] leading-relaxed whitespace-pre-wrap text-emerald-400"
                                        >{{
                                            JSON.stringify(
                                                log.properties.old,
                                                null,
                                                2,
                                            )
                                        }}</pre>
                                </div>
                            </div>
                            <div
                                v-if="log.properties.attributes"
                                class="flex flex-col"
                            >
                                <p
                                    class="mb-4 flex items-center gap-2 text-sm font-bold tracking-wider text-gray-700 uppercase dark:text-slate-200 dark:text-slate-300"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>
                                    Data Setelah Perubahan
                                </p>
                                <div
                                    class="flex-1 rounded-2xl bg-gray-900 p-6 shadow-inner dark:bg-slate-950"
                                >
                                    <pre
                                        class="word-break font-mono text-[13px] leading-relaxed whitespace-pre-wrap text-emerald-400"
                                        >{{
                                            JSON.stringify(
                                                log.properties.attributes,
                                                null,
                                                2,
                                            )
                                        }}</pre>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                !log.properties?.old &&
                                !log.properties?.attributes &&
                                Object.keys(log.properties || {}).length > 0
                            "
                        >
                            <p
                                class="mb-4 flex items-center gap-2 text-sm font-bold tracking-wider text-gray-700 uppercase dark:text-slate-200 dark:text-slate-300"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-indigo-500"
                                ></span>
                                Detail Data (JSON)
                            </p>
                            <div
                                class="rounded-2xl bg-gray-900 p-6 shadow-inner dark:bg-slate-950"
                            >
                                <pre
                                    class="word-break font-mono text-[13px] leading-relaxed whitespace-pre-wrap text-emerald-400"
                                    >{{
                                        JSON.stringify(log.properties, null, 2)
                                    }}</pre>
                            </div>
                        </div>

                        <div
                            v-if="
                                !log.properties ||
                                Object.keys(log.properties).length === 0
                            "
                            class="rounded-2xl border border-dashed border-gray-100 bg-gray-50 py-12 text-center dark:border-slate-800 dark:bg-slate-800"
                        >
                            <div
                                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-500"
                            >
                                <svg
                                    class="h-8 w-8"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                            </div>
                            <p
                                class="font-medium text-gray-500 dark:text-slate-400"
                            >
                                Tidak ada detail data yang direkam pada log ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
