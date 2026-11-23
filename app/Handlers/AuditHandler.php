<?php

namespace App\Handlers;

use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Throwable;

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
        'biteship_order_id' => 'ID Order Biteship',
        'tracking_history' => 'Riwayat Pengiriman',
        'items' => 'Rincian Barang',
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
        'confirmed' => 'Terkonfirmasi',
        'waiting_pickup' => 'Menunggu Pickup',
        'allocated' => 'Kurir Ditugaskan',
        'picking_up' => 'Sedang Dijemput',
        'picked' => 'Paket Diambil',
        'in_transit' => 'Diantar',
        'dropping_off' => 'Sedang Diantar',
        'delivered' => 'Paket Diterima',
        'cancelled' => 'Dibatalkan',
        'on_hold' => 'Ditahan',
        'rejected' => 'Ditolak',
        'courier_not_found' => 'Kurir Tidak Ditemukan',
        'returned' => 'Dikembalikan',
        'disposed' => 'Dimusnahkan',
    ];

    /**
     * Format a key-value audit array into a list of readable bullet points.
     *
     * @param  array<string, mixed>|null  $values
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
            if ($key === 'tracking_history') {
                return self::formatTrackingHistory($value);
            }

            if ($key === 'items') {
                return self::formatItems($value);
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '-';
        }

        return (string) $value;
    }

    /**
     * Format tracking history checkpoints into a concise readable summary.
     *
     * @param  array<int|string, mixed>  $history
     */
    protected static function formatTrackingHistory(array $history): string
    {
        if (empty($history)) {
            return '-';
        }

        $entries = array_values(array_filter($history, fn ($entry): bool => is_array($entry)));
        if (empty($entries)) {
            return '-';
        }

        $latest = $entries[count($entries) - 1];
        $statusKey = strtolower((string) ($latest['status'] ?? ''));
        $statusLabel = $statusKey !== ''
            ? (self::$statusLabels[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey)))
            : '';
        $note = trim((string) ($latest['note'] ?? ''));
        $timestampRaw = trim((string) ($latest['timestamp'] ?? ''));

        $formattedTime = '';
        if ($timestampRaw !== '') {
            try {
                $formattedTime = Carbon::parse($timestampRaw)->timezone('Asia/Jakarta')->format('d/m/Y H:i');
            } catch (Throwable) {
                $formattedTime = $timestampRaw;
            }
        }

        $parts = array_filter([$statusLabel, $note], fn (string $part): bool => $part !== '');
        $summary = implode(' — ', $parts);

        if ($formattedTime !== '') {
            $summary = $summary !== '' ? "{$summary} ({$formattedTime})" : $formattedTime;
        }

        if (count($entries) > 1) {
            return '['.count($entries)." riwayat] {$summary}";
        }

        return $summary !== '' ? $summary : '-';
    }

    /**
     * Format shipment items list into a concise readable string.
     *
     * @param  array<int|string, mixed>  $items
     */
    protected static function formatItems(array $items): string
    {
        $labels = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $qty = (int) ($item['qty'] ?? 1);
            $labels[] = $qty > 1 ? "{$name} ({$qty}x)" : $name;
        }

        return ! empty($labels) ? implode(', ', $labels) : '-';
    }

    /**
     * Record an audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
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
