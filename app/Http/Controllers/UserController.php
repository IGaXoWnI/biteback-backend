<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'zone' => 'sometimes|string',
        ]);

        $address = $this->getAddressFromCoordinates(
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

    private function getAddressFromCoordinates($latitude, $longitude)
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


    public function getAllConsumers(Request $request)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Get all consumers with pagination
        $consumers = User::where('role', 'Consumer')
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

        // Find the user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admins from being deleted through API
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
