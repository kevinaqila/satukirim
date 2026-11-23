<?php

use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withoutVite();
});

test('umkm user cannot access admin users list', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);

    $response = $this->actingAs($umkm)->get(route('admin.users'));

    $response->assertForbidden();
});

test('super admin can view users and toggle active status', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $merchant = User::factory()->create(['role' => 'umkm', 'is_active' => true]);

    $response = $this->actingAs($admin)->get(route('admin.users'));
    $response->assertOk();

    $toggleResponse = $this->actingAs($admin)->patch(route('admin.users.toggle', $merchant));
    $toggleResponse->assertRedirect();

    expect($merchant->fresh()->is_active)->toBeFalse();
});

test('inactive user is blocked from logging in', function () {
    $inactiveUser = User::factory()->create([
        'email' => 'inactive@satukirim.id',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'inactive@satukirim.id',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('super admin can access and inspect any user shipment detail', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $umkm = User::factory()->create(['role' => 'umkm']);
    $otherUmkm = User::factory()->create(['role' => 'umkm']);

    $store = Store::create([
        'user_id' => $umkm->id,
        'name' => 'Toko UMKM Budi',
        'phone' => '08123456789',
        'address' => 'Jl. Mawar No 1',
        'biteship_area_id' => 'AREA_1',
    ]);

    $shipment = Shipment::create([
        'user_id' => $umkm->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-ADMIN-SHOW',
        'status' => 'paid',
        'recipient_name' => 'Penerima Budi',
        'recipient_phone' => '08129876543',
        'destination_address' => 'Jl. Melati No 2',
        'destination_area_id' => 'AREA_2',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    $forbiddenResponse = $this->actingAs($otherUmkm)->get(route('shipments.show', $shipment));
    $forbiddenResponse->assertForbidden();

    $adminResponse = $this->actingAs($admin)->get(route('shipments.show', $shipment));
    $adminResponse->assertOk();
});

test('umkm user cannot access admin audit logs', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);

    $response = $this->actingAs($umkm)->get(route('admin.audit-logs'));

    $response->assertForbidden();
});

test('super admin can access audit logs page', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($admin)->get(route('admin.audit-logs'));

    $response->assertOk();
});

test('umkm user cannot access admin shipments export all', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);

    $response = $this->actingAs($umkm)->getJson(route('admin.shipments.export-all'));

    $response->assertForbidden();
});

test('super admin can export all shipments as json', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'umkm']);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Admin Export',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADM-EXP-1',
        'status' => 'paid',
        'recipient_name' => 'Kiki',
        'recipient_phone' => '08123456781',
        'destination_address' => 'Alamat 1',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADM-EXP-2',
        'status' => 'pending_payment',
        'recipient_name' => 'Lala',
        'recipient_phone' => '08123456782',
        'destination_address' => 'Alamat 2',
        'destination_area_id' => 'AREA_2',
        'weight' => 1000,
        'courier_code' => 'sicepat',
        'courier_service_code' => 'siuntung',
        'courier_service_name' => 'SiCepat SIUNTUNG',
        'shipping_fee' => 12000,
        'total_amount' => 12000,
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.shipments.export-all', [
        'status' => 'paid',
    ]));

    $response->assertOk();
    $data = $response->json();
    expect($data)->toHaveKey('transactions');
    expect(count($data['transactions']))->toBe(1);
    expect($data['transactions'][0]['order_number'])->toBe('SATU-ADM-EXP-1');
});

test('umkm user cannot access create user page or store user', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);

    $this->actingAs($umkm)
        ->get(route('admin.users.create'))
        ->assertForbidden();

    $this->actingAs($umkm)
        ->post(route('admin.users.store'), [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.com',
            'role' => 'umkm',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertForbidden();
});

test('super admin can render create user page', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk();
});

test('super admin can create umkm user with normalized phone, wallet, and audit log', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Toko Baru UMKM',
        'email' => 'tokobaru@example.com',
        'phone' => '081234567890',
        'role' => 'umkm',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('admin.users'));

    $createdUser = User::where('email', 'tokobaru@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->name)->toBe('Toko Baru UMKM');
    expect($createdUser->phone)->toBe('+6281234567890');
    expect($createdUser->role)->toBe('umkm');
    expect($createdUser->is_active)->toBeTrue();
    expect(Hash::check('Password123!', $createdUser->password))->toBeTrue();
    expect($createdUser->wallet)->not->toBeNull();

    $audit = AuditLog::where('auditable_type', $createdUser->getMorphClass())
        ->where('auditable_id', $createdUser->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->new_values)->not->toHaveKey('password');
});

test('super admin can create super admin user without wallet', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Admin Tambahan',
        'email' => 'admin2@example.com',
        'phone' => '',
        'role' => 'super_admin',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('admin.users'));

    $createdUser = User::where('email', 'admin2@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->role)->toBe('super_admin');
    expect($createdUser->phone)->toBeNull();
    expect($createdUser->is_active)->toBeTrue();
    expect($createdUser->wallet)->toBeNull();
});

test('super admin store user validates duplicate email, invalid role, and mismatched password', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Invalid User',
        'email' => 'existing@example.com',
        'role' => 'manager',
        'password' => 'Password123!',
        'password_confirmation' => 'Different123!',
    ]);

    $response->assertSessionHasErrors(['email', 'role', 'password']);
});
