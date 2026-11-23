<?php

use App\Concerns\Auditable;
use App\Handlers\AuditHandler;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

class DummyAuditableModel extends Model
{
    use Auditable;

    protected $guarded = [];
}

test('auditable trait does not pollute attributes with pendingAuditUpdate on updates', function () {
    $model = new DummyAuditableModel([
        'status' => 'pending',
    ]);

    $dirty = ['status' => 'paid'];
    $model->status = 'paid';

    expect($model->getAttributes())->not->toHaveKey('pendingAuditUpdate');

    $model->status = 'confirmed';
    expect($model->getAttributes())->not->toHaveKey('pendingAuditUpdate');
});

test('auditable trait ignores xendit_invoice_id instead of snap_token', function () {
    $shipment = new Shipment;
    $ignored = $shipment->getIgnoredAuditAttributes();

    expect($ignored)->toContain('xendit_invoice_id')
        ->and($ignored)->not->toContain('snap_token');
});

test('audit handler formats tracking history and items without raw json', function () {
    $single = AuditHandler::format([
        'tracking_history' => [
            [
                'status' => 'confirmed',
                'note' => 'Courier order is confirmed. sicepat has been notified to pick up.',
                'timestamp' => '2026-11-19T16:10:52+07:00',
            ],
        ],
        'items' => [
            ['name' => 'Kemeja Flanel', 'qty' => 2],
            ['name' => 'Kopi Arabika', 'qty' => 1],
        ],
    ]);

    expect($single)->toBe([
        'Riwayat Pengiriman: Terkonfirmasi — Courier order is confirmed. sicepat has been notified to pick up. (19/11/2026 16:10)',
        'Rincian Barang: Kemeja Flanel (2x), Kopi Arabika',
    ]);

    $multiple = AuditHandler::format([
        'tracking_history' => [
            ['status' => 'paid', 'note' => 'Dibayar', 'timestamp' => '2026-11-19T16:00:00+07:00'],
            ['status' => 'delivered', 'note' => 'Diterima di rumah', 'timestamp' => '2026-11-20T10:15:00+07:00'],
        ],
    ]);

    expect($multiple)->toBe([
        'Riwayat Pengiriman: [2 riwayat] Paket Diterima — Diterima di rumah (20/11/2026 10:15)',
    ]);
});
