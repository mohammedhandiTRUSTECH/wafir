<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLocationsController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::query()->orderByDesc('id')->get();
        return response()->json(['status' => true, 'data' => $locations]);
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $location]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
        ]);

        $location = Location::query()->create($request->only('name'));

        return response()->json(['status' => true, 'message' => 'Location created successfully', 'data' => $location], 201);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
        ]);

        $location->update($request->only('name'));

        return response()->json(['status' => true, 'message' => 'Location updated successfully', 'data' => $location]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();
        return response()->json(['status' => true, 'message' => 'Location deleted successfully']);
    }
}
