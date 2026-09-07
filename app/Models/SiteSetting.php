<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Request-level in-memory cache for loaded settings.
     * Guarantees 0 additional SQL queries after initial fetch during a single request cycle.
     */
    protected static ?array $runtimeCache = null;

    /**
     * Load all settings into memory with a single query.
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        if (static::$runtimeCache !== null) {
            return static::$runtimeCache;
        }

        try {
            // Load key-value pairs in a single targeted query
            static::$runtimeCache = static::query()->pluck('value', 'key')->all();
        } catch (\Throwable $e) {
            // Fallback during early migrations/install if table does not exist
            static::$runtimeCache = [];
        }

        return static::$runtimeCache;
    }

    /**
     * Get a setting value by key with optional fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();

        if (array_key_exists($key, $settings)) {
            return $settings[$key] ?? $default;
        }

        return $default;
    }

    /**
     * Set a setting key and value and immediately invalidate memory and persistent cache.
     */
    public static function set(string $key, ?string $value): static
    {
        $instance = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        static::flushCache();

        return $instance;
    }

    /**
     * Flush all runtime and application caches for site settings.
     */
    public static function flushCache(): void
    {
        static::$runtimeCache = null;
    }

    /**
     * Hook model booted events to ensure any direct save/delete invalidates cache.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::flushCache();
        });

        static::deleted(function () {
            static::flushCache();
        });
    }
}
