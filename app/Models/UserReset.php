<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReset extends Model
{
    protected $table = 'users_resets';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'selector',
        'token',
        'expires',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }
}
