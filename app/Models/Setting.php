<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Fillable(['key', 'value', 'group', 'description', 'is_secret'])]
class Setting extends Model
{
    /** @var array<string, string> */
    protected $casts = [
        'is_secret' => 'boolean',
        'value' => 'encrypted',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("setting:{$key}", 3600, function () use ($key, $default): mixed {
                return static::fetchValue($key, $default);
            });
        } catch (Throwable) {
            return static::fetchValue($key, $default);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        try {
            Cache::forget("setting:{$key}");

            // Clear setting cache across all tenant prefixes
            $currentTenant = Tenant::current();

            Tenant::all()->each(function (Tenant $tenant) use ($key): void {
                $tenant->makeCurrent();
                Cache::forget("setting:{$key}");
            });

            if ($currentTenant) {
                $currentTenant->makeCurrent();
            } else {
                Tenant::forgetCurrent();
            }
        } catch (Throwable) {
        }
    }

    public static function getGroup(string $group): Collection
    {
        return static::where('group', $group)->get();
    }

    private static function fetchValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }
}
