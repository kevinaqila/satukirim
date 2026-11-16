<?php

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Withdrawal;

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
        'payment_method' => 'cod',
        'item_value' => 101000,
        'recipient_name' => 'Siti',
        'recipient_phone' => '0888888888',
        'destination_address' => 'Bandung Kota',
        'destination_area_id' => 'AREA_BDG',
        'weight' => 500,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('isSuperAdmin', false)
        ->where('metrics.unpaid', 1)
        ->where('metrics.in_transit', 1)
        ->where('metrics.waiting_pickup', 0)
        ->where('metrics.delivered', 0)
        ->where('walletStats.available_balance', 0)
        ->where('walletStats.pending_balance', 100000)
        ->where('defaultStore.name', 'Toko Gudang Surabaya')
        ->where('storesCount', 1)
        ->has('courierPerformance', 2)
        ->has('recentShipments', 2)
    );
});

test('super admin sees platform shipment volume, payment breakdown, and withdrawal metrics', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $umkm = User::factory()->create(['role' => 'umkm']);

    $store = Store::create([
        'user_id' => $umkm->id,
        'name' => 'Toko Mitra A',
        'phone' => '08123456780',
        'address' => 'Jl. Basuki Rahmat No. 1',
        'biteship_area_id' => 'AREA_SBY',
        'is_default' => true,
    ]);

    Shipment::create([
        'user_id' => $umkm->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADM-001',
        'status' => 'delivered',
        'payment_method' => 'cod',
        'item_value' => 150000,
        'recipient_name' => 'Ani',
        'recipient_phone' => '0811111111',
        'destination_address' => 'Semarang',
        'destination_area_id' => 'AREA_SMG',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    Shipment::create([
        'user_id' => $umkm->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADM-002',
        'status' => 'pending_payment',
        'recipient_name' => 'Bambang',
        'recipient_phone' => '0822222222',
        'destination_address' => 'Yogyakarta',
        'destination_area_id' => 'AREA_YOG',
        'weight' => 1000,
        'courier_code' => 'sicepat',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'SiCepat REG',
        'shipping_fee' => 12000,
        'total_amount' => 13000,
    ]);

    Shipment::create([
        'user_id' => $umkm->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADM-003',
        'status' => 'cancelled',
        'recipient_name' => 'Cici',
        'recipient_phone' => '0833333333',
        'destination_address' => 'Malang',
        'destination_area_id' => 'AREA_MLG',
        'weight' => 1000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 10000,
        'total_amount' => 11000,
    ]);

    $umkmWallet = $umkm->getOrCreateWallet();
    Withdrawal::create([
        'wallet_id' => $umkmWallet->id,
        'user_id' => $umkm->id,
        'reference_number' => 'WD-TEST-001',
        'amount' => 50000,
        'status' => 'processing',
    ]);
    Withdrawal::create([
        'wallet_id' => $umkmWallet->id,
        'user_id' => $umkm->id,
        'reference_number' => 'WD-TEST-002',
        'amount' => 75000,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('isSuperAdmin', true)
        ->where('platformStats.cod_count', 1)
        ->where('platformStats.non_cod_count', 0)
        ->where('platformStats.cancelled_shipments', 1)
        ->where('platformStats.delivered_rate', 100)
        ->where('platformStats.pending_withdrawals_count', 1)
        ->where('platformStats.pending_withdrawals_amount', 50000)
        ->where('platformStats.completed_withdrawals_count', 1)
        ->where('platformStats.completed_withdrawals_amount', 75000)
        ->has('recentShipments', 3)
        ->has('umkmList', 1)
    );

    $trend = collect($response->viewData('page')['props']['sevenDayTrend']);
    expect($trend->sum('count'))->toBe(3);
});
