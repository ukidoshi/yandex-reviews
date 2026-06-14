<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'yandex_org_id',
    'name',
    'source_url',
    'average_rating',
    'ratings_count',
    'reviews_count',
    'synced_at',
])]
class Organization extends Model
{
    protected function casts(): array
    {
        return [
            'average_rating' => 'float',
            'ratings_count' => 'integer',
            'reviews_count' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
