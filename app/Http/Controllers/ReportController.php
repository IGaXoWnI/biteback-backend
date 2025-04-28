<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Submit a report for a box
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'box_id' => 'required|exists:boxes,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $existingReport = Report::where('user_id', Auth::id())
            ->where('box_id', $request->box_id)
            ->where('status', '!=', 'resolved')
            ->where('status', '!=', 'dismissed')
            ->first();

        if ($existingReport) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported this box and the report is still being processed'
            ], 400);
        }

        $report = Report::create([
            'user_id' => Auth::id(),
            'box_id' => $request->box_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully',
            'data' => $report
        ]);
    }

    /**
     * Get all reports (admin only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllReports(Request $request)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $reports = Report::with(['user', 'box'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Update report status (admin only)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateReportStatus(Request $request, $id)
    {
        // Check if user is authorized (admin)
        if (!Auth::user()->role === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,under_review,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $report = Report::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }

        $report->status = $request->status;
        $report->admin_notes = $request->admin_notes;
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Report status updated successfully',
            'data' => $report
        ]);
    }

    public function getUserReports()
    {
        $reports = Report::where('user_id', Auth::id())
            ->with(['box'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }
}
