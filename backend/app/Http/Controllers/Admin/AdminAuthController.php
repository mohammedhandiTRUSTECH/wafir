<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth('admin_api')->attempt($credentials)) {
            return response()->json(['status' => false, 'message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'status' => true,
            'token'  => $token,
            'admin'  => auth('admin_api')->user(),
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(['status' => true, 'admin' => auth('admin_api')->user()]);
    }

    public function logout(): JsonResponse
    {
        auth('admin_api')->logout();
        return response()->json(['status' => true, 'message' => 'Logged out successfully']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'token'  => auth('admin_api')->refresh(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $admin = auth('admin_api')->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['status' => false, 'message' => 'Current password is incorrect'], 400);
        }

        $admin->update(['password' => $request->new_password]);

        return response()->json(['status' => true, 'message' => 'Password updated successfully']);
    }
}