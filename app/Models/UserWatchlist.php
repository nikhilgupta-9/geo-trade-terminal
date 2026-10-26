<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWatchlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hs_code_id',
        'baseline_fob_inr',
        'monthly_volume_units',
        'notify_on_increase_only',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'baseline_fob_inr' => 'float',
            'monthly_volume_units' => 'integer',
            'notify_on_increase_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hsCode(): BelongsTo
    {
        return $this->belongsTo(HsCode::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
