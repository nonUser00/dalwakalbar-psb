<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AdminHeader from '@/Components/Layouts/AdminHeader.vue';
import AdminSidebar from '@/Components/Layouts/AdminSidebar.vue';
import Toast from '@/Components/Toast.vue';

defineProps<{
    title?: string;
}>();

const isMobileSidebarOpen = ref(false);
const isDesktopCollapsed = ref(false);

const handleResize = () => {
    if (window.innerWidth >= 1024) {
        isMobileSidebarOpen.value = false;
    }
};

const toggleSidebar = () => {
    if (window.innerWidth < 1024) {
        isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
    }
};

const toggleDesktopCollapse = () => {
    isDesktopCollapsed.value = !isDesktopCollapsed.value;

    if (typeof window !== 'undefined') {
        localStorage.setItem(
            'admin_sidebar_collapsed',
            String(isDesktopCollapsed.value),
        );
    }
};

const closeMobileSidebar = () => {
    if (window.innerWidth < 1024) {
        isMobileSidebarOpen.value = false;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('admin_sidebar_collapsed');

        if (stored !== null) {
            isDesktopCollapsed.value = stored === 'true';
        }
    }

    handleResize();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div
        class="flex h-screen overflow-hidden bg-surface/20 font-sans transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100"
    >
        <Toast />

        <AdminSidebar
            :isMobileSidebarOpen="isMobileSidebarOpen"
            :isDesktopCollapsed="isDesktopCollapsed"
            @toggleSidebar="toggleSidebar"
            @toggleDesktopCollapse="toggleDesktopCollapse"
            @closeMobile="closeMobileSidebar"
        />

        <div class="flex flex-1 flex-col overflow-hidden">
            <AdminHeader
                :isSidebarOpen="isMobileSidebarOpen"
                :isDesktopCollapsed="isDesktopCollapsed"
                @toggleSidebar="toggleSidebar"
                @toggleDesktopCollapse="toggleDesktopCollapse"
            />

            <!-- Main Content Area -->
            <main
                class="relative flex flex-1 flex-col overflow-hidden bg-surface/20 transition-colors duration-200 dark:bg-slate-950"
            >
                <!-- Decorative background pattern -->
                <div
                    class="pointer-events-none absolute inset-0 z-0 opacity-[0.15] dark:opacity-[0.06]"
                    style="
                        background-image: radial-gradient(
                            var(--color-neutral-warm) 1px,
                            transparent 1px
                        );
                        background-size: 24px 24px;
                    "
                ></div>

                <!-- Page Content -->
                <div
                    class="relative flex flex-1 flex-col overflow-auto p-4 md:p-6 lg:p-8"
                >
                    <div class="animate-fade-in-up flex-1">
                        <div v-if="$slots.header" class="mb-6">
                            <slot name="header" />
                        </div>
                        <slot />
                    </div>

                    <!-- Footer -->
                    <footer
                        class="mt-12 border-t border-slate-200 pt-6 pb-2 text-center text-sm font-medium text-slate-500 transition-colors dark:border-slate-700/60 dark:border-slate-800/80 dark:text-slate-400"
                    >
                        &copy; {{ new Date().getFullYear() }} Darullughah
                        Wadda'wah Perwakilan Kalimantan Barat. All Rights
                        Reserved.
                    </footer>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

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
</style>
