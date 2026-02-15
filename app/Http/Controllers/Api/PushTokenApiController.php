<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenApiController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', Rule::in(['android', 'ios', 'web'])],
            'device_name' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $token = trim($validated['token']);
        if ($token === '') {
            return (new BaseResource(['message' => 'Invalid token']))
                ->response($request)
                ->setStatusCode(422);
        }

        PushToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return new BaseResource([
            'message' => 'Push token registered successfully',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $validated = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
        ]);

        $query = PushToken::query()->where('user_id', $user->id);

        $token = trim((string) ($validated['token'] ?? ''));
        if ($token !== '') {
            $query->where('token', $token);
        }

        $deleted = $query->delete();

        return new BaseResource([
            'message' => 'Push token removed successfully',
            'deleted' => $deleted,
        ]);
    }
}
