<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = ['key', 'name', 'description', 'icon'];

    /**
     * Users who have access to this service.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_service')
                    ->withPivot('granted_at', 'expires_at')
                    ->withTimestamps();
    }
}
