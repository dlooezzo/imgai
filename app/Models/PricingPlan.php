<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PricingPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'badge',
        'monthly_price',
        'yearly_price',
        'monthly_price_id',
        'yearly_price_id',
        'monthly_credits',
        'yearly_credits',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
        'button_text',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'monthly_credits' => 'integer',
        'yearly_credits' => 'integer',
    ];

    /**
     * Boot the model. Automatically create a slug if not present.
     */
    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    /**
     * Subscriptions associated with this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Transactions associated with this plan.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Find plan by Paddle Price ID.
     */
    public static function findByPaddlePriceId(string $priceId): ?self
    {
        return static::where('monthly_price_id', $priceId)
            ->orWhere('yearly_price_id', $priceId)
            ->first();
    }

    /**
     * Ensure default plans exist in the database.
     */
    public static function ensureDefaults(): void
    {
        $defaults = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Essential creative AI tools for hobbyists, artists, and solo creators.',
                'badge' => null,
                'monthly_price' => '$19',
                'yearly_price' => '$190',
                'monthly_credits' => 500,
                'yearly_credits' => 6000,
                'features' => [
                    '500 monthly fast generation credits',
                    'Text-to-Image & Image-to-Video models',
                    'Standard GPU cloud queue priority',
                    'Up to 1080p Full-HD rendering',
                    'Personal & commercial project license',
                    'Community Discord & email support',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'button_text' => 'Subscribe',
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'High-throughput cinematic generation with maximum speed and ultra-high resolution.',
                'badge' => 'Studio Choice',
                'monthly_price' => '$49',
                'yearly_price' => '$470',
                'monthly_credits' => 3000,
                'yearly_credits' => 36000,
                'features' => [
                    '3,000 monthly fast generation credits',
                    'Priority Turbo GPU queue access',
                    '2MP Cinematic & 4K Ultra-HD upscaling',
                    'Full commercial rights & royalty-free license',
                    'Dynamic camera motion & temporal control',
                    'Cloudflare R2 private asset storage',
                    'Concurrent batch generation (up to 4)',
                    'Priority 24/7 dedicated studio support',
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'button_text' => 'Subscribe',
            ],
            [
                'name' => 'Advanced',
                'slug' => 'advanced',
                'description' => 'Unlimited-scale studio production for agencies, studios, and high-volume teams.',
                'badge' => null,
                'monthly_price' => '$99',
                'yearly_price' => '$950',
                'monthly_credits' => 10000,
                'yearly_credits' => 120000,
                'features' => [
                    '10,000 monthly ultra-fast generation credits',
                    'Dedicated VIP GPU cluster allocation',
                    'Raw uncompressed video exports (ProRes / WebM)',
                    'Multi-seat workspace collaboration & permissions',
                    'Custom AI LoRA fine-tuning pipeline',
                    'Dedicated account manager & SLA guarantee',
                    'Direct REST API webhook integration access',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'button_text' => 'Subscribe',
            ],
        ];

        foreach ($defaults as $planData) {
            $existing = static::where('slug', $planData['slug'])->first();
            if (!$existing) {
                static::create($planData);
            } else {
                if ((int) $existing->monthly_credits === 0) {
                    $existing->update([
                        'monthly_credits' => $planData['monthly_credits'],
                        'yearly_credits' => $planData['yearly_credits'],
                    ]);
                }
            }
        }
    }
}

