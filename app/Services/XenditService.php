<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Wallet;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class XenditService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.xendit.key');
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.xendit.base_url', 'https://api.xendit.co'), '/');
    }

    /**
     * Build an authenticated HTTP client for Xendit API requests.
     */
    private function client(): PendingRequest
    {
        $client = Http::withBasicAuth($this->apiKey, '')
            ->acceptJson()
            ->timeout(15);

        if (! App::isProduction()) {
            $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Check if the API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Create a disbursement using Xendit Disbursements API.
     *
     * @return array{success: bool, status: string, id: ?string, message: string, raw: ?array}
     */
    public function createDisbursement(
        string $externalId,
        string $bankCode,
        string $accountHolderName,
        string $accountNumber,
        int $amount,
        ?string $description = null
    ): array {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'FAILED',
                'id' => null,
                'message' => 'Kunci API Xendit belum dikonfigurasi di sistem.',
                'raw' => null,
            ];
        }

        $payload = [
            'external_id' => $externalId,
            'bank_code' => strtoupper(trim($bankCode)),
            'account_holder_name' => trim($accountHolderName),
            'account_number' => trim($accountNumber),
            'description' => $description ?? "Tarik Saldo {$externalId}",
            'amount' => $amount,
        ];

        try {
            $response = $this->client()
                ->withHeaders([
                    'X-IDEMPOTENCY-KEY' => $externalId,
                ])
                ->post("{$this->baseUrl}/disbursements", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $status = strtoupper($data['status'] ?? 'PENDING');

                return [
                    'success' => in_array($status, ['PENDING', 'COMPLETED', 'SUCCESS']),
                    'status' => $status,
                    'id' => $data['id'] ?? null,
                    'message' => 'Permintaan penarikan dana berhasil dikirim ke Xendit.',
                    'raw' => $data,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Gagal memproses penarikan ke Xendit (HTTP '.$response->status().').';

            Log::warning('Xendit disbursement failed', [
                'external_id' => $externalId,
                'status' => $response->status(),
                'response' => $errorData,
            ]);

            return [
                'success' => false,
                'status' => 'FAILED',
                'id' => null,
                'message' => $errorMessage,
                'raw' => $errorData,
            ];
        } catch (Throwable $e) {
            Log::error('Xendit disbursement exception', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'FAILED',
                'id' => null,
                'message' => 'Koneksi ke server Xendit terputus: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * Retrieve disbursement detail by ID.
     */
    public function getDisbursement(string $disbursementId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/disbursements/{$disbursementId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Throwable $e) {
            Log::error('Xendit getDisbursement exception', [
                'id' => $disbursementId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Retrieve cash balance from Xendit account.
     */
    public function getBalance(): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/balance?account_type=CASH");

            if ($response->successful()) {
                $data = $response->json();

                return isset($data['balance']) ? (int) $data['balance'] : null;
            }
        } catch (Throwable $e) {
            Log::error('Xendit getBalance exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Create an invoice for shipment payment using Xendit Invoices API.
     *
     * @return array{success: bool, invoice_url: ?string, id: ?string, message: string, raw?: ?array}
     */
    public function createInvoice(Shipment $shipment): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'invoice_url' => null,
                'id' => null,
                'message' => 'Kunci API Xendit belum dikonfigurasi di sistem.',
            ];
        }

        $shipment->loadMissing('user');
        $user = $shipment->user;

        $items = [
            [
                'name' => 'Ongkir: '.($shipment->courier_service_name ?: 'Kurir Pengiriman'),
                'quantity' => 1,
                'price' => $shipment->shipping_fee,
                'category' => 'Shipping',
            ],
        ];

        $serviceFee = $shipment->total_amount - $shipment->shipping_fee;
        if ($serviceFee > 0) {
            $items[] = [
                'name' => 'Biaya Layanan Aplikasi',
                'quantity' => 1,
                'price' => $serviceFee,
                'category' => 'Fee',
            ];
        }

        $payload = [
            'external_id' => $shipment->order_number,
            'amount' => $shipment->total_amount,
            'description' => "Pembayaran Pengiriman {$shipment->order_number}",
            'invoice_duration' => 86400,
            'success_redirect_url' => route('shipments.show', $shipment),
            'failure_redirect_url' => route('shipments.show', $shipment),
            'currency' => 'IDR',
            'items' => $items,
        ];

        $customer = array_filter([
            'given_names' => $user?->name,
            'email' => $user?->email,
            'mobile_number' => $user?->phone,
        ]);

        if (! empty($customer)) {
            $payload['customer'] = $customer;
        }

        if (! empty($user?->email)) {
            $payload['customer_notification_preference'] = [
                'invoice_created' => ['email'],
                'invoice_paid' => ['email'],
            ];
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/v2/invoices", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $invoiceUrl = $data['invoice_url'] ?? null;
                $invoiceId = $data['id'] ?? null;

                if ($invoiceId) {
                    $shipment->update(['xendit_invoice_id' => $invoiceId]);
                }

                return [
                    'success' => true,
                    'invoice_url' => $invoiceUrl,
                    'id' => $invoiceId,
                    'message' => 'Sesi invoice Xendit berhasil dibuat.',
                    'raw' => $data,
                ];
            }

            $errorData = $response->json();
            Log::warning('Xendit createInvoice failed', [
                'order_number' => $shipment->order_number,
                'status' => $response->status(),
                'response' => $errorData,
            ]);

            return [
                'success' => false,
                'invoice_url' => null,
                'id' => null,
                'message' => $errorData['message'] ?? 'Gagal membuat tagihan Xendit.',
                'raw' => $errorData,
            ];
        } catch (Throwable $e) {
            Log::error('Xendit createInvoice exception', [
                'order_number' => $shipment->order_number,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'invoice_url' => null,
                'id' => null,
                'message' => 'Koneksi ke Xendit terputus: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * Retrieve invoice detail by ID.
     */
    public function getInvoice(string $invoiceId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/v2/invoices/{$invoiceId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Throwable $e) {
            Log::error('Xendit getInvoice exception', ['id' => $invoiceId, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check transaction status directly with Xendit Invoice API.
     */
    public function checkInvoiceStatus(Shipment $shipment): ?string
    {
        if (! $this->isConfigured() || empty($shipment->order_number)) {
            return null;
        }

        try {
            if (! empty($shipment->xendit_invoice_id) && ! str_starts_with($shipment->xendit_invoice_id, 'mock_invoice_') && ! str_starts_with($shipment->xendit_invoice_id, 'SNAP_')) {
                $invoice = $this->getInvoice($shipment->xendit_invoice_id);
                if ($invoice) {
                    $status = strtoupper($invoice['status'] ?? '');
                    $paymentMethod = $invoice['payment_method'] ?? 'xendit';

                    if (in_array($status, ['PAID', 'SETTLED'], true)) {
                        $this->markShipmentAsPaid($shipment, $paymentMethod);

                        return 'paid';
                    }

                    if ($status === 'EXPIRED') {
                        $shipment->update(['status' => 'cancelled']);

                        return 'cancelled';
                    }

                    return strtolower($status);
                }
            }

            $response = $this->client()->get("{$this->baseUrl}/v2/invoices", [
                'external_id' => $shipment->order_number,
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $invoices = is_array($data) ? $data : ($data['data'] ?? []);
                if (! empty($invoices)) {
                    $invoice = $invoices[0];
                    $status = strtoupper($invoice['status'] ?? '');
                    $paymentMethod = $invoice['payment_method'] ?? 'xendit';

                    if (in_array($status, ['PAID', 'SETTLED'], true)) {
                        $this->markShipmentAsPaid($shipment, $paymentMethod);

                        return 'paid';
                    }

                    if ($status === 'EXPIRED') {
                        $shipment->update(['status' => 'cancelled']);

                        return 'cancelled';
                    }

                    return strtolower($status);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to check Xendit invoice status: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Mark shipment as paid and assign Biteship waybill under a per-shipment lock.
     */
    public function markShipmentAsPaid(Shipment $shipment, string $paymentType = 'xendit'): void
    {
        Cache::lock("shipment:payment:{$shipment->id}", 20)->block(10, function () use ($shipment, $paymentType): void {
            $freshShipment = Shipment::find($shipment->id);

            if (! $freshShipment || $freshShipment->status !== 'pending_payment') {
                return;
            }

            $biteship = app(BiteshipService::class);
            $biteshipResult = $biteship->createOrder($freshShipment);

            $waybill = ! empty($biteshipResult['waybill_number'])
                ? $biteshipResult['waybill_number']
                : ($freshShipment->waybill_number ?: 'STK'.strtoupper($freshShipment->courier_code).date('ymd').rand(1000, 9999));

            $biteshipOrderId = $biteshipResult['biteship_order_id'] ?? $freshShipment->biteship_order_id;

            $history = $freshShipment->tracking_history ?? [];
            $history[] = [
                'status' => 'paid',
                'note' => 'Pembayaran ongkir berhasil diverifikasi via '.$paymentType,
                'timestamp' => now()->toIso8601String(),
            ];
            $history[] = [
                'status' => 'waiting_pickup',
                'note' => 'Permintaan penjemputan paket telah dijadwalkan ke kurir '.$freshShipment->courier_service_name,
                'timestamp' => now()->addMinutes(2)->toIso8601String(),
            ];

            $freshShipment->update([
                'status' => 'paid',
                'payment_method' => $paymentType,
                'paid_at' => now(),
                'biteship_order_id' => $biteshipOrderId,
                'waybill_number' => $waybill,
                'tracking_history' => $history,
            ]);

            $freshShipment->refresh();
            $shipment->setRawAttributes($freshShipment->getAttributes());

            Wallet::syncPlatformBalances();

            app(WhatsAppService::class)->notifyShipmentReady($freshShipment);
        });
    }

    /**
     * Get list of major supported destination banks in Indonesia.
     *
     * @return array<string, string>
     */
    public static function getSupportedBanks(): array
    {
        return [
            'BCA' => 'Bank Central Asia (BCA)',
            'MANDIRI' => 'Bank Mandiri',
            'BRI' => 'Bank Rakyat Indonesia (BRI)',
            'BNI' => 'Bank Negara Indonesia (BNI)',
            'BSI' => 'Bank Syariah Indonesia (BSI)',
            'CIMB' => 'CIMB Niaga',
            'PERMATA' => 'Bank Permata',
            'JAGO' => 'Bank Jago',
            'SEABANK' => 'SeaBank Indonesia',
            'BNC' => 'Bank Neo Commerce (BNC)',
            'DANAMON' => 'Bank Danamon',
        ];
    }
}
