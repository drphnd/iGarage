<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * An iGarage account: either an admin or a renter.
 *
 * role and verification_status are intentionally not fillable,
 * so a registration form can never make someone an admin or approve itself.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role                 admin | renter
 * @property string|null $nik             16-digit Indonesian ID number (renters only)
 * @property string|null $phone
 * @property string $verification_status  pending | approved | rejected
 */
#[Fillable(['name', 'email', 'password', 'nik', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_RENTER = 'renter';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Vehicles owned by this renter.
     *
     * @return HasMany<Ride, $this>
     */
    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    /**
     * All rents of this renter, reached through their vehicles (users → rides → rents).
     *
     * @return HasManyThrough<Rent, Ride, $this>
     */
    public function rents(): HasManyThrough
    {
        return $this->hasManyThrough(Rent::class, Ride::class);
    }

    /**
     * Whether this account is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Whether this renter has been approved by an admin. Admins are always treated as approved.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->isAdmin() || $this->verification_status === self::STATUS_APPROVED;
    }
}
