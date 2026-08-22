<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
}>();
</script>

<template>
    <button
        :type="type || 'button'"
        class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:opacity-50 sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:focus:ring-blue-500/20"
    >
        <slot />
    </button>
</template>
