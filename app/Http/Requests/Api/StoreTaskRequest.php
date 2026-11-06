<?php

namespace App\Http\Requests\Api;

use App\Models\Task;
use App\Support\TaskRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->isSuperAdmin() ? $user->can('createFor', Task::class) : $user->can('create', Task::class);
    }

    public function rules(): array
    {
        $rules = TaskRules::fields();

        // Only the super admin chooses the owner; for everyone else the field is simply not accepted.
        if ($this->user()->isSuperAdmin()) {
            $rules['employee_id'] = ['required', 'integer', 'exists:employees,id'];
        }

        return $rules;
    }
}