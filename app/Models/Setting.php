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

    /**
     * Dapatkan URL logo pertama (Politeknik Hasnur / Instansi).
     */
    public static function getLogo1Url(): ?string
    {
        $val = static::get('logo_1');
        if ($val && file_exists(public_path('uploads/logos/'.$val))) {
            $mtime = filemtime(public_path('uploads/logos/'.$val));

            return asset('uploads/logos/'.$val).'?v='.$mtime;
        }

        if (file_exists(public_path('assets/img/logo1.png'))) {
            $mtime = filemtime(public_path('assets/img/logo1.png'));

            return asset('assets/img/logo1.png').'?v='.$mtime;
        }

        return null;
    }

    /**
     * Dapatkan URL logo kedua (HIMA TI / Organisasi).
     */
    public static function getLogo2Url(): ?string
    {
        $val = static::get('logo_2');
        if ($val && file_exists(public_path('uploads/logos/'.$val))) {
            $mtime = filemtime(public_path('uploads/logos/'.$val));

            return asset('uploads/logos/'.$val).'?v='.$mtime;
        }

        if (file_exists(public_path('assets/img/logo2.png'))) {
            $mtime = filemtime(public_path('assets/img/logo2.png'));

            return asset('assets/img/logo2.png').'?v='.$mtime;
        }

        return null;
    }
}
