<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('priority', 10)->default('medium');
            $table->string('status', 20)->default('PENDING');
            $table->date('due_date')->nullable();

            // Denormalised "latest review" snapshot for fast display; the full trail is in task_histories.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes chosen from the actual query patterns:
            $table->index(['employee_id', 'status']); // "my tasks" + status filter, manager team scope
            $table->index('status');                  // HR status filter / approval queue
            $table->index('due_date');
            $table->index('created_at');              // date-range filter + default ordering
            $table->index('title');                   // title search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
