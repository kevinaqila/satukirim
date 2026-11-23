export interface ShipmentItemEntry {
    name: string;
    description?: string | null;
    category?: string;
    qty?: number;
    weight?: number;
    value?: number;
    length?: number | null;
    width?: number | null;
    height?: number | null;
}

export interface ShipmentTrackingEvent {
    status: string;
    note: string;
    timestamp: string;
}

export interface ShipmentDetail {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string;
    destination_postal_code: string | null;
    destination_area_id: string;
    destination_area_name: string | null;
    weight: number;
    item_description: string | null;
    item_category: string | null;
    item_value: number;
    items?: ShipmentItemEntry[] | null;
    courier_code: string;
    courier_service_code: string;
    courier_service_name: string;
    shipping_fee: number;
    platform_fee?: number | null;
    total_amount: number;
    estimated_delivery: string | null;
    waybill_number: string | null;
    tracking_history: ShipmentTrackingEvent[] | null;
    payment_method: string | null;
    paid_at: string | null;
    created_at: string;
    updated_at?: string | null;
    store: {
        id: number;
        name: string;
        phone: string;
        address: string;
        area_name: string | null;
        postal_code: string | null;
    };
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
    } | null;
}

export interface ShipmentListItem {
    id: number;
    order_number: string;
    waybill_number: string | null;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string | null;
    destination_area_name: string | null;
    destination_postal_code: string | null;
    weight: number;
    courier_code: string;
    courier_service_name: string;
    total_amount: number;
    shipping_fee: number;
    payment_method: string | null;
    item_description: string | null;
    item_value: number;
    items?: ShipmentItemEntry[] | null;
    estimated_delivery?: string | null;
    created_at: string;
    store?: {
        id: number;
        name: string;
        phone?: string;
        address?: string;
        area_name?: string | null;
    } | null;
}

export interface StoreOption {
    id: number;
    name: string;
    phone: string;
    address: string;
    biteship_area_id: string;
    area_name: string | null;
    postal_code?: string | null;
    is_default: boolean;
}

export interface CreateShipmentItem {
    name: string;
    description?: string | null;
    qty: number | null;
    weight: number | null;
    value: number | null;
    length?: number | null;
    width?: number | null;
    height?: number | null;
}

export interface CourierRate {
    courier_code: string;
    courier_name: string;
    courier_service_code: string;
    courier_service_name: string;
    duration: string;
    price: number;
    type: string;
    available_for_cash_on_delivery?: boolean;
}

export interface PaginatedShipments {
    data: ShipmentListItem[];
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
