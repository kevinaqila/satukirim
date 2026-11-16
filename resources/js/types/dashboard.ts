export interface DashboardShipmentItem {
    id: number;
    order_number: string;
    recipient_name: string;
    recipient_phone?: string;
    destination_area_name?: string | null;
    destination_address?: string;
    weight?: number;
    courier_code: string;
    courier_service_code: string;
    courier_service_name: string;
    total_amount: number;
    status: string;
    waybill_number?: string | null;
    created_at: string;
    store?: {
        id: number;
        name: string;
        phone: string;
    } | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

export interface DashboardMetrics {
    total?: number;
    unpaid: number;
    waiting_pickup: number;
    in_transit: number;
    delivered: number;
    total_volume?: number;
}

export interface SevenDayTrendItem {
    date: string;
    day: string;
    count: number;
}

export interface TopDestinationItem {
    city: string;
    full: string;
    count: number;
}

export interface CourierPerformanceItem {
    code: string;
    name: string;
    count: number;
    delivered_count: number;
    active_count: number;
    avg_fee: number;
    total_fee: number;
    delivery_rate: number;
    share_percentage: number;
}

export interface ServiceBreakdownItem {
    key: string;
    label: string;
    desc: string;
    count: number;
    total_spend: number;
    avg_spend: number;
    percentage: number;
}

export interface StorePerformanceItem {
    id: number;
    name: string;
    phone: string;
    address: string;
    area_name: string | null;
    postal_code: string | null;
    is_default: boolean;
    shipments_count: number;
    delivered_count: number;
    in_transit_count: number;
    total_spend: number;
    avg_weight: number;
    share_percentage: number;
}

export interface LiveTrackingFeedItem {
    id: number;
    order_number: string;
    waybill_number: string | null;
    status: string;
    recipient_name: string;
    destination_area_name: string | null;
    courier_code: string;
    courier_service_name: string;
    note: string;
    time: string;
}

export interface WalletStats {
    available_balance: number;
    pending_balance: number;
    total_withdrawn: number;
}

export interface DefaultStoreInfo {
    id: number;
    name: string;
    area_name: string | null;
    postal_code?: string | null;
    biteship_area_id: string;
}

export interface PlatformStats {
    total_users: number;
    total_umkm?: number;
    active_umkm: number;
    total_stores?: number;
    total_shipments?: number;
    paid_shipments?: number;
    cancelled_shipments?: number;
    delivered_rate?: number;
    platform_revenue?: number;
    total_volume: number;
    total_audit_logs?: number;
    non_cod_count?: number;
    non_cod_volume?: number;
    cod_count?: number;
    cod_volume?: number;
    pending_withdrawals_count?: number;
    pending_withdrawals_amount?: number;
    completed_withdrawals_count?: number;
    completed_withdrawals_amount?: number;
}

export interface AuditLogItem {
    id: number;
    user: { name: string; role: string } | null;
    event: 'created' | 'updated' | 'deleted';
    auditable_type: string;
    auditable_id: number;
    auditable_label: string;
    formatted_changes?: string[];
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    ip_address: string | null;
    created_at: string;
}

export interface UmkmListItem {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    stores_count: number;
    shipments_count: number;
    total_spent: number;
    created_at: string;
}

export function formatRupiah(val: number | undefined): string {
    return Number(val || 0).toLocaleString('id-ID');
}

export function formatRelativeTime(dateStr: string): string {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return '';
    }
}

