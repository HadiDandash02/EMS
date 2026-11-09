<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A user may hold SEVERAL roles. `users.role` stays the MAIN role (it decides the default panel / URL prefix and the
 * label); `extra_roles` holds the additional ones as a JSON list, e.g. ["hr"]. Every permission check asks
 * "does the user have this role?", so the powers of all roles are combined.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('extra_roles')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('extra_roles');
        });
    }
};
