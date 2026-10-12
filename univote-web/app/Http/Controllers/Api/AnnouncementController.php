<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Announcement::with('organization')->orderBy('created_at', 'desc');

        if ($user instanceof \App\Models\Voter) {
            $orgId = $user->organization_id;
            if ($orgId) {
                // If voter belongs to an org, only see global announcements or those targeted to their org
                $query->where(function ($q) use ($orgId) {
                    $q->where('organization_id', $orgId)
                      ->orWhereNull('organization_id');
                });
            } else {
                // Voter has no organization assigned: only global announcements
                $query->whereNull('organization_id');
            }
        } elseif ($request->filled('organization_id')) {
            if ($request->organization_id === 'global') {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $request->organization_id);
            }
        }

        $announcements = $query->get();

        return response()->json($announcements->map(function ($a) {
            return [
                'id' => $a->id,
                'title' => $a->title ?? 'Announcement',
                'content' => $a->content,
                'type' => $a->type ?? 'info',
                'organization_id' => $a->organization_id,
                'organization' => $a->organization ? [
                    'id' => $a->organization->id,
                    'name' => $a->organization->name,
                    'code' => $a->organization->code,
                ] : null,
                'created_at' => $a->created_at,
            ];
        }));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'in:info,warning,success',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $announcement = Announcement::create($request->all());
        $announcement->load('organization');

        return response()->json([
            'id' => $announcement->id,
            'title' => $announcement->title,
            'content' => $announcement->content,
            'type' => $announcement->type,
            'organization_id' => $announcement->organization_id,
            'organization' => $announcement->organization ? [
                'id' => $announcement->organization->id,
                'name' => $announcement->organization->name,
                'code' => $announcement->organization->code,
            ] : null,
            'created_at' => $announcement->created_at,
        ], 201);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();
        return response()->json(['message' => 'Announcement deleted successfully']);
    }
}
