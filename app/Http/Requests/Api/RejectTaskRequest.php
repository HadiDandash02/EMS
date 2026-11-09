<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RejectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reject', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            // [CHANGE-REQUEST #8] (2/3) use 'min:10'
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
