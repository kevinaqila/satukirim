<?php

use Illuminate\Support\Facades\Http;

test('allows guest to search areas via mocked biteship', function () {
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

    $response = $this->getJson(route('public.areas', ['q' => 'Gubeng']));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'areas')
        ->assertJsonPath('areas.0.name', 'Gubeng');
});

test('allows guest to check shipping rates via mocked biteship', function () {
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

    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 1,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'pricing')
        ->assertJsonPath('pricing.0.courier_name', 'JNE');
});

test('validates weight must be greater than or equal to 0.1 kg', function () {
    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 0.05,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['weight']);
});

test('validates weight cannot exceed 10 kg', function () {
    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 10.5,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['weight'])
        ->assertJsonPath('errors.weight.0', 'Berat paket maksimal 10 kg untuk layanan parsel retail reguler/express.');
});

test('allows guest to check shipping rates with decimal weight within 10 kg', function () {
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
            ],
        ], 200),
    ]);

    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 0.5,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    Http::assertSent(fn ($request) => ($request['items'][0]['weight'] ?? null) === 500);
});
