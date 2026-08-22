<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, useSlots } from 'vue';

const props = defineProps<{
    show: boolean;
    title?: string;
    description?: string;
    maxWidth?:
        | 'sm'
        | 'md'
        | 'lg'
        | 'xl'
        | '2xl'
        | '3xl'
        | '4xl'
        | '5xl'
        | '6xl'
        | '7xl'
        | string;
    closeable?: boolean;
    overflowVisible?: boolean;
    zIndexClass?: string;
}>();

const emit = defineEmits(['close']);
const slots = useSlots();

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const maxWidthClass = computed(() => {
    return (
        {
            sm: 'sm:max-w-sm',
            md: 'sm:max-w-md',
            lg: 'sm:max-w-lg',
            xl: 'sm:max-w-xl',
            '2xl': 'sm:max-w-2xl',
            '3xl': 'sm:max-w-3xl',
            '4xl': 'sm:max-w-4xl',
            '5xl': 'sm:max-w-5xl',
            '6xl': 'sm:max-w-6xl',
            '7xl': 'sm:max-w-7xl',
        }[props.maxWidth || '2xl'] ||
        props.maxWidth ||
        'sm:max-w-2xl'
    );
});

watch(
    () => props.show,
    (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <teleport v-if="isMounted" to="body">
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                :class="[
                    'fixed inset-0 flex items-center justify-center p-4 sm:p-0',
                    props.zIndexClass || 'z-[110]',
                ]"
            >
                <!-- Static Backdrop (No click-to-close event) -->
                <div
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity dark:bg-slate-950/80"
                ></div>

                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="show"
                        :class="[
                            'relative mx-auto flex max-h-[90vh] w-full flex-col rounded-2xl bg-white shadow-2xl transition-colors duration-200 dark:border dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100',
                            overflowVisible
                                ? 'overflow-visible'
                                : 'overflow-hidden',
                            maxWidthClass,
                        ]"
                    >
                        <!-- Header only renders if title is provided -->
                        <div
                            v-if="title"
                            class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4 sm:px-6 sm:py-5 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    v-if="slots.icon"
                                    class="flex shrink-0 items-center justify-center"
                                >
                                    <slot name="icon"></slot>
                                </div>
                                <div>
                                    <h3
                                        class="text-[17px] leading-tight font-bold text-slate-800 sm:text-lg dark:text-slate-100"
                                        id="modal-title"
                                    >
                                        {{ title }}
                                    </h3>
                                    <p
                                        v-if="description"
                                        class="mt-1 text-sm leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ description }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="emit('close')"
                                type="button"
                                class="ml-4 shrink-0 rounded-full p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                            >
                                <span class="sr-only">Close</span>
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div
                            :class="[
                                overflowVisible
                                    ? 'overflow-visible'
                                    : 'overflow-y-auto',
                                title ? 'p-5' : '',
                            ]"
                        >
                            <slot></slot>
                        </div>

                        <!-- Footer area -->
                        <div
                            v-if="title || $slots.footer"
                            class="flex shrink-0 flex-col-reverse justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800"
                        >
                            <slot name="footer">
                                <button
                                    v-if="title"
                                    type="button"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 focus:ring-2 focus:ring-gray-200 focus:outline-none sm:w-auto dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                                    @click="emit('close')"
                                >
                                    Tutup
                                </button>
                            </slot>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
