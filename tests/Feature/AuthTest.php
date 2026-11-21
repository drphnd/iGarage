<?php

use App\Models\User;

/*
| Login, logout and the registration rules for password, email and name.
*/

/** Valid registration data. Pass overrides to change any field. */
function registerPayload(array $overrides = []): array
{
    return array_merge([
        'nik' => '3578010101990009',
        'name' => 'Citra Lestari',
        'phone' => '081234567899',
        'email' => 'citra@example.com',
        'password' => 'Rahasia#123',
        'password_confirmation' => 'Rahasia#123',
    ], $overrides);
}

test('a user can log in with the right email and password', function () {
    $user = User::factory()->create(['email' => 'budi@example.com']);   // factory password: "password"

    $this->post(route('login'), ['email' => 'budi@example.com', 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('login ignores uppercase letters and spaces in the email', function () {
    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->post(route('login'), ['email' => ' Budi@Example.COM ', 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

test('a wrong password shows one general error and does not log in', function () {
    User::factory()->create(['email' => 'budi@example.com']);

    $this->post(route('login'), ['email' => 'budi@example.com', 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

    $this->assertGuest();
});

test('login is blocked for a minute after 5 failed attempts', function () {
    User::factory()->create(['email' => 'budi@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login'), ['email' => 'budi@example.com', 'password' => 'wrong']);
    }

    $this->post(route('login'), ['email' => 'budi@example.com', 'password' => 'password'])
        ->assertStatus(429);   // Too Many Requests

    $this->assertGuest();
});

test('logout ends the session and returns to the login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a weak password is rejected', function (string $password) {
    $this->post(route('register'), registerPayload([
        'password' => $password,
        'password_confirmation' => $password,
    ]))->assertSessionHasErrors('password');

    $this->assertDatabaseCount('users', 0);
})->with([
    'too short' => ['Ab#1'],
    'no uppercase' => ['rahasia#123'],
    'no lowercase' => ['RAHASIA#123'],
    'no number' => ['Rahasia#abc'],
    'no symbol' => ['Rahasia1234'],
    'too long' => ['Aa#1' . str_repeat('x', 61)],
]);

test('the password confirmation must match', function () {
    $this->post(route('register'), registerPayload(['password_confirmation' => 'Rahasia#124']))
        ->assertSessionHasErrors('password');

    $this->assertDatabaseCount('users', 0);
});

test('an incomplete email address is rejected', function (string $email) {
    $this->post(route('register'), registerPayload(['email' => $email]))
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('users', 0);
})->with([
    'no domain ending' => ['citra@gmail'],
    'no @' => ['citra.gmail.com'],
    'two @' => ['citra@@gmail.com'],
    'no name' => ['@gmail.com'],
]);

test('the email is saved in lowercase, so the same address cannot register twice', function () {
    $this->post(route('register'), registerPayload(['email' => 'Citra@Example.COM']))
        ->assertSessionHasNoErrors();

    expect(User::first()->email)->toBe('citra@example.com');

    auth()->logout();

    $this->post(route('register'), registerPayload(['nik' => '3578010101990010', 'email' => 'CITRA@example.com']))
        ->assertSessionHasErrors(['email' => 'This email is already registered.']);
});

test('a name with numbers or symbols is rejected', function () {
    $this->post(route('register'), registerPayload(['name' => 'B0di!!!']))
        ->assertSessionHasErrors('name');

    $this->post(route('register'), registerPayload(['name' => "Siti Nur'aini A. Putri-Sari"]))
        ->assertSessionHasNoErrors();
});

test('admin can open the create garages page and a renter can open the rent form', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.garages.create'))
        ->assertOk()
        ->assertSee('Next code');

    $this->actingAs(User::factory()->create())->get(route('rents.create'))
        ->assertOk()
        ->assertSee('Check Available Garages');
});
