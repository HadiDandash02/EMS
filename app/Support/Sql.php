<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class Sql
{
    /**
     * Portable, injection-safe "contains" search.
     * Wildcards typed by the user (% and _) are escaped so they match literally, and an
     * explicit ESCAPE clause makes this behave the same on MySQL and SQLite.
     */
    public static function whereContains(Builder $query, string $column, string $term, string $boolean = 'and'): Builder
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $query->whereRaw("{$column} LIKE ? ESCAPE '!'", ["%{$escaped}%"], $boolean);
    }
}
