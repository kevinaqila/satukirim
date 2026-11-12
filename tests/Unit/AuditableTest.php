<?php

use App\Concerns\Auditable;
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
