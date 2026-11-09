<?php

namespace App\Handlers;

use App\Models\AuditLog;

class AuditHandler
{
    /**
     * Human-friendly label translations for database attributes.
     *
     * @var array<string, string>
     */
    protected static array $attributeLabels = [
        'order_number' => 'No. Pesanan',
        'waybill_number' => 'AWB',
        'status' => 'Status',
        'courier_code' => 'Kurir',
        'courier_service_code' => 'Kode Layanan',
        'courier_service_name' => 'Layanan',
        'shipping_fee' => 'Ongkos Kirim',
        'platform_fee' => 'Biaya Layanan',
        'total_amount' => 'Total Tagihan',
        'item_value' => 'Nilai Barang',
        'item_category' => 'Kategori Barang',
        'item_description' => 'Deskripsi Barang',
        'weight' => 'Berat',
        'recipient_name' => 'Penerima',
        'recipient_phone' => 'No. HP Penerima',
        'destination_address' => 'Alamat Tujuan',
        'destination_area_name' => 'Area Tujuan',
        'destination_postal_code' => 'Kode Pos Tujuan',
        'name' => 'Nama',
        'email' => 'Email',
        'phone' => 'No. Telepon',
        'address' => 'Alamat',
        'postal_code' => 'Kode Pos',
        'area_name' => 'Kecamatan / Kota',
        'is_default' => 'Toko Utama',
        'role' => 'Peran (Role)',
        'is_active' => 'Status Akun',
        'paid_at' => 'Waktu Bayar',
        'payment_method' => 'Metode Bayar',
        'reference_number' => 'No. Referensi',
        'amount' => 'Nominal Penarikan',
        'notes' => 'Catatan',
    ];

    /**
     * Human-friendly status values.
     *
     * @var array<string, string>
     */
    protected static array $statusLabels = [
        'pending_payment' => 'Belum Bayar',
        'paid' => 'Menunggu Pickup',
        'waiting_pickup' => 'Menunggu Pickup',
        'allocated' => 'Kurir Ditugaskan',
        'picking_up' => 'Sedang Dijemput',
        'in_transit' => 'Diantar',
        'delivered' => 'Terkirim',
        'cancelled' => 'Dibatalkan',
    ];

    /**
     * Format a key-value audit array into a list of readable bullet points.
     *
     * @param array<string, mixed>|null $values
     * @return array<int, string>
     */
    public static function format(?array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $formatted = [];

        foreach ($values as $key => $value) {
            $label = self::$attributeLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
            $displayValue = self::formatValue($key, $value);
            $formatted[] = "{$label}: {$displayValue}";
        }

        return $formatted;
    }

    /**
     * Format individual attribute value.
     */
    protected static function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            if ($key === 'is_active') {
                return $value ? 'Aktif' : 'Nonaktif';
            }
            if ($key === 'is_default') {
                return $value ? 'Ya' : 'Tidak';
            }

            return $value ? 'Ya' : 'Tidak';
        }

        if ($key === 'status' && isset(self::$statusLabels[$value])) {
            return self::$statusLabels[$value];
        }

        if (in_array($key, ['shipping_fee', 'total_amount', 'platform_fee', 'item_value'], true)) {
            return 'Rp '.number_format((int) $value, 0, ',', '.');
        }

        if ($key === 'weight') {
            $grams = (int) $value;

            return $grams >= 1000
                ? ($grams / 1000).' kg'
                : "{$grams} gr";
        }

        if ($key === 'role') {
            return $value === 'super_admin' ? 'Super Admin' : 'Merchant UMKM';
        }

        if ($key === 'courier_code') {
            return match (strtolower((string) $value)) {
                'jne' => 'JNE',
                'sicepat' => 'SiCepat',
                'jnt' => 'J&T Express',
                'anteraja' => 'Anteraja',
                'ninja' => 'Ninja Xpress',
                'lion' => 'Lion Parcel',
                'pos' => 'POS Indonesia',
                'tiki' => 'TIKI',
                default => strtoupper((string) $value),
            };
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Record an audit log entry.
     *
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public static function log(
        string $event,
        string $description,
        string $auditableType,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ?AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId ?? 0,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}

