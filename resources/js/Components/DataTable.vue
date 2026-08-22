<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps<{
    columns: { key: string; label: string }[];
    data: any[];
    selectable?: boolean;
    expandable?: boolean;
    pagination?: any;
    disableSelection?: (row: any) => boolean;
}>();

const emit = defineEmits(['search', 'limit', 'selection-change']);
const search = ref('');
const limit = ref(props.pagination?.per_page || 5);

watch(
    () => props.pagination?.per_page,
    (newPerPage) => {
        if (newPerPage) {
            limit.value = newPerPage;
        }
    },
    { immediate: true },
);
const selectedIds = ref<string[]>([]);
const expandedIds = ref<string[]>([]);
const isLimitDropdownOpen = ref(false);

const handleSearch = () => {
    emit('search', search.value);
};

const handleLimit = () => {
    emit('limit', limit.value);
};

const allSelected = computed(() => {
    return (
        props.data.length > 0 && selectedIds.value.length === props.data.length
    );
});

const someSelected = computed(() => {
    return (
        selectedIds.value.length > 0 &&
        selectedIds.value.length < props.data.length
    );
});

const toggleAll = (event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;

    if (checked) {
        selectedIds.value = props.data
            .filter(
                (item) =>
                    !props.disableSelection || !props.disableSelection(item),
            )
            .map((item) => item.id);
    } else {
        selectedIds.value = [];
    }
};

watch(
    selectedIds,
    (newVal) => {
        emit('selection-change', newVal);
    },
    { deep: true },
);

// Reset selection when data changes
watch(
    () => props.data,
    () => {
        selectedIds.value = [];
        expandedIds.value = [];
    },
);

const toggleExpand = (id: string) => {
    const index = expandedIds.value.indexOf(id);

    if (index > -1) {
        expandedIds.value.splice(index, 1);
    } else {
        expandedIds.value.push(id);
    }
};
</script>

<template>
    <div
        class="flex flex-col rounded-2xl border border-gray-100 bg-white shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900"
    >
        <!-- Header Actions -->
        <div
            class="relative z-20 flex flex-col items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white p-5 lg:flex-row dark:border-slate-800 dark:bg-slate-900"
        >
            <!-- Left: Search & Limit -->
            <div class="flex w-full flex-row items-center gap-3 lg:w-auto">
                <!-- Search -->
                <div class="group relative flex-1 sm:w-64 sm:flex-none lg:w-72">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 transition-colors group-focus-within:text-primary dark:text-slate-500 dark:group-focus-within:text-blue-400"
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
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <input
                        type="text"
                        v-model="search"
                        @input="handleSearch"
                        placeholder="Cari data..."
                        class="block w-full appearance-none rounded-xl border border-neutral-warm/20 bg-surface/50 py-2.5 pr-3 pl-10 text-sm font-medium text-primary-dark placeholder-neutral-warm/50 transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-500 dark:focus:bg-slate-800 dark:focus:ring-blue-500/20"
                    />
                </div>

                <!-- Limit -->
                <div class="relative flex w-auto shrink-0 items-center">
                    <button
                        type="button"
                        @click="isLimitDropdownOpen = !isLimitDropdownOpen"
                        class="group flex w-full min-w-18 items-center justify-between rounded-xl border border-neutral-warm/20 bg-surface/50 px-3 py-2.5 text-sm font-bold text-primary-dark shadow-sm transition-all duration-300 hover:bg-white focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-blue-500/20"
                    >
                        <span>{{ limit }}</span>
                        <svg
                            class="ml-2 h-4 w-4 transform text-gray-400 transition-transform duration-300 group-hover:text-primary dark:text-slate-400 dark:text-slate-500 dark:group-hover:text-blue-400"
                            :class="isLimitDropdownOpen ? '-rotate-180' : ''"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>

                    <!-- Full Screen Dropdown Overlay -->
                    <div
                        v-if="isLimitDropdownOpen"
                        class="fixed inset-0 z-40"
                        @click="isLimitDropdownOpen = false"
                    ></div>

                    <!-- Dropdown Menu -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="transform opacity-0 scale-95 -translate-y-2"
                        enter-to-class="transform opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="transform opacity-100 scale-100 translate-y-0"
                        leave-to-class="transform opacity-0 scale-95 -translate-y-2"
                    >
                        <div
                            v-if="isLimitDropdownOpen"
                            class="absolute top-full right-0 z-50 mt-2 w-32 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl ring-1 ring-black/5 focus:outline-none sm:right-auto sm:left-0 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900 dark:ring-white/5"
                        >
                            <div class="px-2 py-2">
                                <p
                                    class="mb-2 px-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Per Halaman
                                </p>
                                <button
                                    v-for="option in [5, 10, 25, 50, 100]"
                                    :key="option"
                                    @click="
                                        limit = option;
                                        handleLimit();
                                        isLimitDropdownOpen = false;
                                    "
                                    type="button"
                                    class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold transition-colors last:mb-0"
                                    :class="
                                        limit === option
                                            ? 'bg-primary/10 text-primary dark:bg-primary/25 dark:text-blue-300'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:bg-slate-800 dark:text-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                    "
                                >
                                    {{ option }}
                                    <svg
                                        v-if="limit === option"
                                        class="h-4 w-4 text-primary dark:text-blue-400"
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
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Right: Bulk Actions, Filters -->
            <div
                class="mt-2 flex w-full flex-wrap items-center justify-end gap-3 lg:mt-0 lg:w-auto"
            >
                <slot name="bulk-actions" :selectedIds="selectedIds"></slot>
                <slot name="filters"></slot>
                <slot name="actions"></slot>
            </div>
        </div>

        <!-- Table Container -->
        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead
                    class="sticky top-0 z-10 border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                >
                    <tr>
                        <th v-if="selectable" class="w-4 px-6 py-4">
                            <div class="flex items-center">
                                <Checkbox
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleAll"
                                />
                            </div>
                        </th>
                        <th v-if="expandable" class="w-4 px-4 py-4"></th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-4 font-bold tracking-wider"
                        >
                            {{ col.label }}
                        </th>
                        <th
                            class="px-6 py-4 text-right font-bold tracking-wider"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <template v-for="(row, i) in data" :key="i">
                        <tr
                            class="group bg-white transition-colors hover:bg-gray-50 dark:bg-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/50 dark:hover:bg-slate-800/80"
                            :class="{
                                'bg-primary/5 hover:bg-primary/10 dark:bg-primary/15 dark:hover:bg-primary/25 dark:hover:bg-slate-700/50':
                                    selectedIds.includes(row.id),
                            }"
                        >
                            <td
                                v-if="selectable"
                                class="px-6 py-4 whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center opacity-70 transition-opacity group-hover:opacity-100"
                                    :class="{
                                        'opacity-100': selectedIds.includes(
                                            row.id,
                                        ),
                                    }"
                                >
                                    <Checkbox
                                        v-if="
                                            !disableSelection ||
                                            !disableSelection(row)
                                        "
                                        :value="row.id"
                                        v-model:checked="selectedIds"
                                    />
                                    <div
                                        v-else
                                        class="h-5 w-5 cursor-not-allowed rounded-full border-2 border-gray-200 bg-gray-50 opacity-50 dark:border-slate-700 dark:bg-slate-800"
                                    ></div>
                                </div>
                            </td>
                            <td
                                v-if="expandable"
                                class="px-4 py-4 whitespace-nowrap"
                            >
                                <button
                                    type="button"
                                    @click="toggleExpand(row.id)"
                                    class="rounded-full p-1 text-gray-400 transition-colors hover:bg-primary/10 hover:text-primary dark:text-slate-400 dark:text-slate-500 dark:hover:bg-slate-700/50 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                                >
                                    <svg
                                        class="h-5 w-5 transition-transform duration-200"
                                        :class="{
                                            'rotate-90': expandedIds.includes(
                                                row.id,
                                            ),
                                        }"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </button>
                            </td>
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-slate-200"
                            >
                                <slot :name="'cell-' + col.key" :row="row">
                                    {{ row[col.key] }}
                                </slot>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <slot name="row-actions" :row="row"></slot>
                            </td>
                        </tr>
                        <!-- Expanded Row -->
                        <tr
                            v-if="expandable && expandedIds.includes(row.id)"
                            class="bg-gray-50 dark:bg-slate-800/50"
                        >
                            <td
                                :colspan="
                                    columns.length +
                                    (selectable ? 1 : 0) +
                                    (expandable ? 1 : 0) +
                                    1
                                "
                                class="border-b border-gray-100 px-0 py-0 dark:border-slate-800"
                            >
                                <div class="overflow-hidden p-6">
                                    <slot name="expanded-row" :row="row">
                                        <slot name="expanded" :row="row"></slot>
                                    </slot>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="data.length === 0">
                        <td
                            :colspan="columns.length + (selectable ? 2 : 1)"
                            class="px-6 py-12"
                        >
                            <div
                                class="flex flex-col items-center justify-center text-gray-500 dark:text-slate-400"
                            >
                                <svg
                                    class="mb-4 h-12 w-12 text-gray-300 dark:text-slate-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                    />
                                </svg>
                                <span class="text-sm font-medium"
                                    >Tidak ada data yang ditemukan.</span
                                >
                                <p
                                    class="mt-1 text-xs text-gray-400 dark:text-slate-500"
                                >
                                    Coba ubah filter atau kata kunci pencarian
                                    Anda.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="$slots.pagination"
            class="mt-auto rounded-b-2xl border-t border-gray-100 bg-gray-50 p-4 dark:border-slate-800 dark:bg-slate-800/50"
        >
            <slot name="pagination"></slot>
        </div>
        <div
            v-else-if="pagination && pagination.links"
            class="mt-auto flex flex-col items-center justify-between gap-4 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-4 sm:flex-row dark:border-slate-800 dark:bg-slate-800/50"
        >
            <div class="text-sm text-gray-500 dark:text-slate-400">
                Menampilkan
                <span class="font-semibold text-gray-900 dark:text-slate-100">{{
                    pagination.from || 0
                }}</span>
                sampai
                <span class="font-semibold text-gray-900 dark:text-slate-100">{{
                    pagination.to || 0
                }}</span>
                dari
                <span class="font-semibold text-gray-900 dark:text-slate-100">{{
                    pagination.total
                }}</span>
                entri
            </div>
            <div class="flex gap-1">
                <Component
                    :is="link.onClick ? 'button' : link.url ? Link : 'span'"
                    v-for="(link, index) in pagination.links"
                    :key="index"
                    :href="link.onClick ? undefined : link.url"
                    type="button"
                    @click="link.onClick ? link.onClick() : null"
                    class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                    :class="[
                        link.active
                            ? 'border-primary bg-primary text-white dark:border-primary dark:bg-primary'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:bg-slate-800',
                        !link.url && !link.onClick
                            ? 'cursor-not-allowed bg-gray-50 opacity-50 dark:bg-slate-800'
                            : 'cursor-pointer',
                    ]"
                >
                    <span
                        v-html="
                            link.label
                                .replace(
                                    /Previous|Next|Sebelumnya|Selanjutnya|Berikutnya|Kembali/gi,
                                    '',
                                )
                                .trim() || link.label
                        "
                    ></span>
                </Component>
            </div>
        </div>
    </div>
</template>
