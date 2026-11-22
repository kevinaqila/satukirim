<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BiteshipController extends Controller
{
    public function __construct(
        protected BiteshipService $biteship
    ) {}

    /**
     * Handle incoming webhook from Biteship.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? $payload;

        $orderId = $data['id'] ?? ($data['order_id'] ?? null);
        $referenceId = $data['reference_id'] ?? null;
        $waybillId = $data['courier']['waybill_id'] ?? ($data['waybill_id'] ?? null);
        $biteshipStatus = strtolower((string) ($data['status'] ?? ''));

        if (! $orderId && ! $referenceId && ! $waybillId) {
            return response()->json(['message' => 'No matching identifiers provided'], 400);
        }

        $shipment = Shipment::query()
            ->when($referenceId, fn ($q) => $q->where('order_number', $referenceId))
            ->when(! $referenceId && $orderId, fn ($q) => $q->where('biteship_order_id', $orderId))
            ->when(! $referenceId && ! $orderId && $waybillId, fn ($q) => $q->where('waybill_number', $waybillId))
            ->first();

        if (! $shipment) {
            Log::info('Biteship webhook received for unknown shipment', [
                'event' => $event,
                'order_id' => $orderId,
                'reference_id' => $referenceId,
                'waybill_id' => $waybillId,
            ]);

            return response()->json(['message' => 'Shipment not found'], 404);
        }

        $this->biteship->syncShipmentTracking(
            shipment: $shipment,
            biteshipStatus: $biteshipStatus,
            biteshipOrderId: is_string($orderId) ? $orderId : null,
            waybillId: is_string($waybillId) ? $waybillId : null,
            note: isset($data['note']) ? (string) $data['note'] : null,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment tracking updated successfully',
        ]);
    }
}
