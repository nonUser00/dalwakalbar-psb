<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        width?: '40' | '48' | '52' | '56' | '64';
    }>(),
    {
        width: '48',
    },
);

const open = ref(false);
const isMobile = ref(false);
const isMounted = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref<{ top: string; left: string; maxHeight?: string }>({
    top: '0px',
    left: '0px',
});

const widthMap = {
    '40': { class: 'w-40', px: 160 },
    '48': { class: 'w-48', px: 192 },
    '52': { class: 'w-52', px: 208 },
    '56': { class: 'w-56', px: 224 },
    '64': { class: 'w-64', px: 256 },
};

const currentWidth = computed(() => widthMap[props.width] || widthMap['48']);

const checkMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

const updateDropdownPosition = () => {
    if (!isMobile.value && triggerRef.value) {
        const rect = triggerRef.value.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        const menuWidth = currentWidth.value.px;

        // Get actual unconstrained height of content
        const innerEl = dropdownRef.value?.firstElementChild as HTMLElement | null;
        const actualHeight = innerEl ? innerEl.scrollHeight : (dropdownRef.value ? dropdownRef.value.scrollHeight : 180);

        const spaceBelow = viewportHeight - rect.bottom - 12;
        const spaceAbove = rect.top - 12;

        let topPx: number;
        let maxH = 'none';

        // Check if fits below or if below has more room
        if (spaceBelow >= actualHeight || spaceBelow >= spaceAbove) {
            // Open downwards
            topPx = rect.bottom + 6;

            if (actualHeight > spaceBelow) {
                maxH = `${Math.max(160, spaceBelow)}px`;
            }
        } else {
            // Open upwards
            topPx = rect.top - 6 - actualHeight;

            if (actualHeight > spaceAbove) {
                topPx = 12;
                maxH = `${Math.max(160, spaceAbove)}px`;
            }
        }

        // Clamp left position so it doesn't spill off the screen left or right
        let leftPx = rect.right - menuWidth;

        if (leftPx < 12) {
leftPx = 12;
}

        if (leftPx + menuWidth > viewportWidth - 12) {
            leftPx = viewportWidth - menuWidth - 12;
        }

        dropdownStyle.value = {
            top: `${topPx}px`,
            left: `${leftPx}px`,
            maxHeight: maxH,
        };
    }
};

const toggle = async () => {
    if (open.value) {
        open.value = false;

        return;
    }

    open.value = true;
    dropdownStyle.value = {
        top: '-9999px',
        left: '-9999px',
        maxHeight: 'none',
    };
    await nextTick();
    updateDropdownPosition();
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const closeOnScroll = (e: Event) => {
    if (open.value && !isMobile.value) {
        const target = e.target as HTMLElement;

        if (dropdownRef.value && dropdownRef.value.contains(target)) {
            return;
        }

        open.value = false;
    }
};

onMounted(() => {
    isMounted.value = true;
    checkMobile();
    window.addEventListener('resize', () => {
        checkMobile();

        if (open.value) {
            updateDropdownPosition();
        }
    });
    document.addEventListener('keydown', closeOnEscape);
    window.addEventListener('scroll', closeOnScroll, true);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
    document.removeEventListener('keydown', closeOnEscape);
    window.removeEventListener('scroll', closeOnScroll, true);
});
</script>

<template>
    <div class="relative">
        <div ref="triggerRef" @click.stop="toggle">
            <slot name="trigger" />
        </div>

        <!-- Desktop Overlay (invisible) to close dropdown on outside click -->
        <div
            v-show="open && !isMobile"
            class="fixed inset-0 z-40 hidden sm:block"
            @click="open = false"
        ></div>

        <!-- Desktop Dropdown (Teleported to avoid overflow hidden) -->
        <Teleport v-if="isMounted" to="body">
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-if="open && !isMobile"
                    ref="dropdownRef"
                    class="fixed z-[100] hidden rounded-xl shadow-xl focus:outline-none sm:block"
                    :class="currentWidth.class"
                    :style="{
                        top: dropdownStyle.top,
                        left: dropdownStyle.left,
                    }"
                >
                    <div
                        class="overflow-y-auto rounded-xl border border-gray-100 bg-white p-1 shadow-lg ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-900 dark:ring-white/5"
                        :style="{
                            maxHeight: dropdownStyle.maxHeight === 'none' ? undefined : dropdownStyle.maxHeight,
                        }"
                        @click="open = false"
                    >
                        <slot name="content" />
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- Mobile Bottom Sheet -->
        <Teleport v-if="isMounted" to="body">
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open && isMobile"
                    class="fixed inset-0 z-[100] flex items-end sm:hidden"
                >
                    <div
                        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm dark:bg-slate-950/70"
                        @click="open = false"
                    ></div>

                    <transition
                        enter-active-class="transition ease-out duration-300 transform"
                        enter-from-class="translate-y-full"
                        enter-to-class="translate-y-0"
                        leave-active-class="transition ease-in duration-200 transform"
                        leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full"
                        appear
                    >
                        <div
                            v-if="open && isMobile"
                            class="relative flex max-h-[90vh] w-full flex-col rounded-t-3xl bg-white shadow-2xl dark:border-t dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div
                                class="sticky top-0 z-10 flex justify-center rounded-t-3xl border-b border-gray-100 bg-white pt-4 pb-2 dark:border-slate-800 dark:bg-slate-900"
                                @click="open = false"
                            >
                                <div
                                    class="h-1.5 w-12 rounded-full bg-gray-200 dark:bg-slate-700"
                                ></div>
                            </div>
                            <div
                                class="flex flex-col gap-1 overflow-y-auto p-4 pb-8"
                                @click="open = false"
                            >
                                <slot name="content" />
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </Teleport>
    </div>
</template>
