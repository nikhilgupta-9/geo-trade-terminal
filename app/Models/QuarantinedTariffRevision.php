<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarantinedTariffRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'circular_id',
        'raw_hs_code',
        'matched_hs_code_id',
        'duty_type',
        'extracted_old_rate',
        'extracted_new_rate',
        'confidence_score',
        'quarantine_reason',
        'raw_snippet',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'extracted_old_rate' => 'float',
            'extracted_new_rate' => 'float',
            'confidence_score' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function circular(): BelongsTo
    {
        return $this->belongsTo(RegulatoryCircular::class, 'circular_id');
    }

    public function matchedHsCode(): BelongsTo
    {
        return $this->belongsTo(HsCode::class, 'matched_hs_code_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING_REVIEW');
    }
}
