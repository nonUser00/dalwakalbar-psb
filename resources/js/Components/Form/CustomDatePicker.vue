<script setup lang="ts">
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue: any;
    placeholder?: string;
    label?: string;
    error?: string;
    minDate?: string;
    maxDate?: string;
    disabled?: boolean;
    required?: boolean;
}>();

defineEmits(['update:modelValue']);

// Ensure modelValue is formatted as YYYY-MM-DD for native HTML date input
const formattedValue = computed(() => {
    if (!props.modelValue) {
        return '';
    }

    if (typeof props.modelValue === 'string') {
        return props.modelValue.substring(0, 10);
    }

    return props.modelValue;
});
</script>

<template>
    <div class="relative w-full">
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200"
        >
            {{ label }}
            <span
                v-if="required || $attrs?.required !== undefined"
                class="ml-1 text-red-500"
                >*</span
            >
        </label>
        <input
            type="date"
            :value="formattedValue"
            @input="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
            :min="minDate"
            :max="maxDate"
            :placeholder="placeholder"
            :disabled="disabled"
            v-bind="$attrs"
            class="block w-full min-w-0 cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:bg-slate-800 dark:focus:ring-blue-500/20 dark:disabled:bg-slate-900 min-h-[42px]"
            :class="{
                'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-rose-500 dark:focus:border-rose-500':
                    error,
            }"
        />
        <p
            v-if="error"
            class="mt-1.5 text-xs font-medium text-red-500 dark:text-rose-400"
        >
            {{ error }}
        </p>
    </div>
</template>
