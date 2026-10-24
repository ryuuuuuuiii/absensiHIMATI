<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'updated_at',
    ];

    public static function get(string $key, ?string $default = ''): ?string
    {
        try {
            $setting = static::where('setting_key', $key)->first();
            return $setting ? ($setting->setting_value ?? $default) : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, ?string $value): bool
    {
        try {
            static::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'updated_at' => now(),
                ]
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
