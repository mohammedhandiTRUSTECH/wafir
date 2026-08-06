<?php

namespace App\Http\Controllers;


use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{


    public function index(): View
    {
        $roles = Role::query()->get();
        return view('roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        if ($role->id != 1) {
            return view('roles.edit', compact('role'));
        }
        return back();
    }

    public function update(Role $role, Request $request)
    {
        $request->validate([
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);
        $role->commission()->updateOrCreate([
            'role_id' => $role->id
        ],[
            'role_id' => $role->id,
            'commission_percentage' => $request->get('commission_percentage'),
        ]);
        return redirect()->route('roles.index');
    }
}
