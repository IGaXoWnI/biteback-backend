<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
   
    public function makeReservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'box_id' => 'required|exists:boxes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $box = Box::find($request->box_id);

        if (!$box) {
            return response()->json([
                'success' => false,
                'message' => 'Box not found'
            ], 404);
        }

        $userHasReservation = Reservation::where('box_id', $box->id)
            ->where('user_id', Auth::id())
            ->where('status', 'reserved')
            ->exists();

        if ($userHasReservation) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a reservation for this box'
            ], 400);
        }

        $activeReservationsCount = Reservation::where('box_id', $box->id)
            ->where('status', 'reserved')
            ->count();

        if ($activeReservationsCount >= $box->quantity_available) {
            return response()->json([
                'success' => false,
                'message' => 'All boxes of this type have been reserved'
            ], 400);
        }

        DB::transaction(function () use ($box) {
            $box->quantity_available = $box->quantity_available - 1;
            $box->save();

            Reservation::create([
                'user_id' => Auth::id(),
                'box_id' => $box->id,
                'status' => 'reserved'
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Box reserved successfully'
        ], 201);
    }


    public function getUserReservations(Request $request)
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->with('box.business')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }


    public function updateReservationStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:picked_up,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found'
            ], 404);
        }

        $isOwner = $reservation->user_id === Auth::id();
        $isBusinessOwner = $reservation->box->business->user_id === Auth::id();

        if (!$isOwner && !$isBusinessOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this reservation'
            ], 403);
        }

        if ($reservation->status !== 'reserved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update reservation that is not in reserved status'
            ], 400);
        }

        if ($request->status === 'canceled') {
            $box = $reservation->box;
            $box->increment('quantity_available');
        }

        $reservation->status = $request->status;
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Reservation status updated successfully',
            'data' => $reservation
        ]);
    }


    public function getBusinessReservations(Request $request)
    {
        $business = Auth::user()->business;

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have a business'
            ], 400);
        }

        $boxIds = $business->boxes()->pluck('id')->toArray();

        if (empty($boxIds)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $reservations = Reservation::whereIn('box_id', $boxIds)
            ->with(['box', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    public function checkIsReserved($box_id)
    {
        $box = Box::find($box_id);

        if (!$box) {
            return response()->json([
                'success' => false,
                'message' => 'Box not found'
            ], 404);
        }

        $userHasReservation = Reservation::where('box_id', $box->id)
            ->where('user_id', Auth::id())
            ->where('status', 'reserved')
            ->exists();

        return response()->json([
            'success' => true,
            'is_reserved' => $userHasReservation
        ]);
    }

    public function confirmPickup(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found'
            ], 404);
        }

        $isOwner = $reservation->user_id === Auth::id();
        $isBusinessOwner = $reservation->box->business->user_id === Auth::id();

        if (!$isOwner && !$isBusinessOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to confirm pickup for this reservation'
            ], 403);
        }

        $reservation->status = 'picked_up';
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Reservation confirmed as picked up successfully',
            'data' => $reservation
        ]);
    }
}
