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

test('valid registration creates a pending tenant and shows the verification page', function () {
    $response = $this->post(route('register'), [
        'nik' => '3578010101990009',
        'name' => 'Citra Lestari',
        'phone' => '081234567899',
        'email' => 'citra@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('account.verification'));

    $user = User::where('email', 'citra@example.com')->first();
    expect($user->role)->toBe(User::ROLE_TENANT)
        ->and($user->verification_status)->toBe(User::STATUS_PENDING);
});

test('registration is rejected when NIK is not 16 digits', function () {
    $this->post(route('register'), [
        'nik' => '12345',
        'name' => 'Citra Lestari',
        'phone' => '081234567899',
        'email' => 'citra@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('nik');

    expect(User::where('email', 'citra@example.com')->exists())->toBeFalse();
});

test('registration cannot make the user an admin', function () {
    $this->post(route('register'), [
        'nik' => '3578010101990010',
        'name' => 'Penyusup',
        'phone' => '081234567800',
        'email' => 'penyusup@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
        'verification_status' => 'approved',
    ]);

    $user = User::where('email', 'penyusup@example.com')->first();
    expect($user->role)->toBe(User::ROLE_TENANT)
        ->and($user->verification_status)->toBe(User::STATUS_PENDING);
});

test('pending tenant is blocked and sent to the verification page', function () {
    $tenant = User::factory()->pending()->create();

    $this->actingAs($tenant)->get(route('my-rents'))->assertRedirect(route('account.verification'));
    $this->actingAs($tenant)->get(route('account.verification'))->assertOk()->assertSee('sedang diverifikasi');
});

test('rejected tenant sees the rejected message', function () {
    $tenant = User::factory()->rejected()->create();

    $this->actingAs($tenant)->get(route('account.verification'))->assertOk()->assertSee('ditolak');
});

test('approved tenant can open My Rents and starts there', function () {
    $tenant = User::factory()->create();

    $this->actingAs($tenant)->get('/')->assertRedirect(route('my-rents'));
    $this->actingAs($tenant)->get(route('my-rents'))->assertOk();
});

test('tenant cannot open admin pages', function () {
    $tenant = User::factory()->create();

    $this->actingAs($tenant)->get(route('admin.garages.index'))->assertForbidden();
});

test('admin starts on the garage list and cannot open tenant pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.garages.index'));
    $this->actingAs($admin)->get(route('admin.garages.index'))->assertOk();
    $this->actingAs($admin)->get(route('my-rents'))->assertForbidden();
});
