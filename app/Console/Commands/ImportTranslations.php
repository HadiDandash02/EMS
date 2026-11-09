<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;

/**
 * Brings the Arabic texts that ship with the code (database/data/translations-ar.json) into the translations table:
 * new keys are added, empty Arabic texts are filled. Texts an admin already wrote or edited are NEVER overwritten.
 * Run it after pulling new code:  php artisan translations:import
 */
class ImportTranslations extends Command
{
    protected $signature = 'translations:import';

    protected $description = 'Add the shipped Arabic texts that are missing from the translations table (never overwrites edits)';

    public function handle(): int
    {
        $file = database_path('data/translations-ar.json');
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        $added = $filled = 0;

        foreach ($data as $key => $arabic) {
            $row = Translation::where('key_hash', sha1($key))->first();

            if (! $row) {
                Translation::create(['key' => $key, 'type' => 'code', 'en' => $key, 'ar' => $arabic]);
                $added++;
            } elseif ($row->ar === null || $row->ar === '') {
                $row->update(['ar' => $arabic]);
                $filled++;
            }
        }

        $this->info("{$added} text(s) added, {$filled} empty text(s) filled.");

        return self::SUCCESS;
    }
}
