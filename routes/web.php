<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Profile\EditProfile;
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
Route::get('/language/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, config('ems.locales')), 404);
    $request->session()->put('locale', $locale);

    return redirect()->back(fallback: '/');
})->name('language');

// ---- Signed in, no prefix: start page, logging out, profile pictures --------------------------
Route::middleware('auth')->group(function () {
    Route::get('/', fn (Request $request) => redirect($request->user()->homeUrl()))->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/avatars/{user}', AvatarController::class)->whereNumber('user')->name('avatar');
});

// ---- Everything else lives under the role: /employee/..., /manager/..., /hr/..., /admin/... ----
// route('tasks.index') needs no argument: SetPanelPrefix fills {panel} with the user's own prefix.
Route::middleware('auth')->prefix('{panel}')->where(['panel' => $prefixes])->group(function () {
    // /hr -> /hr/dashboard
    Route::get('/', fn (Request $request) => redirect($request->user()->homeUrl()))->name('panel.home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // My profile (every role)
    Route::get('/profile', EditProfile::class)->name('profile');

    // My tasks + task detail (per-task access is enforced by TaskPolicy::view)
    Route::get('/tasks', TaskList::class)->defaults('scope', 'mine')->name('tasks.index');
    Route::get('/tasks/{task}', TaskShow::class)->whereNumber('task')->name('tasks.show');

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
        Route::get('/departments', DepartmentManager::class)->name('admin.departments');
    });
});

// ---- Old addresses without the prefix (bookmarks): /tasks/5 -> /employee/tasks/5 (301) ----------
Route::middleware('auth')->get('/{path}', fn (Request $request, string $path) => redirect(
    url($request->user()->url_prefix.'/'.$path).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301,
))->where('path', '(dashboard|tasks|team|approvals|profile|employees|all-tasks|users|departments)(/[A-Za-z0-9/-]*)?');
