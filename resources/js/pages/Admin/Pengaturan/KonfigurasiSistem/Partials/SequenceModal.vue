<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { sequence } from '@/routes/admin/pengaturan/konfigurasi';

const props = defineProps<{
    show: boolean;
    sequenceData: any | null;
}>();

const emit = defineEmits(['close', 'updated']);

const form = useForm({
    id: '',
    prefix: '',
    pattern: '',
    padding: 4,
});

// Drag & Drop State
const patternBlocks = ref<any[]>([]);

const availableVariables = [
    {
        type: 'var',
        label: 'Prefix',
        value: '{PREFIX}',
        color: 'bg-gradient-to-br from-blue-50 to-blue-100 text-blue-700 border-blue-200 shadow-blue-100/50 shadow-sm',
    },
    {
        type: 'var',
        label: 'Tahun (YYYY)',
        value: '{YYYY}',
        color: 'bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-700 border-indigo-200 shadow-indigo-100/50 shadow-sm',
    },
    {
        type: 'var',
        label: 'Bulan (MM)',
        value: '{MM}',
        color: 'bg-gradient-to-br from-sky-50 to-sky-100 text-sky-700 border-sky-200 shadow-sky-100/50 shadow-sm',
    },
    {
        type: 'var',
        label: 'Tanggal (DD)',
        value: '{DD}',
        color: 'bg-gradient-to-br from-teal-50 to-teal-100 text-teal-700 border-teal-200 shadow-teal-100/50 shadow-sm',
    },
    {
        type: 'var',
        label: 'Nomor Urut',
        value: '{AUTONUMBER}',
        color: 'bg-gradient-to-br from-purple-50 to-purple-100 text-purple-700 border-purple-200 shadow-purple-100/50 shadow-sm',
    },
];

const availableSeparators = [
    {
        type: 'separator',
        label: 'Miring ( / )',
        value: '/',
        color: 'bg-white dark:bg-slate-900 text-slate-600 border-slate-200 dark:border-slate-700 shadow-sm',
    },
    {
        type: 'separator',
        label: 'Strip ( - )',
        value: '-',
        color: 'bg-white dark:bg-slate-900 text-slate-600 border-slate-200 dark:border-slate-700 shadow-sm',
    },
];

const parsePattern = (str: string) => {
    if (!str) {
        return [];
    }

    const blocks: any[] = [];
    let currentText = '';
    let inVar = false;
    let currentVar = '';

    for (let i = 0; i < str.length; i++) {
        if (str[i] === '{') {
            if (currentText) {
                // Each character of text is a separator
                for (let j = 0; j < currentText.length; j++) {
                    blocks.push({
                        id: Date.now() + Math.random(),
                        type: 'separator',
                        value: currentText[j],
                    });
                }

                currentText = '';
            }

            inVar = true;
            currentVar = '{';
        } else if (str[i] === '}') {
            currentVar += '}';
            blocks.push({
                id: Date.now() + Math.random(),
                type: 'var',
                value: currentVar,
            });
            currentVar = '';
            inVar = false;
        } else {
            if (inVar) {
                currentVar += str[i];
            } else {
                currentText += str[i];
            }
        }
    }

    if (currentText) {
        for (let j = 0; j < currentText.length; j++) {
            blocks.push({
                id: Date.now() + Math.random(),
                type: 'separator',
                value: currentText[j],
            });
        }
    }

    return blocks;
};

watch(
    () => props.sequenceData,
    (newData) => {
        if (newData) {
            form.id = newData.id;
            form.prefix = newData.prefix || '';
            form.pattern = newData.pattern || '';
            form.padding = newData.padding || 4;
            form.clearErrors();

            patternBlocks.value = parsePattern(form.pattern);
        }
    },
    { immediate: true },
);

watch(
    patternBlocks,
    (newBlocks) => {
        form.pattern = newBlocks.map((b) => b.value).join('');
    },
    { deep: true },
);

const removeBlock = (index: number) => {
    patternBlocks.value.splice(index, 1);
};

const getBlockStyle = (element: any) => {
    if (element.type === 'var') {
        const v = availableVariables.find((v) => v.value === element.value);

        return v
            ? v.color
            : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-200 border-gray-200 dark:border-slate-700';
    }

    return 'bg-white dark:bg-slate-900 text-slate-800 border-slate-200 dark:border-slate-700 shadow-sm';
};

const getBlockLabel = (element: any) => {
    if (element.type === 'var') {
        const v = availableVariables.find((v) => v.value === element.value);

        return v ? v.label : element.value;
    }

    return element.value;
};

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.prefix) {
        form.setError('prefix', 'Prefix wajib diisi.');
        isValid = false;
    }

    if (!form.pattern) {
        form.setError('pattern', 'Pola penomoran tidak boleh kosong.');
        isValid = false;
    } else {
        if (!form.pattern.includes('{PREFIX}')) {
            form.setError('pattern', 'Pola wajib mengandung {PREFIX}.');
            isValid = false;
        }

        if (!form.pattern.includes('{AUTONUMBER}')) {
            form.setError(
                'pattern',
                (form.errors.pattern ? form.errors.pattern + ' ' : '') +
                    'Pola wajib mengandung {AUTONUMBER}.',
            );
            isValid = false;
        }
    }

    if (!isValid) {
        return;
    }

    form.put(sequence.url({ sequence: form.id }), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit('updated');
        },
    });
};
</script>

<template>
    <Modal
        :show="show"
        @close="close"
        max-width="lg"
        title="Edit Penomoran"
        description="Konfirmasi penomoran"
    >
        <template #icon>
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
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
                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                    />
                </svg>
            </div>
        </template>

        <form id="sequenceForm" @submit.prevent="submit" class="space-y-8">
            <!-- Prefix Settings -->
            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900 dark:ring-0"
            >
                <div class="mb-4">
                    <h4
                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                    >
                        Kode Awalan (Prefix)
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Kode unik yang menandakan jenis nomor ini.
                    </p>
                </div>
                <div>
                    <TextInput
                        id="prefix"
                        label="Karakter Prefix *"
                        v-model="form.prefix"
                        :error="form.errors.prefix"
                        placeholder="Contoh: PSB atau INV"
                        class="uppercase"
                    />
                </div>
            </div>

            <!-- Pattern Builder -->
            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900 dark:ring-0"
            >
                <div class="mb-4">
                    <h4
                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                    >
                        Pembangun Pola (Pattern)
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Susun pola penomoran dengan menarik blok ke area kotak
                        putus-putus.
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- Palette Variables -->
                    <div
                        class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div
                            class="mb-3 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase dark:text-slate-500"
                        >
                            Blok Data (Tarik)
                        </div>
                        <draggable
                            :list="availableVariables"
                            :group="{
                                name: 'pattern',
                                pull: 'clone',
                                put: false,
                            }"
                            item-key="value"
                            :clone="
                                (item: any) => ({
                                    id: Date.now() + Math.random(),
                                    type: 'var',
                                    value: item.value,
                                })
                            "
                            class="flex flex-wrap gap-2"
                        >
                            <template #item="{ element }">
                                <div
                                    class="cursor-move rounded-lg border px-3 py-2 text-xs font-bold transition-all select-none hover:scale-105"
                                    :class="element.color"
                                >
                                    {{ element.label }}
                                </div>
                            </template>
                        </draggable>

                        <div
                            class="mt-4 mb-3 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase dark:text-slate-500"
                        >
                            Pemisah Karakter (Tarik)
                        </div>
                        <draggable
                            :list="availableSeparators"
                            :group="{
                                name: 'pattern',
                                pull: 'clone',
                                put: false,
                            }"
                            item-key="value"
                            :clone="
                                (item: any) => ({
                                    id: Date.now() + Math.random(),
                                    type: 'separator',
                                    value: item.value,
                                })
                            "
                            class="flex flex-wrap gap-2"
                        >
                            <template #item="{ element }">
                                <div
                                    class="cursor-move rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm transition-all select-none hover:scale-105 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                >
                                    {{ element.label }}
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Drop Zone -->
                    <div
                        class="relative rounded-xl border-2 border-dashed border-gray-300 bg-white p-4 transition-colors hover:border-indigo-400 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500"
                    >
                        <draggable
                            v-model="patternBlocks"
                            group="pattern"
                            item-key="id"
                            class="flex min-h-[50px] flex-wrap items-center gap-2"
                            ghost-class="opacity-40 scale-95"
                        >
                            <template #item="{ element, index }">
                                <div
                                    class="group relative flex cursor-move items-center rounded-lg border transition-all select-none hover:ring-2 hover:ring-indigo-500/20"
                                    :class="getBlockStyle(element)"
                                >
                                    <div
                                        class="px-3 py-2 text-sm font-bold"
                                        :class="
                                            element.type === 'separator'
                                                ? 'px-4 text-lg font-black text-slate-400 dark:text-slate-300'
                                                : getBlockStyle(element).split(
                                                      ' ',
                                                  )[1]
                                        "
                                    >
                                        {{ getBlockLabel(element) }}
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeBlock(index)"
                                        class="absolute -top-2 -right-2 z-10 hidden h-5 w-5 items-center justify-center rounded-full border border-rose-200 bg-rose-100 text-rose-600 shadow-md transition-colors group-hover:flex hover:bg-rose-500 hover:text-white dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                    >
                                        <svg
                                            class="h-3 w-3"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="3"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </draggable>

                        <div
                            v-if="patternBlocks.length === 0"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center"
                        >
                            <span
                                class="text-sm font-medium text-gray-400 dark:text-slate-500"
                                >Jatuhkan blok ke area ini...</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-slate-300 shadow-inner dark:bg-slate-950"
                    >
                        <svg
                            class="h-4 w-4 shrink-0 text-emerald-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span class="text-xs font-semibold text-slate-400"
                            >HASIL AKHIR:</span
                        >
                        <code class="font-mono font-bold text-emerald-400">{{
                            form.pattern || 'Kosong'
                        }}</code>
                    </div>

                    <div
                        v-if="form.errors.pattern"
                        class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400"
                    >
                        {{ form.errors.pattern }}
                    </div>
                </div>
            </div>

            <!-- Configuration -->
            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900 dark:ring-0"
            >
                <div class="mb-4">
                    <h4
                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                    >
                        Format Angka Urut
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Panjang nol (0) di depan nomor urut untuk standarisasi
                        panjang string.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="col-span-1">
                        <TextInput
                            id="padding"
                            type="number"
                            v-model="form.padding"
                            label="Panjang Digit Padding"
                            required
                            min="1"
                            max="10"
                            :error="form.errors.padding"
                        />
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                @click="close"
                class="w-full rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 focus:ring-2 focus:ring-gray-200 sm:w-auto dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                :disabled="form.processing"
            >
                Batal
            </button>
            <PrimaryButton
                type="submit"
                form="sequenceForm"
                class="mt-3 ml-0 w-full sm:mt-0 sm:ml-3 sm:w-auto"
                :class="{ 'cursor-not-allowed opacity-50': form.processing }"
                :disabled="form.processing"
            >
                <svg
                    v-if="form.processing"
                    class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                Simpan Konfigurasi
            </PrimaryButton>
        </template>
    </Modal>
</template>
