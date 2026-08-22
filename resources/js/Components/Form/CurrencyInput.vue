<script setup lang="ts">
import { ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        modelValue: number | string | null;
        label?: string;
        error?: string;
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
        prefix?: string;
    }>(),
    {
        placeholder: '0',
        disabled: false,
        required: false,
        prefix: 'Rp',
    },
);

const emit = defineEmits(['update:modelValue']);

/**
 * Safely parses any number, numeric string, or float string (e.g. "150000.00" from DB decimal column)
 * into a rounded integer number without accidentally preserving trailing decimal zeroes as thousands digits.
 */
const parseValueToNumber = (
    val: number | string | null | undefined,
): number | null => {
    if (val === null || val === undefined || val === '') {
return null;
}

    const num = typeof val === 'number' ? val : parseFloat(String(val));

    if (isNaN(num)) {
return null;
}

    return Math.round(num);
};

const formatDisplay = (val: number | string | null | undefined): string => {
    if (val === null || val === undefined || val === '') {
return '';
}

    const num = parseValueToNumber(val);

    if (num === null) {
return '';
}

    return num.toLocaleString('id-ID');
};

const displayValue = ref(formatDisplay(props.modelValue));

watch(
    () => props.modelValue,
    (newVal) => {
        const currentNum = parseValueToNumber(displayValue.value);
        const newNum = parseValueToNumber(newVal);

        if (currentNum !== newNum) {
            displayValue.value = formatDisplay(newVal);
        }
    },
    { immediate: true },
);

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const rawInput = target.value;
    const digits = rawInput.replace(/\D/g, '');

    if (!digits) {
        displayValue.value = '';
        emit('update:modelValue', 0);

        return;
    }

    const numericVal = parseInt(digits, 10);
    displayValue.value = numericVal.toLocaleString('id-ID');
    emit('update:modelValue', numericVal);
};

const handleKeyDown = (event: KeyboardEvent) => {
    // Allow navigation keys: backspace, delete, tab, escape, enter, arrows, home, end
    const allowedKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'Escape',
        'Enter',
        'ArrowLeft',
        'ArrowRight',
        'Home',
        'End',
    ];

    if (
        allowedKeys.includes(event.key) ||
        (event.ctrlKey &&
            ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase()))
    ) {
        return;
    }

    // Block non-digit keys
    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
};
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200"
        >
            {{ label }}
            <span
                v-if="required || $attrs?.required !== undefined"
                class="ml-0.5 text-rose-500"
                >*</span
            >
        </label>
        <div
            class="relative flex w-full items-center overflow-hidden rounded-xl border bg-white transition-all focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:focus-within:border-blue-500 dark:focus-within:ring-blue-500/20"
            :class="[
                error
                    ? 'border-rose-500 focus-within:border-rose-500 focus-within:ring-rose-500/20 dark:border-rose-500'
                    : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600',
                disabled
                    ? 'cursor-not-allowed bg-slate-50 opacity-60 dark:bg-slate-900'
                    : '',
            ]"
        >
            <div
                v-if="prefix"
                class="flex h-full shrink-0 items-center border-r border-slate-200 bg-slate-100/80 px-3 py-2 text-xs font-bold text-slate-600 select-none dark:border-slate-700 dark:bg-slate-700 dark:text-slate-300"
            >
                {{ prefix }}
            </div>
            <input
                type="text"
                inputmode="numeric"
                :value="displayValue"
                @input="handleInput"
                @keydown="handleKeyDown"
                :placeholder="placeholder"
                :disabled="disabled"
                v-bind="$attrs"
                class="w-full border-0 bg-transparent px-3 py-2 font-mono text-sm font-bold text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none disabled:cursor-not-allowed dark:text-slate-100 dark:placeholder-slate-500"
            />
        </div>
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
