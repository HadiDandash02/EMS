<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The interface translations live in the DATABASE (no lang/ar.json any more).
 *
 *   locale  'ar'                      the language
 *   group   '*'                       the JSON-style group: the key IS the English text, e.g. "Approve task"
 *   key     the English text          (text column; key_hash makes it uniquely indexable)
 *   value   the translation           (empty = not translated yet, the English text is shown)
 *
 * The first import comes from database/data/translations-ar.json; afterwards the super admin edits everything on
 * the "Translations" page (/admin/translations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('group', 40)->default('*');
            $table->text('key');
            $table->char('key_hash', 40);      // sha1(key): a short, unique handle for the long text
            $table->text('value')->nullable();
            $table->boolean('machine')->default(false);   // true = produced by the Auto-translate function, to be reviewed by a person
            $table->timestamps();

            $table->unique(['locale', 'group', 'key_hash']);
            $table->index(['locale', 'group']);
        });

        $file = database_path('data/translations-ar.json');

        if (is_file($file)) {
            $now = now();
            $rows = [];

            foreach (json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) as $key => $value) {
                $rows[] = ['locale' => 'ar', 'group' => '*', 'key' => $key, 'key_hash' => sha1($key), 'value' => $value, 'created_at' => $now, 'updated_at' => $now];
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('translations')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
