<?php

namespace App\Console\Commands;

use App\Models\Translation;
use App\Support\MachineTranslator;
use Illuminate\Console\Command;

/** Machine-translates the texts that have no translation yet (they are marked "machine" so a person can review them). */
class AutoTranslate extends Command
{
    protected $signature = 'translations:auto {locale=ar} {--limit=50 : how many texts to translate in this run}';

    protected $description = 'Machine-translate the untranslated interface texts';

    public function handle(MachineTranslator $translator): int
    {
        $locale = (string) $this->argument('locale');

        $rows = Translation::untranslated($locale)->limit((int) $this->option('limit'))->get();

        $done = 0;

        foreach ($rows as $row) {
            if ($text = $translator->translate((string) ($row->en ?: $row->key), $locale)) {
                $row->update([$locale => $text, 'machine' => true]);
                $this->line("  {$row->key}  ->  {$text}");
                $done++;
            }
        }

        $this->info("{$done} of {$rows->count()} text(s) translated.");

        return self::SUCCESS;
    }
}
