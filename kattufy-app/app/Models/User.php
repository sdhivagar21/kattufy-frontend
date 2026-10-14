<?php

namespace App\Models;

use App\Modules\Accounts\Enums\Role;
use App\Modules\Providers\Models\Provider;
use App\Support\Concerns\HasUserAttribution;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Everyone who signs in: customers, providers and staff. There are no
 * passwords; identity is the OTP-verified mobile number (Architecture §7).
 * phone_verified_at = NULL means a registration that has not finished OTP.
 */
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasRoles, HasUserAttribution, LogsActivity, Notifiable;

    protected $fillable = ['name', 'phone', 'email', 'profile_address', 'locale', 'status', 'phone_verified_at', 'last_login_at'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    /** The business this account registered or claimed (one per account in V1). */
    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class)->oldestOfMany();
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(Role::staff());
    }

    public function isVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** The account's self-chosen role (customer or provider), if one has been chosen. */
    public function chosenRole(): ?Role
    {
        foreach ([Role::Provider, Role::Customer] as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Filament: staff only, and only after the two-step admin sign-in in this
     * session (EnsureStaffSession also checks this on every admin request).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff() && $this->isActive()
            && session('admin_verified_user_id') === $this->getKey();
    }

    public function getFilamentName(): string
    {
        return $this->name ?: $this->phone;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('accounts')
            ->logOnly(['name', 'email', 'status', 'locale'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
