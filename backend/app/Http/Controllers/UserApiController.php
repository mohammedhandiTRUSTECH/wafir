<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyChildrenResource;
use App\Http\Resources\TeamResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserApiController extends Controller
{

    public function myChildren($id = null): JsonResponse
    {
        $user = auth('api')->user();
        if ($id) {
            $user = User::query()->find($id);
        }
        if($user->hasChildren()){
            return response()->json(['status' => true, 'data' => MyChildrenResource::withExtra($user->children, $user->role_id),  'message' => 'Children retrieved successfully']);
        }
        return response()->json(['status' => false,  'message' => 'User not have children'],400);
    }

    public function myTeam() : JsonResponse
    {
        $user = auth('api')->user();
        $data = ['direct_manager' => null, 'team' => []];
        if($user->parent_id){
            $team = User::query()
                ->where('id','!=',$user->id)
                ->where('parent_id', $user->parent_id)
                ->get();
            $data['direct_manager'] = $user->parent->name;
            $data['team'] = TeamResource::collection($team);
        }
        return response()->json(['status' => false, 'data' => $data, 'message' => 'Team retrieved successfully']);
    }
}
