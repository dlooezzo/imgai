<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'role',
        'credit_balance',
        'paddle_customer_id',
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
            'credit_balance' => 'integer',
        ];
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the image generations created by this user.
     */
    public function imageGenerations()
    {
        return $this->hasMany(Generation::class, 'user_id', 'id');
    }

    /**
     * Get the video generations created by this user.
     */
    public function videoGenerations()
    {
        return $this->hasMany(VideoGeneration::class, 'user_id', 'id');
    }

    /**
     * Subscriptions for this user.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Latest active subscription for this user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->latestOfMany();
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->whereIn('status', ['active', 'trialing'])->exists();
    }

    /**
     * Financial transactions for this user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Credit ledger history for this user.
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Check if the user has enough credits.
     */
    public function hasEnoughCredits(int $amount): bool
    {
        return $this->credit_balance >= $amount;
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include standard users.
     */
    public function scopeStandardUsers($query)
    {
        return $query->where('role', '!=', 'admin')->orWhereNull('role');
    }
}


