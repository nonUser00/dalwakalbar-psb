<script setup lang="ts">
defineOptions({ layout: AdminLayout });
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index } from '@/routes/admin/pengaturan/role-permission';
import {
    store,
    update,
    destroy,
} from '@/routes/admin/pengaturan/role-permission/role';

interface Permission {
    id: string;
    name: string;
    label: string;
}

interface Role {
    id: string;
    name: string;
    permissions: Permission[];
}

const props = defineProps<{
    roles: any;
    filters: Record<string, string>;
    groupedPermissions: Record<string, Permission[]>;
}>();

const columns = [
    { key: 'role', label: 'ROLE' },
    { key: 'guard_name', label: 'GUARD' },
    { key: 'permissions', label: 'PERMISSIONS' },
    { key: 'users_count', label: 'USERS' },
    { key: 'created_at', label: 'DIBUAT' },
];

const showRoleModal = ref(false);
const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);
const isEditing = ref(false);

const form = useForm({
    id: '',
    name: '',
    permissions: [] as string[],
});

const handleSearch = (search: string) => {
    router.get(index.url(), { search }, { preserveState: true, replace: true });
};

const handleLimit = (limit: number) => {
    router.get(index.url(), { limit }, { preserveState: true, replace: true });
};

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.id = '';
    form.name = '';
    form.permissions = [];
    permissionSearch.value = '';
    showRoleModal.value = true;
};

const openEditModal = (role: Role) => {
    isEditing.value = true;
    form.id = role.id;
    form.name = role.name;
    form.permissions = role.permissions.map((p) => p.name);
    form.clearErrors();
    permissionSearch.value = '';
    showRoleModal.value = true;
};

const openDeleteModal = (role: Role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const submitRole = () => {
    if (isEditing.value) {
        form.put(update.url(form.id), {
            onSuccess: () => {
                showRoleModal.value = false;
            },
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => {
                showRoleModal.value = false;
            },
        });
    }
};

const deleteRole = () => {
    if (!roleToDelete.value) {
        return;
    }

    router.delete(destroy.url(roleToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            roleToDelete.value = null;
        },
    });
};

const permissionSearch = ref('');
const filteredGroupedPermissions = computed(() => {
    if (!permissionSearch.value) {
        return props.groupedPermissions;
    }

    const search = permissionSearch.value.toLowerCase();
    const filtered: Record<string, Permission[]> = {};

    for (const [module, permissions] of Object.entries(
        props.groupedPermissions,
    )) {
        if (module.toLowerCase().includes(search)) {
            filtered[module] = permissions;
        } else {
            const matchedPerms = permissions.filter(
                (p) =>
                    p.name.toLowerCase().includes(search) ||
                    p.label.toLowerCase().includes(search),
            );

            if (matchedPerms.length > 0) {
                filtered[module] = matchedPerms;
            }
        }
    }

    return filtered;
});

const selectAllPermissions = () => {
    const permsToAdd: string[] = [];
    Object.values(filteredGroupedPermissions.value).forEach((perms) => {
        perms.forEach((p) => {
            if (!form.permissions.includes(p.name)) {
                permsToAdd.push(p.name);
            }
        });
    });
    form.permissions.push(...permsToAdd);
};

const clearAllPermissions = () => {
    const permsToRemove: string[] = [];
    Object.values(filteredGroupedPermissions.value).forEach((perms) => {
        perms.forEach((p) => {
            permsToRemove.push(p.name);
        });
    });
    form.permissions = form.permissions.filter(
        (p) => !permsToRemove.includes(p),
    );
};

const toggleModule = (moduleName: string, permissions: Permission[]) => {
    const isFullySelected = isModuleFullySelected(permissions);
    toggleModulePermissions(moduleName, permissions, !isFullySelected);
};

const toggleModulePermissions = (
    moduleName: string,
    permissions: Permission[],
    checked: boolean,
) => {
    const modulePermissionNames = permissions.map((p) => p.name);

    if (checked) {
        // Add all module permissions
        modulePermissionNames.forEach((name) => {
            if (!form.permissions.includes(name)) {
                form.permissions.push(name);
            }
        });
    } else {
        // Remove all module permissions
        form.permissions = form.permissions.filter(
            (name) => !modulePermissionNames.includes(name),
        );
    }
};

const isModuleFullySelected = (permissions: Permission[]) => {
    if (permissions.length === 0) {
        return false;
    }

    return permissions.every((p) => form.permissions.includes(p.name));
};
</script>

<template>
    <div class="w-full">
        <Head title="Manajemen Role & Permission" />

        <!-- Page Header & Actions -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Manajemen Role & Permission
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Kelola role dan hak akses pengguna di sistem.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <PrimaryButton @click="openAddModal">
                    <svg
                        class="mr-2 h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Tambah Role
                </PrimaryButton>
            </div>
        </div>

        <div class="mt-6">
            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="roles.data"
                :pagination="roles"
                :selectable="false"
                :expandable="false"
                @search="handleSearch"
                @limit="handleLimit"
            >
                <!-- Cells -->
                <template #cell-role="{ row }">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-100 text-lg font-bold text-blue-700 shadow-xs dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-[15px] font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ row.name }}
                        </p>
                        <span
                            v-if="row.name.toLowerCase() === 'super admin'"
                            class="ml-2 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700 dark:bg-rose-950/50 dark:text-rose-300"
                            >Protected</span
                        >
                    </div>
                </template>

                <template #cell-guard_name="{ row }">
                    <span
                        class="rounded-full border border-gray-200 bg-gray-100 px-3 py-1 text-[13px] font-semibold text-gray-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        {{ row.guard_name }}
                    </span>
                </template>

                <template #cell-permissions="{ row }">
                    <span
                        class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-[13px] font-bold text-primary dark:bg-blue-950/50 dark:text-blue-400"
                    >
                        <svg
                            class="mr-1.5 h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                            />
                        </svg>
                        {{ row.permissions.length }} permissions
                    </span>
                </template>

                <template #cell-users_count="{ row }">
                    <div
                        class="flex items-center text-[13px] font-medium text-slate-700 dark:text-slate-300"
                    >
                        <svg
                            class="mr-2 h-4 w-4 text-gray-400 dark:text-slate-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        {{ row.users_count || 0 }}
                    </div>
                </template>

                <template #cell-created_at="{ row }">
                    <span
                        class="text-[13px] font-medium text-slate-600 dark:text-slate-400"
                    >
                        {{
                            new Date(row.created_at).toLocaleDateString(
                                'en-GB',
                                {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric',
                                },
                            )
                        }}
                    </span>
                </template>

                <!-- Row Actions -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <ActionMenu>
                            <template #trigger>
                                <button
                                    class="rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                    title="Opsi"
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
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                        />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <button
                                    @click="openEditModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:bg-slate-800"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-amber-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    v-if="
                                        row.name.toLowerCase() !==
                                            'super admin' &&
                                        row.name.toLowerCase() !== 'pendaftar'
                                    "
                                    @click="openDeleteModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/30"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-rose-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Hapus
                                </button>
                            </template>
                        </ActionMenu>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Role Modal Form -->
        <Modal
            :show="showRoleModal"
            @close="showRoleModal = false"
            maxWidth="lg"
            :title="isEditing ? 'Edit Role' : 'Tambah Role'"
            :description="isEditing ? 'Perbarui data role' : 'Tambah role baru'"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 dark:bg-blue-950/50"
                >
                    <svg
                        v-if="isEditing"
                        class="h-6 w-6 text-primary dark:text-blue-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-6 w-6 text-primary dark:text-blue-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            </template>
            <form @submit.prevent="submitRole" id="roleForm" class="space-y-6">
                <div>
                    <TextInput
                        v-model="form.name"
                        label="Nama Role"
                        placeholder="Contoh: Panitia Pendaftaran"
                        :error="form.errors.name"
                        :disabled="
                            isEditing &&
                            (form.name.toLowerCase() === 'super admin' ||
                                form.name.toLowerCase() === 'pendaftar')
                        "
                    />
                </div>

                <div>
                    <!-- Header -->
                    <div
                        class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <label
                            class="block text-base font-bold text-gray-800 dark:text-slate-200"
                            >Permissions</label
                        >
                        <div
                            class="flex items-center space-x-3 text-[13px] font-medium"
                        >
                            <button
                                type="button"
                                @click="selectAllPermissions"
                                class="text-primary transition-colors hover:text-primary/80 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                Pilih Semua
                            </button>
                            <span class="text-gray-300 dark:text-slate-700"
                                >|</span
                            >
                            <button
                                type="button"
                                @click="clearAllPermissions"
                                class="text-gray-500 transition-colors hover:text-gray-700 dark:text-slate-200 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Hapus Semua
                            </button>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="mb-4">
                        <TextInput
                            v-model="permissionSearch"
                            placeholder="Cari modul atau permission..."
                        />
                    </div>

                    <div
                        v-if="form.errors.permissions"
                        class="mb-4 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
                    >
                        {{ form.errors.permissions }}
                    </div>

                    <!-- Permissions container -->
                    <div
                        class="custom-scrollbar max-h-[35vh] overflow-y-auto rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div
                            v-if="
                                Object.keys(filteredGroupedPermissions)
                                    .length === 0
                            "
                            class="p-6 text-center text-sm text-gray-500 dark:text-slate-400"
                        >
                            Tidak ada permission yang cocok dengan pencarian.
                        </div>

                        <div
                            v-for="(
                                permissions, module
                            ) in filteredGroupedPermissions"
                            :key="module"
                            class="border-b border-gray-100 p-4 pb-2 last:border-b-0 dark:border-slate-800"
                        >
                            <div class="mb-4 flex items-center">
                                <button
                                    type="button"
                                    @click="toggleModule(module, permissions)"
                                    class="group flex items-center text-left focus:outline-none"
                                >
                                    <h4
                                        class="text-[15px] font-bold text-gray-800 capitalize transition-colors group-hover:text-primary dark:text-slate-200 dark:group-hover:text-blue-400"
                                    >
                                        {{ module }}
                                    </h4>
                                    <span
                                        class="ml-2 text-[13px] font-medium text-gray-400 transition-colors group-hover:text-primary/70 dark:text-slate-500 dark:group-hover:text-blue-400/80"
                                        >({{ permissions.length }})</span
                                    >
                                </button>
                            </div>

                            <div
                                class="mb-2 grid grid-cols-1 gap-x-4 gap-y-3.5 pl-2 sm:grid-cols-2"
                            >
                                <label
                                    v-for="perm in permissions"
                                    :key="perm.id"
                                    class="group flex min-w-0 cursor-pointer items-start"
                                >
                                    <Checkbox
                                        :value="perm.name"
                                        v-model:checked="form.permissions"
                                        class="mt-0.5 shrink-0"
                                    />
                                    <span
                                        class="ml-2.5 flex-1 text-sm leading-tight font-medium break-all text-gray-700 transition-colors group-hover:text-gray-900 dark:text-slate-100 dark:text-slate-200 dark:text-slate-300 dark:group-hover:text-slate-100"
                                        >{{ perm.name }}</span
                                    >
                                </label>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-3 px-1 text-[13px] font-medium text-gray-500 dark:text-slate-400"
                    >
                        {{ form.permissions.length }} permission dipilih
                    </div>
                </div>
            </form>

            <template #footer>
                <SecondaryButton
                    @click="showRoleModal = false"
                    class="w-full justify-center sm:w-auto"
                    >Batal
                </SecondaryButton>
                <PrimaryButton
                    form="roleForm"
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="w-full justify-center sm:w-auto"
                >
                    {{ isEditing ? 'Simpan Data' : 'Simpan Role' }}
                </PrimaryButton>
            </template>
        </Modal>

        <!-- Delete Modal -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            maxWidth="sm"
            title="Hapus Role?"
            description="Konfirmasi hapus"
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50"
                >
                    <svg
                        class="h-6 w-6 text-rose-500"
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
                </div>
            </template>

            <div
                class="p-5 text-sm leading-relaxed text-gray-600 dark:text-slate-300"
            >
                Apakah Anda yakin ingin menghapus role
                <strong class="text-gray-900 dark:text-slate-100"
                    >"{{ roleToDelete?.name }}"</strong
                >? Tindakan ini tidak dapat dibatalkan.
            </div>

            <template #footer>
                <SecondaryButton
                    @click="showDeleteModal = false"
                    class="w-full justify-center sm:w-auto"
                    >Batal
                </SecondaryButton>
                <DangerButton
                    @click="deleteRole"
                    class="w-full justify-center sm:w-auto"
                    >Ya, Hapus</DangerButton
                >
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-track {
    background: #1e293b;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
</style>
