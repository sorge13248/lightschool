<?php

namespace App\Models;

use App\Mail\AccountDeletionCancelled;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Passkey;

/**
 * @property-read UserExpanded|null $expanded
 * @property-read \Illuminate\Database\Eloquent\Collection<Passkey> $passkeys
 */
class User extends Authenticatable implements HasPasskeys
{
    use Notifiable;

    // delight-im/auth compatible schema - no standard Laravel timestamps
    public $timestamps = false;

    protected $fillable = [
        'email',
        'username',
        'password',
        'status',
        'verified',
        'resettable',
        'roles_mask',
        'registered',
        'last_login',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'registered' => 'integer',
        'last_login' => 'integer',
        'verified'   => 'boolean',
    ];

    public function expanded(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserExpanded::class, 'id', 'id');
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class, 'authenticatable_id');
    }

    public function getPassKeyName(): string
    {
        return $this->username;
    }

    public function getPassKeyId(): string
    {
        return (string) $this->id;
    }

    public function getPassKeyDisplayName(): string
    {
        $expanded = $this->expanded;
        $full = trim(($expanded->name ?? '') . ' ' . ($expanded->surname ?? ''));
        return $full ?: $this->username;
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(File::class, 'user_id');
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    // For Laravel auth compatibility
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    // Helper: check if email is verified (status=0 means active, verified=1 means email confirmed)
    public function isEmailVerified(): bool
    {
        return (bool) $this->verified;
    }

    // Helper: check if account is active
    public function isActive(): bool
    {
        return $this->status === 0;
    }

    public function getFullProfile(): ?UserExpanded
    {
        /** @var UserExpanded|null $result */
        $result = $this->expanded;
        return $result;
    }

    public function logAccess(Request $request): void
    {
        Access::create([
            'user'      => $this->id,
            'date'      => now(),
            'ip'        => $request->ip(),
            'allow'     => true,
            'logged_in' => true,
            'agent'     => mb_substr((string) $request->userAgent(), 0, 512),
            'type'      => $this->detectDeviceType((string) $request->userAgent()),
        ]);
    }

    public function cancelPendingDeletion(): void
    {
        $deleted = UserDeletionRequest::where('user_id', $this->id)->delete();

        if ($deleted) {
            $locale = $this->expanded->language ?? config('app.locale', 'en');
            Mail::to($this->email)->locale($locale)->send(new AccountDeletionCancelled());
        }
    }

    protected function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $userAgent)) {
            return 'mobile';
        }
        return 'pc';
    }
}
