export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_path?: string | null;
    email_verified_at: string | null;
    is_super_admin?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: string[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
