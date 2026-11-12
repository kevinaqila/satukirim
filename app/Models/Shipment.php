<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $store_id
 * @property string $order_number
 * @property string $status
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property string $destination_address
 * @property string|null $destination_postal_code
 * @property string $destination_area_id
 * @property string|null $destination_area_name
 * @property int $weight
 * @property string|null $item_description
 * @property string|null $item_category
 * @property int $item_value
 * @property array<int, array<string, mixed>>|null $items
 * @property string $courier_code
 * @property string $courier_service_code
 * @property string $courier_service_name
 * @property int $shipping_fee
 * @property int $total_amount
 * @property string|null $estimated_delivery
 * @property string|null $biteship_order_id
 * @property string|null $waybill_number
 * @property array<array-key, mixed>|null $tracking_history
 * @property string|null $snap_token
 * @property string|null $payment_method
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'store_id',
    'order_number',
    'status',
    'recipient_name',
    'recipient_phone',
    'destination_address',
    'destination_postal_code',
    'destination_area_id',
    'destination_area_name',
    'weight',
    'item_description',
    'item_category',
    'item_value',
    'items',
    'courier_code',
    'courier_service_code',
    'courier_service_name',
    'shipping_fee',
    'total_amount',
    'estimated_delivery',
    'biteship_order_id',
    'waybill_number',
    'tracking_history',
    'snap_token',
    'payment_method',
    'paid_at',
])]
class Shipment extends Model
{
    use Auditable, HasFactory;

    public const PLATFORM_FEE = 1000;

    protected $appends = [
        'platform_fee',
    ];

    public function getPlatformFeeAttribute(): int
    {
        $diff = (int) ($this->attributes['total_amount'] ?? 0) - (int) ($this->attributes['shipping_fee'] ?? 0);

        return $diff > 0 ? $diff : self::PLATFORM_FEE;
    }

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'item_value' => 'integer',
            'items' => 'array',
            'shipping_fee' => 'integer',
            'total_amount' => 'integer',
            'tracking_history' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function generateOrderNumber(): string
    {
        $lastOrder = static::query()
            ->where('order_number', 'like', 'SK-%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $nextNumber = 1;
        if ($lastOrder && preg_match('/^SK-(\d{4})/', $lastOrder, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = static::query()->count() + 1;
        }

        $seq = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($letters) - 1;
        $randomSuffix = $letters[random_int(0, $max)]
            .$letters[random_int(0, $max)]
            .$letters[random_int(0, $max)];

        return "SK-{$seq}{$randomSuffix}";
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit', 'delivered']);
    }
}
