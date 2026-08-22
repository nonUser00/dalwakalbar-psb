<script setup lang="ts">
import CustomSelect from './CustomSelect.vue';

defineOptions({ inheritAttrs: false });

defineProps<{
    modelValue: any;
    label?: string;
    error?: string;
    options: any[];
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
}>();

defineEmits(['update:modelValue']);
</script>

<template>
    <CustomSelect
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        :options="options"
        :label="label"
        :error="error"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        v-bind="$attrs"
    />
</template>
