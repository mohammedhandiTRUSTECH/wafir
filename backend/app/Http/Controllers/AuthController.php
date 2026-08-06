<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(LoginRequest $request): JsonResponse
    {
        $data = ['username' => $request->get('username'), 'password' => $request->get('password')];
        if ($token = auth('api')->attempt($data)) {
            $user = User::query()->where('is_active', true)->where('username', $request->get('username'))->first();
            if ($user) {
                return response()->json(['status' => true, 'user' => new UserResource($user), 'token' => $token]);
            }
        }
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    public function me(): JsonResponse
    {
        return response()->json(['user' => new UserResource(auth('api')->user())]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        if (Hash::check($request->get('current_password'), auth('api')->user()->getAuthPassword())) {
            auth('api')->user()->update(['password' => $request->get('new_password')]);
            return response()->json(['status' => true, 'message' => 'The password has been changed successfully']);
        }
        return response()->json(['status' => false, 'message' => 'The current password is incorrect'], 400);
    }
}
