<script setup lang="ts">
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps<{
    modelValue: string | null;
    label?: string;
    error?: string;
    placeholder?: string;
    required?: boolean;
}>();

defineEmits(['update:modelValue']);

const show = ref(false);
</script>

<template>
    <div>
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-bold text-primary-dark dark:text-slate-200"
            >{{ label }}
            <span
                v-if="required || $attrs?.required !== undefined"
                class="ml-1 text-red-500"
                >*</span
            ></label
        >
        <div class="relative">
            <input
                :type="show ? 'text' : 'password'"
                :value="modelValue"
                @input="
                    $emit(
                        'update:modelValue',
                        ($event.target as HTMLInputElement).value,
                    )
                "
                :placeholder="placeholder"
                v-bind="$attrs"
                class="relative block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 px-3 py-2.5 pr-10 text-sm font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20"
                :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-rose-500 dark:focus:border-rose-500':
                        error,
                }"
            />
            <button
                type="button"
                @click="show = !show"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-sm leading-5 text-slate-400 transition-colors hover:text-primary focus:outline-none dark:text-slate-500 dark:hover:text-blue-400"
            >
                <svg
                    v-if="!show"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                    />
                </svg>
            </button>
        </div>
        <p
            v-if="error"
            class="mt-1.5 text-xs font-medium text-red-500 dark:text-rose-400"
        >
            {{ error }}
        </p>
    </div>
</template>
