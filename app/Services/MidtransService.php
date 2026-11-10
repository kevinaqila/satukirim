<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Wallet;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransService
{
    private string $serverKey;

    private bool $isProduction;

    private string $snapBaseUrl;

    private string $apiBaseUrl;

    public function __construct(?string $serverKey = null, ?bool $isProduction = null)
    {
        $this->serverKey = $serverKey ?? (string) config('services.midtrans.server_key');
        $this->isProduction = $isProduction ?? (bool) config('services.midtrans.is_production', false);
        $this->snapBaseUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
        $this->apiBaseUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Generate or retrieve Snap Token for a shipment order.
     */
    public function getSnapToken(Shipment $shipment): ?string
    {
        if ($shipment->snap_token && ! str_starts_with($shipment->snap_token, 'SNAP_FALLBACK_') && ! str_starts_with($shipment->snap_token, 'SNAP_MOCK_')) {
            return $shipment->snap_token;
        }

        if (empty($this->serverKey)) {
            Log::warning('Midtrans server key is empty, cannot generate Snap Token.');

            return null;
        }

        try {
            $itemDetails = [
                [
                    'id' => 'SHP-'.$shipment->id,
                    'price' => (int) $shipment->shipping_fee,
                    'quantity' => 1,
                    'name' => 'Ongkir: '.$shipment->courier_service_name,
                ],
            ];

            $remainingFee = (int) $shipment->total_amount - (int) $shipment->shipping_fee;
            if ($remainingFee > 0) {
                $itemDetails[] = [
                    'id' => 'FEE-'.$shipment->id,
                    'price' => $remainingFee,
                    'quantity' => 1,
                    'name' => 'Biaya Layanan Aplikasi',
                ];
            }

            $client = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->timeout(12);

            if (! app()->isProduction()) {
                $client->withoutVerifying();
            }

            $response = $client->post("{$this->snapBaseUrl}/transactions", [
                'transaction_details' => [
                    'order_id' => $shipment->order_number,
                    'gross_amount' => (int) $shipment->total_amount,
                ],
                'customer_details' => [
                    'first_name' => $shipment->user?->name ?? 'Merchant',
                    'email' => $shipment->user?->email ?? 'merchant@satukirim.id',
                    'phone' => $shipment->user?->phone ?? '081234567890',
                ],
                'item_details' => $itemDetails,
            ]);

            if ($response->successful()) {
                $token = (string) $response->json('token');
                $shipment->update(['snap_token' => $token]);

                return $token;
            }

            Log::warning('Midtrans Snap API responded with error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (Throwable $e) {
            Log::error('Midtrans Snap request exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Verify Midtrans SHA-512 signature.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        if (empty($this->serverKey)) {
            return true;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Mark shipment as paid and assign waybill.
     */
    public function markAsPaid(Shipment $shipment, string $paymentType = 'midtrans'): void
    {
        if ($shipment->status === 'paid' || in_array($shipment->status, ['allocated', 'picking_up', 'picked', 'in_transit', 'delivered'], true)) {
            return;
        }

        $biteship = app(BiteshipService::class);
        $biteshipResult = $biteship->createOrder($shipment);

        $waybill = ! empty($biteshipResult['waybill_number'])
            ? $biteshipResult['waybill_number']
            : ($shipment->waybill_number ?: 'STK'.strtoupper($shipment->courier_code).date('ymd').rand(1000, 9999));

        $biteshipOrderId = $biteshipResult['biteship_order_id'] ?? $shipment->biteship_order_id;

        $history = $shipment->tracking_history ?? [];
        $history[] = [
            'status' => 'paid',
            'note' => 'Pembayaran ongkir berhasil diverifikasi via '.$paymentType,
            'timestamp' => now()->toIso8601String(),
        ];
        $history[] = [
            'status' => 'waiting_pickup',
            'note' => 'Permintaan penjemputan paket telah dijadwalkan ke kurir '.$shipment->courier_service_name,
            'timestamp' => now()->addMinutes(2)->toIso8601String(),
        ];

        $shipment->update([
            'status' => 'paid',
            'payment_method' => $paymentType,
            'paid_at' => now(),
            'biteship_order_id' => $biteshipOrderId,
            'waybill_number' => $waybill,
            'tracking_history' => $history,
        ]);

        Wallet::syncPlatformBalances();
    }

    /**
     * Check transaction status directly with Midtrans API.
     */
    public function checkStatus(Shipment $shipment): ?string
    {
        if (empty($this->serverKey) || empty($shipment->order_number)) {
            return null;
        }

        try {
            $client = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->timeout(8);

            if (! app()->isProduction()) {
                $client->withoutVerifying();
            }

            $response = $client->get("{$this->apiBaseUrl}/{$shipment->order_number}/status");

            if ($response->successful()) {
                $data = $response->json();
                $transactionStatus = $data['transaction_status'] ?? null;
                $paymentType = $data['payment_type'] ?? 'midtrans';

                if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
                    if ($shipment->status !== 'paid') {
                        $this->markAsPaid($shipment, $paymentType);
                    }

                    return 'paid';
                }

                if (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
                    $shipment->update(['status' => 'cancelled']);

                    return 'cancelled';
                }

                return $transactionStatus;
            }
        } catch (Throwable $e) {
            Log::warning('Failed to check Midtrans status: '.$e->getMessage());
        }

        return null;
    }
}
