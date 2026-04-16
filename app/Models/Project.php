<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'project';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'timestamp',
    ];

    // Relationships

    public function projectFiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectFile::class, 'project', 'code');
    }

    // Statics

    /**
     * Generate a 6-character random uppercase project code.
     */
    public static function generateCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }
}
