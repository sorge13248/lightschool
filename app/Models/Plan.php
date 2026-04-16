<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'disk_space',
    ];

    protected $casts = [
        'disk_space' => 'integer',
    ];

    // Relationships

    public function userProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserExpanded::class, 'plan');
    }
}
