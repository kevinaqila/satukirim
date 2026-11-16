<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        $validatedFilters = $request->validate([
            'tab' => ['nullable', 'string', 'in:cod,withdrawals'],
            'cod_status' => ['nullable', 'string', 'in:all,settled,pending,cancelled'],
            'cod_search' => ['nullable', 'string', 'max:100'],
            'cod_date_preset' => ['nullable', 'string', 'max:20'],
            'cod_date_from' => ['nullable', 'date'],
            'cod_date_to' => ['nullable', 'date', 'after_or_equal:cod_date_from'],
            'wd_status' => ['nullable', 'string', 'in:all,processing,completed,failed'],
            'wd_search' => ['nullable', 'string', 'max:100'],
            'wd_date_preset' => ['nullable', 'string', 'max:20'],
            'wd_date_from' => ['nullable', 'date'],
            'wd_date_to' => ['nullable', 'date', 'after_or_equal:wd_date_from'],
        ]);

        $codQuery = $user->shipments()->where('payment_method', 'cod');

        $completedCount = (clone $codQuery)
            ->where('status', 'delivered')
            ->count();

        $pendingCount = (clone $codQuery)
            ->whereIn('status', ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit'])
            ->count();

        $codStatus = $validatedFilters['cod_status'] ?? 'all';
        $codSearch = trim((string) ($validatedFilters['cod_search'] ?? ''));
        $codDateFrom = $validatedFilters['cod_date_from'] ?? null;
        $codDateTo = $validatedFilters['cod_date_to'] ?? null;

        $filteredCodQuery = (clone $codQuery)->with('store:id,name')->latest();

        if ($codStatus === 'settled') {
            $filteredCodQuery->where('status', 'delivered');
        } elseif ($codStatus === 'pending') {
            $filteredCodQuery->whereIn('status', ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit']);
        } elseif ($codStatus === 'cancelled') {
            $filteredCodQuery->whereIn('status', ['cancelled', 'rejected', 'courier_not_found', 'returned']);
        }

        if ($codSearch !== '') {
            $filteredCodQuery->where(function ($q) use ($codSearch) {
                $q->where('order_number', 'like', "%{$codSearch}%")
                    ->orWhere('waybill_number', 'like', "%{$codSearch}%")
                    ->orWhere('recipient_name', 'like', "%{$codSearch}%");
            });
        }

        $this->applyJakartaDateRange($filteredCodQuery, $codDateFrom, $codDateTo);

        $codShipments = $filteredCodQuery
            ->paginate(10, ['*'], 'cod_page')
            ->withQueryString();

        $wdStatus = $validatedFilters['wd_status'] ?? 'all';
        $wdSearch = trim((string) ($validatedFilters['wd_search'] ?? ''));
        $wdDateFrom = $validatedFilters['wd_date_from'] ?? null;
        $wdDateTo = $validatedFilters['wd_date_to'] ?? null;

        $filteredWdQuery = $wallet->withdrawals()->latest();

        if ($wdStatus === 'processing') {
            $filteredWdQuery->whereIn('status', ['pending', 'processing']);
        } elseif (in_array($wdStatus, ['completed', 'failed'], true)) {
            $filteredWdQuery->where('status', $wdStatus);
        }

        if ($wdSearch !== '') {
            $filteredWdQuery->where(function ($q) use ($wdSearch) {
                $q->where('reference_number', 'like', "%{$wdSearch}%")
                    ->orWhere('notes', 'like', "%{$wdSearch}%");
            });
        }

        $this->applyJakartaDateRange($filteredWdQuery, $wdDateFrom, $wdDateTo);

        $withdrawals = $filteredWdQuery
            ->paginate(10, ['*'], 'wd_page')
            ->withQueryString();

        $withdrawalsCount = $wallet->withdrawals()->count();

        return Inertia::render('Wallet/Index', [
            'wallet' => $wallet,
            'availableBalance' => $wallet->available_balance,
            'pendingBalance' => $wallet->pending_balance,
            'totalWithdrawn' => $wallet->total_withdrawn,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'withdrawalsCount' => $withdrawalsCount,
            'codShipments' => $codShipments,
            'withdrawals' => $withdrawals,
            'supportedBanks' => XenditService::getSupportedBanks(),
            'filters' => [
                'tab' => $validatedFilters['tab'] ?? 'cod',
                'cod_status' => $codStatus,
                'cod_search' => $codSearch,
                'cod_date_preset' => $validatedFilters['cod_date_preset'] ?? 'all',
                'cod_date_from' => $codDateFrom ?? '',
                'cod_date_to' => $codDateTo ?? '',
                'wd_status' => $wdStatus,
                'wd_search' => $wdSearch,
                'wd_date_preset' => $validatedFilters['wd_date_preset'] ?? 'all',
                'wd_date_from' => $wdDateFrom ?? '',
                'wd_date_to' => $wdDateTo ?? '',
            ],
        ]);
    }

    /**
     * Filter query created_at by Asia/Jakarta calendar dates converted to UTC.
     */
    private function applyJakartaDateRange(Builder|HasMany $query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $fromUtc = Carbon::parse($dateFrom, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
            $query->where('created_at', '>=', $fromUtc);
        }

        if ($dateTo) {
            $toUtc = Carbon::parse($dateTo, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');
            $query->where('created_at', '<=', $toUtc);
        }
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
            'account_number' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[0-9]+$/'],
            'account_holder_name' => ['required', 'string', 'min:2', 'max:100'],
            'amount' => ['required', 'integer', 'min:10000'],
            'save_bank' => ['nullable', 'boolean'],
        ], [
            'bank_code.required' => 'Pilih bank tujuan penarikan.',
            'bank_code.in' => 'Bank yang dipilih tidak didukung.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
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
            if (($validated['save_bank'] ?? false) || empty($user->bank_code) || empty($user->bank_account_number)) {
                $user->update([
                    'bank_code' => $validated['bank_code'],
                    'bank_account_number' => $validated['account_number'],
                    'bank_account_name' => $validated['account_holder_name'],
                ]);
            }

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
