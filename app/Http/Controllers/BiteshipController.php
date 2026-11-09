<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Wallet;
use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $updates = [];

        if ($waybillId && empty($shipment->waybill_number)) {
            $updates['waybill_number'] = $waybillId;
        }

        if ($orderId && empty($shipment->biteship_order_id)) {
            $updates['biteship_order_id'] = $orderId;
        }

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
        ];

        $mappedStatus = $statusMap[$biteshipStatus] ?? null;

        if ($mappedStatus && $mappedStatus !== $shipment->status) {
            $updates['status'] = $mappedStatus;

            $history = $shipment->tracking_history ?? [];
            $note = $data['note'] ?? 'Status pengiriman diperbarui kurir menjadi '.ucwords(str_replace('_', ' ', $mappedStatus));

            $history[] = [
                'status' => $mappedStatus,
                'note' => $note,
                'timestamp' => now()->toIso8601String(),
            ];

            $updates['tracking_history'] = $history;
        }

        if (! empty($updates)) {
            $shipment->update($updates);

            if (isset($updates['status']) && in_array($updates['status'], ['delivered', 'cancelled'], true)) {
                $shipment->user?->getOrCreateWallet()->syncBalances();
                Wallet::syncPlatformBalances();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment tracking updated successfully',
        ]);
    }
}

