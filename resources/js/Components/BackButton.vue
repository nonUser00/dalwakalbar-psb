<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-1 text-sm font-medium text-gray-500 transition-colors hover:text-gray-900 dark:text-slate-100 dark:text-slate-400 dark:hover:text-slate-100"
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
                stroke-width="1.5"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
        </svg>
        <slot>Kembali</slot>
    </Link>
</template>
