<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\NotificationLog;
use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected string $session;

    protected mixed $enabled;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?string $session = null,
        mixed $enabled = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.whatsapp.base_url', 'http://localhost:3000'), '/');
        $this->apiKey = $apiKey ?? config('services.whatsapp.api_key');
        $this->session = (string) ($session ?? config('services.whatsapp.session', 'default'));
        $this->enabled = $enabled ?? config('services.whatsapp.enabled');
    }

    /**
     * Determine whether WhatsApp gateway notifications are enabled and configured.
     */
    public function isEnabled(): bool
    {
        if ($this->enabled === false || $this->enabled === 'false' || $this->enabled === '0' || $this->enabled === 0) {
            return false;
        }

        return filled($this->baseUrl) && filled($this->apiKey) && filled($this->session);
    }

    /**
     * Normalize an Indonesian phone number to 628xxx format.
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Format a phone number into a WhatsApp gateway chatId.
     */
    public function toChatId(?string $phone): ?string
    {
        $normalized = $this->normalizePhoneNumber($phone);

        return $normalized !== null ? "{$normalized}@c.us" : null;
    }

    /**
     * Send a plain text WhatsApp message through the gateway API.
     *
     * @return array{success: bool, message: string, data: array<string, mixed>|null}
     */
    public function sendText(string $phone, string $message): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Integrasi WhatsApp belum aktif atau belum dikonfigurasi.',
                'data' => null,
            ];
        }

        $chatId = $this->toChatId($phone);
        if ($chatId === null || blank($message)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tujuan atau isi pesan tidak valid.',
                'data' => null,
            ];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Api-Key' => (string) $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/sendText", [
                    'session' => $this->session,
                    'chatId' => $chatId,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim.',
                    'data' => $response->json(),
                ];
            }

            Log::warning('WhatsApp sendText failed', [
                'status' => $response->status(),
                'chat_id' => $chatId,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengirim pesan WhatsApp (HTTP '.$response->status().').',
                'data' => $response->json(),
            ];
        } catch (Throwable $e) {
            Log::warning('WhatsApp sendText exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server WhatsApp: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Dispatch a WhatsApp notification onto the queue, optionally logged and deduplicated.
     */
    public function dispatchMessage(
        ?string $phone,
        string $message,
        ?string $dedupKey = null,
        ?int $shipmentId = null,
        ?string $type = null,
    ): void {
        if (! $this->isEnabled() || $this->normalizePhoneNumber($phone) === null || blank($message)) {
            return;
        }

        if ($dedupKey !== null && ! Cache::add($dedupKey, true, now()->addDays(7))) {
            return;
        }

        if ($shipmentId !== null && $type !== null && $this->hasExistingNotification($shipmentId, $type)) {
            return;
        }

        $logId = $this->createNotificationLog($shipmentId, (string) $phone, $type, $message);

        try {
            SendWhatsAppMessageJob::dispatch((string) $phone, $message, $logId);
        } catch (Throwable $e) {
            if ($dedupKey !== null) {
                Cache::forget($dedupKey);
            }

            if ($logId !== null) {
                NotificationLog::whereKey($logId)->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            Log::warning('Failed dispatching WhatsApp notification job', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check whether a persisted shipment already has a pending or sent notification for this milestone.
     */
    protected function hasExistingNotification(int $shipmentId, string $type): bool
    {
        try {
            return NotificationLog::query()
                ->where('shipment_id', $shipmentId)
                ->where('type', $type)
                ->whereIn('status', ['pending', 'sent'])
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Persist an initial pending notification log entry for persisted shipments.
     */
    protected function createNotificationLog(?int $shipmentId, string $phone, ?string $type, string $message): ?int
    {
        if ($shipmentId === null) {
            return null;
        }

        try {
            $log = NotificationLog::create([
                'shipment_id' => $shipmentId,
                'recipient_phone' => $this->normalizePhoneNumber($phone) ?? $phone,
                'channel' => 'whatsapp',
                'type' => $type,
                'message' => $message,
                'status' => 'pending',
            ]);

            return $log->id;
        } catch (Throwable $e) {
            Log::warning('Failed creating notification log record', [
                'shipment_id' => $shipmentId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build a deterministic cache key for deduplicating shipment milestone notifications.
     */
    protected function shipmentDedupKey(Shipment $shipment, string $milestone): ?string
    {
        $identifier = $shipment->getKey() ?? ($shipment->order_number ?: null);

        return $identifier !== null ? "whatsapp:shipment:{$identifier}:{$milestone}" : null;
    }

    /**
     * Build a deep-link URL to prefill and display public tracking on the landing page.
     */
    protected function trackingUrl(Shipment $shipment): string
    {
        $waybill = $shipment->waybill_number ?: $shipment->order_number;

        return url('/').'?'.http_build_query([
            'tab' => 'resi',
            'courier' => strtolower(trim($shipment->courier_code)),
            'waybill' => $waybill,
        ]);
    }

    /**
     * Notify recipient when shipment is ready for pickup and waybill is issued.
     */
    public function notifyShipmentReady(Shipment $shipment): void
    {
        $shipment->loadMissing('store');
        $storeName = trim((string) ($shipment->store?->name ?? ''));
        $storePhrase = $storeName !== '' ? " dari *{$storeName}*" : '';
        $waybill = $shipment->waybill_number ?: $shipment->order_number;

        $lines = [
            "Halo *{$shipment->recipient_name}* 👋",
            '',
            "Pesanan Anda{$storePhrase} sedang diproses untuk dikirim melalui *{$shipment->courier_service_name}*.",
            '',
            "📦 Kode Order: *{$shipment->order_number}*",
            "🧾 No. Resi (AWB): *{$waybill}*",
        ];

        if ($shipment->payment_method === 'cod') {
            $codTotal = (int) $shipment->total_amount + (int) $shipment->item_value;
            $lines[] = '💵 Tagihan COD saat paket tiba: *Rp '.number_format($codTotal, 0, ',', '.').'*';
        }

        $lines[] = '';
        $lines[] = 'Lacak posisi & riwayat paket di: '.$this->trackingUrl($shipment);

        $this->dispatchMessage(
            $shipment->recipient_phone,
            implode("\n", $lines),
            $this->shipmentDedupKey($shipment, 'ready'),
            $shipment->getKey(),
            'ready',
        );
    }

    /**
     * Notify recipient when courier updates key shipment tracking milestones.
     */
    public function notifyShipmentStatusUpdated(Shipment $shipment, string $status): void
    {
        $codReminder = '';
        if ($shipment->payment_method === 'cod') {
            $codTotal = (int) $shipment->total_amount + (int) $shipment->item_value;
            $codReminder = ' beserta pembayaran COD sebesar *Rp '.number_format($codTotal, 0, ',', '.').'*';
        }

        $statusMessages = [
            'picking_up' => 'Kurir sedang menjemput paket Anda dari lokasi penjual.',
            'in_transit' => 'Paket Anda sedang dalam perjalanan menuju alamat tujuan.',
            'dropping_off' => "Kurir sedang mengantar paket menuju alamat Anda. Mohon siapkan penerimaan paket{$codReminder}.",
            'delivered' => 'Paket Anda telah diterima di alamat tujuan. Terima kasih telah berbelanja!',
            'on_hold' => 'Pengiriman paket sedang ditahan sementara oleh pihak ekspedisi karena kendala pengantaran. Silakan pantau status resi atau hubungi penjual.',
            'cancelled' => 'Pengiriman paket ini telah dibatalkan. Silakan hubungi penjual untuk informasi lebih lanjut.',
            'rejected' => 'Pengiriman paket ditolak/gagal diproses oleh pihak ekspedisi. Silakan hubungi penjual untuk informasi lebih lanjut.',
            'courier_not_found' => 'Kurir belum ditemukan di area penjemputan sehingga pesanan dibatalkan otomatis oleh ekspedisi. Silakan hubungi penjual untuk informasi lebih lanjut.',
            'returned' => 'Paket dalam proses pengembalian ke pengirim oleh ekspedisi.',
            'disposed' => 'Paket dinyatakan dimusnahkan oleh pihak ekspedisi karena kendala pengiriman/retur yang melewati batas waktu. Silakan hubungi penjual untuk informasi lebih lanjut.',
        ];

        if (! isset($statusMessages[$status])) {
            return;
        }

        $shipment->loadMissing('store');
        $storeName = trim((string) ($shipment->store?->name ?? ''));
        $storePhrase = $storeName !== '' ? " dari *{$storeName}*" : '';
        $waybill = $shipment->waybill_number ?: $shipment->order_number;

        $lines = [
            "Halo *{$shipment->recipient_name}* 👋",
            '',
            "Update pengiriman pesanan{$storePhrase} (*{$shipment->order_number}*):",
            $statusMessages[$status],
            '',
            "🚚 Ekspedisi: {$shipment->courier_service_name}",
            "🧾 No. Resi: *{$waybill}*",
            '',
            'Lacak riwayat lengkap di: '.$this->trackingUrl($shipment),
        ];

        $type = "status:{$status}";

        $this->dispatchMessage(
            $shipment->recipient_phone,
            implode("\n", $lines),
            $this->shipmentDedupKey($shipment, $type),
            $shipment->getKey(),
            $type,
        );
    }
}
