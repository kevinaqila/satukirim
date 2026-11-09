<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $wallet = $user->getOrCreateWallet()->syncBalances();

        $codQuery = $user->shipments()->where('payment_method', 'cod');

        $completedCount = (int) (clone $codQuery)
            ->where('status', 'delivered')
            ->count();

        $pendingCount = (int) (clone $codQuery)
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
        ]);
    }
}
