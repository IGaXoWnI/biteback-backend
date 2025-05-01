<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\User;
use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
   
    public function getKeyStats()
    {
        $totalUsers = User::count();
        
        $activeMerchants = Business::whereHas('boxes', function($query) {
            $query->where('quantity_available', '>', 0);
        })->count();
        
        $itemsSaved = Reservation::where('status', 'picked_up')->count();
        
        $totalRevenue = Reservation::where('status', 'picked_up')
            ->join('boxes', 'reservations.box_id', '=', 'boxes.id')
            ->sum('boxes.discounted_price');
            
        return response()->json([
            'success' => true,
            'data' => [
                'totalUsers' => $totalUsers,
                'activeMerchants' => $activeMerchants,
                'itemsSaved' => $itemsSaved,
                'totalRevenue' => $totalRevenue
            ]
        ]);
    }
    
   
    public function getDetailedStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => User::count(),
                    'consumers' => User::where('role', 'Consumer')->count(),
                    'merchants' => User::where('role', 'Merchant')->count(),
                ],
                'boxes' => [
                    'total_created' => Box::count(),
                    'currently_available' => Box::where('quantity_available', '>', 0)->count(),
                ],
                'reservations' => [
                    'total' => Reservation::count(),
                    'picked_up' => Reservation::where('status', 'picked_up')->count(),
                    'canceled' => Reservation::where('status', 'canceled')->count(),
                    'active' => Reservation::where('status', 'reserved')->count(),
                ],
                'revenue' => [
                    'total' => Reservation::where('status', 'picked_up')
                        ->join('boxes', 'reservations.box_id', '=', 'boxes.id')
                        ->sum('boxes.discounted_price'),
                    'this_month' => Reservation::where('status', 'picked_up')
                        ->whereMonth('reservations.created_at', now()->month)
                        ->join('boxes', 'reservations.box_id', '=', 'boxes.id')
                        ->sum('boxes.discounted_price'),
                ]
            ]
        ]);
    }
}
