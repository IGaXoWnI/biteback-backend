<?php

namespace App\Http\Controllers;


use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BoxController extends Controller
{
 
    public function index(Request $request)
    {
        $query = Box::query()->with('Business');

        // Filter by merchant
        if ($request->has('business_id')) {
            $query->where('business_id', $request->merchant_id);
        }   

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->whereJsonContains('category_tags', $request->category);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('discounted_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('discounted_price', '<=', $request->max_price);
        }

        // Filter by availability
        if ($request->has('availability') && $request->availability === 'available') {
            $query->where('quantity_available', '>', 0);
        }

        // Sort results
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
            'category_tags' => 'nullable|array',
            'category_tags.*' => 'string',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('boxes', 'public');
        }

        $box = new Box();
        $box->business_id = Auth::user()->business->id;
        $box->title = $validated['title'];
        $box->description = $validated['description'];
        $box->original_price = $validated['original_price'];
        $box->discounted_price = $validated['discounted_price'];
        $box->quantity_available = $validated['quantity_available'];
        $box->quantity_reserved = 0;
        $box->pickup_time = $validated['pickup_time'];
        $box->image = $imagePath ? Storage::url($imagePath) : null;
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
}
