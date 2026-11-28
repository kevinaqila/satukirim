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
            return response()->json([
                'status' => 'ok',
                'message' => 'Webhook endpoint ready',
            ]);
        }

        $shipment = Shipment::query()
            ->where(function ($q) use ($referenceId, $orderId, $waybillId) {
                if ($referenceId) {
                    $q->orWhere('order_number', $referenceId);
                }
                if ($orderId) {
                    $q->orWhere('biteship_order_id', $orderId);
                }
                if ($waybillId) {
                    $q->orWhere('waybill_number', $waybillId);
                }
            })
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
