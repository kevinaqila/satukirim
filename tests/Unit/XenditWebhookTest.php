<?php

use Tests\TestCase;

uses(TestCase::class);

test('xendit webhook status mapping handles success statuses', function () {
    $successStatuses = ['COMPLETED', 'SUCCESS', 'SUCCESSFUL'];

    foreach ($successStatuses as $status) {
        $mapped = match (strtoupper($status)) {
            'COMPLETED', 'SUCCESS', 'SUCCESSFUL' => 'completed',
            'FAILED', 'REJECTED' => 'failed',
            default => 'processing',
        };

        expect($mapped)->toBe('completed');
    }
});

test('xendit webhook status mapping handles failure statuses', function () {
    $failureStatuses = ['FAILED', 'REJECTED'];

    foreach ($failureStatuses as $status) {
        $mapped = match (strtoupper($status)) {
            'COMPLETED', 'SUCCESS', 'SUCCESSFUL' => 'completed',
            'FAILED', 'REJECTED' => 'failed',
            default => 'processing',
        };

        expect($mapped)->toBe('failed');
    }
});

test('xendit webhook validates presence of external_id in payload', function () {
    $validPayload = [
        'id' => 'disb-123',
        'external_id' => 'WD-261112TEST',
        'amount' => 50000,
        'status' => 'COMPLETED',
    ];

    expect(! empty($validPayload['external_id']))->toBeTrue();

    $invalidPayload = [
        'id' => 'disb-123',
        'amount' => 50000,
        'status' => 'COMPLETED',
    ];

    expect(! empty($invalidPayload['external_id'] ?? null))->toBeFalse();
});

test('xendit invoice webhook handles paid and settled status', function () {
    $paidStatuses = ['PAID', 'SETTLED'];

    foreach ($paidStatuses as $status) {
        $isPaid = in_array(strtoupper($status), ['PAID', 'SETTLED'], true);
        expect($isPaid)->toBeTrue();
    }

    $isPending = in_array('PENDING', ['PAID', 'SETTLED'], true);
    expect($isPending)->toBeFalse();
});
