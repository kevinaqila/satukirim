<?php

use App\Models\Shipment;
use App\Services\XenditService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('xendit service reports configured status correctly', function () {
    $configuredService = new XenditService(apiKey: 'xnd_test_key_123');
    expect($configuredService->isConfigured())->toBeTrue();

    $unconfiguredService = new XenditService(apiKey: '');
    expect($unconfiguredService->isConfigured())->toBeFalse();
});

test('xendit service handles unconfigured key gracefully during disbursement', function () {
    $service = new XenditService(apiKey: '');

    $result = $service->createDisbursement(
        externalId: 'WD-TEST001',
        bankCode: 'BCA',
        accountHolderName: 'FARREL AHMAD',
        accountNumber: '1234567890',
        amount: 50000
    );

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('FAILED')
        ->and($result['message'])->toContain('belum dikonfigurasi');
});

test('xendit service creates disbursement successfully with proper payload and basic auth', function () {
    Http::fake([
        'api.xendit.co/disbursements' => function ($request) {
            $data = json_decode($request->body(), true);

            expect($request->header('Authorization')[0])->toContain('Basic')
                ->and($request->header('X-IDEMPOTENCY-KEY')[0])->toBe('WD-261112001')
                ->and($data['external_id'])->toBe('WD-261112001')
                ->and($data['bank_code'])->toBe('BCA')
                ->and($data['account_holder_name'])->toBe('FARREL AHMAD')
                ->and($data['account_number'])->toBe('1234567890')
                ->and($data['amount'])->toBe(50000);

            return Http::response([
                'id' => 'disb-test-999',
                'external_id' => 'WD-261112001',
                'amount' => 50000,
                'bank_code' => 'BCA',
                'account_holder_name' => 'FARREL AHMAD',
                'status' => 'PENDING',
                'created' => '2026-11-12T04:00:00.000Z',
            ], 200);
        },
    ]);

    $service = new XenditService(apiKey: 'xnd_development_dummy_key');

    $result = $service->createDisbursement(
        externalId: 'WD-261112001',
        bankCode: 'BCA',
        accountHolderName: 'FARREL AHMAD',
        accountNumber: '1234567890',
        amount: 50000,
        description: 'Tarik Saldo Logistikku WD-261112001'
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('PENDING')
        ->and($result['id'])->toBe('disb-test-999');
});

test('xendit service handles failed response from api', function () {
    Http::fake([
        'api.xendit.co/disbursements' => Http::response([
            'error_code' => 'INSUFFICIENT_BALANCE',
            'message' => 'Deposit balance is insufficient to disburse.',
        ], 400),
    ]);

    $service = new XenditService(apiKey: 'xnd_development_dummy_key');

    $result = $service->createDisbursement(
        externalId: 'WD-261112002',
        bankCode: 'BCA',
        accountHolderName: 'FARREL AHMAD',
        accountNumber: '1234567890',
        amount: 50000000
    );

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('FAILED')
        ->and($result['message'])->toContain('Deposit balance is insufficient');
});

test('xendit service returns supported banks', function () {
    $banks = XenditService::getSupportedBanks();

    expect($banks)->toBeArray()
        ->toHaveKey('BCA')
        ->toHaveKey('MANDIRI')
        ->toHaveKey('BRI')
        ->toHaveKey('BNI')
        ->toHaveKey('BSI')
        ->toHaveKey('JAGO');
});

test('xendit service creates invoice successfully', function () {
    Http::fake([
        'api.xendit.co/v2/invoices' => function ($request) {
            $data = json_decode($request->body(), true);

            expect($request->header('Authorization')[0])->toContain('Basic')
                ->and($data['external_id'])->toBe('STK-TEST-INV')
                ->and($data['amount'])->toBe(25000);

            return Http::response([
                'id' => 'inv-test-123',
                'external_id' => 'STK-TEST-INV',
                'status' => 'PENDING',
                'amount' => 25000,
                'invoice_url' => 'https://checkout-staging.xendit.co/v2/inv-test-123',
            ], 200);
        },
    ]);

    $service = new XenditService(apiKey: 'xnd_development_dummy_key');

    $shipment = new Shipment([
        'order_number' => 'STK-TEST-INV',
        'shipping_fee' => 24000,
        'total_amount' => 25000,
        'courier_service_name' => 'J&T EZ',
    ]);
    $shipment->id = 999;

    $result = $service->createInvoice($shipment);

    expect($result['success'])->toBeTrue()
        ->and($result['id'])->toBe('inv-test-123')
        ->and($result['invoice_url'])->toBe('https://checkout-staging.xendit.co/v2/inv-test-123');
});
