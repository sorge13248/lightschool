<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $table = 'share';

    public $timestamps = false;

    protected $fillable = [
        'sender',
        'receiving',
        'file',
        'comment',
        'timestamp',
        'edit',
        'deleted',
    ];

    protected $casts = [
        'edit'    => 'boolean',
        'deleted' => 'boolean',
    ];

    // Relationships

    public function senderUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender');
    }

    public function receivingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'receiving');
    }

    public function sharedFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'file');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('deleted', 0);
    }

    public function scopeReceived(Builder $query, int $userId): Builder
    {
        return $query->where('receiving', $userId);
    }

    public function scopeSent(Builder $query, int $userId): Builder
    {
        return $query->where('sender', $userId);
    }
}
