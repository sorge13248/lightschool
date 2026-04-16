<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $table = 'themes';

    public $timestamps = false;

    protected $fillable = [
        'author',
        'name',
        'unique_name',
        'icon',
    ];

    // Relationships

    public function authorUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }
}
