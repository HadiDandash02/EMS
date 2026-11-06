<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->loadMissing('employee.department');

        // One grouped query instead of one COUNT per status.
        $mine = Task::query()
            ->where('employee_id', $user->employee?->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $awaiting = $user->isManager()
            ? Task::forTeamOf($user)->where('status', TaskStatus::Completed)->count()
            : null;

        $company = $user->isHr()
            ? [
                'employees' => Employee::count(),
                'tasks' => Task::count(),
                'awaiting' => Task::where('status', TaskStatus::Completed)->count(),
            ]
            : null;

        return view('dashboard', compact('user', 'mine', 'awaiting', 'company'));
    }
}
