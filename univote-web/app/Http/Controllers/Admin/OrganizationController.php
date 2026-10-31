<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    // ─── Organizations ────────────────────────────────────────────────────────

    public function index()
    {
        if (!request()->expectsJson()) {
            return view('index');
        }

        $organizations = Organization::withCount(['voters', 'courses'])
            ->with(['courses' => function ($q) {
                $q->withCount('voters')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'organizations' => $organizations,
            'courses' => Course::getAllCourses(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:organizations,name',
            'code' => 'required|string|max:20|alpha_dash|unique:organizations,code',
        ]);

        $data['code'] = strtoupper($data['code']);

        return response()->json(['organization' => Organization::create($data)], 201);
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'code'      => 'required|string|max:20|alpha_dash|unique:organizations,code,' . $organization->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $data['code'] = strtoupper($data['code']);

        $organization->update($data);

        return response()->json(['organization' => $organization->fresh()]);
    }

    public function destroy(Organization $organization)
    {
        if ($organization->voters()->exists() || $organization->elections()->exists()) {
            return response()->json([
                'message' => 'This organization is assigned to voters or elections and cannot be deleted.',
            ], 422);
        }

        if ($organization->courses()->exists()) {
            return response()->json([
                'message' => 'This organization has courses assigned to it. Please remove all courses first.',
            ], 422);
        }

        $organization->delete();

        return response()->json(['message' => 'Organization deleted successfully.']);
    }

    // ─── Courses (nested under organization) ─────────────────────────────────

    /**
     * List courses belonging to an organization.
     */
    public function listCourses(Organization $organization)
    {
        $courses = $organization->courses()
            ->withCount('voters')
            ->orderBy('name')
            ->get();

        return response()->json(['courses' => $courses]);
    }

    /**
     * Create a course and assign it directly to the organization.
     */
    public function storeCourse(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        // Ensure code is uppercase
        $data['code'] = strtoupper(trim($data['code']));

        // Prevent duplicate code in same org
        $exists = $organization->courses()->where('code', $data['code'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A course with this code already exists in this organization.',
            ], 422);
        }

        $course = $organization->courses()->create([
            'name'            => trim($data['name']),
            'code'            => $data['code'],
            'description'     => $data['description'] ?? null,
            'organization_id' => $organization->id,
            'status'          => 'assigned',
        ]);

        $course->loadCount('voters');

        return response()->json(['course' => $course], 201);
    }

    /**
     * Delete a course that belongs to an organization.
     */
    public function destroyCourse(Organization $organization, Course $course)
    {
        // Ensure the course belongs to this organization
        if ($course->organization_id !== $organization->id) {
            return response()->json(['message' => 'Course does not belong to this organization.'], 403);
        }

        if ($course->voters()->exists()) {
            return response()->json([
                'message' => 'This course has registered voters and cannot be deleted.',
            ], 422);
        }

        if ($course->elections()->exists()) {
            return response()->json([
                'message' => 'This course is linked to elections and cannot be deleted.',
            ], 422);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }
}
