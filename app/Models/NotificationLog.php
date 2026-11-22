<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $shipment_id
 * @property string $recipient_phone
 * @property string $channel
 * @property string|null $type
 * @property string $message
 * @property string $status
 * @property string|null $error_message
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shipment|null $shipment
 */
#[Fillable([
    'shipment_id',
    'recipient_phone',
    'channel',
    'type',
    'message',
    'status',
    'error_message',
    'sent_at',
])]
class NotificationLog extends Model
{
    use HasFactory;

    protected $attributes = [
        'channel' => 'whatsapp',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
