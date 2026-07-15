<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class ManifestVersion
{
    public static function hash(): ?string
    {
        return Cache::remember('manifest-version-hash', 60, function () {
            $path = static::path();

            if (!$path || !file_exists($path)) {
                return null;
            }

            return md5_file($path);
        });
    }

    protected static function path(): ?string
    {
        // Vite >= 5 en Laravel suele usar public/build/.vite/manifest.json
        // Versiones anteriores usan public/build/manifest.json
        $candidates = [
            public_path('build/.vite/manifest.json'),
            public_path('build/manifest.json'),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
