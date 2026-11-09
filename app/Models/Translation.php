<?php

namespace App\Models;

use App\Support\DatabaseTranslationLoader;
use Illuminate\Database\Eloquent\Model;

/**
 * One interface text in one language. The key is the English sentence used in the code: __('Approve task').
 * An empty value means "not translated yet" (the English text is shown).
 */
class Translation extends Model
{
    protected $fillable = ['locale', 'group', 'key', 'value', 'machine'];

    protected $casts = ['machine' => 'boolean'];

    protected static function booted(): void
    {
        // key_hash is always derived from the key, and any change drops the cached copy used by __()
        static::saving(function (Translation $t) {
            $t->key_hash = sha1($t->key);
            $t->group ??= '*';
        });

        static::saved(fn (Translation $t) => DatabaseTranslationLoader::forget($t->locale));
        static::deleted(fn (Translation $t) => DatabaseTranslationLoader::forget($t->locale));
    }
}
