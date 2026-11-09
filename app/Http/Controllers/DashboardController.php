<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Support\DashboardStats;
use Illuminate\Http\Request;

/**
 * One dashboard, three audiences. Each block of charts is built from a task query that is already scoped:
 *   mine    -> the user's own tasks            (everyone who has an employee profile)
 *   team    -> tasks of the manager's reports  (managers)
 *   company -> every task                      (HR and the super admin)
 * The numbers come from App\Support\DashboardStats (one grouped query per statistic).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStats $stats)
    {
        $user = $request->user()->loadMissing('employee.department');

        $mineQuery = Task::query()->where('tasks.employee_id', $user->employee?->id);

        // "My tasks" is part of every employee's and manager's dashboard. HR and the super admin only get it when they
        // really have tasks of their own - otherwise the block would be an empty page of zeros.
        $mine = null;
        if ($user->employee) {
            $headline = $stats->headline($mineQuery);

            if ($headline['total'] > 0 || ! $user->canSeeAll()) {
                $mine = [
                    'headline' => $headline,
                    'status' => $stats->byStatus($mineQuery),
                    'priority' => $stats->byPriority($mineQuery),
                    'trend' => $stats->createdPerDay($mineQuery),
                ];
            }
        }

        $team = null;
        if ($user->isManager()) {
            $teamQuery = Task::forTeamOf($user);
            $team = [
                'members' => $user->employee->directReports()->count(),
                'headline' => $stats->headline($teamQuery),
                'status' => $stats->byStatus($teamQuery),
                'perMember' => $stats->perEmployee($teamQuery),
                'decisions' => $stats->decisions($user),
            ];
        }

        $company = null;
        if ($user->canSeeAll()) {
            $all = Task::query();
            $company = [
                'numbers' => $stats->company(),
                'headline' => $stats->headline($all),
                'status' => $stats->byStatus($all),
                'priority' => $stats->byPriority($all),
                'trend' => $stats->createdPerDay($all),
                'perDepartment' => $stats->perDepartment($all),
                'perEmployee' => $stats->perEmployee($all),
                'decisions' => $stats->decisions(),
                'roles' => $user->isSuperAdmin() ? $stats->usersByRole() : null,
            ];
        }

        // kept for the existing "My tasks" status cards
        $mineCounts = $mine ? array_combine(array_map(fn ($c) => $c->value, \App\Enums\TaskStatus::cases()), $mine['status']['values']) : [];

        return view('dashboard', compact('user', 'mine', 'team', 'company', 'mineCounts'));
    }
}
