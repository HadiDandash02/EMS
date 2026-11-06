<?php

namespace App\Http\Requests\Api;

use App\Support\TaskRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorise BEFORE validating so outsiders get a 403, not hints about the schema.
        return $this->user()->can('update', $this->route('task'));
    }

    /** PUT/PATCH both accept partial payloads. */
    public function rules(): array
    {
        return collect(TaskRules::fields())
            ->map(fn (array $rules) => ['sometimes', ...$rules])
            ->all();
    }
}
