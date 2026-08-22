<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button
            v-if="as == 'button'"
            type="submit"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:bg-slate-700"
        >
            <slot />
        </button>

        <a
            v-else-if="as == 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:bg-slate-700"
        >
            <slot />
        </a>

        <Link
            v-else
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:bg-slate-800 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:bg-slate-700"
        >
            <slot />
        </Link>
    </div>
</template>
