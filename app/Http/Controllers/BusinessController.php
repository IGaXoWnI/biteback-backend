<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessController extends Controller
{
    /**
     * Get all businesses
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBusinesses(Request $request)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        // Get all businesses with pagination
        $businesses = Business::with('user')
                            ->paginate($request->per_page ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $businesses
        ]);
    }
    
    /**
     * Delete a business and its owner
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBusiness($id)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        // Find the business
        $business = Business::find($id);
        
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found'
            ], 404);
        }
        
        // Get the user who owns this business
        $user = $business->user;
        
        // Delete the business
        $business->delete();
        
        // Delete the user if they exist and are not an admin
        if ($user && !$user->isAdmin()) {
            $user->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Business and associated user deleted successfully'
        ]);
    }
}
