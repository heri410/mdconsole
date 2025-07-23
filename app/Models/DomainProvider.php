<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainProvider extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Beziehung zu den Domains
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'provider_id');
    }

    /**
     * Beziehung zu den Domain-Preisen
     */
    public function prices(): HasMany
    {
        return $this->hasMany(DomainPrice::class, 'provider_id');
    }
}
