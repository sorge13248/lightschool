<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $table = 'project_files';

    public $timestamps = false;

    protected $fillable = [
        'project',
        'file',
        'user',
        'editable',
    ];

    protected $casts = [
        'editable' => 'boolean',
    ];

    // Relationships

    /**
     * The project this file belongs to (keyed on code string).
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project', 'code');
    }

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'file', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }
}
