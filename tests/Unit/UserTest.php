<?php

use App\Models\User;

/*
| Unit tests for the User model (no database).
*/

/** An unsaved user with a role and verification status (both are not fillable on purpose). */
function userWith(string $role, string $status): User
{
    $user = new User;
    $user->role = $role;
    $user->verification_status = $status;

    return $user;
}

test('isAdmin is only true for the admin role', function () {
    expect(userWith(User::ROLE_ADMIN, User::STATUS_APPROVED)->isAdmin())->toBeTrue()
        ->and(userWith(User::ROLE_RENTER, User::STATUS_APPROVED)->isAdmin())->toBeFalse();
});

test('a renter is approved only after verification, an admin always is', function () {
    expect(userWith(User::ROLE_RENTER, User::STATUS_APPROVED)->isApproved())->toBeTrue()
        ->and(userWith(User::ROLE_RENTER, User::STATUS_PENDING)->isApproved())->toBeFalse()
        ->and(userWith(User::ROLE_RENTER, User::STATUS_REJECTED)->isApproved())->toBeFalse()
        ->and(userWith(User::ROLE_ADMIN, User::STATUS_PENDING)->isApproved())->toBeTrue();
});

test('role and verification status cannot be mass assigned from a form', function () {
    $user = new User(['name' => 'Intruder', 'role' => User::ROLE_ADMIN, 'verification_status' => User::STATUS_APPROVED]);

    expect($user->role)->toBeNull()
        ->and($user->verification_status)->toBeNull()
        ->and($user->name)->toBe('Intruder');
});
