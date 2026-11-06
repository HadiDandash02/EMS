<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sign in with a username OR the email. Stored lowercase.
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
        });

        // Give any pre-existing account a username derived from its email.
        DB::table('users')->whereNull('username')->orderBy('id')->each(function ($user) {
            $base = preg_replace('/[^a-z0-9_.]/', '', strtolower(strstr($user->email, '@', true))) ?: 'user';
            $username = $base;
            $i = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.++$i;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone', 'avatar_path']);
        });
    }
};
