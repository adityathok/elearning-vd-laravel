export type User = {
    id: number;
    name: string;
    username: string | null;
    email: string;
    role: 'admin' | 'guru' | 'siswa';
    is_active: boolean;
    avatar: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
