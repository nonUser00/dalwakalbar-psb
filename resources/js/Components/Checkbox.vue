<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits<{
    (e: 'update:checked', value: any): void;
    (e: 'update:modelValue', value: any): void;
    (e: 'change', event: Event): void;
}>();

const props = withDefaults(
    defineProps<{
        checked?: boolean | any[];
        modelValue?: boolean | any[];
        value?: any;
        indeterminate?: boolean;
        disabled?: boolean;
        shape?: 'square' | 'circle';
        rounded?: boolean;
        id?: string;
        name?: string;
    }>(),
    {
        checked: undefined,
        modelValue: undefined,
        value: null,
        indeterminate: false,
        disabled: false,
        shape: 'square',
        rounded: false,
        id: undefined,
        name: undefined,
    }
);

const isCircle = computed(() => props.shape === 'circle' || props.rounded);

const proxyChecked = computed({
    get() {
        if (props.modelValue !== undefined) {
            return props.modelValue;
        }

        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
        emit('update:modelValue', val);
    },
});

const handleChange = (e: Event) => {
    emit('change', e);
};
</script>

<template>
    <span
        class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center align-middle"
    >
        <input
            type="checkbox"
            :id="id"
            :name="name"
            :value="value"
            :disabled="disabled"
            v-model="proxyChecked"
            :indeterminate.prop="indeterminate"
            @change="handleChange"
            class="peer absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
        />
        <div
            class="flex h-full w-full items-center justify-center rounded-full border-2 border-gray-300 bg-white transition-all duration-200 ease-out group-hover:border-primary/70 peer-checked:border-primary peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30 peer-focus-visible:ring-offset-2 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:peer-checked:border-primary dark:peer-checked:bg-primary"
        ></div>

        <!-- Checkmark SVG -->
        <svg
            class="pointer-events-none absolute h-3.5 w-3.5 scale-50 text-white opacity-0 transition-all duration-150 ease-out peer-checked:scale-100 peer-checked:opacity-100"
            :class="{ hidden: indeterminate }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M5 13l4 4L19 7"
            />
        </svg>

        <!-- Indeterminate Line -->
        <div
            class="pointer-events-none absolute h-0.5 w-2.5 scale-50 rounded-sm bg-white opacity-0 transition-all duration-150 ease-out peer-checked:scale-50 peer-checked:opacity-0 dark:bg-slate-900"
            :class="{
                '!scale-100 !opacity-100': indeterminate,
                hidden: !indeterminate,
            }"
        ></div>
    </span>
</template>
