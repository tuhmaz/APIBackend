<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\Api\RoleResource;
use App\Http\Resources\Api\PermissionResource;
use App\Http\Resources\BaseResource;

class RoleApiController extends Controller
{
    /**
     * GET /api/roles
     * جلب جميع الأدوار مع عدد المستخدمين لكل دور
     */
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->get();

        return RoleResource::collection($roles)
            ->additional(['success' => true]);
    }

    /**
     * GET /api/roles/{id}
     * عرض دور واحد مع صلاحيته
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return new RoleResource($role);
    }

    /**
     * POST /api/roles
     * إنشاء دور جديد وربط الصلاحيات
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return (new RoleResource($role))
            ->additional(['message' => 'Role created successfully.']);
    }

    /**
     * PUT /api/roles/{id}
     * تحديث دور قائم
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return (new RoleResource($role))
            ->additional(['message' => 'Role updated successfully.']);
    }

    /**
     * DELETE /api/roles/{id}
     * حذف دور
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return new BaseResource(['message' => 'Role deleted successfully.']);
    }

    /**
     * GET /api/permissions
     * جلب جميع الصلاحيات
     */
    public function permissions()
    {
        return PermissionResource::collection(Permission::all())
            ->additional(['success' => true]);
    }
}
