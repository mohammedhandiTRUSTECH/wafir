<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTargetRequest;
use App\Models\SalesTarget;
use App\Models\SaleTargetDetails;
use App\Models\User;
use App\Models\UserTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaleTargetController extends Controller
{

    public function index()
    {
        $salesTargets = SalesTarget::query()->with(['users'])->get();
        return view('sales_targets.index', compact('salesTargets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $assignedUsers = UserTarget::query()->select(['user_id'])->get()->pluck('user_id')->toArray();
        $users = User::query()->select(['id', 'name'])
            ->whereNotIn('id', $assignedUsers)
            ->orderBy('name')
            ->get();
        return view('sales_targets.create', compact('users'));
    }

    public function addCommissionRule()
    {
        $rand = Str::random(5);
        $view = view('sales_targets.add-rule', compact('rand'))->render();
        return response()->json([
            'view' => $view,
            'rand' => $rand,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTargetRequest $request)
    {
        $targetDate = $request->only(['name', 'target']);
        $target = SalesTarget::query()->create($targetDate);
        foreach ($request->get('users') as $user) {
            UserTarget::query()->create([
                'user_id' => $user,
                'sales_target_id' => $target->getKey(),
            ]);
        }
        $this->addCommissionRules($request->get('all_rules'), $target->getKey(), $request);
        session()->flash('target-created', 'Target has been created');
        return redirect(route('sales-targets.show',$target));
    }

    public function addCommissionRules($rands, $key, $request)
    {
        SaleTargetDetails::query()->create([
            'sales_target_id' => $key,
            'sale_percentage' => $request->get('sales_percentage'),
            'commission_percentage' => $request->get('commission_percentage'),
        ]);

        $rands = explode(",", $rands);
        $rands = array_filter($rands);
        foreach ($rands as $rand) {
            if ($request->get('sales_percentage_' . $rand) and $request->get('commission_percentage_' . $rand)) {
                SaleTargetDetails::query()->create([
                    'sales_target_id' => $key,
                    'sale_percentage' => $request->get('sales_percentage_' . $rand),
                    'commission_percentage' => $request->get('commission_percentage_' . $rand),
                ]);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $target = SalesTarget::query()
            ->with(['users','details'])
            ->findOrFail($id);
        return view('sales_targets.show', compact('target'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $target = SalesTarget::query()->findOrFail($id);
        $target->details()->delete();
        $target->users()->delete();
        $target->delete();
        session()->flash('target-deleted', 'Target has been deleted');
        return redirect(route('sales-targets.index'));
    }

    public function addUsers($id)
    {
        $target = SalesTarget::query()->findOrFail($id);
        $users = User::query()->select(['id', 'name'])
            ->with('target')
            ->get();
        return view('sales_targets.add-users', compact('target', 'users'));
    }

    public function storeUsers(Request $request, $id)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);
        $target = SalesTarget::query()->findOrFail($id);
        foreach ($request->get('users') as $user) {
            UserTarget::query()->updateOrCreate([
                'user_id' => $user,
            ],[
                'user_id' => $user,
                'sales_target_id' => $target->getKey(),
            ]);
        }
        session()->flash('user-added', 'User has been added');
        return redirect(route('sales-targets.show', $target));
    }

    public function destroyUserTarget($id)
    {
        UserTarget::query()->findOrFail($id)->delete();
        session()->flash('user-target-deleted', 'User target has been deleted');
        return back();
    }
}
