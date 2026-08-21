<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search, X } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                class="px-4 py-10 text-center text-muted-foreground"
                                colspan="5"
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
</template>
