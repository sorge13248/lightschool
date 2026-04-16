<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    public $table      = 'access';
    public $timestamps = false;

    protected $fillable = [
        'user',
        'date',
        'ip',
        'allow',
        'logged_in',
        'agent',
        'type',
    ];

    protected $casts = [
        'date'      => 'datetime',
        'allow'     => 'boolean',
        'logged_in' => 'boolean',
    ];
}
