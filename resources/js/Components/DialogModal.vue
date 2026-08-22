<script setup lang="ts">
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-4">
            <div
                class="mb-4 text-lg font-bold text-gray-900 dark:text-slate-100"
            >
                <slot name="title" />
            </div>

            <div class="text-sm text-gray-600 dark:text-slate-300">
                <slot name="content" />
            </div>
        </div>

        <template #footer>
            <slot name="footer" />
        </template>
    </Modal>
</template>
