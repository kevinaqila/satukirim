export interface AdminTransactionItem {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone?: string;
    destination_address?: string;
    destination_area_name?: string | null;
    weight?: number;
    courier_code?: string;
    courier_service_name: string;
    shipping_fee: number;
    total_amount: number;
    payment_method?: string | null;
    waybill_number: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    store: {
        id: number;
        name: string;
    };
}

export interface AdminUserItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    is_active: boolean;
    stores_count: number;
    shipments_count: number;
    created_at: string;
}

export interface AdminAuditLogItem {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
    } | null;
    event: 'created' | 'updated' | 'deleted';
    auditable_type: string;
    auditable_id: number;
    auditable_label: string;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    old_formatted?: string[];
    new_formatted?: string[];
    ip_address: string | null;
    created_at: string;
}

