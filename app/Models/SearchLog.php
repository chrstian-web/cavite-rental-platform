<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = ['location_id', 'property_type', 'min_rent', 'max_rent', 'ip_address'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
