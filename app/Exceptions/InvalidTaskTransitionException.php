<?php

namespace App\Exceptions;

use App\Enums\TaskStatus;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when a request is valid and authorised but the task is in the wrong state
 * (e.g. approving an already approved task). Rendered as HTTP 422 for the API.
 */
class InvalidTaskTransitionException extends Exception
{
    public static function between(TaskStatus $from, TaskStatus $to): self
    {
        return new self("A {$from->label()} task cannot be changed to {$to->label()}.");
    }

    public static function notEditable(TaskStatus $status): self
    {
        return new self("Only pending tasks can be edited (this task is {$status->label()}).");
    }

    public function render(Request $request): JsonResponse|false
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return false; // fall back to default handling for web requests
    }
}
