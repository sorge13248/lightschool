<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExport extends Model
{
    protected $table = 'data_exports';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'zip_path',
        'ip_address',
        'requested_at',
        'ready_at',
        'downloaded_at',
        'expires_at',
    ];

    protected $casts = [
        'requested_at'  => 'datetime',
        'ready_at'      => 'datetime',
        'downloaded_at' => 'datetime',
        'expires_at'    => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
