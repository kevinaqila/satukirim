<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Wallet;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BiteshipService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.biteship.key');
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.biteship.base_url', 'https://api.biteship.com'), '/');
    }

    /**
     * Build the authenticated HTTP client instance.
     */
    protected function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->connectTimeout(5)
            ->timeout(12)
            ->acceptJson()
            ->withToken($this->apiKey);

        if (! app()->isProduction()) {
            $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Search areas (kelurahan / kecamatan / kota) by keyword.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchAreas(string $input): array
    {
        $cleanInput = trim($input);

        if (strlen($cleanInput) < 2) {
            return [];
        }

        try {
            $response = $this->client()->get('/v1/maps/areas', [
                'countries' => 'ID',
                'input' => $cleanInput,
                'type' => 'single',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $areas = (array) ($data['areas'] ?? []);

                $lowerInput = strtolower($cleanInput);
                usort($areas, function (array $a, array $b) use ($lowerInput): int {
                    $aDistrict = strtolower($a['administrative_division_level_3_name'] ?? '');
                    $bDistrict = strtolower($b['administrative_division_level_3_name'] ?? '');
                    $aName = strtolower($a['name'] ?? '');
                    $bName = strtolower($b['name'] ?? '');

                    $aPrefix = str_starts_with($aDistrict, $lowerInput) || str_starts_with($aName, $lowerInput);
                    $bPrefix = str_starts_with($bDistrict, $lowerInput) || str_starts_with($bName, $lowerInput);

                    if ($aPrefix !== $bPrefix) {
                        return $aPrefix ? -1 : 1;
                    }

                    return 0;
                });

                return $areas;
            }

            Log::warning('Biteship searchAreas failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'input' => $cleanInput,
            ]);

            return [];
        } catch (Throwable $e) {
            Log::error('Biteship searchAreas exception', [
                'message' => $e->getMessage(),
                'input' => $cleanInput,
            ]);

            return [];
        }
    }

    /**
     * Check courier shipping rates between two area IDs.
     *
     * @param  array<int, string>  $couriers
     * @param  array<int, array<string, mixed>>|null  $items
     * @return array<string, mixed>
     */
    public function checkRates(
        string $originAreaId,
        string $destinationAreaId,
        int $weightInGrams = 1000,
        array $couriers = ['jne', 'sicepat', 'jnt', 'anteraja', 'tiki', 'pos', 'ninja', 'lion'],
        int $codAmount = 50000,
        ?int $length = null,
        ?int $width = null,
        ?int $height = null,
        ?array $items = null,
    ): array {
        $itemsPayload = [];
        if (! empty($items)) {
            foreach ($items as $item) {
                $itemsPayload[] = $this->buildBiteshipItemPayload(
                    item: $item,
                    defaultName: 'Paket Barang',
                    defaultDescription: 'Paket pengiriman',
                    defaultValue: max(1000, $codAmount),
                    minWeight: 10,
                );
            }
        }

        if ($itemsPayload === []) {
            $itemsPayload = [
                $this->buildBiteshipItemPayload(
                    item: [
                        'name' => 'Paket Barang',
                        'description' => 'Paket pengiriman',
                        'value' => max(1000, $codAmount),
                        'weight' => min(10000, max(100, $weightInGrams)),
                        'qty' => 1,
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                    ],
                    defaultName: 'Paket Barang',
                    defaultDescription: 'Paket pengiriman',
                    defaultValue: max(1000, $codAmount),
                    minWeight: 100,
                ),
            ];
        }

        $payload = [
            'origin_area_id' => $originAreaId,
            'destination_area_id' => $destinationAreaId,
            'couriers' => implode(',', $couriers),
            'items' => $itemsPayload,
            'destination_cash_on_delivery' => max(1000, $codAmount),
            'destination_cash_on_delivery_type' => '7_days',
        ];

        try {
            $response = $this->client()->post('/v1/rates/couriers', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $pricing = (array) ($data['pricing'] ?? []);
                $filtered = array_values(array_filter($pricing, function (array $p): bool {
                    $serviceCode = strtolower($p['courier_service_code'] ?? '');
                    $serviceType = strtolower($p['type'] ?? ($p['service_type'] ?? ''));
                    $serviceName = strtolower($p['courier_service_name'] ?? '');

                    $isCargo = str_contains($serviceCode, 'jtr')
                        || str_contains($serviceCode, 'cargo')
                        || str_contains($serviceCode, 'truck')
                        || str_contains($serviceCode, 'gokil')
                        || str_contains($serviceCode, 'big')
                        || str_contains($serviceType, 'cargo')
                        || str_contains($serviceType, 'trucking')
                        || str_contains($serviceName, 'trucking')
                        || str_contains($serviceName, 'kargo');

                    $isInstant = str_contains($serviceType, 'instant')
                        || str_contains($serviceCode, 'instant');

                    return ! $isCargo && ! $isInstant;
                }));

                $filtered = array_map(function (array $p): array {
                    $courierCode = strtolower($p['courier_code'] ?? '');

                    $isCod = isset($p['available_for_cash_on_delivery'])
                        ? filter_var($p['available_for_cash_on_delivery'], FILTER_VALIDATE_BOOLEAN)
                        : in_array($courierCode, ['jne', 'sicepat', 'jnt', 'anteraja', 'sap', 'idexpress'], true);

                    if (in_array($courierCode, ['lion', 'tiki', 'pos'], true)) {
                        $isCod = false;
                    }

                    $p['available_for_cash_on_delivery'] = $isCod;

                    return $p;
                }, $filtered);

                return [
                    'success' => true,
                    'pricing' => $filtered,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Gagal mengambil tarif pengiriman dari kurir.';

            Log::warning('Biteship checkRates failed', [
                'status' => $response->status(),
                'error' => $errorData,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => (string) $errorMessage,
                'pricing' => [],
            ];
        } catch (Throwable $e) {
            Log::error('Biteship checkRates exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kendala jaringan saat menghubungi ekspedisi kurir.',
                'pricing' => [],
            ];
        }
    }

    /**
     * Get order details and tracking status from Biteship.
     *
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        $cleanId = trim($orderId);

        try {
            $response = $this->client()->get("/v1/orders/{$cleanId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorData = $response->json();

            return [
                'success' => false,
                'message' => $errorData['error'] ?? $errorData['message'] ?? 'Data pesanan tidak ditemukan di Biteship.',
            ];
        } catch (Throwable $e) {
            Log::error('Biteship getOrder exception: '.$e->getMessage(), ['order_id' => $cleanId]);

            return [
                'success' => false,
                'message' => 'Terjadi kendala saat memeriksa pesanan di Biteship.',
            ];
        }
    }

    /**
     * Track a waybill / order status from courier.
     *
     * @return array<string, mixed>
     */
    public function trackWaybill(string $waybillId, string $courierCode): array
    {
        $cleanWaybill = trim($waybillId);
        $cleanCourier = strtolower(trim($courierCode));

        try {
            $response = $this->client()->get("/v1/trackings/{$cleanWaybill}/couriers/{$cleanCourier}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            $errorData = $response->json();

            return [
                'success' => false,
                'message' => $errorData['error'] ?? $errorData['message'] ?? 'Nomor resi tidak ditemukan atau belum tercatat di sistem kurir.',
            ];
        } catch (Throwable $e) {
            Log::error('Biteship trackWaybill exception', [
                'message' => $e->getMessage(),
                'waybill' => $cleanWaybill,
                'courier' => $cleanCourier,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kendala saat memeriksa status resi pengiriman.',
            ];
        }
    }

    /**
     * Create an order in Biteship to dispatch courier pickup.
     *
     * @return array<string, mixed>
     */
    public function createOrder(Shipment $shipment): array
    {
        $shipment->loadMissing('store');
        $store = $shipment->store;

        $originPhone = preg_replace('/\D/', '', $store?->phone ?? '');
        $destPhone = preg_replace('/\D/', '', $shipment->recipient_phone ?? '');

        $courierCompany = strtolower(trim($shipment->courier_code));
        $courierType = strtolower(trim($shipment->courier_service_code));

        $itemsPayload = [];
        if (! empty($shipment->items) && is_array($shipment->items)) {
            foreach ($shipment->items as $item) {
                $itemDescription = trim((string) ($item['description'] ?? ''));

                $itemsPayload[] = $this->buildBiteshipItemPayload(
                    item: [
                        ...$item,
                        'description' => $itemDescription !== ''
                            ? $itemDescription
                            : ($item['category'] ?? $shipment->item_category),
                    ],
                    defaultName: '',
                    defaultDescription: $shipment->item_category,
                    defaultValue: 0,
                    minWeight: 10,
                );
            }
        }

        if ($itemsPayload === []) {
            $itemsPayload = [
                $this->buildBiteshipItemPayload(
                    item: [
                        'name' => $shipment->item_description,
                        'description' => $shipment->item_category,
                        'value' => $shipment->item_value,
                        'weight' => $shipment->weight,
                        'qty' => 1,
                    ],
                    defaultName: '',
                    defaultDescription: null,
                    defaultValue: 1000,
                    minWeight: 100,
                ),
            ];
        }

        $payload = [
            'reference_id' => $shipment->order_number,
            'origin_contact_name' => $store?->name,
            'origin_contact_phone' => $originPhone,
            'origin_address' => $store?->address,
            'origin_area_id' => $store?->biteship_area_id,
            'destination_contact_name' => $shipment->recipient_name,
            'destination_contact_phone' => $destPhone,
            'destination_address' => $shipment->destination_address,
            'destination_area_id' => $shipment->destination_area_id,
            'courier_company' => $courierCompany,
            'courier_type' => $courierType,
            'delivery_type' => 'now',
            'order_note' => 'Order '.$shipment->order_number,
            'items' => $itemsPayload,
        ];

        if (! empty($store?->postal_code)) {
            $payload['origin_postal_code'] = (int) $store->postal_code;
        }

        if (! empty($shipment->destination_postal_code)) {
            $payload['destination_postal_code'] = (int) $shipment->destination_postal_code;
        }

        if ($shipment->payment_method === 'cod') {
            $codTotalAmount = $shipment->item_value + $shipment->shipping_fee;
            if ($codTotalAmount > 0) {
                $payload['destination_cash_on_delivery'] = $codTotalAmount;
                $payload['destination_cash_on_delivery_type'] = '7_days';
            }
        }

        try {
            $response = $this->client()->post('/v1/orders', $payload);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorCode = $errorData['code'] ?? null;
                $errorMessage = strtolower($errorData['error'] ?? ($errorData['message'] ?? ''));

                if ($errorCode === 40002005 || $errorCode === 40002013 || str_contains($errorMessage, 'scheduled') || str_contains($errorMessage, 'delivery_type')) {
                    $wibNow = now('Asia/Jakarta');
                    $pickupDate = $wibNow->hour >= 15 ? $wibNow->copy()->addDay()->format('Y-m-d') : $wibNow->format('Y-m-d');
                    $pickupTime = $wibNow->hour >= 15 ? '10:00' : $wibNow->copy()->addHours(2)->format('H:i');

                    $payload['delivery_type'] = 'scheduled';
                    $payload['delivery_date'] = $pickupDate;
                    $payload['delivery_time'] = $pickupTime;

                    $retryResponse = $this->client()->post('/v1/orders', $payload);
                    if ($retryResponse->successful()) {
                        $response = $retryResponse;
                    }
                } elseif ($errorCode === 40002007 || str_contains($errorMessage, 'not available for scheduled')) {
                    unset($payload['delivery_date'], $payload['delivery_time']);
                    $payload['delivery_type'] = 'now';

                    $retryResponse = $this->client()->post('/v1/orders', $payload);
                    if ($retryResponse->successful()) {
                        $response = $retryResponse;
                    }
                }
            }

            if ($response->successful()) {
                $data = $response->json();
                $biteshipOrderId = $data['id'] ?? null;
                $waybill = $data['courier']['waybill_id'] ?? ($data['courier']['tracking_id'] ?? null);

                return [
                    'success' => true,
                    'data' => $data,
                    'biteship_order_id' => $biteshipOrderId,
                    'waybill_number' => $waybill,
                ];
            }

            $errorData = $response->json();
            Log::warning('Biteship createOrder failed', [
                'status' => $response->status(),
                'error' => $errorData,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => $errorData['error'] ?? $errorData['message'] ?? 'Gagal membuat pesanan pengiriman di Biteship.',
                'error' => $errorData,
            ];
        } catch (Throwable $e) {
            Log::error('Biteship createOrder exception: '.$e->getMessage(), [
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi API Biteship: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Map raw Biteship status string into internal shipment status.
     */
    public function mapStatus(?string $biteshipStatus): ?string
    {
        $normalized = str($biteshipStatus ?? '')->trim()->lower()->toString();

        $statusMap = [
            'confirmed' => 'confirmed',
            'scheduled' => 'confirmed',
            'allocated' => 'allocated',
            'pickingup' => 'picking_up',
            'picking_up' => 'picking_up',
            'dalam penjemputan' => 'picking_up',
            'picked' => 'picked',
            'droppingoff' => 'dropping_off',
            'dropping_off' => 'dropping_off',
            'in_transit' => 'in_transit',
            'intransit' => 'in_transit',
            'on_process' => 'in_transit',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'rejected' => 'rejected',
            'on_hold' => 'on_hold',
            'onhold' => 'on_hold',
            'courier_not_found' => 'courier_not_found',
            'couriernotfound' => 'courier_not_found',
            'returned' => 'returned',
            'return_in_transit' => 'returned',
            'disposed' => 'disposed',
        ];

        return $statusMap[$normalized] ?? null;
    }

    /**
     * Synchronize shipment status, waybill, tracking history, wallets, and WhatsApp notification under a lock.
     *
     * @param  array<int, array<string, mixed>>|null  $historyItems
     */
    public function syncShipmentTracking(
        Shipment $shipment,
        ?string $biteshipStatus,
        ?string $biteshipOrderId = null,
        ?string $waybillId = null,
        ?string $note = null,
        ?array $historyItems = null,
    ): Shipment {
        return Cache::lock("shipment:tracking:{$shipment->id}", 15)->block(5, function () use (
            $shipment,
            $biteshipStatus,
            $biteshipOrderId,
            $waybillId,
            $note,
            $historyItems,
        ): Shipment {
            $freshShipment = Shipment::find($shipment->id);

            if (! $freshShipment) {
                return $shipment;
            }

            $updates = [];

            if (filled($biteshipOrderId) && empty($freshShipment->biteship_order_id)) {
                $updates['biteship_order_id'] = $biteshipOrderId;
            }

            if (filled($waybillId) && $waybillId !== $freshShipment->waybill_number) {
                $updates['waybill_number'] = $waybillId;
            }

            $mappedStatus = $this->mapStatus($biteshipStatus);
            $statusChanged = $mappedStatus !== null && $mappedStatus !== $freshShipment->status;

            if ($statusChanged) {
                $updates['status'] = $mappedStatus;
            }

            if (is_array($historyItems) && $historyItems !== []) {
                $formattedHistory = [];
                foreach ($historyItems as $item) {
                    $itemStatus = $item['status'] ?? '';
                    $formattedHistory[] = [
                        'status' => $this->mapStatus((string) $itemStatus) ?? ($itemStatus !== '' ? $itemStatus : 'in_transit'),
                        'note' => $item['note'] ?? 'Status kurir: '.str($itemStatus)->trim()->lower()->toString(),
                        'timestamp' => $item['updated_at'] ?? now()->toIso8601String(),
                    ];
                }

                if ($formattedHistory !== ($freshShipment->tracking_history ?? [])) {
                    $updates['tracking_history'] = $formattedHistory;
                }
            } elseif ($statusChanged) {
                $history = $freshShipment->tracking_history ?? [];
                $history[] = [
                    'status' => $mappedStatus,
                    'note' => $note ?: 'Status pengiriman diperbarui kurir menjadi '.ucwords(str_replace('_', ' ', $mappedStatus)),
                    'timestamp' => now()->toIso8601String(),
                ];

                $updates['tracking_history'] = $history;
            }

            if ($updates !== []) {
                $freshShipment->update($updates);
                $freshShipment->refresh();

                if ($statusChanged && in_array($mappedStatus, ['delivered', 'cancelled', 'rejected', 'courier_not_found', 'returned', 'disposed'], true)) {
                    $freshShipment->user?->getOrCreateWallet()->syncBalances();
                    Wallet::syncPlatformBalances();
                }

                if ($statusChanged) {
                    app(WhatsAppService::class)->notifyShipmentStatusUpdated($freshShipment, $mappedStatus);
                }
            }

            $shipment->setRawAttributes($freshShipment->getAttributes());

            return $freshShipment;
        });
    }

    /**
     * Build a Biteship item payload with optional volumetric dimensions.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, int|string>
     */
    private function buildBiteshipItemPayload(
        array $item,
        string $defaultName,
        ?string $defaultDescription = null,
        int $defaultValue = 0,
        int $minWeight = 10,
    ): array {
        $name = trim((string) ($item['name'] ?? ''));
        $description = trim((string) ($item['description'] ?? ''));
        $resolvedName = $name !== '' ? $name : trim($defaultName);
        $resolvedDescription = $description !== '' ? $description : trim((string) ($defaultDescription ?? ''));

        $payload = [];

        if ($resolvedName !== '') {
            $payload['name'] = $resolvedName;
        }

        if ($resolvedDescription !== '') {
            $payload['description'] = $resolvedDescription;
        }

        $payload['value'] = max(1000, (int) ($item['value'] ?? $defaultValue));
        $payload['weight'] = min(10000, max($minWeight, (int) ($item['weight'] ?? $minWeight)));
        $payload['quantity'] = max(1, (int) ($item['qty'] ?? $item['quantity'] ?? 1));

        $length = (int) ($item['length'] ?? 0);
        $width = (int) ($item['width'] ?? 0);
        $height = (int) ($item['height'] ?? 0);

        if ($length > 0 && $width > 0 && $height > 0) {
            $payload['length'] = min(500, $length);
            $payload['width'] = min(500, $width);
            $payload['height'] = min(500, $height);
        }

        return $payload;
    }
}
