<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalSpaceImage extends Model
{
    protected $fillable = ['rental_space_id', 'path', 'is_cover', 'sort_order'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function rentalSpace(): BelongsTo
    {
        return $this->belongsTo(RentalSpace::class);
    }
}
