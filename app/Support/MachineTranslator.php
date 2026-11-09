<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Automatic (machine) translation of interface texts, used by the "Auto-translate" button on /admin/translations
 * and by `php artisan translations:auto`.
 *
 * It calls a public translation service (MyMemory by default; the address is configurable in config/ems.php /
 * .env: TRANSLATION_API_URL). Only the interface sentences are sent - never user data. Machine translations are
 * marked in the database so a person reviews them.
 *
 * Placeholders such as :name, :count, :minutes are protected: they are replaced by neutral tokens before the
 * text is sent and put back afterwards. If one of them gets lost, the result is refused (null).
 */
class MachineTranslator
{
    public function translate(string $text, string $to = 'ar', string $from = 'en'): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // :name -> [[0]]  (kept in order so they can be restored)
        $placeholders = [];
        $protected = preg_replace_callback('/:[a-z_]+/i', function ($m) use (&$placeholders) {
            $placeholders[] = $m[0];

            return '[['.(count($placeholders) - 1).']]';
        }, $text);

        try {
            $response = Http::timeout(10)->acceptJson()->get(config('ems.translator.url'), array_filter([
                'q' => $protected,
                'langpair' => "{$from}|{$to}",
                'de' => config('ems.translator.email'),
            ]));
        } catch (\Throwable) {
            return null;   // no network / service down
        }

        if (! $response->successful()) {
            return null;
        }

        $translated = trim((string) data_get($response->json(), 'responseData.translatedText', ''));

        // the service sometimes answers with its own error text, or leaves the input unchanged
        if ($translated === '' || str_contains(mb_strtoupper($translated), 'MYMEMORY') || (int) data_get($response->json(), 'responseStatus', 200) !== 200) {
            return null;
        }

        foreach ($placeholders as $i => $original) {
            if (! str_contains($translated, "[[{$i}]]")) {
                return null;   // a placeholder was damaged: never save a broken sentence
            }
            $translated = str_replace("[[{$i}]]", $original, $translated);
        }

        return $translated;
    }
}
