<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConfirmation extends Model
{
    protected $table = 'users_confirmations';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'selector',
        'token',
        'expires',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
