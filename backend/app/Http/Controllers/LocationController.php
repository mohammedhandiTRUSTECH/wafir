<?php

namespace App\Http\Controllers;


use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{

    public function index()
    {
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }
    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        Location::query()->create([
            'name' => $request->get('name'),
        ]);
        session()->flash('success','Location created successfully');
        return redirect()->route('locations.index');
    }

    public function edit( Location $location)
    {
        return view('locations.edit', compact('location'));
    }
    public function update(Location $location ,Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $location->update([
            'name' => $request->get('name'),
        ]);
        session()->flash('success','Location updated successfully');
        return redirect()->route('locations.index');
    }
}
