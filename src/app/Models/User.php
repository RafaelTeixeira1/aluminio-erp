<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function createdReceivables(): HasMany
    {
        return $this->hasMany(Receivable::class, 'created_by_user_id');
    }

    public function settledReceivables(): HasMany
    {
        return $this->hasMany(Receivable::class, 'settled_by_user_id');
    }

    public function createdPayables(): HasMany
    {
        return $this->hasMany(Payable::class, 'created_by_user_id');
    }

    public function settledPayables(): HasMany
    {
        return $this->hasMany(Payable::class, 'settled_by_user_id');
    }

    public function cashEntries(): HasMany
    {
        return $this->hasMany(CashEntry::class);
    }

    public function operationalNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest('created_at');
    }
}
