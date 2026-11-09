<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * History rows whose task was deleted. They can exist when the tables were created as MyISAM (which ignores
 * foreign keys, so "cascade on delete" never ran). The approval history page crashed on them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('task_histories')->whereNotIn('task_id', DB::table('tasks')->select('id'))->delete();
    }

    public function down(): void
    {
        // deleted rows cannot be restored (they described tasks that no longer exist)
    }
};
