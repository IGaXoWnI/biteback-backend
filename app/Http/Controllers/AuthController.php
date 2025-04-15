<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'role' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->has('businessName')) {
            Business::create([
                'user_id' => $user->id,
                'business_name' => $request->businessName,
                'business_type' => $request->businessType,
                'business_address' => $request->businessAddress,
                'city' => $request->city,
                'postal_code' => $request->postalCode,
                'number_of_locations' => $request->numberOfLocations ?? '1',
                'estimated_surplus_units' => $request->estimatedSurplusUnits,
                'heard_about_us' => $request->heardAboutUs,
            ]);
        }

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
