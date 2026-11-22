<?php

use App\Models\Shipment;
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

test('biteship service correctly identifies courier cod availability', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                [
                    'courier_name' => 'JNE',
                    'courier_code' => 'jne',
                    'courier_service_name' => 'Reguler',
                    'courier_service_code' => 'reg',
                    'type' => 'standard',
                    'price' => 19000,
                    'available_for_cash_on_delivery' => true,
                ],
                [
                    'courier_name' => 'Ninja Van',
                    'courier_code' => 'ninja',
                    'courier_service_name' => 'Standard',
                    'courier_service_code' => 'standard',
                    'type' => 'standard',
                    'price' => 18000,
                    'available_for_cash_on_delivery' => false,
                ],
                [
                    'courier_name' => 'Lion Parcel',
                    'courier_code' => 'lion',
                    'courier_service_name' => 'REGPACK',
                    'courier_service_code' => 'regpack',
                    'type' => 'standard',
                    'price' => 17000,
                    'available_for_cash_on_delivery' => true,
                ],
            ],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->checkRates('IDnp60281', 'IDnp10110', 1000, codAmount: 75000);

    expect($result['success'])->toBeTrue()
        ->and($result['pricing'])->toHaveCount(3)
        ->and($result['pricing'][0]['available_for_cash_on_delivery'])->toBeTrue()
        ->and($result['pricing'][1]['available_for_cash_on_delivery'])->toBeFalse()
        ->and($result['pricing'][2]['available_for_cash_on_delivery'])->toBeFalse();

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['destination_cash_on_delivery'] === 75000
            && $data['destination_cash_on_delivery_type'] === '7_days';
    });
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

test('biteship service includes optional dimensions in check rates and omits when incomplete', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [],
        ], 200),
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');

    $service->checkRates('IDnp60281', 'IDnp10110', 1000, length: 30, width: 20, height: 15);
    Http::assertSent(function ($request) {
        $item = $request->data()['items'][0] ?? [];

        return ($item['length'] ?? null) === 30
            && ($item['width'] ?? null) === 20
            && ($item['height'] ?? null) === 15;
    });

    $service->checkRates('IDnp60281', 'IDnp10110', 1000, length: 30, width: null, height: 15);
    Http::assertSent(function ($request) {
        $item = $request->data()['items'][0] ?? [];

        return ! array_key_exists('length', $item)
            && ! array_key_exists('width', $item)
            && ! array_key_exists('height', $item);
    });
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

    $shipment = new Shipment([
        'order_number' => 'SATU-TEST-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'weight' => 1000,
        'item_value' => 50000,
        'shipping_fee' => 19000,
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
            && isset($data['destination_cash_on_delivery'])
            && $data['destination_cash_on_delivery'] === 69000
            && ($data['destination_cash_on_delivery_type'] ?? '') === '7_days';
    });
});

test('biteship service maps multiple items in shipment payload correctly', function () {
    Http::fake([
        'api.biteship.com/v1/orders' => Http::response([
            'success' => true,
            'id' => 'biteship_multi_item_123',
            'courier' => [
                'waybill_id' => 'JNE-MULTI-999',
            ],
        ], 200),
    ]);

    $shipment = new Shipment([
        'order_number' => 'SATU-MULTI-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'weight' => 750,
        'item_value' => 225000,
        'shipping_fee' => 19000,
        'payment_method' => 'cod',
        'items' => [
            [
                'name' => 'Kemeja Flanel',
                'qty' => 2,
                'weight' => 250,
                'value' => 75000,
            ],
            [
                'name' => 'Topi Snapback',
                'qty' => 1,
                'weight' => 250,
                'value' => 75000,
            ],
        ],
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->createOrder($shipment);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        $data = $request->data();

        return count($data['items']) === 2
            && $data['items'][0]['name'] === 'Kemeja Flanel'
            && $data['items'][0]['quantity'] === 2
            && $data['items'][1]['name'] === 'Topi Snapback'
            && $data['items'][1]['quantity'] === 1;
    });
});

test('biteship service maps optional item dimensions in create order payload', function () {
    Http::fake([
        'api.biteship.com/v1/orders' => Http::response([
            'success' => true,
            'id' => 'biteship_dim_order_1',
            'courier' => ['waybill_id' => 'JNE-DIM-001'],
        ], 200),
    ]);

    $shipment = new Shipment([
        'order_number' => 'SATU-DIM-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Jl. Gubeng',
        'destination_area_id' => 'IDnp60281',
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'weight' => 500,
        'item_value' => 100000,
        'shipping_fee' => 19000,
        'payment_method' => 'non_cod',
        'items' => [
            [
                'name' => 'Dus Keripik',
                'qty' => 1,
                'weight' => 500,
                'value' => 100000,
                'length' => 30,
                'width' => 25,
                'height' => 20,
            ],
        ],
    ]);

    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');
    $result = $service->createOrder($shipment);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        $item = $request->data()['items'][0] ?? [];

        return ($item['length'] ?? null) === 30
            && ($item['width'] ?? null) === 25
            && ($item['height'] ?? null) === 20;
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

    $shipment = new Shipment([
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

    $shipment = new Shipment([
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

test('biteship service maps all official biteship statuses consistently', function () {
    $service = new BiteshipService(apiKey: 'dummy-key', baseUrl: 'https://api.biteship.com');

    expect($service->mapStatus('confirmed'))->toBe('confirmed')
        ->and($service->mapStatus('scheduled'))->toBe('confirmed')
        ->and($service->mapStatus('allocated'))->toBe('allocated')
        ->and($service->mapStatus('pickingUp'))->toBe('picking_up')
        ->and($service->mapStatus('picked'))->toBe('picked')
        ->and($service->mapStatus('in_transit'))->toBe('in_transit')
        ->and($service->mapStatus('on_process'))->toBe('in_transit')
        ->and($service->mapStatus('droppingOff'))->toBe('dropping_off')
        ->and($service->mapStatus('delivered'))->toBe('delivered')
        ->and($service->mapStatus('onHold'))->toBe('on_hold')
        ->and($service->mapStatus('return_in_transit'))->toBe('returned')
        ->and($service->mapStatus('returned'))->toBe('returned')
        ->and($service->mapStatus('cancelled'))->toBe('cancelled')
        ->and($service->mapStatus('rejected'))->toBe('rejected')
        ->and($service->mapStatus('courier_not_found'))->toBe('courier_not_found')
        ->and($service->mapStatus('disposed'))->toBe('disposed');
});
