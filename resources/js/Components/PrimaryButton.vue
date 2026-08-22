<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
}>();
</script>

<template>
    <button
        :type="type || 'submit'"
        class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-transparent bg-primary px-3 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-primary-dark focus:bg-primary-dark focus:ring-2 focus:ring-primary/20 focus:outline-none active:bg-primary-dark disabled:opacity-50 sm:px-4 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:bg-blue-500 dark:focus:ring-blue-500/30"
    >
        <slot />
    </button>
</template>
