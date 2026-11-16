<?php

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can view wallet page with accurate cod balances', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Saldo COD',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'AREA_1',
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-COD-001',
        'status' => 'delivered',
        'payment_method' => 'cod',
        'paid_at' => now(),
        'recipient_name' => 'Pembeli 1',
        'recipient_phone' => '0811111111',
        'destination_address' => 'Alamat 1',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'item_value' => 150000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-COD-002',
        'status' => 'in_transit',
        'payment_method' => 'cod',
        'paid_at' => now(),
        'recipient_name' => 'Pembeli 2',
        'recipient_phone' => '0822222222',
        'destination_address' => 'Alamat 2',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'item_value' => 200000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 12000,
        'total_amount' => 12000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-NONCOD-003',
        'status' => 'delivered',
        'payment_method' => 'qris',
        'paid_at' => now(),
        'recipient_name' => 'Pembeli Non-COD',
        'recipient_phone' => '0833333333',
        'destination_address' => 'Alamat 3',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'item_value' => 300000,
        'courier_code' => 'sicepat',
        'courier_service_code' => 'siunt',
        'courier_service_name' => 'SiCepat Untung',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    $response = $this->actingAs($user)->get(route('wallet.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Wallet/Index')
        ->where('availableBalance', 149000)
        ->where('pendingBalance', 199000)
        ->where('completedCount', 1)
        ->where('pendingCount', 1)
        ->has('codShipments.data', 2)
        ->where('wallet.available_balance', 149000)
        ->where('wallet.pending_balance', 199000)
    );

    $wallet = $user->fresh()->wallet;
    expect($wallet)->not->toBeNull();
    expect($wallet->available_balance)->toBe(149000);
    expect($wallet->pending_balance)->toBe(199000);
});

test('unauthenticated user cannot view wallet page', function () {
    $response = $this->get(route('wallet.index'));
    $response->assertRedirect(route('login'));
});

test('wallet page supports full withdrawal pagination and cod or withdrawal filters', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Filter Wallet',
        'phone' => '08123456789',
        'address' => 'Jl. Basuki Rahmat',
        'biteship_area_id' => 'AREA_1',
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-COD-SETTLED',
        'status' => 'delivered',
        'payment_method' => 'cod',
        'recipient_name' => 'Ani',
        'recipient_phone' => '0811111111',
        'destination_address' => 'Surabaya',
        'destination_area_id' => 'AREA_SBY',
        'weight' => 1000,
        'item_value' => 120000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SK-COD-PENDING',
        'status' => 'in_transit',
        'payment_method' => 'cod',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0822222222',
        'destination_address' => 'Malang',
        'destination_area_id' => 'AREA_MLG',
        'weight' => 1000,
        'item_value' => 80000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 12000,
        'total_amount' => 12000,
    ]);

    $wallet = $user->getOrCreateWallet()->syncBalances();

    for ($i = 1; $i <= 7; $i++) {
        Withdrawal::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'reference_number' => "WD-TEST-00{$i}",
            'amount' => 15000,
            'status' => $i <= 5 ? 'completed' : 'processing',
            'notes' => 'Transfer ke Bank Central Asia (BCA) - 1234567890 a/n Seller',
        ]);
    }

    $this->actingAs($user)
        ->get(route('wallet.index', ['tab' => 'withdrawals', 'wd_status' => 'completed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wallet/Index')
            ->where('filters.tab', 'withdrawals')
            ->where('withdrawalsCount', 7)
            ->has('withdrawals.data', 5)
            ->has('codShipments.data', 2)
        );

    $this->actingAs($user)
        ->get(route('wallet.index', ['cod_status' => 'settled', 'cod_search' => 'SETTLED']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wallet/Index')
            ->has('codShipments.data', 1)
            ->where('codShipments.data.0.order_number', 'SK-COD-SETTLED')
        );
});
