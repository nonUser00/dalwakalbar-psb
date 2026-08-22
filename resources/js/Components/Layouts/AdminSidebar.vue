<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const props = defineProps<{
    isMobileSidebarOpen: boolean;
    isDesktopCollapsed?: boolean;
}>();

const emit = defineEmits([
    'toggleSidebar',
    'toggleDesktopCollapse',
    'closeMobile',
]);
const page = usePage();

const handleMobileClose = () => {
    emit('closeMobile');
};

const menuGroups = [
    {
        heading: 'UTAMA',
        title: 'Dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        url: '/admin/dashboard',
    },
    {
        heading: 'PENDAFTARAN PPDB',
        title: 'Manajemen Pendaftar',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        items: [
            { title: 'Pendaftar Draft', url: '/admin/pendaftar/draft' },
            {
                title: 'Pendaftar Submit',
                url: '/admin/pendaftar/submit',
            },
            {
                title: 'Pendaftar Tagihan',
                url: '/admin/pendaftar/tagihan',
            },
            {
                title: 'Set Interview',
                url: '/admin/pendaftar/set-interview',
            },
            {
                title: 'Penilaian Interview',
                url: '/admin/pendaftar/penilaian-interview',
            },
            { title: 'Pengumuman', url: '/admin/pendaftar/pengunguman' },
        ],
    },
    {
        heading: 'KEBERANGKATAN',
        title: 'Keberangkatan',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        items: [
            {
                title: 'Rombongan Pesawat',
                url: '/admin/pendaftar/rombongan?type=pesawat',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Rombongan Kapal',
                url: '/admin/pendaftar/rombongan?type=kapal',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Kedatangan',
                url: '/admin/pendaftar/kedatangan',
                disabled: true,
                badgeText: 'Soon',
            },
        ],
    },
    {
        heading: 'MANAJEMEN KEUANGAN',
        title: 'Kelola Biaya Tagihan',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        items: [
            {
                title: 'Tagihan Pendaftaran',
                url: '/admin/keuangan/tagihan-pendaftaran',
            },
            {
                title: 'Tagihan Rombongan',
                url: '/admin/keuangan/tagihan-rombongan',
            },
            {
                title: 'Tagihan Interview',
                url: '/admin/keuangan/tagihan-interview',
            },
            {
                title: 'Tagihan Biasa',
                url: '/admin/keuangan/tagihan-biasa',
            },
        ],
    },
    {
        title: 'Perbankan & VA',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        items: [
            { title: 'Bank & Biaya Admin', url: '/admin/keuangan/bank' },
            { title: 'Virtual Account', url: '/admin/keuangan/va' },
        ],
    },
    {
        heading: 'LAPORAN & RIWAYAT',
        title: 'Riwayat & Laporan',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        items: [
            {
                title: 'Riwayat Pendaftaran',
                url: '/admin/laporan/pendaftaran',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Riwayat Tagihan',
                url: '/admin/laporan/tagihan',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Riwayat Pembayaran',
                url: '/admin/laporan/pembayaran',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Riwayat Interview',
                url: '/admin/laporan/interview',
                disabled: true,
                badgeText: 'Soon',
            },
            {
                title: 'Riwayat Rombongan',
                url: '/admin/laporan/rombongan',
                disabled: true,
                badgeText: 'Soon',
            },
        ],
    },
    {
        heading: 'DATA AKADEMIK',
        title: 'Akademik',
        icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        items: [
            { title: 'Program Pendidikan', url: '/admin/akademik/program' },
            { title: 'Tahun Akademik', url: '/admin/akademik/tahun-akademik' },
            { title: 'Dokumen Lampiran', url: '/admin/akademik/dokumen' },
        ],
    },
    {
        heading: 'MASTER DATA',
        title: 'Master Data',
        icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        items: [
            {
                title: 'Ukuran Baju',
                url: '/admin/master/ukuran-baju',
            },
            {
                title: 'Pend. Terakhir Ortu',
                url: '/admin/master/pendidikan-terakhir-orang-tua',
            },
            {
                title: 'Penghasilan Ortu',
                url: '/admin/master/penghasilan-orang-tua',
            },
            {
                title: 'Pekerjaan Ortu',
                url: '/admin/master/pekerjaan-orang-tua',
            },
            {
                title: 'Pend. Sebelumnya',
                url: '/admin/master/pendidikan-sebelumnya',
            },
        ],
    },
    {
        heading: 'PENGATURAN SISTEM',
        title: 'Pengaturan',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        icon2: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        items: [
            {
                title: 'Role & Permission',
                url: '/admin/pengaturan/role-permission',
            },
            { title: 'Pegawai', url: '/admin/pengaturan/pegawai' },
            { title: 'Log Aktivitas', url: '/admin/pengaturan/log' },
            {
                title: 'Konfigurasi Sistem',
                url: '/admin/pengaturan/konfigurasi',
            },
        ],
    },
];

const openMenus = ref<string[]>([]);
const activeMenu = ref<string | null>(null);

const activeFlyout = ref<string | null>(null);
let flyoutTimeout: ReturnType<typeof setTimeout> | null = null;

const isDesktop = () => {
    return typeof window !== 'undefined' && window.innerWidth >= 1024;
};

const handleMouseEnterGroup = (title: string) => {
    if (!props.isDesktopCollapsed || !isDesktop()) {
        return;
    }

    if (flyoutTimeout) {
        clearTimeout(flyoutTimeout);
    }

    activeFlyout.value = title;
};

const handleMouseLeaveGroup = () => {
    if (!props.isDesktopCollapsed || !isDesktop()) {
        return;
    }

    flyoutTimeout = setTimeout(() => {
        activeFlyout.value = null;
    }, 200);
};

const handleMouseEnterFlyout = () => {
    if (flyoutTimeout) {
        clearTimeout(flyoutTimeout);
    }
};

const toggleGroupClick = (title: string, hasItems: boolean) => {
    if (props.isDesktopCollapsed && isDesktop()) {
        if (!hasItems) {
            return;
        }

        activeFlyout.value = activeFlyout.value === title ? null : title;
    } else {
        toggleMenu(title, hasItems);
    }
};

const isItemActive = (itemUrl: string) => {
    if (!itemUrl || itemUrl === '#') {
        return false;
    }

    if (itemUrl.includes('?')) {
        return page.url === itemUrl || page.url.startsWith(itemUrl + '&');
    }

    const [pagePath] = page.url.split('?');

    if (pagePath === itemUrl || pagePath.startsWith(itemUrl + '/')) {
        return true;
    }

    // Special matching for Pendaftar Detail (/admin/pendaftar/{id})
    const isPendaftarShow = /^\/admin\/pendaftar\/[0-9a-fA-F-]+$/.test(
        pagePath,
    );

    if (isPendaftarShow) {
        const pendaftar = (page.props as any).pendaftar;
        const status = (pendaftar?.status || '').toUpperCase();

        if (
            itemUrl === '/admin/pendaftar/draft' &&
            (status === 'DRAFT' || !status)
        ) {
            return true;
        }

        if (itemUrl === '/admin/pendaftar/submit' && status === 'SUBMITTED') {
            return true;
        }

        if (itemUrl === '/admin/pendaftar/tagihan' && status === 'TAGIHAN') {
            return true;
        }

        if (
            (itemUrl === '/admin/pendaftar/set-interview' ||
                itemUrl === '/admin/pendaftar/interview') &&
            status === 'INTERVIEW'
        ) {
            return true;
        }

        if (
            (itemUrl === '/admin/pendaftar/penilaian-interview' ||
                itemUrl === '/admin/pendaftar/penilaian') &&
            status === 'PENILAIAN'
        ) {
            return true;
        }
    }

    return false;
};

const determineActiveMenu = () => {
    menuGroups.forEach((group) => {
        const isGroupActive = group.items
            ? group.items.some((item) => isItemActive(item.url))
            : group.url && isItemActive(group.url);

        if (isGroupActive) {
            activeMenu.value = group.title;

            if (!openMenus.value.includes(group.title)) {
                openMenus.value.push(group.title);
            }
        }
    });
};

onMounted(() => {
    determineActiveMenu();
});

watch(
    () => page.url,
    () => {
        determineActiveMenu();
        activeFlyout.value = null;
    },
);

const toggleMenu = (title: string, hasItems: boolean) => {
    if (!hasItems) {
        return;
    }

    if (openMenus.value.includes(title)) {
        openMenus.value = openMenus.value.filter((m) => m !== title);
    } else {
        openMenus.value = openMenus.value.filter((m) => m === activeMenu.value);
        openMenus.value.push(title);
    }
};

const formatBadgeNumber = (count: number | undefined | null): string | null => {
    if (!count || count <= 0) return null;
    return count > 99 ? '99+' : String(count);
};

const getSidebarBadge = (url: string): string | null => {
    const badges = (page.props as any).sidebar_badges || {};

    if (url === '/admin/pendaftar/submit') {
        return formatBadgeNumber(badges.pendaftar_submit);
    }
    if (url === '/admin/pendaftar/tagihan') {
        return formatBadgeNumber(badges.pendaftar_tagihan);
    }
    if (url === '/admin/pendaftar/set-interview') {
        return formatBadgeNumber(badges.set_interview);
    }
    if (url === '/admin/pendaftar/penilaian-interview') {
        return formatBadgeNumber(badges.penilaian_interview);
    }
    return null;
};

const getGroupTotalBadge = (group: any): string | null => {
    if (!group.items) return null;
    let total = 0;
    const badges = (page.props as any).sidebar_badges || {};

    group.items.forEach((item: any) => {
        if (item.url === '/admin/pendaftar/submit') total += (badges.pendaftar_submit || 0);
        if (item.url === '/admin/pendaftar/tagihan') total += (badges.pendaftar_tagihan || 0);
        if (item.url === '/admin/pendaftar/set-interview') total += (badges.set_interview || 0);
        if (item.url === '/admin/pendaftar/penilaian-interview') total += (badges.penilaian_interview || 0);
    });

    return formatBadgeNumber(total);
};
</script>

<template>
    <!-- Mobile Overlay -->
    <div
        v-if="isMobileSidebarOpen"
        @click="$emit('toggleSidebar')"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs transition-opacity lg:hidden"
    ></div>

    <!-- Sidebar Container -->
    <aside
        :class="[
            'fixed top-0 z-50 flex h-full flex-col border-r border-slate-200 bg-white transition-all duration-300 ease-in-out lg:relative dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900',
            isMobileSidebarOpen ? 'left-0' : '-left-64',
            'w-64 lg:left-0',
            isDesktopCollapsed
                ? 'lg:w-20 lg:overflow-visible'
                : 'lg:w-64 lg:overflow-hidden',
            'overflow-hidden',
        ]"
    >
        <!-- Logo Area -->
        <div
            class="flex h-16 shrink-0 items-center border-b border-slate-200 px-4 transition-all duration-300 dark:border-slate-700/80 dark:border-slate-800"
        >
            <!-- Expanded view logos -->
            <div
                class="flex w-full items-center space-x-2 overflow-hidden"
                :class="isDesktopCollapsed ? 'flex lg:hidden' : 'flex'"
            >
                <img
                    src="/image/logos/logo-1.png"
                    alt="Logo 1"
                    class="h-8 w-auto shrink-0 object-contain"
                />
                <div
                    class="h-5 w-px shrink-0 bg-slate-200 dark:bg-slate-700"
                ></div>
                <img
                    src="/image/logos/logo-2.png"
                    alt="Logo 2"
                    class="h-8 w-auto shrink-0 object-contain"
                />
                <div class="ml-1 flex flex-col truncate">
                    <span
                        class="truncate text-xs leading-tight font-bold text-slate-800 dark:text-slate-100"
                        >PSB Dalwa</span
                    >
                    <span
                        class="truncate text-[10px] leading-tight font-semibold tracking-wide text-slate-400 uppercase dark:text-slate-400"
                        >Perwakilan Kalbar</span
                    >
                </div>
            </div>

            <!-- Collapsed view logo -->
            <div
                v-if="isDesktopCollapsed"
                class="hidden w-full items-center justify-center lg:flex"
            >
                <img
                    src="/image/logos/logo-1.png"
                    alt="Logo 1"
                    class="h-8 w-auto object-contain"
                />
            </div>
        </div>

        <!-- Navigation Scroll Area -->
        <nav
            :class="[
                isDesktopCollapsed
                    ? 'custom-scrollbar overflow-y-auto lg:overflow-visible'
                    : 'custom-scrollbar overflow-y-auto',
                'flex-1 space-y-1.5 px-3 py-4',
            ]"
        >
            <template v-for="(group, idx) in menuGroups" :key="idx">
                <!-- Group Heading -->
                <div
                    v-if="group.heading"
                    class="mt-5 mb-1.5 px-3.5 first:mt-0"
                    :class="isDesktopCollapsed ? 'block lg:hidden' : 'block'"
                >
                    <h3
                        class="text-[11px] font-bold tracking-wider text-slate-400/80 uppercase dark:text-slate-500"
                    >
                        {{ group.heading }}
                    </h3>
                </div>
                <div
                    v-if="group.heading && isDesktopCollapsed"
                    class="mx-1 mt-4 mb-2 hidden border-t border-slate-200 first:mt-0 lg:block dark:border-slate-700/70 dark:border-slate-800"
                ></div>

                <!-- Single Link (No Items) -->
                <div
                    v-if="!group.items"
                    class="relative"
                    @mouseenter="handleMouseEnterGroup(group.title)"
                    @mouseleave="handleMouseLeaveGroup"
                >
                    <Link
                        @click="handleMobileClose"
                        :href="group.url || '#'"
                        :class="[
                            isItemActive(group.url || '')
                                ? 'bg-primary font-semibold text-white shadow-md shadow-primary/25 dark:bg-primary-dark dark:text-white'
                                : 'font-semibold text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-slate-100',
                            'group/item flex items-center rounded-xl px-3 py-2.5 text-[13.5px] transition-all duration-200',
                            isDesktopCollapsed
                                ? 'justify-start px-3 lg:justify-center lg:px-0'
                                : 'justify-start px-3',
                        ]"
                    >
                        <svg
                            class="h-5 w-5 shrink-0 transition-colors"
                            :class="[
                                isItemActive(group.url || '')
                                    ? 'text-white'
                                    : 'text-slate-400 group-hover/item:text-primary dark:text-slate-400 dark:group-hover/item:text-blue-400',
                            ]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="group.icon"
                            />
                        </svg>
                        <span
                            class="ml-3 truncate whitespace-nowrap"
                            :class="
                                isDesktopCollapsed
                                    ? 'inline lg:hidden'
                                    : 'inline'
                            "
                        >
                            {{ group.title }}
                        </span>
                    </Link>

                    <!-- Tooltip when collapsed on desktop -->
                    <div
                        v-if="
                            isDesktopCollapsed && activeFlyout === group.title
                        "
                        class="animate-fade-in-fast pointer-events-none absolute top-1/2 left-full z-50 ml-3 hidden -translate-y-1/2 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold whitespace-nowrap text-white shadow-xl shadow-slate-900/20 lg:block dark:border dark:border-slate-800 dark:bg-slate-800"
                    >
                        {{ group.title }}
                    </div>
                </div>

                <!-- Accordion Group (Has Items / Leveling Menu) -->
                <div
                    v-else
                    class="relative flex flex-col"
                    @mouseenter="handleMouseEnterGroup(group.title)"
                    @mouseleave="handleMouseLeaveGroup"
                >
                    <button
                        @click="toggleGroupClick(group.title, true)"
                        :class="[
                            activeMenu === group.title
                                ? 'bg-primary font-semibold text-white shadow-md shadow-primary/25 dark:bg-primary-dark dark:text-white'
                                : openMenus.includes(group.title)
                                  ? 'bg-slate-100/90 font-semibold text-slate-900 dark:bg-slate-800/80 dark:text-slate-100'
                                  : 'font-semibold text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-slate-100',
                            'group/btn flex w-full cursor-pointer items-center rounded-xl px-3 py-2.5 text-[13.5px] transition-all duration-200',
                            isDesktopCollapsed
                                ? 'justify-between px-3 lg:justify-center lg:px-0'
                                : 'justify-between px-3',
                        ]"
                    >
                        <div class="flex min-w-0 items-center">
                            <div class="relative">
                                <svg
                                    class="h-5 w-5 shrink-0 transition-colors"
                                    :class="[
                                        activeMenu === group.title
                                            ? 'text-white'
                                            : openMenus.includes(group.title)
                                              ? 'text-primary dark:text-blue-400'
                                              : 'text-slate-400 group-hover/btn:text-primary dark:text-slate-400 dark:group-hover/btn:text-blue-400',
                                    ]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="group.icon"
                                    />
                                    <path
                                        v-if="group.icon2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="group.icon2"
                                    />
                                </svg>
                                <span
                                    v-if="isDesktopCollapsed && getGroupTotalBadge(group)"
                                    class="absolute -top-1.5 -right-1.5 hidden h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1 text-[10px] font-black leading-none text-white ring-2 ring-white lg:inline-flex dark:ring-slate-900"
                                >
                                    {{ getGroupTotalBadge(group) }}
                                </span>
                            </div>
                            <span
                                class="ml-3 truncate whitespace-nowrap"
                                :class="
                                    isDesktopCollapsed
                                        ? 'inline lg:hidden'
                                        : 'inline'
                                "
                            >
                                {{ group.title }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                v-if="!openMenus.includes(group.title) && getGroupTotalBadge(group)"
                                class="h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1 text-[10px] font-black leading-none text-white shadow-xs"
                                :class="isDesktopCollapsed ? 'hidden' : 'inline-flex'"
                            >
                                {{ getGroupTotalBadge(group) }}
                            </span>
                            <svg
                                class="h-4 w-4 shrink-0 transition-transform duration-200"
                                :class="[
                                    isDesktopCollapsed
                                        ? 'inline lg:hidden'
                                        : 'inline',
                                    activeMenu === group.title
                                        ? openMenus.includes(group.title)
                                            ? 'rotate-180 text-white'
                                            : 'text-white/80'
                                        : openMenus.includes(group.title)
                                          ? 'rotate-180 text-primary dark:text-blue-400'
                                          : 'text-slate-400 dark:text-slate-500',
                                ]"
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
                        </div>
                    </button>

                    <!-- Inline Accordion Submenu (Mobile view ALWAYS, Desktop view when NOT collapsed) -->
                    <div
                        :class="[
                            isDesktopCollapsed ? 'block lg:hidden' : 'block',
                            openMenus.includes(group.title)
                                ? 'grid-rows-[1fr] opacity-100'
                                : 'grid-rows-[0fr] opacity-0',
                            'grid transition-all duration-300 ease-in-out',
                        ]"
                    >
                        <div class="overflow-hidden">
                            <div class="mt-1 space-y-0.5 pl-3">
                                <div
                                    class="ml-3.5 space-y-0.5 border-l border-slate-200 py-1 pl-2.5 dark:border-slate-700 dark:border-slate-800"
                                >
                                    <component
                                        :is="item.disabled ? 'div' : Link"
                                        v-for="(item, itemIdx) in group.items"
                                        :key="itemIdx"
                                        @click="item.disabled ? null : handleMobileClose()"
                                        :href="item.disabled ? undefined : item.url"
                                        :class="[
                                            item.disabled
                                                ? 'cursor-not-allowed opacity-55 text-slate-400 dark:text-slate-500 select-none'
                                                : isItemActive(item.url)
                                                  ? 'bg-primary/10 font-semibold text-primary dark:bg-primary/25 dark:text-blue-300'
                                                  : 'font-medium text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-200',
                                            'flex items-center justify-between rounded-lg px-3 py-2 text-[13px] transition-all duration-200',
                                        ]"
                                    >
                                        <span class="truncate">{{ item.title }}</span>
                                        <span
                                            v-if="item.badgeText"
                                            class="ml-2 inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-slate-500 uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            {{ item.badgeText }}
                                        </span>
                                        <span
                                            v-else-if="getSidebarBadge(item.url)"
                                            class="ml-2 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1 text-[10px] font-black leading-none text-white shadow-xs"
                                        >
                                            {{ getSidebarBadge(item.url) }}
                                        </span>
                                    </component>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Flyout Submenu Card (Collapsed Desktop View ONLY) -->
                    <div
                        v-if="
                            isDesktopCollapsed && activeFlyout === group.title
                        "
                        @mouseenter="handleMouseEnterFlyout"
                        @mouseleave="handleMouseLeaveGroup"
                        :class="[
                            idx >= menuGroups.length - 3 ? 'bottom-0' : 'top-0',
                            'animate-fade-in-fast absolute left-full z-50 ml-3 hidden w-56 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-2xl ring-1 ring-slate-900/5 lg:block dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:ring-white/5',
                        ]"
                    >
                        <!-- Flyout Header -->
                        <div
                            class="mb-2 flex items-center justify-between border-b border-slate-100 px-2 py-1.5 dark:border-slate-800"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div
                                    class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/25 dark:text-blue-300"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="group.icon"
                                        />
                                    </svg>
                                </div>
                                <span
                                    class="truncate text-xs font-bold text-slate-800 dark:text-slate-100"
                                    >{{ group.title }}</span
                                >
                            </div>
                            <span
                                v-if="getGroupTotalBadge(group)"
                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1 text-[10px] font-black leading-none text-white shadow-xs"
                            >
                                {{ getGroupTotalBadge(group) }}
                            </span>
                        </div>

                        <!-- Flyout Items -->
                        <div
                            class="custom-scrollbar max-h-[calc(100vh-140px)] space-y-0.5 overflow-y-auto"
                        >
                            <component
                                :is="item.disabled ? 'div' : Link"
                                v-for="(item, itemIdx) in group.items"
                                :key="itemIdx"
                                @click="
                                    if (!item.disabled) {
                                        handleMobileClose();
                                        activeFlyout = null;
                                    }
                                "
                                :href="item.disabled ? undefined : item.url"
                                :class="[
                                    item.disabled
                                        ? 'cursor-not-allowed opacity-55 text-slate-400 dark:text-slate-500 select-none'
                                        : isItemActive(item.url)
                                          ? 'bg-primary/10 font-semibold text-primary dark:bg-primary/25 dark:text-blue-300'
                                          : 'font-medium text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-200',
                                    'flex items-center justify-between rounded-lg px-3 py-2 text-[13px] transition-colors',
                                ]"
                            >
                                <span class="truncate">{{ item.title }}</span>
                                <span
                                    v-if="item.badgeText"
                                    class="ml-2 inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-slate-500 uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                                >
                                    {{ item.badgeText }}
                                </span>
                                <span
                                    v-else-if="getSidebarBadge(item.url)"
                                    class="ml-2 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1 text-[10px] font-black leading-none text-white shadow-xs"
                                >
                                    {{ getSidebarBadge(item.url) }}
                                </span>
                            </component>
                        </div>
                    </div>
                </div>
            </template>
        </nav>
    </aside>
</template>

<style scoped>
/* Custom Scrollbar for Sidebar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}

@keyframes fadeInFast {
    from {
        opacity: 0;
        transform: translateX(-4px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-fade-in-fast {
    animation: fadeInFast 0.15s ease-out forwards;
}
</style>
