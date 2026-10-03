<?php

namespace HasinHayder\TyroLogin\Tests\Fixtures;

/**
 * Test user with role support for the 2FA skip/forced role features.
 *
 * Simulates a Tyro-integrated user model: new users automatically get the
 * default "user" role (like Tyro's assign_default_role), and hasRole() is
 * backed by a scalar role column.
 */
class RoleUser extends User {
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected static function booted(): void {
        static::creating(function ($model) {
            $model->role ??= 'user';
        });
    }

    public function hasRole(string $role): bool {
        return $this->role === $role;
    }
}
