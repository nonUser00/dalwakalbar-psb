<script setup lang="ts">
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<template>
    <button
        type="button"
        @click="toggleDarkMode"
        class="relative flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-800 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        :aria-label="
            isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'
        "
    >
        <!-- Sun Icon (shown in dark mode) -->
        <svg
            v-if="isDark"
            class="h-5 w-5 text-amber-400 transition-transform duration-300 hover:rotate-45"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>

        <!-- Moon Icon (shown in light mode) -->
        <svg
            v-else
            class="h-5 w-5 text-slate-600 transition-transform duration-300 hover:-rotate-12"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>
    </button>
</template>
