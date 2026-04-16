<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $table = 'apps';

    public $timestamps = false;

    protected $fillable = [
        'unique_name',
        'timestamp',
    ];
}
