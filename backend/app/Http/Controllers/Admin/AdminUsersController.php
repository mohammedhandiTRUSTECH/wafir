<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\ERPService;
use App\Jobs\ERPGetUsersJob;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserParent;
use App\Models\UserTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUsersController extends Controller
{
    private ERPService $erp;

    public function __construct(ERPService $erp)
    {
        $this->erp = $erp;
    }

    public function index(Request $request): JsonResponse
    {
        $search  = $request->get('search');
        $roleId  = $request->get('role_id');
        $perPage = $request->get('per_page', 20);

        $users = User::query()
            ->with(['role', 'parent', 'userLocation.location', 'target.target'])
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%$search%")
                ->orWhere('username', 'LIKE', "%$search%"))
            ->when($roleId, fn($q) => $q->where('role_id', $roleId))
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json(['status' => true, 'data' => $users]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['role', 'parent', 'children.role', 'userLocations.location', 'userParents.parent', 'target.target.details']);
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'oid'      => 'nullable|string',
            'is_active'=> 'nullable|boolean',
        ]);

        $user = User::query()->create([
            'name'      => $request->name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'oid'       => $request->oid,
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json(['status' => true, 'message' => 'User created successfully', 'data' => $user], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'username'    => 'sometimes|string|unique:users,username,' . $user->id,
            'password'    => 'nullable|string|min:6',
            'role_id'     => 'sometimes|exists:roles,id',
            'is_active'   => 'sometimes|boolean',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $data = $request->only(['name', 'username', 'role_id']);
        if ($request->has('is_active')) {
            $data['is_active'] = (bool) $request->is_active;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        if ($request->filled('location_id') && (!$user->userLocation || $user->userLocation->location_id != $request->location_id)) {
            UserLocation::query()->create([
                'user_id'     => $user->id,
                'location_id' => $request->location_id,
            ]);
        }

        $user->update($data);

        return response()->json(['status' => true, 'message' => 'User updated successfully', 'data' => $user->fresh(['role', 'userLocation'])]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->update(['parent_id' => null]);
        User::query()->where('parent_id', $user->id)->update(['parent_id' => null]);
        $user->delete();

        return response()->json(['status' => true, 'message' => 'User deleted successfully']);
    }

    public function hierarchy(): JsonResponse
    {
        $managers = User::query()
            ->where('role_id', 4)
            ->with(['children.children.children'])
            ->get();

        return response()->json(['status' => true, 'data' => $managers]);
    }

    public function children(User $user): JsonResponse
    {
        $available = User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('parent_id')
            ->with('role')
            ->get();

        return response()->json([
            'status'     => true,
            'data'       => [
                'user'      => $user->load('children.role'),
                'available' => $available,
            ],
        ]);
    }

    public function addChildren(User $user, Request $request): JsonResponse
    {
        $request->validate([
            'users'   => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        $erpService = $this->erp;

        foreach ($request->users as $childId) {
            $child = User::query()->with(['target', 'userParents'])->find($childId);

            if ($child->userParents->count()) {
                $lastParent = $child->userParents->first();
                $total = 0;
                $resp  = $erpService->totalSalesByDate($lastParent->start_date, now()->format('Y-m-d'), $child->oid, 1, false);
                if ($resp['success'] && isset($resp['data'][0]->TotalSales)) {
                    $total = $resp['data'][0]->TotalSales;
                }
                $lastParent->update([
                    'total_sales' => $total,
                    'target'      => $child->target ? $child->target->target->target : 0,
                ]);
                UserParent::query()->create([
                    'user_id'    => $childId,
                    'parent_id'  => $user->id,
                    'start_date' => now()->format('Y-m-d'),
                    'total_sales'=> 0,
                ]);
            } else {
                UserParent::query()->create([
                    'user_id'    => $childId,
                    'parent_id'  => $user->id,
                    'start_date' => '2025-01-01',
                    'total_sales'=> 0,
                ]);
            }

            $child->update(['parent_id' => $user->id]);
        }

        return response()->json(['status' => true, 'message' => 'Children added successfully']);
    }

    public function removeChild(User $user): JsonResponse
    {
        $user->update(['parent_id' => null]);
        return response()->json(['status' => true, 'message' => 'Child removed successfully']);
    }

    public function sales(User $user, Request $request): JsonResponse
    {
        if ($user->role_id !== 1) {
            return response()->json(['status' => false, 'message' => 'Sales are only available for sales representatives'], 422);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $resp = $this->erp->totalSalesByDate($request->start_date, $request->end_date, $user->oid);

        return response()->json([
            'status' => $resp['success'],
            'data'   => $resp['data'] ?? [],
            'user'   => $user->only(['id', 'name', 'oid']),
        ]);
    }

    public function syncFromERP(): JsonResponse
    {
        dispatch(new ERPGetUsersJob());
        return response()->json(['status' => true, 'message' => 'ERP sync started. Users will be updated in a few minutes.']);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => Role::all()]);
    }
}
