<?php

use App\Handlers\AuditHandler;

test('it formats audit attributes into readable indonesian labels', function () {
    $raw = [
        'status' => 'in_transit',
        'waybill_number' => 'JNE1234567890',
        'shipping_fee' => 15000,
        'total_amount' => 20000,
        'weight' => 1200,
        'courier_code' => 'jne',
        'is_active' => true,
    ];

    $formatted = AuditHandler::format($raw);

    expect($formatted)->toContain('Status: Diantar')
        ->toContain('AWB: JNE1234567890')
        ->toContain('Ongkos Kirim: Rp 15.000')
        ->toContain('Total Tagihan: Rp 20.000')
        ->toContain('Berat: 1.2 kg')
        ->toContain('Kurir: JNE')
        ->toContain('Status Akun: Aktif');
});

test('it handles empty or null values gracefully', function () {
    expect(AuditHandler::format(null))->toBe([])
        ->and(AuditHandler::format([]))->toBe([]);
});

