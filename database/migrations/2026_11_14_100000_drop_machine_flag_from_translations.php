<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The automatic (machine) translation feature was removed: translations are written and edited by people only. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('translations', 'machine')) {
            Schema::table('translations', fn (Blueprint $table) => $table->dropColumn('machine'));
        }
    }

    public function down(): void
    {
        Schema::table('translations', fn (Blueprint $table) => $table->boolean('machine')->default(false));
    }
};