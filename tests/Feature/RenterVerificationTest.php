<?php

use App\Models\User;

/*
| Admin verifies renter registrations: approve or reject, only while pending.
*/

test('admin sees pending renters on the verification page', function () {
    $admin = User::factory()->admin()->create();
    $pending = User::factory()->pending()->create(['name' => 'Adi Pratama']);
    $approved = User::factory()->create(['name' => 'Budi Santoso']);

    $this->actingAs($admin)->get(route('admin.renters.index'))
        ->assertOk()
        ->assertSee('Adi Pratama')
        ->assertDontSee('Budi Santoso');   // default filter shows only pending renters
});

test('admin can approve a pending renter, who can then open My Rents', function () {
    $admin = User::factory()->admin()->create();
    $renter = User::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.renters.approve', $renter))
        ->assertSessionHas('success');

    expect($renter->fresh()->verification_status)->toBe(User::STATUS_APPROVED);

    $this->actingAs($renter->fresh())->get(route('my-rents'))->assertOk();
});

test('admin can reject a pending renter, who stays blocked', function () {
    $admin = User::factory()->admin()->create();
    $renter = User::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.renters.reject', $renter))
        ->assertSessionHas('success');

    expect($renter->fresh()->verification_status)->toBe(User::STATUS_REJECTED);

    $this->actingAs($renter->fresh())->get(route('my-rents'))->assertRedirect(route('account.verification'));
});

test('an already verified renter cannot be changed again', function () {
    $admin = User::factory()->admin()->create();
    $renter = User::factory()->rejected()->create();

    $this->actingAs($admin)->patch(route('admin.renters.approve', $renter))
        ->assertSessionHas('error');

    expect($renter->fresh()->verification_status)->toBe(User::STATUS_REJECTED);
});

test('verification actions cannot target an admin account', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.renters.reject', $otherAdmin))->assertNotFound();
});

test('a renter cannot open the verification page or approve anyone', function () {
    $renter = User::factory()->create();
    $pending = User::factory()->pending()->create();

    $this->actingAs($renter)->get(route('admin.renters.index'))->assertForbidden();
    $this->actingAs($renter)->patch(route('admin.renters.approve', $pending))->assertForbidden();

    expect($pending->fresh()->verification_status)->toBe(User::STATUS_PENDING);
});
