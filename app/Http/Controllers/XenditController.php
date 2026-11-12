<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Withdrawal;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditController extends Controller
{
    /**
     * Handle incoming webhook notifications from Xendit (Disbursements & Invoices).
     */
    public function webhook(Request $request, XenditService $xendit): JsonResponse
    {
        $callbackToken = config('services.xendit.callback_token');
        $incomingToken = $request->header('x-callback-token');

        if (! empty($callbackToken) && $incomingToken !== $callbackToken) {
            Log::warning('Xendit webhook rejected: invalid callback token');

            return response()->json(['message' => 'Unauthorized callback token'], 401);
        }

        $payload = $request->all();
        $externalId = $payload['external_id'] ?? '';
        $status = strtoupper($payload['status'] ?? '');

        Log::info('Xendit webhook received', [
            'external_id' => $externalId,
            'status' => $status,
            'id' => $payload['id'] ?? null,
        ]);

        if (empty($externalId)) {
            return response()->json(['message' => 'Missing external_id parameter'], 400);
        }

        // 1. Check if external_id belongs to a Withdrawal (Disbursement)
        $withdrawal = Withdrawal::where('reference_number', $externalId)->first();
        if ($withdrawal) {
            if (in_array($status, ['COMPLETED', 'SUCCESS', 'SUCCESSFUL'], true)) {
                $withdrawal->update(['status' => 'completed']);
            } elseif (in_array($status, ['FAILED', 'REJECTED'], true)) {
                $failureReason = $payload['failure_code'] ?? 'Ditolak oleh perbankan';
                $existingNotes = $withdrawal->notes ?? '';
                $withdrawal->update([
                    'status' => 'failed',
                    'notes' => trim("{$existingNotes} [Gagal: {$failureReason}]"),
                ]);
            }

            $withdrawal->wallet->syncBalances();

            return response()->json([
                'status' => 'success',
                'message' => 'Disbursement status updated successfully',
            ]);
        }

        // 2. Check if external_id belongs to a Shipment (Invoice)
        $shipment = Shipment::where('order_number', $externalId)->first();
        if ($shipment) {
            if (in_array($status, ['PAID', 'SETTLED'], true)) {
                $paymentMethod = $payload['payment_method'] ?? 'xendit';
                $xendit->markShipmentAsPaid($shipment, $paymentMethod);
            } elseif ($status === 'EXPIRED') {
                if ($shipment->status === 'pending_payment') {
                    $shipment->update(['status' => 'cancelled']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Shipment invoice status updated successfully',
            ]);
        }

        Log::warning('Xendit webhook: record not found', ['external_id' => $externalId]);

        return response()->json(['message' => 'Record not found for given external_id'], 404);
    }
}
