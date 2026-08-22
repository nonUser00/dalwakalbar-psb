<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const page = usePage();
const show = ref(false);
const isMounted = ref(false);
const message = ref('');
const type = ref<'success' | 'error' | 'info'>('info');
let timeout: any = null;

const checkFlash = () => {
    const flash = page.props.flash as any;
    const errors = page.props.errors as any;

    if (flash?.success) {
        message.value = flash.success;
        type.value = 'success';
        showToast();
    } else if (flash?.error) {
        message.value = flash.error;
        type.value = 'error';
        showToast();
    } else if (errors && Object.keys(errors).length > 0) {
        const firstErrorKey = Object.keys(errors)[0];
        message.value = errors[firstErrorKey];
        type.value = 'error';
        showToast();
    }
};

const showToast = () => {
    show.value = true;

    if (timeout) {
        clearTimeout(timeout);
    }

    timeout = setTimeout(() => {
        show.value = false;
    }, 4000);
};

watch(
    () => page.props.flash,
    () => {
        checkFlash();
    },
    { deep: true },
);

watch(
    () => page.props.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            const firstErrorKey = Object.keys(errors)[0];
            message.value = errors[firstErrorKey];
            type.value = 'error';
            showToast();
        }
    },
    { deep: true },
);

onMounted(() => {
    isMounted.value = true;
    checkFlash();
});
</script>

<template>
    <teleport to="body" v-if="isMounted">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[120] flex justify-center px-4 sm:top-6 sm:px-0"
        >
            <transition name="toast">
                <div
                    v-if="show"
                    class="pointer-events-auto flex w-full max-w-md items-center gap-3.5 overflow-hidden rounded-[20px] border border-transparent bg-white p-4 shadow-[0_8px_30px_rgb(0,0,0,0.12)] backdrop-blur-xl sm:w-max sm:min-w-90 dark:border-slate-800 dark:bg-slate-900/95"
                >
                    <!-- Icon Container -->
                    <div
                        :class="[
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-full',
                            type === 'success'
                                ? 'bg-emerald-50 dark:bg-emerald-500/20'
                                : type === 'error'
                                  ? 'bg-rose-50 dark:bg-rose-500/20'
                                  : 'bg-blue-50 dark:bg-blue-500/20',
                        ]"
                    >
                        <!-- Success -->
                        <svg
                            v-if="type === 'success'"
                            class="h-5 w-5 text-emerald-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4.5 12.75l6 6 9-13.5"
                            />
                        </svg>
                        <!-- Error -->
                        <svg
                            v-else-if="type === 'error'"
                            class="h-5 w-5 text-rose-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <!-- Info -->
                        <svg
                            v-else
                            class="h-5 w-5 text-blue-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                            />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1 py-0.5">
                        <p
                            class="text-[13px] font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                        >
                            {{
                                type === 'success'
                                    ? 'Sukses'
                                    : type === 'error'
                                      ? 'Peringatan'
                                      : 'Informasi'
                            }}
                        </p>
                        <p
                            class="mt-0.5 truncate text-[13px] leading-snug font-medium text-gray-500 sm:whitespace-normal dark:text-slate-400"
                        >
                            {{ message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        @click="show = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:bg-slate-800 dark:text-slate-300 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                            />
                        </svg>
                    </button>
                </div>
            </transition>
        </div>
    </teleport>
</template>

<style scoped>
.toast-enter-active {
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-24px) scale(0.95);
}
</style>
