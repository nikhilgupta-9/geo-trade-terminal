<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'circular_id',
        'hs_code_id',
        'duty_type',
        'old_rate',
        'new_rate',
        'action',
        'condition_notes',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'old_rate' => 'float',
            'new_rate' => 'float',
            'delta' => 'float',
            'effective_from' => 'date',
        ];
    }

    public function circular(): BelongsTo
    {
        return $this->belongsTo(RegulatoryCircular::class, 'circular_id');
    }

    public function hsCode(): BelongsTo
    {
        return $this->belongsTo(HsCode::class, 'hs_code_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(TariffAlert::class, 'revision_id');
    }

    public function scopeIncreases(Builder $query): Builder
    {
        return $query->where('action', 'INCREASE');
    }

    public function scopeDecreases(Builder $query): Builder
    {
        return $query->where('action', 'DECREASE');
    }

    public function scopeExemptions(Builder $query): Builder
    {
        return $query->where('action', 'EXEMPTED');
    }
}
