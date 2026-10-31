<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        if (!request()->expectsJson()) {
            return view('index');
        }

        $query = Announcement::with(['organization', 'course', 'admin']);

        if (request()->filled('target_type') && request('target_type') !== 'all_targets') {
            $query->where('target_type', request('target_type'));
        }
        if (request()->filled('organization_id')) {
            $orgId = request('organization_id');
            if ($orgId === 'global') {
                $query->whereNull('organization_id')->whereNull('course_id');
            } else {
                $query->where('organization_id', $orgId);
            }
        }
        if (request()->filled('course_id')) {
            $query->where('course_id', request('course_id'));
        }

        $announcements = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                $targetType = $a->target_type ?? ($a->course_id ? 'course' : ($a->organization_id ? 'organization' : 'all'));
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'type' => $a->type,
                    'target_type' => $targetType,
                    'organization_id' => $a->organization_id,
                    'course_id' => $a->course_id,
                    'organization' => $a->organization ? [
                        'id' => $a->organization->id,
                        'name' => $a->organization->name,
                        'code' => $a->organization->code,
                    ] : null,
                    'course' => $a->course ? [
                        'id' => $a->course->id,
                        'name' => $a->course->name,
                        'code' => $a->course->code,
                    ] : null,
                    'admin_id' => $a->admin_id,
                    'admin' => $a->admin ? [
                        'id' => $a->admin->id,
                        'name' => $a->admin->name,
                    ] : null,
                    'created_at' => $a->created_at,
                    'updated_at' => $a->updated_at,
                ];
            });

        $organizations = Organization::orderBy('name')->get(['id', 'name', 'code']);
        $allCourses = Course::getAllCourses();

        return response()->json([
            'announcements' => $announcements,
            'organizations' => $organizations,
            'courses' => $allCourses,
            'enrolled_courses' => $allCourses,
        ]);
    }

    public function store(Request $request)
    {
        $targetType = $request->input('target_type', 'all');

        $request->validate([
            'content' => 'required|string|max:1000',
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:info,warning,success',
            'target_type' => 'nullable|string|in:all,organization,course',
            'organization_id' => $targetType === 'organization' ? 'required|exists:organizations,id' : 'nullable|exists:organizations,id',
            'course_id' => $targetType === 'course' ? 'required|exists:courses,id' : 'nullable|exists:courses,id',
        ]);

        $organizationId = $request->input('organization_id');
        $courseId = null;

        if ($targetType === 'course') {
            $course = Course::findOrFail($request->input('course_id'));
            $courseId = $course->id;
            $organizationId = $course->organization_id ?: ($organizationId ?: $course->voters()->whereNotNull('organization_id')->value('organization_id'));
        } elseif ($targetType === 'all') {
            $organizationId = null;
            $courseId = null;
        }

        $admin = Auth::guard('admin')->user();

        $announcement = Announcement::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'type' => $request->input('type', 'info'),
            'target_type' => $targetType,
            'organization_id' => $organizationId,
            'course_id' => $courseId,
            'admin_id' => $admin?->id,
        ]);

        $announcement->load(['organization', 'course', 'admin']);

        return response()->json([
            'success' => true,
            'message' => 'Announcement created successfully!',
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'type' => $announcement->type,
                'target_type' => $announcement->target_type,
                'organization_id' => $announcement->organization_id,
                'course_id' => $announcement->course_id,
                'organization' => $announcement->organization ? [
                    'id' => $announcement->organization->id,
                    'name' => $announcement->organization->name,
                    'code' => $announcement->organization->code,
                ] : null,
                'course' => $announcement->course ? [
                    'id' => $announcement->course->id,
                    'name' => $announcement->course->name,
                    'code' => $announcement->course->code,
                ] : null,
                'admin_id' => $announcement->admin_id,
                'admin' => $announcement->admin ? [
                    'id' => $announcement->admin->id,
                    'name' => $announcement->admin->name,
                ] : null,
                'created_at' => $announcement->created_at,
            ],
        ], 201);
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load(['organization', 'course', 'admin']);
        $organizations = Organization::orderBy('name')->get(['id', 'name', 'code']);
        $allCourses = Course::getAllCourses();

        return response()->json([
            'announcement' => $announcement,
            'organizations' => $organizations,
            'courses' => $allCourses,
            'enrolled_courses' => $allCourses,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $targetType = $request->input('target_type', $announcement->target_type ?? 'all');

        $request->validate([
            'content' => 'required|string|max:1000',
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:info,warning,success',
            'target_type' => 'nullable|string|in:all,organization,course',
            'organization_id' => $targetType === 'organization' ? 'required|exists:organizations,id' : 'nullable|exists:organizations,id',
            'course_id' => $targetType === 'course' ? 'required|exists:courses,id' : 'nullable|exists:courses,id',
        ]);

        $organizationId = $request->input('organization_id');
        $courseId = null;

        if ($targetType === 'course') {
            $course = Course::findOrFail($request->input('course_id'));
            $courseId = $course->id;
            $organizationId = $course->organization_id ?: ($organizationId ?: $course->voters()->whereNotNull('organization_id')->value('organization_id'));
        } elseif ($targetType === 'all') {
            $organizationId = null;
            $courseId = null;
        }

        $announcement->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'type' => $request->input('type', 'info'),
            'target_type' => $targetType,
            'organization_id' => $organizationId,
            'course_id' => $courseId,
        ]);

        $announcement->load(['organization', 'course', 'admin']);

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully!',
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'type' => $announcement->type,
                'target_type' => $announcement->target_type,
                'organization_id' => $announcement->organization_id,
                'course_id' => $announcement->course_id,
                'organization' => $announcement->organization ? [
                    'id' => $announcement->organization->id,
                    'name' => $announcement->organization->name,
                    'code' => $announcement->organization->code,
                ] : null,
                'course' => $announcement->course ? [
                    'id' => $announcement->course->id,
                    'name' => $announcement->course->name,
                    'code' => $announcement->course->code,
                ] : null,
                'admin_id' => $announcement->admin_id,
                'admin' => $announcement->admin ? [
                    'id' => $announcement->admin->id,
                    'name' => $announcement->admin->name,
                ] : null,
                'created_at' => $announcement->created_at,
                'updated_at' => $announcement->updated_at,
            ],
        ]);
    }

    public function destroy(Announcement $announcement)
    {
        try {
            $announcement->delete();

            return response()->json([
                'success' => true,
                'message' => 'Announcement deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete announcement: ' . $e->getMessage(),
            ], 500);
        }
    }
}
