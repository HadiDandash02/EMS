<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Tasks\TaskShow;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Everyone: their own tasks + task detail (per-task access is enforced by TaskPolicy::view)
    Route::get('/tasks', TaskList::class)->defaults('scope', 'mine')->name('tasks.index');
    Route::get('/tasks/{task}', TaskShow::class)->whereNumber('task')->name('tasks.show');

    // Managers: their team's tasks
    Route::get('/team/tasks', TaskList::class)->defaults('scope', 'team')
        ->middleware('role:manager')->name('team.tasks');

    // Managers (own decisions) and HR (all decisions)
    Route::get('/approvals', ApprovalHistory::class)
        ->middleware('role:manager,hr')->name('approvals.history');

    // HR: company-wide views
    Route::middleware('role:hr')->prefix('hr')->group(function () {
        Route::get('/tasks', TaskList::class)->defaults('scope', 'all')->name('hr.tasks');
        Route::get('/employees', EmployeeList::class)->name('hr.employees');
    });
});
