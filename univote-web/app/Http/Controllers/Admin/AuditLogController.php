<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('index');
        }

        $query = AuditLog::with('organization');

        // Search by actor, action, model_type, or IP
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Organization filter
        if ($orgId = $request->input('organization_id')) {
            $query->where('organization_id', $orgId);
        }

        // Platform filter
        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        // Action filter
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Sorting (support sort by organization, created_at, action, platform, id)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'organization') {
            $query->leftJoin('organizations', 'audit_logs.organization_id', '=', 'organizations.id')
                  ->orderBy('organizations.name', $sortOrder)
                  ->select('audit_logs.*');
        } elseif (in_array($sortBy, ['created_at', 'action', 'platform', 'id', 'actor_name'])) {
            $query->orderBy('audit_logs.' . $sortBy, $sortOrder);
        } else {
            $query->orderBy('audit_logs.created_at', 'desc');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        // Stats summary
        $totalLogs = AuditLog::count();
        $mobileLogsCount = AuditLog::where('platform', 'Mobile App')->count();
        $webLogsCount = AuditLog::where('platform', 'Web Browser')->count();
        $voteCastCount = AuditLog::where('action', 'VOTE_CAST')->count();

        // Available actions for filtering dropdown
        $availableActions = AuditLog::distinct()->pluck('action')->filter()->values();

        // Organizations for sorting & filtering dropdown
        $organizations = Organization::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'logs' => $logs,
            'organizations' => $organizations,
            'summary' => [
                'total' => $totalLogs,
                'mobile' => $mobileLogsCount,
                'web' => $webLogsCount,
                'votes_cast' => $voteCastCount,
            ],
            'available_actions' => $availableActions,
        ]);
    }

    public function show(Request $request, $id)
    {
        $log = AuditLog::with('organization')->findOrFail($id);

        if (!$request->expectsJson()) {
            return view('index');
        }

        return response()->json(['log' => $log]);
    }

    /**
     * Delete an individual audit log entry.
     */
    public function destroy($id)
    {
        $log = AuditLog::findOrFail($id);
        $logId = $log->id;
        $logAction = $log->action;
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => "Audit log entry #{$logId} [{$logAction}] deleted successfully.",
        ]);
    }

    /**
     * Delete audit logs in bulk or by group criteria (organization, action, platform, date range, or list of IDs).
     */
    public function deleteGroup(Request $request)
    {
        $query = AuditLog::query();

        // Explicit list of IDs
        if ($request->has('ids') && is_array($request->input('ids')) && count($request->input('ids')) > 0) {
            $ids = array_filter(array_map('intval', $request->input('ids')));
            $deletedCount = AuditLog::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "Successfully deleted {$deletedCount} selected audit log(s).",
            ]);
        }

        // Group filters
        $appliedCriteria = 0;

        if ($orgId = $request->input('organization_id')) {
            $query->where('organization_id', $orgId);
            $appliedCriteria++;
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
            $appliedCriteria++;
        }

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
            $appliedCriteria++;
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
            $appliedCriteria++;
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
            $appliedCriteria++;
        }

        if ($appliedCriteria === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one criteria to delete a group of logs, or use Delete All.',
            ], 422);
        }

        $deletedCount = $query->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "Successfully deleted {$deletedCount} matching audit log(s) for the specified group.",
        ]);
    }

    /**
     * Clear / delete all audit logs.
     */
    public function deleteAll()
    {
        $count = AuditLog::count();
        AuditLog::truncate();

        return response()->json([
            'success' => true,
            'deleted_count' => $count,
            'message' => "All {$count} audit log entries have been cleared successfully.",
        ]);
    }
}
