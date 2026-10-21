<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:1|max:120',
            'sex' => 'required|string|max:30',
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:voters',
            'password' => 'required|string|min:6',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $voter = Voter::create([
            'name' => $request->name,
            'age' => $request->age,
            'sex' => $request->sex,
            'course' => $request->course,
            'year_level' => $request->year_level,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $request->organization_id,
        ]);

        $token = $voter->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $voter->id,
                'name' => $voter->name,
                'email' => $voter->email,
                'role' => 'student',
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Try voter first
        $user = Voter::where('email', $request->email)->first();
        $role = 'student';

        // If not a voter, try admin
        if (!$user) {
            $user = Admin::where('email', $request->email)->first();
            $role = 'admin';
        }

        // User not found
        if (!$user) {
            \App\Models\AuditLog::record([
                'user_id'    => null,
                'actor_name' => $request->email,
                'action'     => 'LOGIN_FAILED',
                'description'=> 'Failed login attempt via Mobile API (user not found)',
                'severity'   => 'warning',
            ]);
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Google-only account — has no manually set password
        if ($role === 'student' && $user->google_id && !$user->password_manually_set) {
            return response()->json([
                'error' => 'This account was created with Google Sign-In. Please use the "Continue with Google" button to log in.',
            ], 401);
        }

        // Wrong password
        if (!Hash::check($request->password, $user->password)) {
            \App\Models\AuditLog::record([
                'user_id'    => null,
                'actor_name' => $request->email,
                'action'     => 'LOGIN_FAILED',
                'description'=> 'Failed login attempt via Mobile API',
                'severity'   => 'warning',
            ]);
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        \App\Models\AuditLog::record([
            'user_id'    => $user->id,
            'user_type'  => get_class($user),
            'actor_name' => $user->name ?? $user->email,
            'action'     => 'LOGIN_SUCCESS',
            'description'=> 'User logged in via Mobile API',
        ]);

        if ($role === 'student') {
            $user->load(['facialProfile', 'organization']);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'hasVoted' => $role === 'student' ? $user->hasVotedIn(null) : false,
                'is_profile_completed' => $role === 'student' ? (bool)$user->is_profile_completed : true,
                'needs_setup' => $role === 'student' ? $user->needsProfileSetup() : false,
                'organization_id' => $role === 'student' ? $user->organization_id : null,
                'organization' => ($role === 'student' && $user->organization) ? [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                    'code' => $user->organization->code,
                ] : null,
                'google_id' => $role === 'student' ? $user->google_id : null,
                'age' => $role === 'student' ? $user->age : null,
                'sex' => $role === 'student' ? $user->sex : null,
                'course' => $role === 'student' ? $user->course : null,
                'year_level' => $role === 'student' ? $user->year_level : null,
                'facial_config' => $role === 'student' ? $user->facial_config : null,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            \App\Models\AuditLog::record([
                'user_id'    => $user->id,
                'user_type'  => get_class($user),
                'actor_name' => $user->name ?? $user->email,
                'action'     => 'LOGOUT',
                'description'=> 'User logged out via Mobile API',
            ]);
            $user->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $role = $user instanceof Admin ? 'admin' : 'student';

        $response = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'created_at' => $user->created_at,
        ];

        if ($role === 'student' && method_exists($user, 'load')) {
            $user->load(['facialProfile', 'organization']);
            $response['facial_config'] = $user->facial_config;
            $response['facial_required'] = $user->isFacialVerificationRequired();
            $response['is_profile_completed'] = (bool)$user->is_profile_completed;
            $response['needs_setup'] = $user->needsProfileSetup();
            $response['organization_id'] = $user->organization_id;
            $response['organization'] = $user->organization ? [
                'id' => $user->organization->id,
                'name' => $user->organization->name,
                'code' => $user->organization->code,
            ] : null;
            $response['google_id'] = $user->google_id;
            $response['age'] = $user->age;
            $response['sex'] = $user->sex;
            $response['course'] = $user->course;
            $response['year_level'] = $user->year_level;
        }

        return response()->json($response);
    }

    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'nullable|string',
            'email' => 'required_without:id_token|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $email = null;
        $name = $request->name;
        $googleId = $request->google_id;

        if ($request->filled('id_token')) {
            // Verify Google ID Token via Google's tokeninfo API
            $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $request->id_token,
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Invalid or expired Google ID token.'], 401);
            }

            $payload = $response->json();
            $email = strtolower($payload['email'] ?? '');
            $name = $payload['name'] ?? $name;
            $googleId = $payload['sub'] ?? $googleId;
        } else {
            $email = strtolower($request->email);
        }

        if (empty($email)) {
            return response()->json(['error' => 'Unable to determine email from Google account.'], 422);
        }

        // Strictly enforce ssct.edu.ph domain restriction (also support snsu.edu.ph if configured)
        $allowedDomains = array_map('trim', explode(',', strtolower(env('ALLOWED_GOOGLE_DOMAINS', 'ssct.edu.ph,snsu.edu.ph'))));
        $emailDomain = substr(strrchr($email, "@"), 1);

        if (!in_array($emailDomain, $allowedDomains)) {
            return response()->json([
                'error' => "Access Restricted: You must log in using an official @ssct.edu.ph Google account."
            ], 403);
        }

        // Find or create voter
        $voter = Voter::where('email', $email)->orWhere(function ($q) use ($googleId) {
            if ($googleId) $q->where('google_id', $googleId);
        })->first();

        $isNewUser = false;

        if (!$voter) {
            $isNewUser = true;
            $voter = Voter::create([
                'name' => $name ?: explode('@', $email)[0],
                'email' => $email,
                'google_id' => $googleId,
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'age' => 18,
                'sex' => 'Other',
                'course' => 'Not Specified',
                'year_level' => '1st Year',
                'is_verified' => true,
                'is_profile_completed' => false,
            ]);
        } else {
            if ($googleId && !$voter->google_id) {
                $voter->update(['google_id' => $googleId]);
            }
        }

        $voter->load(['facialProfile', 'organization']);
        $token = $voter->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $voter->id,
                'name' => $voter->name,
                'email' => $voter->email,
                'role' => 'student',
                'hasVoted' => $voter->hasVotedIn(null),
                'is_new_user' => $isNewUser,
                'is_profile_completed' => (bool)$voter->is_profile_completed,
                'needs_setup' => $voter->needsProfileSetup(),
                'organization_id' => $voter->organization_id,
                'organization' => $voter->organization ? [
                    'id' => $voter->organization->id,
                    'name' => $voter->organization->name,
                    'code' => $voter->organization->code,
                ] : null,
                'google_id' => $voter->google_id,
                'age' => $voter->age,
                'sex' => $voter->sex,
                'course' => $voter->course,
                'year_level' => $voter->year_level,
                'facial_config' => $voter->facial_config,
            ],
            'token' => $token,
        ]);
    }

    public function setupProfile(Request $request)
    {
        $voter = $request->user();
        if (!$voter || !($voter instanceof Voter)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'nullable|string|min:6',
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:15|max:100',
            'sex' => 'required|string|in:Male,Female,Other',
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:50',
            'organization_id' => 'required|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $profileData = [
            'name' => $request->name,
            'age' => $request->age,
            'sex' => $request->sex,
            'course' => $request->course,
            'year_level' => $request->year_level,
            'organization_id' => $request->organization_id,
            'is_profile_completed' => true,
        ];

        if ($request->filled('password')) {
            $profileData['password'] = Hash::make($request->password);
            $profileData['password_manually_set'] = true;
        }

        $voter->update($profileData);

        $voter->refresh();
        $voter->load(['facialProfile', 'organization']);

        \App\Models\AuditLog::record([
            'user_id'    => $voter->id,
            'user_type'  => get_class($voter),
            'actor_name' => $voter->name ?? $voter->email,
            'action'     => 'PROFILE_SETUP',
            'description'=> 'Voter completed student profile setup',
        ]);

        return response()->json([
            'message' => 'Profile setup saved successfully',
            'user' => [
                'id' => $voter->id,
                'name' => $voter->name,
                'email' => $voter->email,
                'role' => 'student',
                'hasVoted' => $voter->hasVotedIn(null),
                'is_profile_completed' => (bool)$voter->is_profile_completed,
                'needs_setup' => $voter->needsProfileSetup(),
                'organization_id' => $voter->organization_id,
                'organization' => $voter->organization ? [
                    'id' => $voter->organization->id,
                    'name' => $voter->organization->name,
                    'code' => $voter->organization->code,
                ] : null,
                'google_id' => $voter->google_id,
                'age' => $voter->age,
                'sex' => $voter->sex,
                'course' => $voter->course,
                'year_level' => $voter->year_level,
                'facial_config' => $voter->facial_config,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $voter = $request->user();
        if (!$voter || !($voter instanceof Voter)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|required|integer|min:15|max:100',
            'sex' => 'sometimes|required|string|in:Male,Female,Other',
            'course' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Only allow editing name, age, sex, and course
        $allowed = [];
        if ($request->has('name')) $allowed['name'] = $request->name;
        if ($request->has('age')) $allowed['age'] = (int)$request->age;
        if ($request->has('sex')) $allowed['sex'] = $request->sex;
        if ($request->has('course')) $allowed['course'] = $request->course;

        $voter->update($allowed);
        $voter->refresh();
        $voter->load(['facialProfile', 'organization']);

        \App\Models\AuditLog::record([
            'user_id'    => $voter->id,
            'user_type'  => get_class($voter),
            'actor_name' => $voter->name ?? $voter->email,
            'action'     => 'PROFILE_UPDATE',
            'description'=> 'Voter updated personal profile information',
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $voter->id,
                'name' => $voter->name,
                'email' => $voter->email,
                'role' => 'student',
                'hasVoted' => $voter->hasVotedIn(null),
                'is_profile_completed' => (bool)$voter->is_profile_completed,
                'needs_setup' => $voter->needsProfileSetup(),
                'organization_id' => $voter->organization_id,
                'organization' => $voter->organization ? [
                    'id' => $voter->organization->id,
                    'name' => $voter->organization->name,
                    'code' => $voter->organization->code,
                ] : null,
                'google_id' => $voter->google_id,
                'age' => $voter->age,
                'sex' => $voter->sex,
                'course' => $voter->course,
                'year_level' => $voter->year_level,
                'facial_config' => $voter->facial_config,
            ],
        ]);
    }
}
