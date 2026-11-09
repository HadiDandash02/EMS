<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Finds every text used with the double-underscore helper in the code and adds the missing ones to the translations table
 * (with an empty value = "not translated yet"). Run it after adding new texts, then translate them on /admin/translations.
 */
class ScanTranslations extends Command
{
    protected $signature = 'translations:scan {locale=ar}';

    protected $description = 'Add the texts used in the code that are not in the translations table yet';

    /** @return array<string, string> key => first file that uses it */
    public static function keysInCode(): array
    {
        $found = [];

        foreach ([base_path('app'), base_path('resources/views')] as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file), $m);

                foreach ($m[1] as $key) {
                    $found[stripcslashes($key)] ??= $file->getFilename();
                }
            }
        }

        return $found;
    }

    public function handle(): int
    {
        $locale = (string) $this->argument('locale');
        $existing = Translation::where('locale', $locale)->where('group', '*')->pluck('key_hash')->flip();
        $added = 0;

        foreach (self::keysInCode() as $key => $file) {
            if (! $existing->has(sha1($key))) {
                Translation::create(['locale' => $locale, 'group' => '*', 'key' => $key, 'value' => null]);
                $this->line("  + {$key}   ({$file})");
                $added++;
            }
        }

        $this->info($added === 0 ? 'Every text used in the code is already in the translations table.' : "{$added} new text(s) added - translate them on /admin/translations.");

        return self::SUCCESS;
    }
}
