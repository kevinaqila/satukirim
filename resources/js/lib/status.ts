export type StatusVariant = 'warning' | 'info' | 'primary' | 'success' | 'danger' | 'neutral';

export interface StatusConfig {
    label: string;
    variant: StatusVariant;
    dotClass: string;
    badgeClass: string;
    textClass: string;
}

export const STATUS_CONFIG: Record<string, StatusConfig> = {
    pending_payment: {
        label: 'Belum Bayar',
        variant: 'warning',
        dotClass: 'bg-yellow-500',
        badgeClass: 'bg-yellow-50 text-yellow-800 border-yellow-200/80',
        textClass: 'text-yellow-600',
    },
    paid: {
        label: 'Menunggu Pickup',
        variant: 'info',
        dotClass: 'bg-blue-500',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200/80',
        textClass: 'text-blue-700',
    },
    confirmed: {
        label: 'Terkonfirmasi',
        variant: 'info',
        dotClass: 'bg-blue-500',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200/80',
        textClass: 'text-blue-700',
    },
    waiting_pickup: {
        label: 'Menunggu Pickup',
        variant: 'info',
        dotClass: 'bg-blue-500',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200/80',
        textClass: 'text-blue-700',
    },
    allocated: {
        label: 'Kurir Ditugaskan',
        variant: 'info',
        dotClass: 'bg-blue-500',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200/80',
        textClass: 'text-blue-700',
    },
    picking_up: {
        label: 'Dalam Penjemputan',
        variant: 'info',
        dotClass: 'bg-indigo-500',
        badgeClass: 'bg-indigo-50 text-indigo-800 border-indigo-200/80',
        textClass: 'text-indigo-700',
    },
    picked: {
        label: 'Paket Diambil',
        variant: 'primary',
        dotClass: 'bg-[#853953]',
        badgeClass: 'bg-rose-50 text-[#853953] border-rose-200/80',
        textClass: 'text-[#853953]',
    },
    in_transit: {
        label: 'Dalam Perjalanan',
        variant: 'primary',
        dotClass: 'bg-[#853953]',
        badgeClass: 'bg-rose-50 text-[#853953] border-rose-200/80',
        textClass: 'text-[#853953]',
    },
    dropping_off: {
        label: 'Sedang Diantar',
        variant: 'primary',
        dotClass: 'bg-purple-600',
        badgeClass: 'bg-purple-50 text-purple-800 border-purple-200/80',
        textClass: 'text-purple-700',
    },
    delivered: {
        label: 'Terkirim',
        variant: 'success',
        dotClass: 'bg-emerald-600',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        textClass: 'text-emerald-700',
    },
    cancelled: {
        label: 'Dibatalkan',
        variant: 'danger',
        dotClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-800 border-red-200/80',
        textClass: 'text-red-700',
    },
    on_hold: {
        label: 'Ditahan',
        variant: 'warning',
        dotClass: 'bg-amber-500',
        badgeClass: 'bg-amber-50 text-amber-800 border-amber-200/80',
        textClass: 'text-amber-700',
    },
    rejected: {
        label: 'Ditolak',
        variant: 'danger',
        dotClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-800 border-red-200/80',
        textClass: 'text-red-700',
    },
    courier_not_found: {
        label: 'Kurir Tidak Ditemukan',
        variant: 'danger',
        dotClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-800 border-red-200/80',
        textClass: 'text-red-700',
    },
    returned: {
        label: 'Dikembalikan (Retur)',
        variant: 'danger',
        dotClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-800 border-red-200/80',
        textClass: 'text-red-700',
    },
};

export function getShipmentStatus(status: string | undefined | null): StatusConfig {
    const key = (status || '').toLowerCase().trim();
    return (
        STATUS_CONFIG[key] || {
            label: status || 'Unknown',
            variant: 'neutral',
            dotClass: 'bg-stone-500',
            badgeClass: 'bg-stone-100 text-stone-700 border-stone-200',
            textClass: 'text-stone-700',
        }
    );
}

const COURIER_LOGOS: Record<string, string> = {
    jne: '/images/couriers/jne.svg',
    jnt: '/images/couriers/jnt.svg',
    sicepat: '/images/couriers/sicepat.svg',
    anteraja: '/images/couriers/anteraja.svg',
    ninja: '/images/couriers/ninjaxpress.svg',
    ninjaxpress: '/images/couriers/ninjaxpress.svg',
    lion: '/images/couriers/lionparcel.svg',
    lionparcel: '/images/couriers/lionparcel.svg',
    pos: '/images/couriers/posindonesia.svg',
    posindonesia: '/images/couriers/posindonesia.svg',
    idexpress: '/images/couriers/idexpress.svg',
    ide: '/images/couriers/idexpress.svg',
    tiki: '/images/couriers/tiki.svg',
    wahana: '/images/couriers/wahana.svg',
    sap: '/images/couriers/sapexpress.svg',
    sapexpress: '/images/couriers/sapexpress.svg',
};

export function getCourierLogo(codeOrName: string | undefined | null): string | null {
    if (!codeOrName) return null;
    const raw = codeOrName.toLowerCase();
    const clean = raw.replace(/[^a-z0-9]/g, '');
    if (COURIER_LOGOS[clean]) return COURIER_LOGOS[clean];
    if (COURIER_LOGOS[raw]) return COURIER_LOGOS[raw];
    if (raw.includes('j&t') || raw.includes('jnt')) return COURIER_LOGOS.jnt;
    if (raw.includes('jne')) return COURIER_LOGOS.jne;
    if (raw.includes('sicepat')) return COURIER_LOGOS.sicepat;
    if (raw.includes('anteraja')) return COURIER_LOGOS.anteraja;
    if (raw.includes('ninja')) return COURIER_LOGOS.ninjaxpress;
    if (raw.includes('lion')) return COURIER_LOGOS.lionparcel;
    if (raw.includes('pos')) return COURIER_LOGOS.posindonesia;
    if (raw.includes('id express') || raw.includes('idexpress')) return COURIER_LOGOS.idexpress;
    if (raw.includes('tiki')) return COURIER_LOGOS.tiki;
    if (raw.includes('wahana')) return COURIER_LOGOS.wahana;
    if (raw.includes('sap')) return COURIER_LOGOS.sapexpress;
    return null;
}

const PROVINCE_NAMES = new Set([
    'jawa barat',
    'jawa timur',
    'jawa tengah',
    'dki jakarta',
    'banten',
    'd.i. yogyakarta',
    'di yogyakarta',
    'daerah istimewa yogyakarta',
    'sumatera utara',
    'sumatera selatan',
    'sumatera barat',
    'bali',
    'sulawesi selatan',
    'sulsel',
    'kepulauan riau',
    'riau',
    'lampung',
    'kalimantan timur',
    'kalimantan selatan',
    'kalimantan barat',
    'ntb',
    'nusa tenggara barat',
    'ntt',
    'nusa tenggara timur',
]);

/**
 * Extracts a concise "Kecamatan, Kota" string from Biteship's area_name
 * (e.g. "Menteng, Menteng, Jakarta Pusat" -> "Menteng, Jakarta Pusat",
 *  "Sosromenduran, Gedong Tengen, Kota Yogyakarta" -> "Gedong Tengen, Yogyakarta")
 */
export function formatShortDestination(areaName?: string | null, fallbackAddress?: string | null): string {
    const raw = (areaName || fallbackAddress || '').trim();
    if (!raw) return '-';

    // Strip trailing postal code like ". 60118"
    const withoutPostal = raw.replace(/\.\s*\d{5}$/, '').trim();
    const parts = withoutPostal
        .split(',')
        .map((p) => p.trim())
        .filter(Boolean);

    if (parts.length === 0) return raw;

    // Deduplicate identical segments (case-insensitive, e.g. "Menteng, Menteng")
    const deduped: string[] = [];
    for (const part of parts) {
        if (deduped.length === 0 || deduped[deduped.length - 1].toLowerCase() !== part.toLowerCase()) {
            deduped.push(part);
        }
    }

    // Remove trailing province segment if present
    if (deduped.length > 1 && PROVINCE_NAMES.has(deduped[deduped.length - 1].toLowerCase())) {
        deduped.pop();
    }

    // Take at most the last 2 segments (Kecamatan, Kota)
    const shortParts = deduped.slice(-2);
    return shortParts.join(', ');
}

export function excerptText(text?: string | null, maxLength = 22): string {
    if (!text) return '-';
    const clean = text.trim();
    if (clean.length <= maxLength) return clean;
    return `${clean.slice(0, maxLength).trimEnd()}…`;
}

export function formatDateTime(dateStr?: string | null): string {
    if (!dateStr || !dateStr.trim()) return '-';
    let d = new Date(dateStr);
    if (isNaN(d.getTime()) && dateStr.includes(' ')) {
        d = new Date(dateStr.replace(' ', 'T'));
    }
    if (isNaN(d.getTime())) return '-';

    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
}

export function formatRelativeTime(dateStr?: string | null): string {
    if (!dateStr || !dateStr.trim()) return '-';
    let d = new Date(dateStr);
    if (isNaN(d.getTime()) && dateStr.includes(' ')) {
        d = new Date(dateStr.replace(' ', 'T'));
    }
    if (isNaN(d.getTime())) return '-';

    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} mnt lalu`;
    if (diffHour < 24) return `${diffHour} jam lalu`;
    if (diffDay < 7) return `${diffDay} hr lalu`;

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}

export function formatTrackingNote(note?: string | null): string {
    if (!note || !note.trim()) return '-';
    const text = note.trim();
    const lower = text.toLowerCase();

    // 1. Order confirmed / courier notified
    if (lower.includes('courier order is confirmed') || lower.includes('has been notified to pick up')) {
        const pickupMatch = text.match(/pickup number:\s*([^\s]+)/i);
        const pickupStr = pickupMatch ? ` (Kode Pickup: ${pickupMatch[1]})` : '';
        const courierMatch = text.match(/confirmed\.\s*([a-zA-Z0-9_\-]+)\s+has been notified/i);
        const courierName = courierMatch ? courierMatch[1].toUpperCase() : 'Kurir';
        return `Order terkonfirmasi di ekspedisi. ${courierName} telah dinotifikasi untuk jadwal penjemputan${pickupStr}.`;
    }

    // 2. Allocated
    if (lower.includes('courier is allocated and ready to pick up') || lower.includes('courier is allocated')) {
        return 'Kurir telah ditugaskan dan bersiap menuju lokasi penjemputan.';
    }

    // 3. Picking up
    if (lower.includes('on the way to pick up') || lower.includes('heading to pick up')) {
        return 'Kurir sedang dalam perjalanan menuju lokasi penjemputan (toko).';
    }

    // 4. Picked
    if (lower.includes('has been picked by courier') || lower.includes('item picked up') || lower.includes('item has been picked')) {
        return 'Paket telah berhasil di-pickup oleh kurir.';
    }

    // 5. In transit to destination / hub
    if (lower.includes('on the way to destination') || lower.includes('in transit to')) {
        return 'Paket sedang dalam perjalanan menuju kota/alamat tujuan.';
    }

    // 6. Dropping off / Out for delivery
    if (lower.includes('dropping off item to destination') || lower.includes('out for delivery') || lower.includes('dropping off')) {
        return 'Kurir sedang dalam perjalanan mengantar paket ke alamat penerima.';
    }

    // 7. Delivered
    if (lower.includes('delivered to') || lower.includes('item has been delivered') || lower.includes('package delivered')) {
        const toMatch = text.match(/delivered to\s+([^,.]+)/i);
        if (toMatch) {
            return `Paket telah berhasil diterima oleh ${toMatch[1].trim()}.`;
        }
        return 'Paket telah berhasil diterima oleh penerima.';
    }

    // 8. On hold
    if (lower.includes('on hold') || lower.includes('package is on hold')) {
        return 'Paket sementara ditahan di hub operasional kurir.';
    }

    // 9. Rejected / Cancelled / Courier not found / Returned
    if (lower.includes('courier not found') || lower.includes('courier is not found')) {
        return 'Kurir tidak ditemukan di sekitar area penjemputan.';
    }
    if (lower.includes('cancelled') || lower.includes('canceled')) {
        return 'Pengiriman dibatalkan.';
    }
    if (lower.includes('rejected')) {
        return 'Pengiriman ditolak oleh pihak ekspedisi.';
    }
    if (lower.includes('returned') || lower.includes('return')) {
        return 'Paket sedang dalam proses pengembalian ke pengirim (retur).';
    }

    return text;
}

export function formatWeight(grams?: number | null): string {
    if (!grams || grams <= 0) return '-';
    if (grams < 1000) {
        return `${grams} gr`;
    }
    const kg = grams / 1000;
    return `${Number.isInteger(kg) ? kg : kg.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg`;
}

