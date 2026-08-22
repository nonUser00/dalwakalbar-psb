<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';

defineProps<{
    isSidebarOpen: boolean;
    isDesktopCollapsed?: boolean;
}>();

defineEmits(['toggleSidebar', 'toggleDesktopCollapse']);
const dropdownOpen = ref(false);

const page = usePage();
import { logout } from '@/routes/admin';
import { index as profileIndex } from '@/routes/admin/profile';
const user = computed(() => page.props.auth?.user as any);
</script>

<template>
    <header
        class="relative z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-2xs backdrop-blur-md transition-colors duration-200 lg:px-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900/90"
    >
        <!-- Left Side Controls -->
        <div class="flex items-center gap-2">
            <!-- Mobile Hamburger -->
            <button
                @click="$emit('toggleSidebar')"
                class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-500 transition-all hover:bg-slate-100 hover:text-slate-800 focus:outline-none lg:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                title="Buka Sidebar"
            >
                <svg
                    class="h-5 w-5"
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
            </button>

            <!-- Desktop Sidebar Toggle Button (Modern Panel Left Icon) -->
            <button
                @click="$emit('toggleDesktopCollapse')"
                class="hidden h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-500 transition-all hover:bg-slate-100 hover:text-slate-800 focus:outline-none lg:flex dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                :title="
                    isDesktopCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar'
                "
            >
                <svg
                    class="h-5 w-5 transition-all duration-200"
                    :class="
                        isDesktopCollapsed
                            ? 'text-primary dark:text-blue-400'
                            : 'text-slate-500 dark:text-slate-400'
                    "
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <rect
                        x="3"
                        y="3"
                        width="18"
                        height="18"
                        rx="3"
                        stroke-width="2"
                    />
                    <path d="M9 3v18" stroke-width="2" />
                </svg>
            </button>
        </div>

        <!-- Right Side User Menu & Notifications -->
        <div class="flex items-center gap-1.5 sm:gap-2.5">
            <!-- Notifications Button -->
            <button
                class="relative flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-500 transition-all hover:bg-slate-100 hover:text-slate-800 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                title="Notifikasi"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    ></path>
                </svg>
                <span
                    class="absolute top-2.5 right-2.5 h-2 w-2 animate-pulse rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"
                ></span>
            </button>

            <!-- Dark Mode Toggle Button (Positioned right next to Notifications icon) -->
            <DarkModeToggle />

            <div class="h-5 w-px bg-slate-200 dark:bg-slate-700"></div>

            <!-- Profile Dropdown -->
            <div class="relative">
                <button
                    @click="dropdownOpen = !dropdownOpen"
                    class="flex cursor-pointer items-center gap-2.5 rounded-full py-1 pr-2 pl-3 focus:outline-none"
                >
                    <div class="hidden flex-col text-right lg:flex">
                        <span
                            class="max-w-40 truncate text-[13px] leading-tight font-bold text-slate-800 dark:text-slate-200"
                            >{{ user?.name || 'Admin' }}</span
                        >
                        <span
                            class="mt-0.5 max-w-40 truncate text-[11px] font-medium text-slate-500 dark:text-slate-400"
                            >{{ user?.email || '-' }}</span
                        >
                    </div>
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-blue-200 bg-blue-100 text-sm font-bold text-blue-700 shadow-2xs dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300"
                    >
                        <img
                            v-if="user?.foto_url"
                            :src="user.foto_url"
                            :alt="user?.name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="text-xs font-bold text-blue-700 uppercase dark:text-blue-300"
                            >{{ user?.name?.charAt(0) || 'A' }}</span
                        >
                    </div>
                    <svg
                        class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 dark:text-slate-500"
                        :class="{ 'rotate-180': dropdownOpen }"
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

                <!-- Dropdown Menu Overlay -->
                <div
                    v-if="dropdownOpen"
                    @click="dropdownOpen = false"
                    class="fixed inset-0 z-40 bg-transparent"
                ></div>

                <!-- Dropdown Panel -->
                <transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                    <div
                        v-if="dropdownOpen"
                        class="absolute right-0 z-50 mt-2 w-64 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-900/5 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:ring-white/5"
                    >
                        <div
                            class="border-b border-slate-100 bg-slate-50/50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                        >
                            <p
                                class="truncate text-sm font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ user?.name || 'Admin' }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-xs font-medium text-slate-500 dark:text-slate-400"
                            >
                                {{ user?.email || '-' }}
                            </p>
                        </div>

                        <div class="space-y-0.5 p-1.5">
                            <Link
                                :href="profileIndex.url()"
                                @click="dropdownOpen = false"
                                class="flex items-center rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                            >
                                <svg
                                    class="mr-2.5 h-4 w-4 text-slate-400 dark:text-slate-500"
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
                                Profil Saya
                            </Link>

                            <Link
                                :href="logout.url()"
                                method="post"
                                as="button"
                                class="flex w-full items-center rounded-xl px-3.5 py-2.5 text-left text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                            >
                                <svg
                                    class="mr-2.5 h-4 w-4 text-rose-500 dark:text-rose-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    />
                                </svg>
                                Keluar
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>
