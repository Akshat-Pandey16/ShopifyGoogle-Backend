<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6'
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password'])
        ]);

        return response()->json([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => 'User created successfully',
            'data' => []
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!empty($user)) {
            if (Hash::check($validated['password'], $user->password)) {
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    "token" => $token,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Invalid password',
                    'data' => []
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'User not found',
                'data' => []
            ], 404);
        }
    }

    public function logout()
    {
        $user = auth()->user();
        return response()->json([
            'message' => 'Logout successful',
            'data' => []
        ], 200);
    }

    public function profile()
    {
        $user = auth()->user();
        return response()->json([
            "user" => $user
        ], 200);
    }
}
