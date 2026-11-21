<?php

use App\Models\User;

/*
| Admin verifies tenant registrations: approve or reject, only while pending.
*/

test('admin sees pending tenants on the verification page', function () {
    $admin = User::factory()->admin()->create();
    $pending = User::factory()->pending()->create(['name' => 'Adi Pratama']);
    $approved = User::factory()->create(['name' => 'Budi Santoso']);

    $this->actingAs($admin)->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertSee('Adi Pratama')
        ->assertDontSee('Budi Santoso');   // default filter shows only pending tenants
});

test('admin can approve a pending tenant, who can then open My Rents', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.tenants.approve', $tenant))
        ->assertSessionHas('success');

    expect($tenant->fresh()->verification_status)->toBe(User::STATUS_APPROVED);

    $this->actingAs($tenant->fresh())->get(route('my-rents'))->assertOk();
});

test('admin can reject a pending tenant, who stays blocked', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.tenants.reject', $tenant))
        ->assertSessionHas('success');

    expect($tenant->fresh()->verification_status)->toBe(User::STATUS_REJECTED);

    $this->actingAs($tenant->fresh())->get(route('my-rents'))->assertRedirect(route('account.verification'));
});

test('an already verified tenant cannot be changed again', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->rejected()->create();

    $this->actingAs($admin)->patch(route('admin.tenants.approve', $tenant))
        ->assertSessionHas('error');

    expect($tenant->fresh()->verification_status)->toBe(User::STATUS_REJECTED);
});

test('verification actions cannot target an admin account', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.tenants.reject', $otherAdmin))->assertNotFound();
});

test('a tenant cannot open the verification page or approve anyone', function () {
    $tenant = User::factory()->create();
    $pending = User::factory()->pending()->create();

    $this->actingAs($tenant)->get(route('admin.tenants.index'))->assertForbidden();
    $this->actingAs($tenant)->patch(route('admin.tenants.approve', $pending))->assertForbidden();

    expect($pending->fresh()->verification_status)->toBe(User::STATUS_PENDING);
});
