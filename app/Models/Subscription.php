<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paddle_subscription_id',
        'paddle_customer_id',
        'paddle_price_id',
        'pricing_plan_id',
        'status',
        'billing_period',
        'next_billed_at',
        'canceled_at',
        'raw_metadata',
    ];

    protected $casts = [
        'next_billed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'raw_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trialing']);
    }
}
