<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Models\Box;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{

    public function getAllBusinesses(Request $request)
    {
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $businesses = Business::with('user')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $businesses
        ]);
    }


    public function deleteBusiness($id)
    {
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $business = Business::find($id);

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found'
            ], 404);
        }

        $user = $business->user;

        $business->delete();

        if ($user && !$user->isAdmin()) {
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Business and associated user deleted successfully'
        ]);
    }


    public function getBusinessStatistics(Request $request)
    {
        $business = Auth::user()->business;

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'No business associated with this account'
            ], 404);
        }

        $businessBoxIds = $business->boxes()->pluck('id')->toArray();

        if (empty($businessBoxIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'revenue' => [
                        'total' => 0,
                        'thisMonth' => 0
                    ],
                    'pickupRate' => 0,
                    'topBoxes' => [],
                    'customerEngagement' => [
                        'uniqueCustomers' => 0,
                        'returningCustomers' => 0,
                        'returnRate' => 0
                    ]
                ]
            ]);
        }

        $revenue = Reservation::whereIn('box_id', $businessBoxIds)
            ->where('status', 'picked_up')
            ->join('boxes', 'reservations.box_id', '=', 'boxes.id')
            ->sum('boxes.discounted_price');

        $thisMonthRevenue = Reservation::whereIn('box_id', $businessBoxIds)
            ->where('status', 'picked_up')
            ->whereMonth('reservations.created_at', now()->month)
            ->join('boxes', 'reservations.box_id', '=', 'boxes.id')
            ->sum('boxes.discounted_price');

        $totalReservations = Reservation::whereIn('box_id', $businessBoxIds)->count();
        $pickedUpReservations = Reservation::whereIn('box_id', $businessBoxIds)
            ->where('status', 'picked_up')
            ->count();

        $pickupRate = $totalReservations > 0
            ? round(($pickedUpReservations / $totalReservations) * 100, 2)
            : 0;

        $topBoxes = Box::where('business_id', $business->id)
            ->withCount(['reservations' => function ($query) {
                $query->where('status', 'picked_up');
            }])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reservations_count')
            ->limit(5)
            ->get(['id', 'title', 'discounted_price', 'quantity_available']);

        $uniqueCustomers = Reservation::whereIn('box_id', $businessBoxIds)
            ->distinct('user_id')
            ->count('user_id');

        $returningCustomers = DB::table('reservations')
            ->select('user_id')
            ->whereIn('box_id', $businessBoxIds)
            ->where('status', 'picked_up')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $returnRate = $uniqueCustomers > 0
            ? round(($returningCustomers / $uniqueCustomers) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => [
                    'total' => $revenue,
                    'thisMonth' => $thisMonthRevenue
                ],
                'pickupRate' => $pickupRate,
                'topBoxes' => $topBoxes,
                'customerEngagement' => [
                    'uniqueCustomers' => $uniqueCustomers,
                    'returningCustomers' => $returningCustomers,
                    'returnRate' => $returnRate
                ]
            ]
        ]);
    }

    
}
