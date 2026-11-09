<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Admin\DeleteDepartment;
use App\Livewire\Admin\DeleteUser;
use App\Livewire\Admin\DepartmentForm;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserManager;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Tasks\ConfirmAction;
use App\Livewire\Tasks\RejectTask;
use App\Livewire\Tasks\TaskForm;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Tasks\TaskShow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// employee|manager|hr|admin (config/ems.php "panels")
$prefixes = implode('|', array_values(config('ems.panels')));

// ---- Guests --------------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

// Language switch (works for guests too): remembered in the session
Route::get('/language/{locale}', LanguageController::class)->name('language');

// ---- Signed in, no prefix: start page, logging out, profile pictures --------------------------
Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/avatars/{user}', AvatarController::class)->whereNumber('user')->name('avatar');
});

// ---- Everything else lives under the role: /employee/..., /manager/..., /hr/..., /admin/... ----
// route('tasks.index') needs no argument: SetPanelPrefix fills {panel} with the user's own prefix.
Route::middleware('auth')->prefix('{panel}')->where(['panel' => $prefixes])->group(function () {
    // /hr -> /hr/dashboard
    Route::get('/', HomeController::class)->name('panel.home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // My profile (every role)
    Route::get('/profile', EditProfile::class)->name('profile');

    // My tasks + task detail (per-task access is enforced by TaskPolicy::view)
    Route::get('/tasks', TaskList::class)->defaults('scope', 'mine')->name('tasks.index');
    Route::get('/tasks/create', TaskForm::class)->name('tasks.create');
    Route::get('/tasks/{task}', TaskShow::class)->whereNumber('task')->name('tasks.show');
    // Every form and confirmation is its own page (no popups)
    Route::get('/tasks/{task}/edit', TaskForm::class)->whereNumber('task')->name('tasks.edit');
    Route::get('/tasks/{task}/reject', RejectTask::class)->whereNumber('task')->name('tasks.reject');
    Route::get('/tasks/{task}/confirm/{action}', ConfirmAction::class)->whereNumber('task')->whereIn('action', ['complete', 'approve', 'cancel', 'delete'])->name('tasks.confirm');

    // Manager: the team's tasks
    Route::get('/team/tasks', TaskList::class)->defaults('scope', 'team')
        ->middleware('role:manager')->name('team.tasks');

    // Manager (own decisions) and HR / admin (all decisions)
    Route::get('/approvals', ApprovalHistory::class)
        ->middleware('role:manager,hr')->name('approvals.history');

    // HR (and admin): company-wide views
    Route::middleware('role:hr')->group(function () {
        Route::get('/all-tasks', TaskList::class)->defaults('scope', 'all')->name('tasks.all');
        Route::get('/employees', EmployeeList::class)->name('employees.index');
    });

    // Super admin only: accounts and departments (CRUD)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', UserManager::class)->name('admin.users');
        Route::get('/users/create', UserForm::class)->name('admin.users.create');
        Route::get('/users/{user}/edit', UserForm::class)->whereNumber('user')->name('admin.users.edit');
        Route::get('/users/{user}/delete', DeleteUser::class)->whereNumber('user')->name('admin.users.delete');

        Route::get('/translations', \App\Livewire\Admin\TranslationManager::class)->name('admin.translations');

        Route::get('/departments', DepartmentManager::class)->name('admin.departments');
        Route::get('/departments/create', DepartmentForm::class)->name('admin.departments.create');
        Route::get('/departments/{department}/edit', DepartmentForm::class)->whereNumber('department')->name('admin.departments.edit');
        Route::get('/departments/{department}/delete', DeleteDepartment::class)->whereNumber('department')->name('admin.departments.delete');
    });
});

// ---- Old addresses without the prefix (bookmarks): /tasks/5 -> /employee/tasks/5 (301) ----------
Route::middleware('auth')->get('/{path}', LegacyRedirectController::class)->where('path', '(dashboard|tasks|team|approvals|profile|employees|all-tasks|users|departments)(/[A-Za-z0-9/-]*)?');
