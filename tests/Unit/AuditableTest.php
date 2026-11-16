<?php

use App\Concerns\Auditable;
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

    // Simulate updating event
    $dirty = ['status' => 'paid'];
    $model->status = 'paid';

    // Check that pendingAuditUpdate is never in attributes
    expect($model->getAttributes())->not->toHaveKey('pendingAuditUpdate');

    // Simulate another change
    $model->status = 'confirmed';
    expect($model->getAttributes())->not->toHaveKey('pendingAuditUpdate');
});

test('auditable trait ignores xendit_invoice_id instead of snap_token', function () {
    $shipment = new Shipment;
    $ignored = $shipment->getIgnoredAuditAttributes();

    expect($ignored)->toContain('xendit_invoice_id')
        ->and($ignored)->not->toContain('snap_token');
});
