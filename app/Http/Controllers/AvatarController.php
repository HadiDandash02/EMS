<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** Profile pictures live on the private disk and are streamed only to signed-in users. */
class AvatarController extends Controller
{
    public function __invoke(User $user)
    {
        $disk = Storage::disk('local');

        abort_unless($user->avatar_path && $disk->exists($user->avatar_path), 404);

        return $disk->response($user->avatar_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
