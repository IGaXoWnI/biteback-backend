<?php

namespace App\Http\Controllers;


use App\Models\Box;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class BoxController extends Controller
{

    public function index(Request $request)
    {
        $query = Box::query()->with('Business');


        if ($request->has('business_id')) {
            $query->where('business_id', $request->merchant_id);
        }

        if ($request->has('category') && $request->category) {
            $query->whereJsonContains('category_tags', $request->category);
        }

        if ($request->has('min_price')) {
            $query->where('discounted_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('discounted_price', '<=', $request->max_price);
        }

        if ($request->has('availability') && $request->availability === 'available') {
            $query->where('quantity_available', '>', 0);
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('discounted_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('discounted_price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'discount':
                    $query->orderByRaw('(original_price - discounted_price) / original_price DESC');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $perPage = $request->per_page ?? 15;
        $boxes = $query->paginate($perPage);


        return response()->json([
            'success' => true,
            'data' => $boxes
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'original_price' => 'required|numeric|min:0',
            'discounted_price' => 'required|numeric|min:0|lte:original_price',
            'quantity_available' => 'required|integer|min:1',
            'pickup_time' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload
        $path = $request->file('image')->store('images', 'public');
        $url = asset('storage/' . $path);

        $box = new Box();
        $box->business_id = Auth::user()->business->id;
        $box->title = $validated['title'];
        $box->description = $validated['description'];
        $box->original_price = $validated['original_price'];
        $box->discounted_price = $validated['discounted_price'];
        $box->quantity_available = $validated['quantity_available'];
        $box->quantity_reserved = 0;
        $box->pickup_time = $validated['pickup_time'];
        $box->image = $url;
        $box->rating = 0;
        $box->is_active = true;
        $box->save();

        return response()->json([
            'success' => true,
            'message' => 'Box created successfully',
            'data' => $box
        ], 201);
    }




    public function show($id)
    {
        $box = Box::with('business')->find($id);

        if (!$box) {
            return response()->json([
                'success' => false,
                'message' => 'Box not found'
            ], 404);
        }

        $discountPercentage = 0;
        if ($box->original_price > 0) {
            $discountPercentage = round(
                (($box->original_price - $box->discounted_price) / $box->original_price) * 100
            );
        }

        $responseData = [
            'id' => $box->id,
            'title' => $box->title,
            'description' => $box->description,
            'price' => [
                'original' => (float) $box->original_price,
                'discounted' => (float) $box->discounted_price,
                'discount_percentage' => $discountPercentage
            ],
            'rating' => (float) $box->rating,
            'pickup_time' => $box->pickup_time,
            'quantity' => [
                'available' => (int) $box->quantity_available,
                'reserved' => (int) $box->quantity_reserved
            ],
            'image' => $box->image,
            'category_tags' => json_decode($box->category_tags) ?? [],
            'created_at' => $box->created_at,
            'updated_at' => $box->updated_at
        ];

        if ($box->business) {
            $responseData['business'] = [
                'id' => $box->business->id,
                'name' => $box->business->business_name,
                'address' => $box->business->business_address ?? null,
            ];
        } else {
            $responseData['business'] = null;
        }



        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    }

    public function getAll(Request $request)
    {
        $boxes = Box::with('business')->get();

        return response()->json([
            'success' => true,
            'data' => $boxes
        ]);
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'original_price' => 'required|numeric|min:0',
            'discounted_price' => 'required|numeric|min:0|lte:original_price',
            'quantity_available' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
            'pickup_time' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Validation error',
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        $box = Box::findOrFail($id);
        $bussiness_owner = Business::with('user')->where('id', $box->business_id)->first();
        dd($bussiness_owner);



        if ($bussiness_owner->user_id !== Auth::user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($request->hasFile('image')) {
            if ($box->image && Storage::exists('public/images/' . basename($box->image))) {
                Storage::delete('public/images/' . basename($box->image));
            }

            $imagePath = $request->file('image')->store('images', 'public');
            $box->image = Storage::url($imagePath);
        }

        $box->title = $request->title;
        $box->description = $request->description;
        $box->original_price = $request->original_price;
        $box->discounted_price = $request->discounted_price;
        $box->quantity_available = $request->quantity_available;
        $box->is_active = $request->is_active;
        $box->pickup_time = $request->pickup_time;

        // Calculate discount percentage
        if ($request->original_price > 0) {
            $discountAmount = $request->original_price - $request->discounted_price;
            $box->discount_percentage = round(($discountAmount / $request->original_price) * 100);
        }

        $box->save();

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully',
            'data' => $box
        ]);
    }


    public function getAllZone(Request $request)
    {
        $latitude = Auth::user()->latitude;
        $longitude = Auth::user()->longitude;
        $radius = Auth::user()->zone * 1000; // zone in meters

        $boxes = Box::with('business')->get();

        $boxData = [];

        foreach ($boxes as $box) {
            $address = $box->business->business_address;

            // Geocode the address using OpenCageData
            $geoResponse = Http::get('https://api.opencagedata.com/geocode/v1/json', [
                'q' => $address,
                'key' => '16a6b2414b4f4109bd6e21c5591ecdc4',
            ]);

            $geo = $geoResponse->json();

            if (!empty($geo['results'])) {
                $lat = $geo['results'][0]['geometry']['lat'];
                $lon = $geo['results'][0]['geometry']['lng'];

                $boxData[] = [
                    'box' => $box,
                    'lat' => $lat,
                    'lon' => $lon,
                ];
            }
        }

        $userLocation = [$longitude, $latitude];
        $mapboxToken = 'pk.eyJ1IjoiZ2F4b3duMDciLCJhIjoiY204bjBmOWttMWlpeTJrc2V2ZHd4dGF2diJ9.49gPJT5GklJQsNc5fO7AtA';

        $results = [];

        foreach ($boxData as $data) {
            $startLon = $userLocation[0];
            $startLat = $userLocation[1];
            $destLon = $data['lon'];
            $destLat = $data['lat'];

            // Request Mapbox Directions API
            $response = Http::get("https://api.mapbox.com/directions/v5/mapbox/driving/{$startLon},{$startLat};{$destLon},{$destLat}", [
                'access_token' => $mapboxToken,
                'geometries' => 'geojson',
                'overview' => 'simplified',
            ]);

            $directions = $response->json();

            if (isset($directions['routes'][0])) {
                $distance = $directions['routes'][0]['distance'];

                if ($distance <= $radius) {
                    $results[] = [
                        'box' => $data['box'],
                        'distance_in_meters' => $distance,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Boxes retrieved successfully',
            'data' => $results
        ], 200);
    }


    public function getAllBoxes(Request $request)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Get all boxes with pagination and relationships
        $boxes = Box::with('business')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $boxes
        ]);
    }

    /**
     * Delete a box
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBox($id)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Find the box
        $box = Box::find($id);

        if (!$box) {
            return response()->json([
                'success' => false,
                'message' => 'Box not found'
            ], 404);
        }

        // Delete the box
        $box->delete();

        return response()->json([
            'success' => true,
            'message' => 'Box deleted successfully'
        ]);
    }
}
