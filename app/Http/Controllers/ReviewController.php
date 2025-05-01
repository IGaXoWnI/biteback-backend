<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
 
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation = Reservation::find($request->reservation_id);
        
        if ($reservation->user_id !== Auth::id() || $reservation->status !== 'picked_up') {
            return response()->json([
                'success' => false,
                'message' => 'You can only review boxes you have picked up'
            ], 403);
        }
        
        $review = Review::create([
            'user_id' => Auth::id(),
            'box_id' => $reservation->box_id,
            'reservation_id' => $reservation->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'success' => true,
            'data' => $review
        ], 201);
    }

  
    public function getBoxReviews($box_id)
    {
        $box = Box::find($box_id);
        
        if (!$box) {
            return response()->json([
                'success' => false,
                'message' => 'Box not found'
            ], 404);
        }
        
        $reviews = Review::where('box_id', $box_id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(request()->per_page ?? 10);
            
        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}
