<?php

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
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

test('allows guest to check shipping rates with optional volumetric dimensions', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                [
                    'courier_name' => 'JNE',
                    'courier_service_name' => 'Reguler',
                    'price' => 38000,
                    'duration' => '1 - 2 hari',
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 0.5,
        'length' => 30,
        'width' => 25,
        'height' => 20,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    Http::assertSent(function ($request) {
        $item = $request['items'][0] ?? [];

        return ($item['weight'] ?? null) === 500
            && ($item['length'] ?? null) === 30
            && ($item['width'] ?? null) === 25
            && ($item['height'] ?? null) === 20;
    });
});

test('validates public rate dimensions must be filled together when provided', function () {
    $response = $this->postJson(route('public.rates'), [
        'origin_area_id' => 'IDnp60281',
        'destination_area_id' => 'IDnp10110',
        'weight' => 1,
        'length' => 30,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['width', 'height']);
});

test('public tracking returns safe satukirim shipment timeline without exposing pii', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Gudang Utama Surabaya',
        'phone' => '081234567890',
        'address' => 'Jl. Raya Manyar No. 42',
        'biteship_area_id' => 'IDnp60117',
        'area_name' => 'Sukolilo, Surabaya, Jawa Timur',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-0069JKD',
        'waybill_number' => 'WYB-1794841290984',
        'status' => 'picking_up',
        'recipient_name' => 'Farrel Ahmad',
        'recipient_phone' => '+62895383926013',
        'destination_address' => 'Jl. Rahasia No. 99',
        'destination_area_id' => 'IDnp60189',
        'destination_area_name' => 'Sukomanunggal, Surabaya, Jawa Timur',
        'weight' => 750,
        'courier_code' => 'jne',
        'courier_service_code' => 'yes',
        'courier_service_name' => 'JNE Yakin Esok Sampai (YES)',
        'shipping_fee' => 11000,
        'total_amount' => 12000,
        'estimated_delivery' => '1 - 1 days',
        'tracking_history' => [
            ['status' => 'confirmed', 'note' => 'Order terkonfirmasi', 'timestamp' => '2026-11-16T22:01:00+07:00'],
            ['status' => 'picking_up', 'note' => 'Kurir dalam penjemputan', 'timestamp' => '2026-11-16T22:28:00+07:00'],
        ],
    ]);

    $response = $this->getJson(route('public.track', [
        'courier' => 'jne',
        'waybill' => 'WYB-1794841290984',
    ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.source', 'satukirim')
        ->assertJsonPath('data.origin_store_name', 'Gudang Utama Surabaya')
        ->assertJsonPath('data.destination_area_name', 'Sukomanunggal, Surabaya, Jawa Timur')
        ->assertJsonPath('data.estimated_delivery', '1 - 1 days')
        ->assertJsonCount(2, 'data.history')
        ->assertJsonMissingPath('data.recipient_phone')
        ->assertJsonMissingPath('data.destination_address');
});
