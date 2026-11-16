<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->phone)->toBe('081234567890');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('bank settings page is displayed and payout bank account can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bank.edit'))
        ->assertOk();

    $response = $this
        ->actingAs($user)
        ->from(route('bank.edit'))
        ->patch(route('bank.update'), [
            'bank_code' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Kevin Aqila',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('bank.edit'));

    $user->refresh();
    expect($user->bank_code)->toBe('BCA');
    expect($user->bank_account_number)->toBe('1234567890');
    expect($user->bank_account_name)->toBe('Kevin Aqila');
});

test('bank settings rejects invalid bank code or non-numeric account number', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('bank.edit'))
        ->patch(route('bank.update'), [
            'bank_code' => 'UNKNOWN',
            'bank_account_number' => 'abc-123',
            'bank_account_name' => 'A',
        ]);

    $response->assertSessionHasErrors(['bank_code', 'bank_account_number', 'bank_account_name']);
});
