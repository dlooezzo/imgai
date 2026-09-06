<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'show_in_navigation',
        'navigation_label',
        'navigation_order',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_directive',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'created_by',
    ];

    protected $casts = [
        'show_in_navigation' => 'boolean',
        'navigation_order' => 'integer',
    ];

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include pages shown in navigation.
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true)->orderBy('navigation_order', 'asc');
    }

    /**
     * Check if the page is currently published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Get the navigation label with fallback to page title.
     */
    public function getNavLabelAttribute(): string
    {
        return !empty($this->navigation_label) ? $this->navigation_label : $this->title;
    }

    /**
     * Get the effective SEO meta title.
     */
    public function getEffectiveMetaTitleAttribute(): string
    {
        if (!empty($this->meta_title)) {
            return $this->meta_title;
        }

        return $this->title . ' — ' . config('app.name', 'IMGAI Studio');
    }

    /**
     * Get the effective SEO meta description.
     */
    public function getEffectiveMetaDescriptionAttribute(): string
    {
        if (!empty($this->meta_description)) {
            return $this->meta_description;
        }

        if (!empty($this->excerpt)) {
            return $this->excerpt;
        }

        return Str::limit(strip_tags($this->content), 160);
    }

    /**
     * User who created the page.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
