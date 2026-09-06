<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ToolArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_key',
        'title',
        'slug',
        'seo_title',
        'meta_description',
        'excerpt',
        'content_html',
        'featured_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Scope for published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for specific tool.
     */
    public function scopeForTool($query, string $toolKey)
    {
        return $query->where('tool_key', $toolKey);
    }

    /**
     * Check if published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Effective SEO title.
     */
    public function getEffectiveSeoTitle(): string
    {
        if (!empty($this->seo_title)) {
            return $this->seo_title;
        }

        return $this->title . ' | IMGAI Studio';
    }

    /**
     * Effective Meta description.
     */
    public function getEffectiveMetaDescription(): string
    {
        if (!empty($this->meta_description)) {
            return $this->meta_description;
        }

        if (!empty($this->excerpt)) {
            return $this->excerpt;
        }

        return Str::limit(strip_tags($this->content_html), 155);
    }

    /**
     * Word count calculation.
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->content_html));
    }

    /**
     * Reading time estimation.
     */
    public function getReadingTimeAttribute(): int
    {
        return max(1, (int) ceil($this->word_count / 200));
    }
}
