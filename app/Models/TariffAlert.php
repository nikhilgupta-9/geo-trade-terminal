<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hs_code_id',
        'revision_id',
        'old_rate',
        'new_rate',
        'delta',
        'estimated_monthly_impact_inr',
        'status',
        'channel',
    ];

    protected function casts(): array
    {
        return [
            'old_rate' => 'float',
            'new_rate' => 'float',
            'delta' => 'float',
            'estimated_monthly_impact_inr' => 'float',
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

    public function revision(): BelongsTo
    {
        return $this->belongsTo(TariffRevision::class, 'revision_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'UNREAD');
    }
}
