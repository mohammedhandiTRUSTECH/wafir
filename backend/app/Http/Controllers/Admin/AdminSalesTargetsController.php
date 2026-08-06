<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;
use App\Models\SaleTargetDetails;
use App\Models\User;
use App\Models\UserTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSalesTargetsController extends Controller
{
    public function index(): JsonResponse
    {
        $targets = SalesTarget::query()
            ->with(['details', 'users.user.role'])
            ->get();

        return response()->json(['status' => true, 'data' => $targets]);
    }

    public function show(string $id): JsonResponse
    {
        $target = SalesTarget::query()
            ->with(['details', 'users.user.role'])
            ->findOrFail($id);

        return response()->json(['status' => true, 'data' => $target]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'target'                => 'required|numeric|min:0',
            'users'                 => 'nullable|array',
            'users.*'               => 'exists:users,id',
            'rules'                 => 'required|array|min:1',
            'rules.*.sale_percentage'       => 'required|numeric|min:0|max:100',
            'rules.*.commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $target = SalesTarget::query()->create($request->only(['name', 'target']));

        foreach ($request->rules as $rule) {
            SaleTargetDetails::query()->create([
                'sales_target_id'      => $target->id,
                'sale_percentage'      => $rule['sale_percentage'],
                'commission_percentage'=> $rule['commission_percentage'],
            ]);
        }

        foreach ($request->input('users', []) as $userId) {
            UserTarget::query()->create([
                'user_id'         => $userId,
                'sales_target_id' => $target->id,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Target created successfully',
            'data'    => $target->load(['details', 'users.user']),
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $target = SalesTarget::query()->findOrFail($id);
        $target->details()->delete();
        $target->users()->delete();
        $target->delete();

        return response()->json(['status' => true, 'message' => 'Target deleted successfully']);
    }

    public function assignUsers(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'users'   => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        $target = SalesTarget::query()->findOrFail($id);

        foreach ($request->users as $userId) {
            UserTarget::query()->updateOrCreate(
                ['user_id' => $userId],
                ['user_id' => $userId, 'sales_target_id' => $target->id]
            );
        }

        return response()->json(['status' => true, 'message' => 'Users assigned successfully', 'data' => $target->load('users.user')]);
    }

    public function removeUser(string $userTargetId): JsonResponse
    {
        UserTarget::query()->findOrFail($userTargetId)->delete();
        return response()->json(['status' => true, 'message' => 'User removed from target']);
    }

    public function availableUsers(): JsonResponse
    {
        $assignedIds = UserTarget::query()->pluck('user_id')->toArray();

        $users = User::query()
            ->select(['id', 'name', 'role_id'])
            ->with('role')
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'data' => $users]);
    }

    public function addRule(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'sale_percentage'       => 'required|numeric|min:0|max:100',
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $target = SalesTarget::query()->findOrFail($id);

        $rule = SaleTargetDetails::query()->create([
            'sales_target_id'      => $target->id,
            'sale_percentage'      => $request->sale_percentage,
            'commission_percentage'=> $request->commission_percentage,
        ]);

        return response()->json(['status' => true, 'message' => 'Rule added successfully', 'data' => $rule], 201);
    }

    public function removeRule(string $ruleId): JsonResponse
    {
        SaleTargetDetails::query()->findOrFail($ruleId)->delete();
        return response()->json(['status' => true, 'message' => 'Rule removed successfully']);
    }
}