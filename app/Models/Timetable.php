<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $table = 'timetable';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'year',
        'day',
        'slot',
        'subject',
        'book',
        'fore',
        'deleted_at',
    ];

    // Relationships

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user', $userId);
    }

    public function scopeForYear(Builder $query, string $year): Builder
    {
        return $query->where('year', $year);
    }
}
