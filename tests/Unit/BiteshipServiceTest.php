<?php

use App\Services\BiteshipService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('biteship service can search areas', function () {
    Http::fake([
        'api.biteship.com/v1/maps/areas*' => Http::response([
            'success' => true,
            'areas' => [
                [
                    'id' => 'IDnp60281',
                    'name' => 'Gubeng',
                    'administrative_division_level_1_name' => 'Jawa Timur',
                    'administrative_division_level_2_name' => 'Kota Surabaya',
                    'administrative_division_level_3_name' => 'Gubeng',
                    'postal_code' => 60281,
                ],
            ],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $results = $service->searchAreas('Gubeng');

    expect($results)->toBeArray()
        ->toHaveCount(1)
        ->and($results[0]['name'])->toBe('Gubeng')
        ->and($results[0]['id'])->toBe('IDnp60281');
});

test('biteship service returns empty array on short input', function () {
    $service = new BiteshipService(apiKey: 'dummy-key');
    $results = $service->searchAreas('a');

    expect($results)->toBeArray()->toBeEmpty();
});

test('biteship service can check rates between two areas', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                [
                    'courier_name' => 'JNE',
                    'courier_service_name' => 'Reguler',
                    'price' => 19000,
                    'duration' => '1 - 2 hari',
                ],
                [
                    'courier_name' => 'SiCepat',
                    'courier_service_name' => 'SIUNTUNG',
                    'price' => 17500,
                    'duration' => '1 - 2 hari',
                ],
            ],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->checkRates('IDnp60281', 'IDnp10110', 1000);

    expect($result['success'])->toBeTrue()
        ->and($result['pricing'])->toHaveCount(2)
        ->and($result['pricing'][0]['courier_name'])->toBe('JNE')
        ->and($result['pricing'][0]['price'])->toBe(19000);
});

test('biteship service filters out cargo and instant courier services', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                [
                    'courier_name' => 'JNE',
                    'courier_service_name' => 'Reguler',
                    'courier_service_code' => 'reg',
                    'type' => 'standard',
                    'price' => 19000,
                ],
                [
                    'courier_name' => 'JNE',
                    'courier_service_name' => 'JTR Cargo',
                    'courier_service_code' => 'jtr',
                    'type' => 'cargo',
                    'price' => 35000,
                ],
                [
                    'courier_name' => 'SiCepat',
                    'courier_service_name' => 'GOKIL Cargo',
                    'courier_service_code' => 'gokil',
                    'type' => 'cargo',
                    'price' => 30000,
                ],
                [
                    'courier_name' => 'Gojek',
                    'courier_service_name' => 'Instant Bike',
                    'courier_service_code' => 'instant',
                    'type' => 'instant',
                    'price' => 25000,
                ],
            ],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->checkRates('IDnp60281', 'IDnp10110', 1000);

    expect($result['success'])->toBeTrue()
        ->and($result['pricing'])->toHaveCount(1)
        ->and($result['pricing'][0]['courier_service_code'])->toBe('reg');
});

test('biteship service clamps weight payload between 100g and 10000g', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => function ($request) {
            $data = $request->data();
            $weight = $data['items'][0]['weight'];

            return Http::response([
                'success' => true,
                'pricing' => [],
                'clamped_weight' => $weight,
            ], 200);
        },
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');

    $service->checkRates('IDnp60281', 'IDnp10110', 15000);
    Http::assertSent(fn ($request) => $request->data()['items'][0]['weight'] === 10000);

    $service->checkRates('IDnp60281', 'IDnp10110', 50);
    Http::assertSent(fn ($request) => $request->data()['items'][0]['weight'] === 100);
});

test('biteship service handles tracking response', function () {
    Http::fake([
        'api.biteship.com/v1/trackings/JT123456/couriers/jnt' => Http::response([
            'success' => true,
            'status' => 'on_process',
            'history' => [
                ['note' => 'Paket telah diterima drop point', 'status' => 'drop_off'],
            ],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->trackWaybill('JT123456', 'jnt');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['status'])->toBe('on_process');
});

test('biteship service can create order and returns order id and waybill', function () {
    Http::fake([
        'api.biteship.com/v1/orders' => Http::response([
            'success' => true,
            'message' => 'Order created successfully',
            'id' => 'biteship_order_test_999',
            'status' => 'allocated',
            'courier' => [
                'tracking_id' => 'TRK123456789',
                'waybill_id' => 'JNE0987654321',
                'company' => 'jne',
                'type' => 'reg',
            ],
            'price' => 18000,
        ], 200),
    ]);

    $shipment = new \App\Models\Shipment([
        'order_number' => 'SATU-TEST-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'weight' => 1000,
        'item_value' => 50000,
        'payment_method' => 'cod',
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->createOrder($shipment);

    expect($result['success'])->toBeTrue()
        ->and($result['biteship_order_id'])->toBe('biteship_order_test_999')
        ->and($result['waybill_number'])->toBe('JNE0987654321');

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $request->url() === 'https://api.biteship.com/v1/orders'
            && $data['courier_company'] === 'jne'
            && isset($data['cash_on_delivery'])
            && $data['cash_on_delivery']['amount'] === 50000;
    });
});

test('biteship service automatically retries when schedule time has passed', function () {
    $callCount = 0;
    Http::fake([
        'api.biteship.com/v1/orders' => function ($request) use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'success' => false,
                    'error' => 'Time already passed. Set new delivery time.',
                    'code' => 40002013,
                ], 400);
            }

            return Http::response([
                'success' => true,
                'id' => 'biteship_order_retried_123',
                'courier' => [
                    'waybill_id' => 'RETRY-AWB-999',
                ],
            ], 200);
        },
    ]);

    $shipment = new \App\Models\Shipment([
        'order_number' => 'SATU-RETRY-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'weight' => 1000,
        'item_value' => 50000,
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->createOrder($shipment);

    expect($result['success'])->toBeTrue()
        ->and($result['biteship_order_id'])->toBe('biteship_order_retried_123')
        ->and($result['waybill_number'])->toBe('RETRY-AWB-999')
        ->and($callCount)->toBe(2);
});

test('biteship service automatically falls back to now when courier does not support scheduled delivery', function () {
    $callCount = 0;
    Http::fake([
        'api.biteship.com/v1/orders' => function ($request) use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'success' => false,
                    'error' => 'Courier is not available for scheduled delivery',
                    'code' => 40002007,
                ], 400);
            }

            return Http::response([
                'success' => true,
                'id' => 'biteship_order_fallback_now_456',
                'courier' => [
                    'waybill_id' => 'NOW-AWB-777',
                ],
            ], 200);
        },
    ]);

    $shipment = new \App\Models\Shipment([
        'order_number' => 'SATU-NOW-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'yes',
        'weight' => 1000,
        'item_value' => 50000,
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->createOrder($shipment);

    expect($result['success'])->toBeTrue()
        ->and($result['biteship_order_id'])->toBe('biteship_order_fallback_now_456')
        ->and($result['waybill_number'])->toBe('NOW-AWB-777')
        ->and($callCount)->toBe(2);
});
