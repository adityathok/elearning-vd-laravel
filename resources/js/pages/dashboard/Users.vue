<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';

type UserRole = 'admin' | 'guru' | 'siswa';
type RoleFilterValue = UserRole | 'all';

type DashboardUser = {
    id: number;
    name: string;
    username: string | null;
    email: string;
    role: UserRole;
    is_active: boolean;
    avatar: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: DashboardUser[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

type RoleOption = {
    label: string;
    value: UserRole;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        role: UserRole | null;
        q: string | null;
    };
    roles: RoleOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Users',
                href: '/dashboard/users',
            },
        ],
    },
});

const { getInitials } = useInitials();
const page = usePage();

const isUserModalOpen = ref(false);
const editingUser = ref<DashboardUser | null>(null);

const userForm = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    avatar: null as File | null,
});

const isEditing = computed(() => editingUser.value !== null);
const modalTitle = computed(() =>
    isEditing.value ? 'Edit user' : 'Tambah admin',
);
const modalDescription = computed(() =>
    isEditing.value
        ? 'Update data user yang dipilih.'
        : 'User baru dari halaman ini otomatis dibuat sebagai admin.',
);

const searchQuery = computed(() => props.filters.q ?? '');

const dashboardUsersUrl = (
    params: { role?: UserRole | null; q?: string | null } = {},
) => {
    const searchParams = new URLSearchParams();

    if (params.role) {
        searchParams.set('role', params.role);
    }

    const q = params.q?.trim();

    if (q) {
        searchParams.set('q', q);
    }

    const query = searchParams.toString();

    return query ? `/dashboard/users?${query}` : '/dashboard/users';
};

const roleFilterOptions = computed(() => [
    {
        label: 'All',
        value: 'all' as RoleFilterValue,
    },
    ...props.roles.map((role) => ({
        label: role.label,
        value: role.value,
    })),
]);

const selectedRoleFilter = computed<RoleFilterValue>(
    () => props.filters.role ?? 'all',
);
const currentUserId = computed(() => page.props.auth.user.id);

const clearSearchHref = computed(() =>
    dashboardUsersUrl({ role: props.filters.role }),
);

const previousLink = computed(() => props.users.links[0] ?? null);
const nextLink = computed(
    () => props.users.links[props.users.links.length - 1] ?? null,
);

const usersSummary = computed(() => {
    if (props.users.total === 0) {
        return 'Tidak ada user';
    }

    return `${props.users.total} user terdaftar`;
});

const roleBadgeClass = (role: UserRole) => {
    if (role === 'admin') {
        return 'border-[#1a1a1a] bg-[#1a1a1a] text-white';
    }

    if (role === 'guru') {
        return 'border-[#c9e0fc] bg-[#c9e0fc] text-[#1a1a1a]';
    }

    return 'border-[#c2c2c2] bg-white text-[#1a1a1a]';
};

const statusBadgeClass = (isActive: boolean) => {
    if (isActive) {
        return 'border-[#356373] bg-white text-[#356373]';
    }

    return 'border-[#b3262b] bg-[#f9d4d2] text-[#5a1313]';
};

const updateRoleFilter = (value: unknown) => {
    const role = String(value) as RoleFilterValue;

    if (role === selectedRoleFilter.value) {
        return;
    }

    router.visit(
        dashboardUsersUrl({
            role: role === 'all' ? null : role,
            q: props.filters.q,
        }),
        {
            preserveScroll: true,
        },
    );
};

const canDeleteUser = (user: DashboardUser) =>
    user.role === 'admin' && user.id !== currentUserId.value;

const deleteUser = (user: DashboardUser) => {
    if (!canDeleteUser(user)) {
        return;
    }

    const confirmed = window.confirm(`Hapus admin ${user.name}?`);

    if (!confirmed) {
        return;
    }

    router.delete(`/dashboard/users/${user.id}`, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    editingUser.value = null;
    userForm.clearErrors();
    userForm.reset();
    userForm.is_active = true;
    isUserModalOpen.value = true;
};

const openEditModal = (user: DashboardUser) => {
    editingUser.value = user;
    userForm.clearErrors();
    userForm.name = user.name;
    userForm.username = user.username ?? '';
    userForm.email = user.email;
    userForm.password = '';
    userForm.password_confirmation = '';
    userForm.is_active = user.is_active;
    userForm.avatar = null;
    isUserModalOpen.value = true;
};

const closeUserModal = () => {
    isUserModalOpen.value = false;
    editingUser.value = null;
    userForm.clearErrors();
    userForm.reset();
};

const setAvatarFile = (event: Event) => {
    const input = event.target as HTMLInputElement;

    userForm.avatar = input.files?.[0] ?? null;
};

const submitUserForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeUserModal,
        forceFormData: true,
    };

    if (editingUser.value) {
        userForm
            .transform((data) => ({
                ...data,
                _method: 'patch',
            }))
            .post(`/dashboard/users/${editingUser.value.id}`, options);

        return;
    }

    userForm.transform((data) => data).post('/dashboard/users', options);
};
</script>

<template>
    <Head title="Users" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-white p-4 font-['Forma_DJR_Micro',Arial,sans-serif] text-[#1a1a1a] md:p-6"
    >
        <section
            class="flex flex-col justify-end p-4 md:flex-row md:justify-between md:p-6"
        >
            <div>
                <p class="mt-3 text-base leading-[1.38] text-[#3d3d3d]">
                    Kelola admin, guru, dan siswa dari satu tampilan yang mudah
                    dipindai.
                </p>
                <p class="mt-4 text-sm leading-normal text-[#636363]">
                    {{ usersSummary }}
                </p>
            </div>
            <Button
                type="button"
                class="h-11 rounded-lg bg-[#024ad8] px-6 text-sm font-semibold tracking-[0.7px] text-white uppercase shadow-none hover:bg-[#0e3191]"
                @click="openCreateModal"
            >
                <Plus />
                Tambah
            </Button>
        </section>

        <section class="grid gap-4 rounded-2xl bg-[#f7f7f7] p-4 md:p-6">
            <div
                class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
            >
                <div class="grid gap-2 sm:w-56">
                    <Select
                        :model-value="selectedRoleFilter"
                        @update:model-value="updateRoleFilter"
                    >
                        <SelectTrigger
                            class="h-11 w-full rounded-[4px] border-[#c2c2c2] bg-white px-4 text-sm font-medium text-[#1a1a1a] shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                        >
                            <SelectValue placeholder="Pilih role" />
                        </SelectTrigger>
                        <SelectContent
                            class="rounded-lg border-[#e8e8e8] bg-white text-[#1a1a1a] shadow-[0_8px_24px_rgba(26,26,26,0.12)]"
                        >
                            <SelectItem
                                v-for="filter in roleFilterOptions"
                                :key="filter.value"
                                :value="filter.value"
                                class="rounded-[4px] text-sm text-[#1a1a1a] focus:bg-[#f7f7f7] focus:text-[#1a1a1a]"
                            >
                                {{ filter.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <form
                    class="flex w-full flex-col gap-3 xl:max-w-2xl xl:flex-row"
                    method="get"
                    action="/dashboard/users"
                >
                    <input
                        v-if="filters.role"
                        type="hidden"
                        name="role"
                        :value="filters.role"
                    />
                    <div class="relative flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-[#636363]"
                        />
                        <Input
                            class="h-11 rounded-[4px] border-[#c2c2c2] bg-white pl-11 text-base text-[#1a1a1a] shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0 md:text-sm"
                            type="search"
                            name="q"
                            :default-value="searchQuery"
                            placeholder="Search by name or email"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button
                            type="submit"
                            class="h-11 rounded-[4px] bg-[#1a1a1a] px-6 text-sm font-semibold tracking-[0.7px] text-white uppercase shadow-none hover:bg-[#000000]"
                        >
                            Search
                        </Button>
                        <Button
                            v-if="filters.q"
                            as-child
                            variant="outline"
                            type="button"
                            class="h-11 rounded-[4px] border-[#1a1a1a] bg-white px-5 text-sm font-semibold tracking-[0.7px] text-[#1a1a1a] uppercase shadow-none hover:bg-white"
                        >
                            <Link :href="clearSearchHref" preserve-scroll>
                                <X />
                                Clear
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </section>

        <div
            class="overflow-hidden rounded-2xl border border-[#e8e8e8] bg-white shadow-[0_2px_8px_rgba(26,26,26,0.08)]"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm text-[#1a1a1a]">
                    <thead
                        class="border-b border-[#e8e8e8] bg-[#f7f7f7] text-left text-xs text-[#636363] uppercase"
                    >
                        <tr>
                            <th
                                class="px-5 py-4 font-semibold tracking-[0.7px]"
                            >
                                User
                            </th>
                            <th
                                class="px-5 py-4 font-semibold tracking-[0.7px]"
                            >
                                Username
                            </th>
                            <th
                                class="px-5 py-4 font-semibold tracking-[0.7px]"
                            >
                                Role
                            </th>
                            <th
                                class="px-5 py-4 font-semibold tracking-[0.7px]"
                            >
                                Status
                            </th>
                            <th
                                class="px-5 py-4 font-semibold tracking-[0.7px]"
                            >
                                Created
                            </th>
                            <th
                                class="px-5 py-4 text-right font-semibold tracking-[0.7px]"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e8e8e8]">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="bg-white"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        class="size-11 rounded-2xl border border-[#e8e8e8] bg-[#f7f7f7]"
                                    >
                                        <AvatarImage
                                            v-if="user.avatar"
                                            :src="user.avatar"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback
                                            class="rounded-2xl bg-[#f7f7f7] text-sm font-medium text-[#1a1a1a]"
                                        >
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-base leading-[1.38] font-medium text-[#1a1a1a]"
                                        >
                                            {{ user.name }}
                                        </div>
                                        <div
                                            class="truncate text-sm leading-[1.5] text-[#636363]"
                                        >
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[#636363]">
                                {{ user.username ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <Badge
                                    variant="outline"
                                    class="rounded-lg px-3 py-1 text-sm font-medium capitalize"
                                    :class="roleBadgeClass(user.role)"
                                >
                                    {{ user.role }}
                                </Badge>
                            </td>
                            <td class="px-5 py-4">
                                <Badge
                                    variant="outline"
                                    class="rounded-lg px-3 py-1 text-sm font-medium"
                                    :class="statusBadgeClass(user.is_active)"
                                >
                                    {{ user.is_active ? 'active' : 'inactive' }}
                                </Badge>
                            </td>
                            <td class="px-5 py-4 text-[#636363]">
                                {{
                                    user.created_at
                                        ? new Date(
                                              user.created_at,
                                          ).toLocaleDateString()
                                        : '-'
                                }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="h-10 rounded-[4px] border-[#1a1a1a] bg-white px-4 text-xs font-bold tracking-[0.126px] text-[#1a1a1a] uppercase shadow-none hover:bg-white"
                                        @click="openEditModal(user)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        v-if="canDeleteUser(user)"
                                        type="button"
                                        variant="outline"
                                        class="h-10 rounded-[4px] border-[#b3262b] bg-white px-4 text-xs font-bold tracking-[0.126px] text-[#b3262b] uppercase shadow-none hover:bg-white"
                                        @click="deleteUser(user)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                class="px-5 py-12 text-center text-[#636363]"
                                colspan="6"
                            >
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm leading-[1.5] text-[#636363]">
                Showing {{ users.from ?? 0 }} to {{ users.to ?? 0 }} of
                {{ users.total }}
            </p>

            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="outline"
                    class="h-10 rounded-[4px] border-[#1a1a1a] bg-white px-4 text-xs font-bold tracking-[0.126px] text-[#1a1a1a] uppercase shadow-none hover:bg-white"
                    :disabled="!previousLink?.url"
                >
                    <Link
                        :href="previousLink?.url ?? '#'"
                        preserve-scroll
                        :class="{
                            'pointer-events-none opacity-50':
                                !previousLink?.url,
                        }"
                    >
                        <ChevronLeft />
                        Previous
                    </Link>
                </Button>

                <span class="px-2 text-sm leading-[1.5] text-[#636363]">
                    Page {{ users.current_page }} of {{ users.last_page }}
                </span>

                <Button
                    as-child
                    variant="outline"
                    class="h-10 rounded-[4px] border-[#1a1a1a] bg-white px-4 text-xs font-bold tracking-[0.126px] text-[#1a1a1a] uppercase shadow-none hover:bg-white"
                    :disabled="!nextLink?.url"
                >
                    <Link
                        :href="nextLink?.url ?? '#'"
                        preserve-scroll
                        :class="{
                            'pointer-events-none opacity-50': !nextLink?.url,
                        }"
                    >
                        Next
                        <ChevronRight />
                    </Link>
                </Button>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isUserModalOpen">
        <DialogContent
            class="rounded-2xl border-[#e8e8e8] bg-white p-6 text-[#1a1a1a] shadow-[0_8px_24px_rgba(26,26,26,0.12)] sm:max-w-2xl"
        >
            <DialogHeader>
                <DialogTitle
                    class="text-2xl leading-[1.17] font-medium tracking-normal text-[#1a1a1a]"
                >
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription class="text-sm leading-[1.5] text-[#636363]">
                    {{ modalDescription }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-5" @submit.prevent="submitUserForm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="user-name">Name</Label>
                        <Input
                            id="user-name"
                            v-model="userForm.name"
                            class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                            type="text"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError :message="userForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-username">Username</Label>
                        <Input
                            id="user-username"
                            v-model="userForm.username"
                            class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                            type="text"
                            required
                            autocomplete="username"
                            placeholder="username"
                        />
                        <InputError :message="userForm.errors.username" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="user-email">Email</Label>
                    <Input
                        id="user-email"
                        v-model="userForm.email"
                        class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="userForm.errors.email" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="user-password">
                            Password
                            <span
                                v-if="isEditing"
                                class="text-muted-foreground"
                            >
                                optional
                            </span>
                        </Label>
                        <Input
                            id="user-password"
                            v-model="userForm.password"
                            class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                            type="password"
                            :required="!isEditing"
                            autocomplete="new-password"
                            placeholder="Password"
                        />
                        <InputError :message="userForm.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-password-confirmation">
                            Confirm password
                        </Label>
                        <Input
                            id="user-password-confirmation"
                            v-model="userForm.password_confirmation"
                            class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                            type="password"
                            :required="!isEditing"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                        />
                        <InputError
                            :message="userForm.errors.password_confirmation"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="user-avatar">Avatar</Label>
                    <Input
                        id="user-avatar"
                        class="h-11 rounded-[4px] border-[#c2c2c2] bg-white shadow-none file:text-[#1a1a1a] focus-visible:border-[#1a1a1a] focus-visible:ring-0"
                        type="file"
                        accept="image/*"
                        @change="setAvatarFile"
                    />
                    <p class="text-xs leading-[1.33] text-[#636363]">
                        Upload gambar maksimal 500KB.
                    </p>
                    <p
                        v-if="isEditing && editingUser?.avatar"
                        class="text-xs leading-[1.33] text-[#636363]"
                    >
                        Avatar saat ini tetap dipakai jika tidak upload gambar
                        baru.
                    </p>
                    <InputError :message="userForm.errors.avatar" />
                </div>

                <label class="flex items-center gap-2 text-sm text-[#1a1a1a]">
                    <input
                        v-model="userForm.is_active"
                        class="size-4 rounded-[4px] border-[#c2c2c2] accent-[#024ad8]"
                        type="checkbox"
                    />
                    Active
                </label>
                <InputError :message="userForm.errors.is_active" />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 rounded-[4px] border-[#1a1a1a] bg-white px-6 text-sm font-semibold tracking-[0.7px] text-[#1a1a1a] uppercase shadow-none hover:bg-white"
                        :disabled="userForm.processing"
                        @click="closeUserModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="h-11 rounded-[4px] bg-[#024ad8] px-6 text-sm font-semibold tracking-[0.7px] text-white uppercase shadow-none hover:bg-[#0e3191]"
                        :disabled="userForm.processing"
                    >
                        {{ isEditing ? 'Save' : 'Tambah admin' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
