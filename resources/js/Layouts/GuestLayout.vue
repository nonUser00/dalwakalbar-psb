<script setup lang="ts">
import { onMounted } from 'vue';

onMounted(() => {
    document.documentElement.classList.remove('dark');
});
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-white font-sans selection:bg-primary/20 selection:text-primary-dark lg:flex-row"
    >
        <!-- Content Section (Left) -->
        <div class="relative z-10 flex h-screen w-full flex-col bg-white lg:w-1/2">
            <!-- This is where the page content will be injected -->
            <slot />
        </div>

        <!-- Image Section (Right) -->
        <div
            class="relative hidden h-screen w-full overflow-hidden bg-surface p-4 sm:p-6 lg:block lg:w-1/2 lg:p-8"
        >
            <div
                class="group relative h-full w-full overflow-hidden rounded-4xl bg-black shadow-2xl"
            >
                <img
                    src="/image/hero.jpg"
                    alt="Background"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-[20s] ease-out group-hover:scale-110"
                />
            </div>
        </div>
    </div>
</template>

<style>
/* Global styles for auth pages that use this layout */
.page-enter-active,
.page-leave-active {
    transition:
        opacity 0.5s ease,
        transform 0.5s ease;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}
</style>
