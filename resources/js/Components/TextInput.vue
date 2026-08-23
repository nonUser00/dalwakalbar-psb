<script setup lang="ts">
import { onMounted, ref } from 'vue';

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

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div v-if="label || error" class="w-full">
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
            ref="input"
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
            class="relative block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder-gray-400 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :class="{
                'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                    error,
            }"
        />
        <p v-if="error" class="mt-1.5 text-xs font-medium text-red-500">
            {{ error }}
        </p>
    </div>
    <input
        v-else
        ref="input"
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
        class="relative block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder-gray-400 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        :class="{
            'border-red-500 focus:border-red-500 focus:ring-red-500/20': error,
        }"
    />
</template>
