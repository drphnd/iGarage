<?php

use App\Models\User;

/*
| Account access: login, registration, verification block, and role-based start pages.
*/

test('guest opening the home page is sent to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('login and register pages can be opened', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('valid registration creates a pending renter and shows the verification page', function () {
    $response = $this->post(route('register'), [
        'nik' => '3578010101990009',
        'name' => 'Citra Lestari',
        'phone' => '081234567899',
        'email' => 'citra@example.com',
        'password' => 'Rahasia#123',
        'password_confirmation' => 'Rahasia#123',
    ]);

    $response->assertRedirect(route('account.verification'));

    $user = User::where('email', 'citra@example.com')->first();
    expect($user->role)->toBe(User::ROLE_RENTER)
        ->and($user->verification_status)->toBe(User::STATUS_PENDING);
});

test('registration is rejected when NIK is not 16 digits', function () {
    $this->post(route('register'), [
        'nik' => '12345',
        'name' => 'Citra Lestari',
        'phone' => '081234567899',
        'email' => 'citra@example.com',
        'password' => 'Rahasia#123',
        'password_confirmation' => 'Rahasia#123',
    ])->assertSessionHasErrors('nik');

    expect(User::where('email', 'citra@example.com')->exists())->toBeFalse();
});

test('registration cannot make the user an admin', function () {
    $this->post(route('register'), [
        'nik' => '3578010101990010',
        'name' => 'Intruder',
        'phone' => '081234567800',
        'email' => 'intruder@example.com',
        'password' => 'Rahasia#123',
        'password_confirmation' => 'Rahasia#123',
        'role' => 'admin',
        'verification_status' => 'approved',
    ]);

    $user = User::where('email', 'intruder@example.com')->first();
    expect($user->role)->toBe(User::ROLE_RENTER)
        ->and($user->verification_status)->toBe(User::STATUS_PENDING);
});

test('pending renter is blocked and sent to the verification page', function () {
    $renter = User::factory()->pending()->create();

    $this->actingAs($renter)->get(route('my-rents'))->assertRedirect(route('account.verification'));
    $this->actingAs($renter)->get(route('account.verification'))->assertOk()->assertSee('being verified');
});

test('rejected renter sees the rejected message', function () {
    $renter = User::factory()->rejected()->create();

    $this->actingAs($renter)->get(route('account.verification'))->assertOk()->assertSee('was rejected');
});

test('approved renter can open My Rents and starts there', function () {
    $renter = User::factory()->create();

    $this->actingAs($renter)->get('/')->assertRedirect(route('my-rents'));
    $this->actingAs($renter)->get(route('my-rents'))->assertOk();
});

test('renter cannot open admin pages', function () {
    $renter = User::factory()->create();

    $this->actingAs($renter)->get(route('admin.garages.index'))->assertForbidden();
});

test('admin starts on the garage list and cannot open renter pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.garages.index'));
    $this->actingAs($admin)->get(route('admin.garages.index'))->assertOk();
    $this->actingAs($admin)->get(route('my-rents'))->assertForbidden();
});
