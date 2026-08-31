<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DssCriteria extends Model
{
    protected $table = 'dss_criteria';

    protected $fillable = ['key', 'label', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function weights(): HasMany
    {
        return $this->hasMany(DssWeight::class);
    }

    public function activeWeight(): ?DssWeight
    {
        return $this->weights()->where('is_active', true)->latest()->first();
    }
}
