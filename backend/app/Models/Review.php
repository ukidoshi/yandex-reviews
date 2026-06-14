<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'yandex_review_id',
    'author_name',
    'published_at',
    'text',
    'rating',
])]
class Review extends Model
{
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
