<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contact';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'contact_id',
        'name',
        'surname',
        'fav',
        'trash',
        'deleted',
    ];

    protected $casts = [
        'fav'     => 'boolean',
        'trash'   => 'boolean',
        'deleted' => 'boolean',
    ];

    // Relationships

    /**
     * The user who owns this contact entry.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The user record of the contact person.
     */
    public function contactUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('deleted', 0);
    }

    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('fav', 1);
    }

    public function scopeInTrash(Builder $query): Builder
    {
        return $query->where('trash', 1);
    }
}
