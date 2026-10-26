<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'chapter',
        'description',
        'unit',
        'base_bcd_rate',
        'sws_rate',
        'igst_rate',
        'aidc_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_bcd_rate' => 'float',
            'sws_rate' => 'float',
            'igst_rate' => 'float',
            'aidc_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TariffRevision::class);
    }

    public function latestRevision()
    {
        return $this->hasOne(TariffRevision::class)->latestOfMany();
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(UserWatchlist::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(TariffAlert::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeChapter(Builder $query, string $chapter): Builder
    {
        return $query->where('chapter', $chapter);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('chapter', 'LIKE', "%{$term}%");
        });
    }
}
