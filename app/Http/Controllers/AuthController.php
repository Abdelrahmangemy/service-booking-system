<?php
namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $role = Role::where('name', $request->role)->first();
        $user = User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'timezone' => $request->timezone ?? 'UTC'
        ]);
        $token = $user->createToken('api-token')->accessToken;

        return response()->json([
            'user' => new UserResource($user->load('role')),
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email',$request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['message'=>'Invalid credentials'], 401);
        }
        $token = $user->createToken('api-token')->accessToken;
        return response()->json([
            'user' => new UserResource($user->load('role')),
            'token' => $token
        ]);
    }
}
