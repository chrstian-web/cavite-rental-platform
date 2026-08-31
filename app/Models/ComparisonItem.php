<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparisonItem extends Model
{
    protected $fillable = ['comparison_id', 'property_id'];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(Comparison::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
