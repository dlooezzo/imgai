<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paddle_transaction_id',
        'paddle_subscription_id',
        'paddle_price_id',
        'pricing_plan_id',
        'status',
        'amount',
        'currency',
        'type',
        'processed_at',
        'raw_payload',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'paddle_subscription_id', 'paddle_subscription_id');
    }
}
