<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        description?: string;
        zIndexClass?: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    }>(),
    {
        maxWidth: 'lg',
    },
);

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth || 'lg'];
});

defineEmits(['close', 'reset', 'apply']);

// Prevent body scroll when modal is open
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
                    props.zIndexClass || 'z-[120]',
                ]"
            >
                <!-- Static Backdrop (No click event to close) -->
                <div
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity dark:bg-slate-950/80"
                ></div>

                <!-- Modal Content -->
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
                            'relative mx-auto flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl transition-colors duration-200 dark:border dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100',
                            maxWidthClass,
                        ]"
                    >
                        <!-- Header -->
                        <div
                            class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/50"
                        >
                            <div>
                                <h3
                                    class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                                >
                                    {{ title }}
                                </h3>
                                <p
                                    v-if="description"
                                    class="mt-1 text-xs text-gray-500 dark:text-slate-400"
                                >
                                    {{ description }}
                                </p>
                            </div>
                            <button
                                @click="$emit('close')"
                                type="button"
                                class="rounded-full p-1.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:text-slate-300 dark:text-slate-400 dark:text-slate-500 dark:hover:bg-slate-700/50 dark:hover:bg-slate-800 dark:hover:text-slate-200"
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
                        <div class="space-y-5 overflow-y-auto p-5">
                            <slot></slot>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex shrink-0 flex-col-reverse justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800"
                        >
                            <button
                                @click="$emit('reset')"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 focus:ring-2 focus:ring-gray-200 focus:outline-none sm:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                            >
                                Reset
                            </button>
                            <button
                                @click="$emit('apply')"
                                class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-primary-dark hover:shadow-lg focus:ring-2 focus:ring-primary/30 focus:outline-none sm:w-auto dark:bg-primary-dark dark:hover:bg-primary"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
