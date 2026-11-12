<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request, XenditService $xenditService): Response
    {
        $user = $request->user();

        $wallet = $user->getOrCreateWallet()->syncBalances();

        $processingWithdrawals = $wallet->withdrawals()
            ->whereIn('status', ['pending', 'processing'])
            ->get();

        if ($processingWithdrawals->isNotEmpty() && $xenditService->isConfigured()) {
            $hasUpdates = false;
            foreach ($processingWithdrawals as $withdrawal) {
                $disbId = null;
                if (preg_match('/\[Xendit:\s*([^\]]+)\]/', (string) $withdrawal->notes, $matches)) {
                    $disbId = trim($matches[1]);
                }

                $detail = $disbId ? $xenditService->getDisbursement($disbId) : null;
                if ($detail) {
                    $xStatus = strtoupper($detail['status'] ?? '');
                    if (in_array($xStatus, ['COMPLETED', 'SUCCESS', 'SUCCESSFUL'])) {
                        $withdrawal->update(['status' => 'completed']);
                        $hasUpdates = true;
                    } elseif (in_array($xStatus, ['FAILED', 'REJECTED'])) {
                        $failureReason = $detail['failure_code'] ?? 'Ditolak perbankan';
                        $withdrawal->update([
                            'status' => 'failed',
                            'notes' => trim($withdrawal->notes." [Gagal: {$failureReason}]"),
                        ]);
                        $hasUpdates = true;
                    }
                }
            }

            if ($hasUpdates) {
                $wallet->syncBalances();
            }
        }

        $codQuery = $user->shipments()->where('payment_method', 'cod');

        $completedCount = (clone $codQuery)
            ->where('status', 'delivered')
            ->count();

        $pendingCount = (clone $codQuery)
            ->whereIn('status', ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit'])
            ->count();

        $codShipments = (clone $codQuery)
            ->with('store:id,name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $recentWithdrawals = $wallet->withdrawals()
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Wallet/Index', [
            'wallet' => $wallet,
            'availableBalance' => $wallet->available_balance,
            'pendingBalance' => $wallet->pending_balance,
            'totalWithdrawn' => $wallet->total_withdrawn,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'codShipments' => $codShipments,
            'recentWithdrawals' => $recentWithdrawals,
            'supportedBanks' => XenditService::getSupportedBanks(),
        ]);
    }

    /**
     * Process wallet withdrawal via Xendit Disbursements API.
     */
    public function withdraw(Request $request, XenditService $xenditService): RedirectResponse
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet()->syncBalances();

        $supportedBanks = XenditService::getSupportedBanks();

        $validated = $request->validate([
            'bank_code' => ['required', 'string', 'in:'.implode(',', array_keys($supportedBanks))],
            'account_number' => ['required', 'string', 'min:5', 'max:30'],
            'account_holder_name' => ['required', 'string', 'min:2', 'max:100'],
            'amount' => ['required', 'integer', 'min:10000'],
        ], [
            'bank_code.required' => 'Pilih bank tujuan penarikan.',
            'bank_code.in' => 'Bank yang dipilih tidak didukung.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_holder_name.required' => 'Nama pemilik rekening wajib diisi.',
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.min' => 'Minimal penarikan saldo adalah Rp10.000.',
        ]);

        $amount = $validated['amount'];

        if ($wallet->available_balance < $amount) {
            return back()->withErrors([
                'amount' => 'Saldo siap tarik tidak mencukupi (Saldo Anda: Rp '.number_format($wallet->available_balance, 0, ',', '.').').',
            ]);
        }

        $bankName = $supportedBanks[$validated['bank_code']] ?? $validated['bank_code'];
        $ref = Withdrawal::generateReferenceNumber();

        $disbursementResult = $xenditService->createDisbursement(
            externalId: $ref,
            bankCode: $validated['bank_code'],
            accountHolderName: $validated['account_holder_name'],
            accountNumber: $validated['account_number'],
            amount: $amount,
            description: "Tarik Saldo {$user->name} {$ref}"
        );

        if (! $disbursementResult['success']) {
            return back()->withErrors([
                'general' => $disbursementResult['message'],
            ]);
        }

        $status = match ($disbursementResult['status']) {
            'COMPLETED', 'SUCCESS' => 'completed',
            default => 'processing',
        };

        $xenditId = $disbursementResult['id'] ? "[Xendit: {$disbursementResult['id']}] " : '';

        DB::transaction(function () use ($wallet, $user, $ref, $amount, $status, $xenditId, $bankName, $validated) {
            $wallet->withdrawals()->create([
                'user_id' => $user->id,
                'reference_number' => $ref,
                'amount' => $amount,
                'status' => $status,
                'notes' => "{$xenditId}Transfer ke {$bankName} - {$validated['account_number']} a/n {$validated['account_holder_name']}",
            ]);

            $wallet->syncBalances();
        });

        return back()->with('success', 'Penarikan saldo sebesar Rp '.number_format($amount, 0, ',', '.').' berhasil diajukan dengan nomor referensi '.$ref.'.');
    }
}
