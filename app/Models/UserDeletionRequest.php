<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeletionRequest extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'request_timestamp', 'deletion_timestamp'];

    protected $casts = [
        'request_timestamp' => 'datetime',
        'deletion_timestamp' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
