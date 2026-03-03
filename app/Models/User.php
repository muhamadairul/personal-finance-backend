<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'photo_url',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'is_pro',
        'subscription_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'date_of_birth'      => 'date',
            'is_pro'             => 'boolean',
            'subscription_until' => 'datetime',
        ];
    }

    /**
     * Check if user has active Pro subscription.
     */
    public function isPro(): bool
    {
        if (!$this->is_pro) {
            return false;
        }

        // If subscription_until is set, check if it's still valid
        if ($this->subscription_until !== null) {
            return $this->subscription_until->isFuture();
        }

        // is_pro = true and no expiry → lifetime/manual pro
        return true;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // All users can access admin panel for now
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function subscriptionLogs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class);
    }
}
