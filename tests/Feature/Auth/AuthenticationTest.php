<?php

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('umkm user is redirected to dashboard instead of intended admin url after login', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);

    $response = $this->withSession(['url.intended' => url('/admin/users')])
        ->post(route('login.store'), [
            'email' => $umkm->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($umkm);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('super admin user is redirected to intended admin url after login', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $response = $this->withSession(['url.intended' => url('/admin/users')])
        ->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(url('/admin/users'));
});

test('user is redirected to dashboard instead of another users shipment or store url after login', function () {
    $owner = User::factory()->create(['role' => 'umkm']);
    $otherUser = User::factory()->create(['role' => 'umkm']);

    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Toko Pemilik',
        'phone' => '08123456789',
        'address' => 'Jl. Melati No 1',
        'biteship_area_id' => 'AREA_1',
    ]);

    $shipment = Shipment::create([
        'user_id' => $owner->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-AUTH-01',
        'status' => 'pending_payment',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0812345678',
        'destination_address' => 'Alamat Tujuan',
        'destination_area_id' => 'AREA_2',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE REG',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    $shipmentResponse = $this->withSession(['url.intended' => route('shipments.show', $shipment)])
        ->post(route('login.store'), [
            'email' => $otherUser->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($otherUser);
    $shipmentResponse->assertRedirect(route('dashboard', absolute: false));

    $this->post(route('logout'));

    $storeResponse = $this->withSession(['url.intended' => route('stores.edit', $store)])
        ->post(route('login.store'), [
            'email' => $otherUser->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($otherUser);
    $storeResponse->assertRedirect(route('dashboard', absolute: false));
});

test('custom inertia error page is rendered for 404 and 403 responses', function () {
    $this->withoutVite();

    $notFoundResponse = $this->get('/halaman-yang-tidak-pernah-ada');
    $notFoundResponse->assertNotFound();
    $notFoundResponse->assertInertia(fn (Assert $page) => $page
        ->component('ErrorPage')
        ->where('status', 404)
    );

    $umkm = User::factory()->create(['role' => 'umkm']);

    $authNotFoundResponse = $this->actingAs($umkm)->get('/halaman-yang-tidak-pernah-ada');
    $authNotFoundResponse->assertNotFound();
    $authNotFoundResponse->assertInertia(fn (Assert $page) => $page
        ->component('ErrorPage')
        ->where('status', 404)
        ->where('auth.user.id', $umkm->id)
    );

    $forbiddenResponse = $this->actingAs($umkm)->get(route('admin.users'));
    $forbiddenResponse->assertForbidden();
    $forbiddenResponse->assertInertia(fn (Assert $page) => $page
        ->component('ErrorPage')
        ->where('status', 403)
    );
});

test('google oauth redirect handles missing config gracefully and redirects to google when configured', function () {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $missingResponse = $this->get(route('auth.google.redirect'));
    $missingResponse->assertRedirect(route('login'))
        ->assertSessionHasErrors(['google']);

    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');
    Socialite::fake('google');

    $redirectResponse = $this->get(route('auth.google.redirect'));
    $redirectResponse->assertRedirect();
});

test('google oauth callback registers new umkm user and links existing active user while blocking inactive user', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123456',
        'name' => 'Toko Baru Google',
        'email' => 'toko.google@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $registerResponse = $this->get(route('auth.google.callback'));
    $registerResponse->assertRedirect(route('dashboard', absolute: false));

    $createdUser = User::where('email', 'toko.google@example.com')->first();
    expect($createdUser)->not->toBeNull()
        ->and($createdUser->google_id)->toBe('google-123456')
        ->and($createdUser->role)->toBe('umkm')
        ->and($createdUser->is_active)->toBeTrue()
        ->and($createdUser->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($createdUser);

    $this->post(route('logout'));

    $inactiveUser = User::factory()->create([
        'email' => 'nonaktif@example.com',
        'is_active' => false,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-999999',
        'name' => 'User Nonaktif',
        'email' => 'nonaktif@example.com',
    ]));

    $blockedResponse = $this->get(route('auth.google.callback'));
    $blockedResponse->assertRedirect(route('login'))
        ->assertSessionHasErrors(['google']);
    $this->assertGuest();
});
