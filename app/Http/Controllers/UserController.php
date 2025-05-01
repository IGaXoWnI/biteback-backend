<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{



    public function updateUserLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'zone' => 'sometimes|string',
        ]);

        $address = $this->addrFromCoords(
            $request->latitude,
            $request->longitude
        );

        $id = Auth::user()->id;
        $user = User::find($id);
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->zone = $request->zone ?? $user->zone;
        $user->address = $address;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'user' => $user,
                'address' => $address
            ]
        ]);
    }

    private function addrFromCoords($latitude, $longitude)
    {
        $apiKey = '93c44a46f2924bbc886b818dcf32aea3';
        $url = "https://api.opencagedata.com/geocode/v1/json?q={$latitude}+{$longitude}&key={$apiKey}";

        $response = Http::get($url);
        $data = $response->json();

        if (isset($data['results'][0]['formatted'])) {
            return $data['results'][0]['formatted'];
        }

        return null;
    }
    // public function sendLatLong(Request $request)
    // {
    //     $request->validate([
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'zone' => 'required|numeric|min:1|max:50'
    //     ]);

    //     $id = Auth::user()->id;
    //     $user = User::find($id);
    //     $user->latitude = $request->latitude;
    //     $user->longitude = $request->longitude;
    //     $user->zone = $request->zone;
    //     $user->save();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Location updated successfully'
    //     ]);
    // }


    public function getAllConsumers(Request $request)
    {
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }


        $consumers = User::where('role', "Consumer")
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $consumers
        ]);
    }


    public function deleteUser($id)
    {
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
    // public function checkLocationSet(Request $request)
    // {
    //     $user = Auth::user();

    //     $hasLocation = !empty($user->latitude) && !empty($user->longitude) && !empty($user->zone);

    //     return response()->json([
    //         'success' => true,
    //         'locationSet' => $hasLocation,
    //         'data' => $hasLocation ? [
    //             'latitude' => $user->latitude,
    //             'longitude' => $user->longitude,
    //             'zone' => $user->zone
    //         ] : null
    //     ]);
    // }


     
    public function updateUserStatus(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }
}
