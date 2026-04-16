<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read User|null $senderUser
 */
class MessageChat extends Model
{
    protected $table = 'message_chat';

    public $timestamps = false;

    protected $fillable = [
        'message_list_id',
        'sender',
        'cypher',
        'body',
        'attachment',
        'date',
        'read_at',
    ];

    protected $casts = [
        'date'    => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relationships

    public function messageList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_list_id');
    }

    public function senderUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender');
    }
}
