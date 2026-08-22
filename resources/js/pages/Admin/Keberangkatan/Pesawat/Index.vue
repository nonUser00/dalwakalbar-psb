<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
</script>

<template>
    <div class="w-full">
        <Head title="Pesawat" />
        <div
            class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <h1
                class="text-2xl font-bold text-gray-800 dark:text-slate-100 dark:text-slate-200"
            >
                Pesawat
            </h1>
            <p
                class="mt-4 text-gray-600 dark:text-slate-300 dark:text-slate-400"
            >
                Halaman ini belum diimplementasikan.
            </p>
        </div>
    </div>
</template>
