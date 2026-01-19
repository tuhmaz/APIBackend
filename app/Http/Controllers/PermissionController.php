<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return view('content.dashboard.permissions.index', compact('permissions'));
    }

    public function create()
    {
        $validGuards = $this->validModelBackedGuards();
        return view('content.dashboard.permissions.create', compact('validGuards'));
    }

    public function store(Request $request)
{
    try {
        $validGuards = $this->validModelBackedGuards();
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions'],
            'guard_name' => ['required', 'string', Rule::in($validGuards)]
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        return redirect()->route('dashboard.permissions.index')
            ->with('success', __('Permission created successfully.'));
    } catch (\Exception $e) {
        return back()->withInput()
            ->withErrors(['error' => __('Failed to create permission. Please try again.')]);
    }
}

    public function edit(Permission $permission)
    {
        $validGuards = $this->validModelBackedGuards();
        return view('content.dashboard.permissions.edit', compact('permission', 'validGuards'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validGuards = $this->validModelBackedGuards();
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
            'guard_name' => ['required', 'string', Rule::in($validGuards)]
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        return redirect()->route('dashboard.permissions.index')
            ->with('success', __('Permission updated successfully.'));
    }

    public function destroy(Permission $permission)
    {
        try {
            // Normalize to a guard that has a valid provider model to avoid Spatie relation resolution errors
            $validGuards = $this->validModelBackedGuards();
            $defaultGuard = config('auth.defaults.guard', 'web');
            $fallbackGuard = in_array($defaultGuard, $validGuards, true)
                ? $defaultGuard
                : ( $validGuards[0] ?? 'web');

            if (! in_array($permission->guard_name, $validGuards, true)) {
                $permission->guard_name = $fallbackGuard;
                $permission->save();
            }

            // Proceed with deletion
            $permission->delete();
            return redirect()->route('dashboard.permissions.index')
                ->with('success', __('Permission deleted successfully.'));
        } catch (\Throwable $e) {
            Log::error('Failed to delete permission', [
                'permission_id' => $permission->id ?? null,
                'guard_name' => $permission->guard_name ?? null,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => __('Failed to delete permission. Please check guard configuration and try again.')]);
        }
    }

    /**
     * Return guard names that have a configured provider model class.
     * This avoids using guards like one without a model which breaks Spatie relations.
     *
     * @return array<int, string>
     */
    private function validModelBackedGuards(): array
    {
        $guards = config('auth.guards', []);
        $providers = config('auth.providers', []);

        $valid = [];
        foreach ($guards as $guardName => $guardConfig) {
            $providerName = $guardConfig['provider'] ?? null;
            if (! $providerName) {
                continue;
            }
            $provider = $providers[$providerName] ?? null;
            $modelClass = $provider['model'] ?? null;
            if (is_string($modelClass) && class_exists($modelClass)) {
                $valid[] = $guardName;
            }
        }

        // Always ensure at least 'web' is present if configured correctly
        return array_values(array_unique($valid));
    }
}
