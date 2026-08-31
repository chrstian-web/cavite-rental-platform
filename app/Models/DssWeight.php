<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DssWeight extends Model
{
    protected $fillable = [
        'dss_criteria_id', 'weight_percentage', 'scoring_rules', 'is_active', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'decimal:2',
            'scoring_rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(DssCriteria::class, 'dss_criteria_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
