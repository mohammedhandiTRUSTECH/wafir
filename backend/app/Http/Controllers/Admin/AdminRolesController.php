<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRolesController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()->with('commission')->get();
        return response()->json(['status' => true, 'data' => $roles]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('commission');
        return response()->json(['status' => true, 'data' => $role]);
    }

    public function updateCommission(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        RoleCommission::query()->updateOrCreate(
            ['role_id' => $role->id],
            ['commission_percentage' => $request->commission_percentage]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Commission updated successfully',
            'data'    => $role->load('commission'),
        ]);
    }
}
