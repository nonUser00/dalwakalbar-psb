<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
}>();
</script>

<template>
    <button
        :type="type || 'button'"
        class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-transparent bg-rose-600 px-3 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-rose-700 focus:ring-2 focus:ring-rose-500/20 focus:outline-none disabled:opacity-50 sm:px-4 dark:bg-rose-600 dark:hover:bg-rose-500 dark:focus:ring-rose-500/30"
    >
        <slot />
    </button>
</template>
