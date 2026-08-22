<script setup lang="ts">
import Cropper from 'cropperjs';
import { ref, onUnmounted, nextTick, computed } from 'vue';
import 'cropperjs/dist/cropper.css';

const props = defineProps<{
    modelValue: string | null; // URL of existing image or newly cropped base64
    initialImage?: string | null;
    aspectRatio?: number;
    label?: string;
    error?: string;
    helpText?: string;

    required?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'change', 'remove']);

const imageRef = ref<HTMLImageElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const cropper = ref<Cropper | null>(null);

const rawImageUrl = ref<string | null>(null);
const showCropperModal = ref(false);
const isDragging = ref(false);

const processFile = (file: File) => {
    // Check file type
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Format file tidak didukung. Harap unggah JPG atau PNG.');

        return;
    }

    // Check file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal 2MB.');

        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        if (e.target?.result) {
            rawImageUrl.value = e.target.result as string;
            showCropperModal.value = true;
            initCropper();
        }
    };
    reader.readAsDataURL(file);
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files[0]) {
        processFile(input.files[0]);
    }
};

const handleDrop = (event: DragEvent) => {
    isDragging.value = false;

    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        processFile(event.dataTransfer.files[0]);
    }
};

const initCropper = () => {
    nextTick(() => {
        if (imageRef.value) {
            if (cropper.value) {
                cropper.value.destroy();
            }

            cropper.value = new Cropper(imageRef.value, {
                aspectRatio: props.aspectRatio || 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        }
    });
};

const cancelCrop = () => {
    showCropperModal.value = false;
    rawImageUrl.value = null;

    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const saveCrop = () => {
    if (cropper.value) {
        const canvas = cropper.value.getCroppedCanvas({
            width: 800,
            height: Math.round(800 / (props.aspectRatio || 1)),
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        const croppedBase64 = canvas.toDataURL('image/jpeg', 0.9);
        emit('update:modelValue', croppedBase64);
        emit('change', croppedBase64);

        cancelCrop();
    }
};

const isRemoved = ref(false);

const displayImage = computed(() => {
    if (props.modelValue) {
        return props.modelValue;
    }

    if (!isRemoved.value && props.initialImage) {
        return props.initialImage;
    }

    return null;
});

const removeImage = () => {
    emit('update:modelValue', null);
    emit('change', null);
    emit('remove');
    isRemoved.value = true;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const triggerUpload = () => {
    if (!displayImage.value && fileInput.value) {
        fileInput.value.click();
    }
};

onUnmounted(() => {
    if (cropper.value) {
        cropper.value.destroy();
    }
});
</script>

<template>
    <div class="space-y-2">
        <label
            v-if="label"
            class="block text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300"
            >{{ label }}
            <span
                v-if="required || $attrs?.required !== undefined"
                class="ml-1 text-red-500"
                >*</span
            ></label
        >

        <!-- Drag & Drop Upload Area -->
        <div
            class="relative flex min-h-50 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed transition-all"
            :class="[
                displayImage
                    ? 'border-gray-200 dark:border-slate-700'
                    : isDragging
                      ? 'border-primary bg-primary/5 dark:border-blue-500 dark:bg-blue-500/10'
                      : 'border-primary/50 bg-white hover:bg-primary/5 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800/50',
                error
                    ? 'border-rose-400 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/40'
                    : '',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerUpload"
        >
            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleFileChange"
            />

            <!-- Existing Image State -->
            <template v-if="displayImage">
                <img
                    :src="displayImage || undefined"
                    class="block max-h-75 max-w-full"
                    ref="imageRef"
                    alt="Upload"
                />

                <!-- Overlay Actions -->
                <div
                    class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-black/60 opacity-0 transition-opacity hover:opacity-100"
                >
                    <button
                        type="button"
                        @click.stop="() => fileInput?.click()"
                        class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-800 transition-colors hover:bg-primary hover:text-white dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-blue-600"
                    >
                        Ganti Foto
                    </button>
                    <button
                        type="button"
                        @click.stop="removeImage"
                        class="rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-rose-600"
                    >
                        Hapus Foto
                    </button>
                </div>
            </template>

            <!-- Empty / Upload State -->
            <template v-else>
                <div class="pointer-events-none flex flex-col items-center p-8">
                    <!-- Upload Icon -->
                    <svg
                        class="mb-4 h-16 w-16 text-primary dark:text-slate-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                        />
                    </svg>

                    <!-- Browse Button -->
                    <div
                        class="mb-3 rounded-full bg-primary px-8 py-2.5 font-semibold text-white dark:bg-slate-800 dark:text-slate-300"
                    >
                        Browse
                    </div>

                    <!-- Drop Text -->
                    <p
                        class="mb-3 font-medium text-gray-400 dark:text-slate-500"
                    >
                        drop a file here
                    </p>

                    <!-- Support Text -->
                    <p
                        class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300"
                    >
                        <span class="text-red-500">*</span>File supported .png,
                        .jpg & .webp
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Maksimal 2MB
                    </p>
                </div>
            </template>
        </div>

        <p
            v-if="error"
            class="mt-2 flex items-center text-xs font-medium text-rose-500"
        >
            <svg
                class="mr-1 h-3.5 w-3.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
            {{ error }}
        </p>

        <!-- Cropper Modal (Teleport to body to overlay everything) -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showCropperModal"
                    class="fixed inset-0 z-[130] flex items-center justify-center bg-gray-900/80 p-4 backdrop-blur-sm sm:p-6"
                >
                    <div
                        class="flex max-h-full w-full max-w-2xl flex-col overflow-hidden rounded-4xl bg-white shadow-2xl dark:bg-slate-900"
                    >
                        <div
                            class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-slate-100"
                            >
                                Potong Gambar
                            </h3>
                            <button
                                @click="cancelCrop"
                                class="rounded-full bg-gray-100 p-2 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:bg-slate-800 dark:text-slate-300 dark:text-slate-500 dark:hover:bg-slate-700"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div
                            class="min-h-75 flex-1 overflow-hidden bg-gray-50 p-4 sm:p-6 dark:bg-slate-800/50"
                        >
                            <div
                                class="flex max-h-[60vh] w-full items-center justify-center"
                            >
                                <img
                                    ref="imageRef"
                                    :src="rawImageUrl || undefined"
                                    class="hidden max-h-[60vh] max-w-full"
                                    style="display: block; max-width: 100%"
                                />
                            </div>
                        </div>

                        <div
                            class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <button
                                type="button"
                                @click="cancelCrop"
                                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-800/50 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="saveCrop"
                                class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/30 transition-all hover:bg-primary/90 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                            >
                                Simpan Potongan
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
