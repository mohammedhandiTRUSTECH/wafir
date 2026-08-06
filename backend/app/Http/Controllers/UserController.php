<?php

namespace App\Http\Controllers;

use App\Excel\ImportUsersSales;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Services\ERPService;
use App\Jobs\ERPGetUsersJob;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserParent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    private ERPService $erp;

    public function __construct(ERPService $erp)
    {
        $this->erp = $erp;
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $roleId = $request->get('role_id');
        $users = User::query()
            ->with(['parent', 'userLocation'])
            ->when(!is_null($search), function ($query) use ($search) {
                $query->where('name', "LIKE", "%$search%");
            })
            ->when(!is_null($roleId), function ($query) use ($roleId) {
                $query->where('role_id', $roleId);
            })
            ->orderByDesc('id')->paginate(20);
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function updateFromERP()
    {
        dispatch(new ERPGetUsersJob());
        session()->flash('users-update', 'The update users process is started, please refresh the page after 5 minutes.');
        return back();
    }

    public function userChildren(User $user): View
    {
        $users = User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('parent_id')
            ->get();
        return view('users.add-children', compact('users', 'user'));
    }

    public function addUserChildren(User $user, Request $request)
    {
        $this->validate($request, [
            'users' => 'required|array',
            'users.*' => 'exists:users,id'
        ]);
        $erpService = $this->erp;
        foreach ($request->get('users') as $child) {
            $ch = User::query()->with(['target'])->find($child);
            if (count($ch->userParents)) {
                $lastParent = $ch->userParents[0];
                $startDate = $lastParent->start_date;
                $endDate = now()->format('Y-m-d');
                $total = 0;
                $resp = $erpService->totalSalesByDate($startDate, $endDate, $ch->oid, 1, false);
                if ($resp['success'] and is_array($resp['data']) and isset($resp['data'][0])) {
                    $total = $resp['data'][0]->TotalSales;
                }
                $target = $ch->target ? $ch->target->target->target : 0;
                $lastParent->update([
                    'total_sales' => $total,
                    'target' => $target,
                ]);
                UserParent::query()->create([
                    'user_id' => $child,
                    'parent_id' => $user->id,
                    'start_date' => now()->format('Y-m-d'),
                    'total_sales' => 0
                ]);
            } else {
                UserParent::query()->create([
                    'user_id' => $child,
                    'parent_id' => $user->id,
                    'start_date' => '2025-01-01',
                    'total_sales' => 0,
                ]);
            }
            $ch->update(['parent_id' => $user->id]);
        }
        session()->flash('success', 'Children added successfully');
        return back();
    }

    public function removeChild(User $user)
    {
        $user->update(['parent_id' => null]);
        session()->flash('success', 'Children removed successfully');
        return back();
    }

    public function edit($id)
    {
        $user = User::query()->find($id);
        if ($user) {
            $roles = Role::query()->get();
            $locations = Location::query()->get();
            return view('users.edit', compact('user', 'roles', 'locations'));
        }
        return back();
    }

    public function update($id, UpdateUserRequest $request)
    {
        $user = User::query()->find($id);
        if ($user) {
            $data = $request->only(['name', 'username', 'role_id']);
            $data['is_active'] = (bool)$request->get('is_active');
            if ($request->get('password')) {
                $data['password'] = Hash::make($request->get('password'));
            }
            if ($request->get('location_id') and (!$user->userLocation or $user->userLocation->location_id != $request->get('location_id'))) {
                UserLocation::query()->create([
                    'user_id' => $user->id,
                    'location_id' => $request->get('location_id')
                ]);
            }
            $user->update($data);
            session()->flash('users-update', 'User updated successfully');
            return redirect()->route('users.index');
        }
        return back();
    }

    public function userHierarchy()
    {
        $salesManagers = User::query()->where('role_id', 4)->get();
        return view('users.user-hierarchy', compact('salesManagers'));
    }

    public function userSales(User $user, Request $request)
    {
        if($user->role_id != 1){
            return back();
        }
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $getTotalSales = [];
        $totalSales = null;
        if ($startDate && $endDate) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            $getTotalSales = $this->erp->totalSalesByDate($startDate, $endDate, $user->oid);
        }
        return view('users.sales', compact('startDate', 'totalSales', 'endDate', 'user','getTotalSales'));
    }

    public function create()
    {
        $roles = Role::query()->get();
        return view('users.create', compact('roles'));
    }

    public function show(User $user) : View
    {
        $user->load(['userParents','userLocations']);
        return view('users.show', compact('user'));
    }


    public function store(CreateUserRequest $request)
    {
        $data = $request->only(['role_id', 'username', 'name', 'password']);
        $data['is_active'] = true;
        User::query()->create($data);
        session()->flash('users-created', 'User created successfully');
        return redirect()->route('users.index');
    }

    public function getOid($id, Request $request): JsonResponse
    {
        if ($request->get('passCode') == '@@321456') {
            $user = User::query()->find($id);
            if ($user) {
                return response()->json(['status' => true, 'message' => $user->oid]);
            }
            return response()->json(['status' => false, 'message' => 'User Not found']);
        }
        return response()->json(['status' => false, 'message' => 'Incorrect password']);
    }

    public function importSales()
    {
        return view('users.import-sales');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        Excel::import(new ImportUsersSales(), $request->file('file'));
        session()->flash('users-imported', 'Users imported successfully');
        return back();
    }


}
