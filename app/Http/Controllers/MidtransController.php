<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    public function __construct(
        protected MidtransService $midtrans
    ) {}

    public function getToken(Request $request, Shipment $shipment): JsonResponse
    {
        abort_unless($shipment->user_id === $request->user()->id || $request->user()->isSuperAdmin(), 403);

        $token = $this->midtrans->getSnapToken($shipment);

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }

    public function checkStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless($shipment->user_id === $request->user()->id || $request->user()->isSuperAdmin(), 403);

        $status = $this->midtrans->checkStatus($shipment);
        $shipment->refresh();

        if ($shipment->status === 'paid') {
            if (empty($shipment->biteship_order_id)) {
                $biteshipResult = app(\App\Services\BiteshipService::class)->createOrder($shipment);
                if (! empty($biteshipResult['success'])) {
                    $shipment->update([
                        'biteship_order_id' => $biteshipResult['biteship_order_id'] ?? null,
                        'waybill_number' => $biteshipResult['waybill_number'] ?: $shipment->waybill_number,
                    ]);
                }
            }

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Pembayaran berhasil diverifikasi! Kurir telah dijadwalkan.',
            ]);
        }

        return back()->with('toast', [
            'type' => 'info',
            'message' => 'Status transaksi: '.($status ? strtoupper($status) : 'Menunggu pembayaran via Midtrans.'),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? 'midtrans';

        if (! $orderId || ! $signatureKey) {
            return response()->json(['message' => 'Invalid webhook payload'], 400);
        }

        $isValid = $this->midtrans->verifySignature((string) $orderId, (string) $statusCode, (string) $grossAmount, (string) $signatureKey);

        if (! $isValid) {
            Log::warning('Midtrans webhook signature mismatch for order: '.$orderId);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $shipment = Shipment::where('order_number', $orderId)->first();

        if (! $shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            $this->midtrans->markAsPaid($shipment, $paymentType);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $shipment->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'success']);
    }
}
