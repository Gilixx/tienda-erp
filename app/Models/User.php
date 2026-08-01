<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',  // auto-bcrypt on assignment
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
        ];
    }

    /**
     * Services/modules contracted by this user.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'user_service')
            ->withPivot('granted_at', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Almacenes a los que este usuario tiene acceso concedido (pivote almacen_user).
     */
    public function almacenesConAcceso(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Inventory\Almacen::class, 'almacen_user')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    /**
     * Check if the user is an admin (full access to all modules).
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user has access to a specific service/module.
     * Admins always have access.
     */
    public function hasService(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->services()
            ->where('key', $key)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
