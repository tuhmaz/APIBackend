<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\BaseResource;

class PermissionApiController extends Controller
{
    /**
     * List all permissions
     */
    public function index()
    {
        return new BaseResource([
            'permissions' => Permission::all()
        ]);
    }

    /**
     * Create a permission
     */
    public function store(Request $request)
    {
        try {
            $validGuards = $this->validModelBackedGuards();

            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:permissions'],
                'guard_name' => [
                    'required',
                    'string',
                    Rule::in($validGuards)
                ],
            ]);

            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name,
            ]);

            return new BaseResource([
                'message' => 'Permission created successfully.',
                'permission' => $permission
            ]);

        } catch (\Throwable $e) {
            Log::error('Permission create error', ['error' => $e->getMessage()]);

            return (new BaseResource(['message' => 'Failed to create permission. Please try again.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Single permission details
     */
    public function show(Permission $permission)
    {
        return new BaseResource([
            'permission' => $permission
        ]);
    }

    /**
     * Update permission
     */
    public function update(Request $request, Permission $permission)
    {
        $validGuards = $this->validModelBackedGuards();

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('permissions')->ignore($permission->id)
            ],
            'guard_name' => [
                'required',
                'string',
                Rule::in($validGuards)
            ],
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);

        return new BaseResource([
            'message' => 'Permission updated successfully.',
            'permission' => $permission
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission)
    {
        try {
            $validGuards = $this->validModelBackedGuards();

            // Fix invalid guard names before delete
            $defaultGuard = config('auth.defaults.guard', 'web');
            $fallback = in_array($defaultGuard, $validGuards)
                ? $defaultGuard
                : ($validGuards[0] ?? 'web');

            if (! in_array($permission->guard_name, $validGuards, true)) {
                $permission->guard_name = $fallback;
                $permission->save();
            }

            $permission->delete();

            return new BaseResource([
                'message' => 'Permission deleted successfully.'
            ]);

        } catch (\Throwable $e) {
            Log::error('Permission delete error', [
                'failed_id' => $permission->id,
                'error' => $e->getMessage()
            ]);

            return (new BaseResource(['message' => 'Failed to delete permission. Please check guard configuration.']))
                ->response(request())
                ->setStatusCode(500);
        }
    }

    /**
     * Returns only guards connected to a real user model (important for Spatie)
     */
    private function validModelBackedGuards(): array
    {
        $guards = config('auth.guards', []);
        $providers = config('auth.providers', []);

        $valid = [];

        foreach ($guards as $guardName => $guardConfig) {
            $providerName = $guardConfig['provider'] ?? null;
            if (!$providerName) continue;

            $provider = $providers[$providerName] ?? null;
            $model = $provider['model'] ?? null;

            if (is_string($model) && class_exists($model)) {
                $valid[] = $guardName;
            }
        }

        return array_values(array_unique($valid));
    }
}
