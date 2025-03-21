<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = Auth::login($user);

        return response()->json([

            "message" => "User registered successfully",
            "user" => $user,
            "autorisations" => [
                'token' => $token,
                'type' => 'Bearer',
            ]

        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        return response()->json([
            "status" => "success",
            "message" => "User logged in successfully",
            "user" => $user,
            "autorisations" => [
                'token' => $token,
                'type' => 'Bearer',
            ]
        ]);
    }


    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'User logged out successfully']);
    }

    public function refresh()
    {
        $token = Auth::refresh();
        return response()->json([
            'token' => $token,
            'type' => 'Bearer',
        ]);
    }
}
