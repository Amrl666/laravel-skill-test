<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_draft',
        'published_at',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->is_draft
                && $this->published_at !== null
                && $this->published_at->lte(now())
        );
    }
}
