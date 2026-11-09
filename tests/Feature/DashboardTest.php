<?php

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard with live metrics', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Gudang Surabaya',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No. 5',
        'biteship_area_id' => 'AREA_SBY',
        'is_default' => true,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-001',
        'status' => 'pending_payment',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0899999999',
        'destination_address' => 'Jakarta Barat',
        'destination_area_id' => 'AREA_JKT',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 18000,
        'total_amount' => 18000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-002',
        'status' => 'in_transit',
        'recipient_name' => 'Siti',
        'recipient_phone' => '0888888888',
        'destination_address' => 'Bandung Kota',
        'destination_area_id' => 'AREA_BDG',
        'weight' => 500,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('metrics.unpaid', 1)
        ->where('metrics.in_transit', 1)
        ->where('metrics.waiting_pickup', 0)
        ->where('metrics.delivered', 0)
        ->where('defaultStore.name', 'Toko Gudang Surabaya')
        ->where('storesCount', 1)
        ->has('recentShipments', 2)
    );
});
