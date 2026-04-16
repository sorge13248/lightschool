<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MessageChat> $chats
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MessageActor> $actors
 */
class Message extends Model
{
    protected $table = 'message_list';

    public $timestamps = false;

    protected $fillable = [
        'subject',
        'timestamp',
    ];

    // Relationships

    public function chats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MessageChat::class, 'message_list_id');
    }

    public function actors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MessageActor::class, 'list_id');
    }

    // Scopes

    /**
     * Filter message threads that involve the given user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('actors', function (Builder $q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
