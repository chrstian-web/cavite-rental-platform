<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualTourScene extends Model
{
    protected $fillable = ['virtual_tour_id', 'title', 'panorama_image', 'description', 'sort_order'];

    public function virtualTour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class);
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(SceneHotspot::class, 'scene_id');
    }
}
