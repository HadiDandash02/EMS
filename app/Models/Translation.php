<?php

namespace App\Models;

use App\Support\DatabaseTranslationLoader;
use Illuminate\Database\Eloquent\Model;

/**
 * One translation key with its English and Arabic text. The key is what the code uses: __('Approve task').
 * A null / empty text means "not translated yet" (the key itself is shown).
 */
class Translation extends Model
{
    public const TYPES = ['code', 'keyword'];

    /** the languages that have a column */
    public const LOCALES = ['en', 'ar'];

    protected $fillable = ['key', 'type', 'en', 'ar', 'machine'];

    protected $casts = ['machine' => 'boolean'];

    protected static function booted(): void
    {
        // key_hash is always derived from the key, and any change drops the cached copies used by __()
        static::saving(function (Translation $t) {
            $t->key_hash = sha1($t->key);
            $t->type ??= 'code';
        });

        static::saved(fn () => DatabaseTranslationLoader::forgetAll());
        static::deleted(fn () => DatabaseTranslationLoader::forgetAll());
    }

    /** rows whose text in the given language ('en' or 'ar') is empty */
    public function scopeUntranslated($query, string $locale = 'ar')
    {
        return $query->where(fn ($q) => $q->whereNull($locale)->orWhere($locale, ''));
    }

    public function scopeTranslated($query, string $locale = 'ar')
    {
        return $query->whereNotNull($locale)->where($locale, '!=', '');
    }
}
