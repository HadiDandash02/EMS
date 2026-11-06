<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // One login account <-> one employee profile.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // RESTRICT: a department that still has staff cannot be deleted.
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            // Self reference = reporting line. Nullable: top of the hierarchy (HR / heads).
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_number', 20)->unique();
            $table->string('job_title', 100);
            $table->date('hired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
