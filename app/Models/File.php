<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'file';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'diary_type',
        'diary_date',
        'diary_reminder',
        'diary_priority',
        'diary_color',
        'n_ver',
        'header',
        'cypher',
        'html',
        'footer',
        'file_url',
        'file_type',
        'file_size',
        'fav',
        'icon',
        'create_date',
        'last_view',
        'last_edit',
        'folder',
        'trash',
        'history',
        'bypass',
        'deleted_at',
    ];

    protected $casts = [
        'fav'            => 'integer',
        'trash'          => 'integer',
        'diary_priority' => 'integer',
        'file_size'      => 'integer',
        'n_ver'          => 'integer',
        'folder'         => 'integer',
        'user_id'        => 'integer',
    ];

    // Relationships

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Parent folder (self-referential).
     */
    public function parentFolder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'folder', 'id');
    }

    /**
     * Children files/folders inside this folder (self-referential).
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(File::class, 'folder', 'id');
    }

    public function projectFiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectFile::class, 'file', 'id');
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Share::class, 'file', 'id');
    }

    // Scopes

    /**
     * Files that are not deleted and not in trash.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at')->where('trash', 0);
    }

    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('fav', 1);
    }

    public function scopeInTrash(Builder $query): Builder
    {
        return $query->where('trash', 1)->whereNull('deleted_at');
    }

    public function scopeInFolder(Builder $query, ?int $folderId): Builder
    {
        if ($folderId === null) {
            return $query->whereNull('folder');
        }

        return $query->where('folder', $folderId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public static function getHumanSize(int $bytes, int $decimals = 2): string
    {
        $sz = 'BKMGTP';
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . (isset($sz[$factor]) ? $sz[$factor] : 'B');
    }
}
