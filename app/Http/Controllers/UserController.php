<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Requests\User\UserUpdateRolesPermissionsRequest;
use App\Http\Requests\User\UserBulkDeleteRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);
        $roles = Role::all();

        return view('content.dashboard.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view('content.dashboard.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password'])
            ]);

            if (isset($validated['role'])) {
                $user->assignRole($validated['role']);
            }
            
            if ($request->hasFile('profile_photo')) {
                 $path = $request->file('profile_photo')->store('profiles', 'public');
                 $user->profile_photo_path = $path;
                 $user->save();
            }

            DB::commit();

            return redirect()->route('dashboard.users.index')
                ->with('success', 'User created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Failed to create user')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('content.dashboard.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('content.dashboard.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $validated = $request->validated();
        
        $user->fill($validated);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profiles', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Cannot delete your own account');
        }

        $user->delete();
        return redirect()->route('dashboard.users.index')
            ->with('success', 'User deleted successfully');
    }

    /**
     * Show permissions and roles form.
     */
    public function permissions_roles(User $user)
    {
        $roles = Role::all();
        $permissions = Permission::all();
        return view('content.dashboard.users.permissions-roles', compact('user', 'roles', 'permissions'));
    }

    /**
     * Update permissions and roles.
     */
    public function update_permissions_roles(UserUpdateRolesPermissionsRequest $request, User $user)
    {
         $user->syncRoles($request->roles ?? []);
         $user->syncPermissions($request->permissions ?? []);
         
         return back()->with('success', 'Roles and permissions updated');
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(UserBulkDeleteRequest $request)
    {
        $count = 0;
        foreach ($request->user_ids as $id) {
            $user = User::find($id);
            if ($user && $user->id !== Auth::id()) {
                $user->delete();
                $count++;
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => "$count users deleted successfully"]);
        }

        return back()->with('success', "$count users deleted successfully");
    }
}
