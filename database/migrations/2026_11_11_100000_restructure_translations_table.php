<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per translation KEY with a column for each language (English | Arabic), like a translation catalogue:
 *
 *   key       the key used in the code: __('Approve task')
 *   type      'code'    = found in the code by the scan (or imported)
 *             'keyword' = added by hand on the Translations page
 *   en / ar   the English and the Arabic text (null = not translated yet; an empty English text shows the key itself)
 *   machine   true = the Arabic text came from the Auto-translate function and still has to be reviewed
 *
 * The first version (one row per language) is converted without losing anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        $old = Schema::hasTable('translations')
            ? DB::table('translations')->where('locale', 'ar')->where('group', '*')->get(['key', 'value', 'machine', 'created_at', 'updated_at'])
            : collect();

        Schema::dropIfExists('translations');

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->text('key');
            $table->char('key_hash', 40)->unique();     // sha1(key): a short, unique handle for the long text
            $table->string('type', 10)->default('code')->index();
            $table->text('en')->nullable();
            $table->text('ar')->nullable();
            $table->boolean('machine')->default(false);
            $table->timestamps();
        });

        foreach ($old->chunk(100) as $chunk) {
            DB::table('translations')->insert($chunk->map(fn ($r) => [
                'key' => $r->key, 'key_hash' => sha1($r->key), 'type' => 'code',
                'en' => $r->key,                       // the English text is the key itself until someone edits it
                'ar' => $r->value, 'machine' => (bool) $r->machine,
                'created_at' => $r->created_at, 'updated_at' => $r->updated_at,
            ])->all());
        }
    }

    public function down(): void
    {
        $rows = DB::table('translations')->get(['key', 'ar', 'machine', 'created_at', 'updated_at']);

        Schema::dropIfExists('translations');

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('group', 40)->default('*');
            $table->text('key');
            $table->char('key_hash', 40);
            $table->text('value')->nullable();
            $table->boolean('machine')->default(false);
            $table->timestamps();
            $table->unique(['locale', 'group', 'key_hash']);
            $table->index(['locale', 'group']);
        });

        foreach ($rows->chunk(100) as $chunk) {
            DB::table('translations')->insert($chunk->map(fn ($r) => [
                'locale' => 'ar', 'group' => '*', 'key' => $r->key, 'key_hash' => sha1($r->key), 'value' => $r->ar,
                'machine' => $r->machine, 'created_at' => $r->created_at, 'updated_at' => $r->updated_at,
            ])->all());
        }
    }
};
