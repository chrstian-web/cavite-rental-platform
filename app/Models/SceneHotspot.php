<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SceneHotspot extends Model
{
    protected $fillable = [
        'scene_id', 'target_scene_id', 'position_x', 'position_y', 'position_z', 'label', 'type',
    ];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(VirtualTourScene::class, 'scene_id');
    }

    public function targetScene(): BelongsTo
    {
        return $this->belongsTo(VirtualTourScene::class, 'target_scene_id');
    }
}
