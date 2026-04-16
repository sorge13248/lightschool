<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read User|null $user
 */
class MessageActor extends Model
{
    protected $table = 'message_actors';

    public $timestamps = false;

    protected $fillable = [
        'list_id',
        'user_id',
    ];

    // Relationships

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class, 'list_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
