<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Pencil, Plus, Search, X } from '@lucide/vue';
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
import { useInitials } from '@/composables/useInitials';

type UserRole = 'admin' | 'guru' | 'siswa';

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

const isUserModalOpen = ref(false);
const editingUser = ref<DashboardUser | null>(null);

const userForm = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    avatar: '',
});

const isEditing = computed(() => editingUser.value !== null);
const modalTitle = computed(() => (isEditing.value ? 'Edit user' : 'Tambah admin'));
const modalDescription = computed(() =>
    isEditing.value
        ? 'Update data user yang dipilih.'
        : 'User baru dari halaman ini otomatis dibuat sebagai admin.',
);

const searchQuery = computed(() => props.filters.q ?? '');

const dashboardUsersUrl = (params: { role?: UserRole | null; q?: string | null } = {}) => {
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

const roleFilters = computed(() => [
    {
        label: 'All',
        value: null,
        href: dashboardUsersUrl({ q: props.filters.q }),
        active: props.filters.role === null,
    },
    ...props.roles.map((role) => ({
        label: role.label,
        value: role.value,
        href: dashboardUsersUrl({ role: role.value, q: props.filters.q }),
        active: props.filters.role === role.value,
    })),
]);

const clearSearchHref = computed(() =>
    dashboardUsersUrl({ role: props.filters.role }),
);

const previousLink = computed(() => props.users.links[0] ?? null);
const nextLink = computed(
    () => props.users.links[props.users.links.length - 1] ?? null,
);

const roleVariant = (role: UserRole) => {
    if (role === 'admin') {
        return 'default';
    }

    if (role === 'guru') {
        return 'secondary';
    }

    return 'outline';
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
    userForm.avatar = user.avatar ?? '';
    isUserModalOpen.value = true;
};

const closeUserModal = () => {
    isUserModalOpen.value = false;
    editingUser.value = null;
    userForm.clearErrors();
    userForm.reset();
};

const submitUserForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeUserModal,
    };

    userForm.transform((data) => ({
        ...data,
        avatar: data.avatar.trim() || null,
    }));

    if (editingUser.value) {
        userForm.patch(`/dashboard/users/${editingUser.value.id}`, options);

        return;
    }

    userForm.post('/dashboard/users', options);
};
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">Users</h1>
                <p class="text-sm text-muted-foreground">
                    {{ users.total }} users
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button type="button" @click="openCreateModal">
                    <Plus />
                    Tambah
                </Button>
                <Button
                    v-for="filter in roleFilters"
                    :key="filter.label"
                    as-child
                    :variant="filter.active ? 'default' : 'outline'"
                    size="sm"
                >
                    <Link :href="filter.href" preserve-scroll>
                        {{ filter.label }}
                    </Link>
                </Button>
            </div>
        </div>

        <form
            class="flex flex-col gap-2 sm:max-w-xl sm:flex-row"
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
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    class="pl-9"
                    type="search"
                    name="q"
                    :default-value="searchQuery"
                    placeholder="Search by name or email"
                />
            </div>
            <div class="flex gap-2">
                <Button type="submit">Search</Button>
                <Button
                    v-if="filters.q"
                    as-child
                    variant="outline"
                    type="button"
                >
                    <Link :href="clearSearchHref" preserve-scroll>
                        <X />
                        Clear
                    </Link>
                </Button>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">User</th>
                            <th class="px-4 py-3 font-medium">Username</th>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="bg-background"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <Avatar class="size-9 rounded-lg">
                                        <AvatarImage
                                            v-if="user.avatar"
                                            :src="user.avatar"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback class="rounded-lg">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">
                                            {{ user.name }}
                                        </div>
                                        <div class="truncate text-xs text-muted-foreground">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.username ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="roleVariant(user.role)">
                                    {{ user.role }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.is_active ? 'outline' : 'destructive'">
                                    {{ user.is_active ? 'active' : 'inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.created_at ? new Date(user.created_at).toLocaleDateString() : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="openEditModal(user)"
                                >
                                    <Pencil />
                                    Edit
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                class="px-4 py-10 text-center text-muted-foreground"
                                colspan="6"
                            >
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted-foreground">
                Showing {{ users.from ?? 0 }} to {{ users.to ?? 0 }} of
                {{ users.total }}
            </p>

            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!previousLink?.url"
                >
                    <Link
                        :href="previousLink?.url ?? '#'"
                        preserve-scroll
                        :class="{ 'pointer-events-none opacity-50': !previousLink?.url }"
                    >
                        <ChevronLeft />
                        Previous
                    </Link>
                </Button>

                <span class="text-sm text-muted-foreground">
                    Page {{ users.current_page }} of {{ users.last_page }}
                </span>

                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!nextLink?.url"
                >
                    <Link
                        :href="nextLink?.url ?? '#'"
                        preserve-scroll
                        :class="{ 'pointer-events-none opacity-50': !nextLink?.url }"
                    >
                        Next
                        <ChevronRight />
                    </Link>
                </Button>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isUserModalOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ modalTitle }}</DialogTitle>
                <DialogDescription>
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
                            <span v-if="isEditing" class="text-muted-foreground">
                                optional
                            </span>
                        </Label>
                        <Input
                            id="user-password"
                            v-model="userForm.password"
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
                            type="password"
                            :required="!isEditing"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                        />
                        <InputError :message="userForm.errors.password_confirmation" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="user-avatar">Avatar URL/path</Label>
                    <Input
                        id="user-avatar"
                        v-model="userForm.avatar"
                        type="text"
                        placeholder="avatars/admin.png"
                    />
                    <InputError :message="userForm.errors.avatar" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="userForm.is_active"
                        class="size-4 rounded border-input"
                        type="checkbox"
                    />
                    Active
                </label>
                <InputError :message="userForm.errors.is_active" />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="userForm.processing"
                        @click="closeUserModal"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="userForm.processing">
                        {{ isEditing ? 'Save' : 'Tambah admin' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
