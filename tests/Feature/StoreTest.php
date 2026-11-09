<?php

use App\Models\Store;
use App\Models\User;

test('authenticated user can view stores page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stores.index'));

    $response->assertOk();
});

test('user can create a store with valid biteship area', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('stores.store'), [
        'name' => 'Toko Surabaya Pusat',
        'phone' => '+6281234567890',
        'address' => 'Jl. Pemuda No. 10',
        'postal_code' => '60271',
        'biteship_area_id' => 'IDNP6IDNC148IDND1127IDZ60271',
        'area_name' => 'Embong Kaliasin, Genteng, Surabaya',
        'is_default' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('stores', [
        'user_id' => $user->id,
        'name' => 'Toko Surabaya Pusat',
        'biteship_area_id' => 'IDNP6IDNC148IDND1127IDZ60271',
        'is_default' => 1,
    ]);
});

test('ensures only one store can be marked as default at a time', function () {
    $user = User::factory()->create();

    $store1 = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Pertama',
        'phone' => '0811111111',
        'address' => 'Alamat 1',
        'biteship_area_id' => 'AREA_1',
        'is_default' => true,
    ]);

    $store2 = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Kedua',
        'phone' => '0822222222',
        'address' => 'Alamat 2',
        'biteship_area_id' => 'AREA_2',
        'is_default' => false,
    ]);

    $this->actingAs($user)->patch(route('stores.default', $store2));

    expect($store1->fresh()->is_default)->toBeFalse();
    expect($store2->fresh()->is_default)->toBeTrue();
});

test('user cannot update or delete store belonging to another user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $storeA = Store::create([
        'user_id' => $userA->id,
        'name' => 'Toko User A',
        'phone' => '0811111111',
        'address' => 'Alamat A',
        'biteship_area_id' => 'AREA_A',
        'is_default' => true,
    ]);

    $response = $this->actingAs($userB)->put(route('stores.update', $storeA), [
        'name' => 'Toko Dibajak',
        'phone' => '0811111111',
        'address' => 'Alamat A',
        'biteship_area_id' => 'AREA_A',
    ]);
    $response->assertForbidden();

    $deleteResponse = $this->actingAs($userB)->delete(route('stores.destroy', $storeA));
    $deleteResponse->assertForbidden();
});

test('authenticated user can view create store page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stores.create'));

    $response->assertOk();
});

test('authenticated user can view edit store page for their own store', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Saya',
        'phone' => '081234567890',
        'address' => 'Jl. Test No. 1',
        'biteship_area_id' => 'AREA_1',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->get(route('stores.edit', $store));

    $response->assertOk();
});

test('user cannot view edit store page of another user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $storeA = Store::create([
        'user_id' => $userA->id,
        'name' => 'Toko User A',
        'phone' => '081234567890',
        'address' => 'Jl. Test No. 1',
        'biteship_area_id' => 'AREA_A',
        'is_default' => true,
    ]);

    $response = $this->actingAs($userB)->get(route('stores.edit', $storeA));

    $response->assertForbidden();
});
