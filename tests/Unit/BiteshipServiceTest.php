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
