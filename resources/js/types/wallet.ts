export interface CodShipmentItem {
    id: number;
    order_number: string;
    waybill_number: string | null;
    status: string;
    recipient_name: string;
    destination_address?: string | null;
    destination_area_name: string | null;
    courier_code?: string;
    courier_service_name: string;
    item_description: string | null;
    item_value: number;
    shipping_fee: number;
    total_amount: number;
    created_at: string;
    store?: {
        id: number;
        name: string;
    };
}

export interface WithdrawalItem {
    id: number;
    reference_number: string;
    amount: number;
    status: string;
    notes?: string | null;
    created_at: string;
}

export interface PaginatedResult<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

export interface WalletData {
    id: number;
    user_id: number;
    available_balance: number;
    pending_balance: number;
    total_withdrawn: number;
}

export interface WalletFilters {
    tab?: 'cod' | 'withdrawals';
    cod_status?: string;
    cod_search?: string;
    cod_date_preset?: string;
    cod_date_from?: string;
    cod_date_to?: string;
    wd_status?: string;
    wd_search?: string;
    wd_date_preset?: string;
    wd_date_from?: string;
    wd_date_to?: string;
}

