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
}
