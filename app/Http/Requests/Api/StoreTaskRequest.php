<?php

namespace App\Http\Requests\Api;

use App\Models\Task;
use App\Support\TaskRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return TaskRules::fields();
    }
}
