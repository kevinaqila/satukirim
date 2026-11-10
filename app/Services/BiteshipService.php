<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Http\Client\PendingRequest;
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
     * @return array<string, mixed>
     */
    public function checkRates(
        string $originAreaId,
        string $destinationAreaId,
        int $weightInGrams = 1000,
        array $couriers = ['jne', 'sicepat', 'jnt', 'anteraja', 'tiki', 'pos', 'ninja', 'lion']
    ): array {
        $payload = [
            'origin_area_id' => $originAreaId,
            'destination_area_id' => $destinationAreaId,
            'couriers' => implode(',', $couriers),
            'items' => [
                [
                    'name' => 'Paket Barang',
                    'description' => 'Paket pengiriman',
                    'value' => 50000,
                    'weight' => min(10000, max(100, $weightInGrams)),
                    'quantity' => 1,
                ],
            ],
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

        $originPhone = preg_replace('/\D/', '', (string) ($store?->phone ?? '081234567890'));
        $destPhone = preg_replace('/\D/', '', (string) $shipment->recipient_phone);

        $originPostal = (int) ($store?->postal_code ?? 0);
        $destPostal = (int) ($shipment->destination_postal_code ?? 0);

        $courierCompany = strtolower(trim((string) $shipment->courier_code));
        $courierType = strtolower(trim((string) $shipment->courier_service_code));

        $payload = [
            'reference_id' => $shipment->order_number,
            'origin_contact_name' => $store?->name ?? 'Toko Pengirim',
            'origin_contact_phone' => $originPhone,
            'origin_address' => $store?->address ?? 'Alamat Toko',
            'origin_area_id' => $store?->biteship_area_id,
            'destination_contact_name' => $shipment->recipient_name,
            'destination_contact_phone' => $destPhone,
            'destination_address' => $shipment->destination_address,
            'destination_area_id' => $shipment->destination_area_id,
            'courier_company' => $courierCompany,
            'courier_type' => $courierType,
            'delivery_type' => 'now',
            'order_note' => 'Order '.$shipment->order_number,
            'items' => [
                [
                    'name' => $shipment->item_description ?: 'Paket Pengiriman UMKM',
                    'description' => $shipment->item_category ?: 'Retail',
                    'value' => (int) ($shipment->item_value > 0 ? $shipment->item_value : 50000),
                    'quantity' => 1,
                    'weight' => max(100, (int) $shipment->weight),
                ],
            ],
        ];

        if ($originPostal > 0) {
            $payload['origin_postal_code'] = $originPostal;
        }
        if ($destPostal > 0) {
            $payload['destination_postal_code'] = $destPostal;
        }

        if ($shipment->payment_method === 'cod') {
            $codTotalAmount = (int) ($shipment->item_value + $shipment->shipping_fee);
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

                // If courier explicitly requires scheduled delivery
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
}
