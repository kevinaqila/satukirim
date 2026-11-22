<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicShippingController extends Controller
{
    /**
     * Search areas (cities/districts) for autocomplete.
     */
    public function searchAreas(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $areas = $biteship->searchAreas($validated['q']);

        return response()->json([
            'success' => true,
            'areas' => $areas,
        ]);
    }

    /**
     * Calculate public shipping rates between origin and destination.
     */
    public function calculateRates(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'origin_area_id' => ['required', 'string', 'max:100'],
            'destination_area_id' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'length' => ['nullable', 'integer', 'min:1', 'max:500', 'required_with:width,height'],
            'width' => ['nullable', 'integer', 'min:1', 'max:500', 'required_with:length,height'],
            'height' => ['nullable', 'integer', 'min:1', 'max:500', 'required_with:length,width'],
        ], [
            'weight.max' => 'Berat paket maksimal 10 kg untuk layanan parsel retail reguler/express.',
            'weight.min' => 'Berat paket minimal 0.1 kg (100 gram).',
            'length.required_with' => 'Isi lengkap panjang, lebar, dan tinggi (cm) untuk menghitung volumetrik.',
            'width.required_with' => 'Isi lengkap panjang, lebar, dan tinggi (cm) untuk menghitung volumetrik.',
            'height.required_with' => 'Isi lengkap panjang, lebar, dan tinggi (cm) untuk menghitung volumetrik.',
        ]);

        $weightInGrams = (int) round($validated['weight'] * 1000);

        $result = $biteship->checkRates(
            originAreaId: $validated['origin_area_id'],
            destinationAreaId: $validated['destination_area_id'],
            weightInGrams: $weightInGrams,
            length: $validated['length'] ?? null,
            width: $validated['width'] ?? null,
            height: $validated['height'] ?? null,
        );

        return response()->json($result);
    }

    /**
     * Public waybill live tracking.
     */
    public function trackWaybill(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'waybill' => ['required', 'string', 'max:80'],
            'courier' => ['required', 'string', 'max:30'],
        ]);

        $cleanWaybill = trim($validated['waybill']);
        $cleanCourier = strtolower(trim($validated['courier']));

        $shipment = Shipment::with('store:id,name,area_name')
            ->where(function ($query) use ($cleanWaybill): void {
                $query->where('waybill_number', $cleanWaybill)
                    ->orWhere('order_number', $cleanWaybill);
            })
            ->where('courier_code', $cleanCourier)
            ->first();

        if ($shipment) {
            if (! empty($shipment->biteship_order_id) && ! in_array($shipment->status, ['delivered', 'cancelled', 'disposed'], true)) {
                $orderResult = $biteship->getOrder($shipment->biteship_order_id);
                if (! empty($orderResult['success']) && ! empty($orderResult['data'])) {
                    $orderData = $orderResult['data'];
                    $historyList = $orderData['courier']['history'] ?? ($orderData['history'] ?? null);

                    $shipment = $biteship->syncShipmentTracking(
                        shipment: $shipment,
                        biteshipStatus: isset($orderData['status']) ? (string) $orderData['status'] : null,
                        waybillId: isset($orderData['courier']['waybill_id']) ? (string) $orderData['courier']['waybill_id'] : null,
                        historyItems: is_array($historyList) ? $historyList : null,
                    );
                    $shipment->loadMissing('store:id,name,area_name');
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'source' => 'satukirim',
                    'order_number' => $shipment->order_number,
                    'waybill_id' => $shipment->waybill_number ?: $shipment->order_number,
                    'status' => $shipment->status,
                    'courier' => [
                        'company' => $shipment->courier_code,
                        'name' => $shipment->courier_service_name,
                    ],
                    'origin_store_name' => $shipment->store?->name,
                    'origin_area_name' => $shipment->store?->area_name,
                    'destination_area_name' => $shipment->destination_area_name,
                    'estimated_delivery' => $shipment->estimated_delivery,
                    'weight' => $shipment->weight,
                    'payment_method' => $shipment->payment_method,
                    'created_at' => $shipment->created_at?->toIso8601String(),
                    'paid_at' => $shipment->paid_at?->toIso8601String(),
                    'history' => $shipment->tracking_history ?? [],
                ],
            ]);
        }

        $result = $biteship->trackWaybill(
            $cleanWaybill,
            $cleanCourier
        );

        return response()->json($result);
    }
}
