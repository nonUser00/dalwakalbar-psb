<script setup lang="ts">
defineOptions({ inheritAttrs: false });

defineProps<{
    modelValue: string | number | null;
    label?: string;
    error?: string;
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
}>();

defineEmits(['update:modelValue']);
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
        <input
            :type="type || 'text'"
            :value="modelValue"
            @input="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
            :placeholder="placeholder"
            :disabled="disabled"
            v-bind="$attrs"
            class="relative block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 px-3 py-2.5 text-sm font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-800 dark:focus:ring-blue-500/20"
            :class="{
                'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-rose-500 dark:focus:border-rose-500':
                    error,
            }"
        />
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="transform -translate-y-1 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-1 opacity-0"
        >
            <p
                v-if="error"
                class="mt-1 flex items-center gap-1 text-[11px] font-medium text-rose-600 dark:text-rose-400"
            >
                <svg
                    class="h-3 w-3 shrink-0 text-rose-500 dark:text-rose-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span>{{ error }}</span>
            </p>
        </Transition>
    </div>
</template>
