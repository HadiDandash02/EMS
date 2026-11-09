<?php

namespace App\Support;

use App\Models\Translation;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Cache;

/**
 * Makes the translation helper (double underscore with an English sentence) read its translation from the DATABASE (table `translations`).
 *
 * Laravel asks its loader for the JSON-style group ('*') of a language when a page uses the helper. This loader
 * answers that question from the database and delegates everything else (the validation.php, auth.php... files)
 * to the normal file loader.
 *
 * Speed: one query per language, then the whole list is kept in the cache. Saving a translation clears it.
 */
class DatabaseTranslationLoader implements Loader
{
    public function __construct(private readonly Loader $files) {}

    public function load($locale, $group, $namespace = null): array
    {
        $fromFiles = $this->files->load($locale, $group, $namespace);

        if ($group === '*' && $namespace === '*') {
            return array_merge($fromFiles, self::forLocale($locale));   // the database wins over any file
        }

        return $fromFiles;
    }

    /** @return array<string, string> English text => translation, only for texts that are translated */
    public static function forLocale(string $locale): array
    {
        try {
            return Cache::rememberForever(self::cacheKey($locale), fn () => Translation::query()
                ->where('locale', $locale)->where('group', '*')->whereNotNull('value')->where('value', '!=', '')
                ->pluck('value', 'key')->all());
        } catch (\Throwable) {
            return [];   // the table does not exist yet (before the first migration): behave like "no translations"
        }
    }

    public static function forget(string $locale): void
    {
        try {
            Cache::forget(self::cacheKey($locale));
        } catch (\Throwable) {
            // cache not available: nothing to clear
        }
    }

    private static function cacheKey(string $locale): string
    {
        return "translations.{$locale}";
    }

    public function addNamespace($namespace, $hint)
    {
        $this->files->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->files->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->files->namespaces();
    }
}
