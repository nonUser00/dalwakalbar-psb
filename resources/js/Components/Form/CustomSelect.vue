<script setup lang="ts">
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue: any;
    options: any[];
    placeholder?: string;
    label?: string;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}>();

defineEmits(['update:modelValue']);

// Normalize options array whether passed as strings ['A', 'B'], objects [{ value: 'a', label: 'A' }], or models [{ id: 'a', name: 'A' }]
const normalizedOptions = computed(() => {
    if (!props.options || !Array.isArray(props.options)) {
        return [];
    }

    return props.options.map((opt) => {
        if (typeof opt === 'object' && opt !== null) {
            const val =
                'value' in opt
                    ? opt.value
                    : 'id' in opt
                      ? opt.id
                      : 'code' in opt
                        ? opt.code
                        : opt;

            const lbl =
                'label' in opt
                    ? opt.label
                    : 'name' in opt
                      ? opt.name
                      : 'nama' in opt
                        ? opt.nama
                        : val;

            return {
                value: val === null || val === undefined ? '' : val,
                label:
                    lbl !== undefined && lbl !== null && String(lbl).trim() !== ''
                        ? lbl
                        : (props.placeholder || '-- Pilih --'),
            };
        }

        return {
            value: opt === null || opt === undefined ? '' : opt,
            label:
                opt !== null && opt !== undefined && String(opt).trim() !== ''
                    ? opt
                    : (props.placeholder || '-- Pilih --'),
        };
    });
});

const hasEmptyOption = computed(() => {
    return normalizedOptions.value.some(
        (opt) => opt.value === '' || opt.value === null || opt.value === undefined,
    );
});

const isValueInOptions = computed(() => {
    const val = props.modelValue;
    if (val === '' || val === null || val === undefined) {
        return true;
    }
    return normalizedOptions.value.some((opt) => String(opt.value) === String(val));
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

        <div class="relative flex items-center">
            <select
                :value="modelValue === null || modelValue === undefined ? '' : modelValue"
                @change="
                    $emit(
                        'update:modelValue',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
                :disabled="disabled"
                v-bind="$attrs"
                class="block w-full cursor-pointer appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pr-10 pl-3.5 text-sm font-medium text-gray-900 shadow-2xs transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20 dark:disabled:bg-slate-900"
                :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-rose-500 dark:focus:border-rose-500':
                        error,
                }"
            >
                <option
                    v-if="!hasEmptyOption"
                    value=""
                    class="dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ placeholder || '-- Pilih --' }}
                </option>
                <option
                    v-if="!isValueInOptions && modelValue !== '' && modelValue !== null && modelValue !== undefined"
                    :value="modelValue"
                    class="hidden"
                >
                    {{ modelValue }}
                </option>
                <option
                    v-for="(opt, idx) in normalizedOptions"
                    :key="idx"
                    :value="opt.value"
                    class="dark:bg-slate-800 dark:text-slate-200"
                >
                    {{ opt.label }}
                </option>
            </select>

            <!-- Custom Chevron Down Arrow Icon positioned with comfortable 14px (pr-3.5) padding -->
            <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 dark:text-slate-500"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
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
