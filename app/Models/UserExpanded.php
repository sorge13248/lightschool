<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read Plan|null $plan
 */
class UserExpanded extends Model
{
    protected $table = 'users_expanded';

    // 1-to-1 with users; id is both PK and FK
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'surname',
        'profile_picture',
        'wallpaper',
        'taskbar',
        'taskbar_size',
        'type',
        'accent',
        'theme',
        'language',
        'plan',
        'twofa',
        'deac_twofa',
        'privacy_show_email',
        'privacy_show_full_name',
        'privacy_show_profile_picture',
        'privacy_allow_contacts',
        'password_last_change',
        'blocked',
    ];

    protected $casts = [
        // wallpaper is stored as JSON string (e.g. {"id":1,"opacity":"0.5","color":"0, 0, 0"})
        'wallpaper' => 'array',
        // accent is stored as a plain 6-char hex string (no #), not JSON
        'taskbar'   => 'array',
        'blocked'   => 'array',
    ];

    // Relationships

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan', 'id');
    }

    public function profilePictureFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'profile_picture', 'id');
    }

    // Helpers

    /**
     * Returns the wallpaper array: {id, opacity, color}
     */
    public function getWallpaperArray(): array
    {
        if (is_array($this->wallpaper)) {
            return $this->wallpaper;
        }

        return [];
    }

    /**
     * Returns the accent color hex string (without #), e.g. "1e6ad3".
     */
    public function getAccentColor(): ?string
    {
        return $this->accent ?: null;
    }

    /**
     * Returns taskbar as an array of app-purchase IDs (CSV → array).
     */
    public function getTaskbarArray(): array
    {
        return array_values($this->taskbar ?? []);
    }
}
