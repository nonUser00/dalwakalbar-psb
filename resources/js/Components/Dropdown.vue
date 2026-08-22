<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right' | 'top' | 'bottom' | 'responsive';
        width?: '48' | '64' | 'max';
        contentClasses?: string;
    }>(),
    {
        align: 'right',
        width: '48',
        contentClasses: 'py-1 bg-white dark:bg-slate-900',
    },
);

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        '48': 'w-48',
        '64': 'w-64',
        max: 'w-max',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'origin-top-left left-0';
    } else if (props.align === 'right') {
        return 'origin-top left-1/2 -translate-x-1/2 sm:origin-top-right sm:left-auto sm:right-0 sm:translate-x-0';
    } else if (props.align === 'top') {
        return 'origin-bottom mb-2 bottom-full right-0';
    } else if (props.align === 'responsive') {
        return 'origin-top-left left-0 sm:origin-top-right sm:left-auto sm:right-0 sm:translate-x-0';
    } else {
        return 'origin-top right-0';
    }
});

const open = ref(false);
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-50 mt-2 rounded-xl shadow-xl focus:outline-none dark:shadow-slate-900/50"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
            >
                <div
                    class="overflow-hidden rounded-xl border border-gray-100 bg-white dark:border-slate-800 dark:bg-slate-900"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>
