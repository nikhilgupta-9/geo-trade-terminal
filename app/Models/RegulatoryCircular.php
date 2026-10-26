<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryCircular extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'notification_number',
        'title',
        'gazette_url',
        'local_pdf_path',
        'issue_date',
        'effective_date',
        'parse_status',
        'raw_payload',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'effective_date' => 'date',
            'raw_payload' => 'array',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TariffRevision::class, 'circular_id');
    }

    public function scopeParsed(Builder $query): Builder
    {
        return $query->where('parse_status', 'PARSED');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('parse_status', 'PENDING');
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }
}
