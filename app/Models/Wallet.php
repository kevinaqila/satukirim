<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $available_balance
 * @property int $pending_balance
 * @property int $total_withdrawn
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'available_balance',
    'pending_balance',
    'total_withdrawn',
])]
class Wallet extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'available_balance' => 'integer',
            'pending_balance' => 'integer',
            'total_withdrawn' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Synchronize wallet balances based on user's COD shipments (UMKM) or settled platform fees (Super Admin).
     */
    public function syncBalances(): self
    {
        $user = $this->user;
        if (! $user) {
            return $this;
        }

        if ($user->role === 'super_admin') {
            $paidShipments = Shipment::whereNotIn('status', ['pending_payment', 'cancelled'])
                ->get(['shipping_fee', 'total_amount', 'payment_method', 'item_value', 'status']);

            $accumulatedRevenue = (int) $paidShipments->sum(function (Shipment $s): int {
                $feeDiff = (int) $s->total_amount - (int) $s->shipping_fee;
                if ($feeDiff > 0) {
                    return $feeDiff;
                }

                return Shipment::PLATFORM_FEE;
            });

            $deductedWithdrawals = (int) $this->withdrawals()
                ->whereIn('status', ['completed', 'pending', 'processing'])
                ->sum('amount');

            $completedWithdrawals = (int) $this->withdrawals()
                ->where('status', 'completed')
                ->sum('amount');

            $this->update([
                'available_balance' => max(0, $accumulatedRevenue - $deductedWithdrawals),
                'pending_balance' => 0,
                'total_withdrawn' => $completedWithdrawals,
            ]);

            return $this;
        }

        $codQuery = $user->shipments()->where('payment_method', 'cod');

        $deliveredSum = (int) (clone $codQuery)
            ->where('status', 'delivered')
            ->get(['item_value'])
            ->sum(fn ($s) => max(0, $s->item_value - Shipment::PLATFORM_FEE));

        $pendingSum = (int) (clone $codQuery)
            ->whereIn('status', ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit'])
            ->get(['item_value'])
            ->sum(fn ($s) => max(0, $s->item_value - Shipment::PLATFORM_FEE));

        $deductedWithdrawals = (int) $this->withdrawals()
            ->whereIn('status', ['completed', 'pending', 'processing'])
            ->sum('amount');

        $completedWithdrawals = (int) $this->withdrawals()
            ->where('status', 'completed')
            ->sum('amount');

        $this->update([
            'available_balance' => max(0, $deliveredSum - $deductedWithdrawals),
            'pending_balance' => $pendingSum,
            'total_withdrawn' => $completedWithdrawals,
        ]);

        return $this;
    }

    /**
     * Synchronize platform revenue wallet for all Super Admin accounts after a transaction completes.
     */
    public static function syncPlatformBalances(): void
    {
        User::where('role', 'super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->getOrCreateWallet()->syncBalances());
    }
}
