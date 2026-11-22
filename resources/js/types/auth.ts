export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    bank_code?: string | null;
    bank_account_number?: string | null;
    bank_account_name?: string | null;
    role?: 'super_admin' | 'umkm';
    is_active?: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
