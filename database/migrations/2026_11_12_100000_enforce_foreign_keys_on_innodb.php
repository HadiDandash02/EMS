<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * XAMPP's MySQL used to create tables as MyISAM, which IGNORES foreign keys: the earlier migrations declared them
 * but the database never created them (no cascade, no restrict, orphan rows). This migration repairs such a
 * database (it does nothing on a database that is already correct, and nothing on SQLite used by the tests):
 *
 *   1. converts the application tables to InnoDB,
 *   2. removes the rows the cascades would have removed / empties the links that "set null" would have emptied,
 *   3. creates the foreign keys (same rules as the original migrations).
 */
return new class extends Migration
{
    private const TABLES = ['users', 'departments', 'employees', 'tasks', 'task_histories', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && $this->engine($table) !== 'InnoDB') {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }
        }

        // 2. orphans (what the missing cascades / set-null rules should have taken care of)
        DB::table('employees')->whereNotIn('user_id', DB::table('users')->select('id'))->delete();
        DB::table('employees')->whereNotIn('department_id', DB::table('departments')->select('id'))->delete();
        // (MySQL does not allow updating a table while a sub-query reads the same table: read the ids first)
        DB::table('employees')->whereNotNull('manager_id')->whereNotIn('manager_id', DB::table('employees')->pluck('id'))->update(['manager_id' => null]);
        DB::table('tasks')->whereNotIn('employee_id', DB::table('employees')->select('id'))->delete();
        DB::table('tasks')->whereNotNull('reviewed_by')->whereNotIn('reviewed_by', DB::table('users')->select('id'))->update(['reviewed_by' => null]);
        DB::table('task_histories')->whereNotIn('task_id', DB::table('tasks')->select('id'))->delete();
        DB::table('task_histories')->whereNotNull('actor_id')->whereNotIn('actor_id', DB::table('users')->select('id'))->update(['actor_id' => null]);

        // 3. the foreign keys
        $this->foreign('employees', 'user_id', 'users', 'cascade');
        $this->foreign('employees', 'department_id', 'departments', 'restrict');
        $this->foreign('employees', 'manager_id', 'employees', 'set null');
        $this->foreign('tasks', 'employee_id', 'employees', 'cascade');
        $this->foreign('tasks', 'reviewed_by', 'users', 'set null');
        $this->foreign('task_histories', 'task_id', 'tasks', 'cascade');
        $this->foreign('task_histories', 'actor_id', 'users', 'set null');
    }

    public function down(): void
    {
        // Nothing to undo: a database with working foreign keys is the intended state.
    }

    private function engine(string $table): ?string
    {
        return DB::selectOne('SELECT ENGINE e FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table])?->e;
    }

    private function foreign(string $table, string $column, string $references, string $onDelete): void
    {
        $name = "{$table}_{$column}_foreign";

        $exists = DB::selectOne('SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?', [$table, $name])->c;

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($column, $references, $onDelete) {
            $t->foreign($column)->references('id')->on($references)->onDelete($onDelete);
        });
    }
};
