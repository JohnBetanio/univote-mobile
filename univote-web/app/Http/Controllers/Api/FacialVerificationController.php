<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\FacialProfile;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FacialVerificationController extends Controller
{
    private RekognitionService $rekognition;

    public function __construct(RekognitionService $rekognition)
    {
        $this->rekognition = $rekognition;
    }

    public function getConfig(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can access facial verification'], 403);
        }

        $voter->load('facialProfile');

        return response()->json([
            'facial_config' => $voter->facial_config,
            'is_required' => $voter->isFacialVerificationRequired(),
            'driver' => $this->rekognition->getActiveDriver(),
            'min_quality_score' => $this->rekognition->getQualityThreshold() / 100,
            'max_attempts_before_lockout' => 5,
            'session_ttl_minutes' => 15,
        ]);
    }

    public function enroll(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can enroll facial verification'], 403);
        }

        $validator = Validator::make($request->all(), [
            'face_data' => 'required|string',
            'quality_score' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        @ini_set('memory_limit', '256M');
        @set_time_limit(30);

        try {
            // Decode the base64 image to raw bytes
            $imageBytes = RekognitionService::decodeImage($request->face_data);

            if (!RekognitionService::validateImageFormat($imageBytes)) {
                return response()->json([
                    'error' => 'Invalid image format. Please use JPEG or PNG.',
                ], 422);
            }

            // Pre-flight: detect face (only reject if truly no face or severe obstruction)
            $qualityThreshold = $this->rekognition->getQualityThreshold();
            $detection = $this->rekognition->detectFaceQuality($imageBytes);

            if (!$detection['hasFace']) {
                return response()->json([
                    'error' => $detection['error'] ?? 'No face detected. Please position your face clearly in the camera.',
                    'error_code' => $detection['error_code'] ?? 'no_face_detected',
                    'quality_score' => 0,
                    'min_required' => $qualityThreshold / 100,
                ], 422);
            }

            if (($detection['faceCount'] ?? 1) > 1) {
                return response()->json([
                    'error' => $detection['error'] ?? 'Multiple faces detected. Please ensure only your face is visible.',
                    'error_code' => 'multiple_faces',
                    'quality_score' => 0,
                    'min_required' => $qualityThreshold / 100,
                ], 422);
            }

            // If the voter already has a face enrolled in Rekognition, remove the old one first
            $existingProfile = FacialProfile::where('voter_id', $voter->id)->first();
            if ($existingProfile && !empty($existingProfile->face_id)) {
                try {
                    $this->rekognition->removeFace($existingProfile->face_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to remove old face from Rekognition during re-enroll', [
                        'voter_id' => $voter->id,
                        'old_face_id' => $existingProfile->face_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Enroll the face
            $enrollResult = $this->rekognition->enrollFace($voter->id, $imageBytes);

            if (!$enrollResult['indexed']) {
                return response()->json([
                    'error' => 'Could not index face. Please try again with better lighting.',
                    'error_code' => 'indexing_failed',
                ], 422);
            }

            // Save to database
            $profile = FacialProfile::updateOrCreate(
                ['voter_id' => $voter->id],
                [
                    'face_data' => $request->face_data, // Keep base64 as preview and local matching source
                    'face_id' => $enrollResult['face_id'],
                    'external_image_id' => $enrollResult['external_image_id'],
                    'quality_brightness' => $enrollResult['quality']['brightness'],
                    'quality_sharpness' => $enrollResult['quality']['sharpness'],
                    'is_verified' => true,
                    'is_enabled' => true,
                    'verification_attempts' => 0,
                    'last_verified_at' => now(),
                    'last_failed_at' => null,
                ]
            );

            // Generate an active facial verification session token immediately
            $sessionToken = $this->generateFaceSessionToken($voter->id);

            Log::info('Facial profile enrolled successfully', [
                'voter_id' => $voter->id,
                'face_id' => $enrollResult['face_id'],
                'mode' => $enrollResult['mode'] ?? 'unknown',
                'brightness' => $enrollResult['quality']['brightness'],
                'sharpness' => $enrollResult['quality']['sharpness'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Facial profile enrolled successfully!',
                'session_token' => $sessionToken,
                'session_expires_at' => now()->addMinutes(15)->toIso8601String(),
                'quality' => [
                    'brightness' => $enrollResult['quality']['brightness'],
                    'sharpness' => $enrollResult['quality']['sharpness'],
                ],
                'facial_config' => $voter->fresh()->load('facialProfile')->facial_config,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Facial enrollment error', [
                'voter_id' => $voter->id,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to enroll face. Please ensure your face is well-lit and clearly centered.',
                'error_code' => 'enrollment_failed',
            ], 422);
        }
    }

    public function verify(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can use facial verification'], 403);
        }

        $validator = Validator::make($request->all(), [
            'face_data' => 'required|string',
            'context' => 'nullable|string|in:voting,login,general',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $profile = $voter->facialProfile;

        if (!$profile || !$profile->is_enrolled) {
            return response()->json([
                'error' => 'No facial profile enrolled. Please enroll face verification first.',
                'error_code' => 'not_enrolled',
                'facial_config' => [
                    'is_enrolled' => false,
                    'is_verified' => false,
                    'is_enabled' => true,
                    'status' => 'not_enrolled',
                ],
            ], 404);
        }

        if (!$profile->is_enabled) {
            return response()->json([
                'error' => 'Facial verification is disabled for your account.',
                'error_code' => 'disabled',
            ], 403);
        }

        @ini_set('memory_limit', '256M');
        @set_time_limit(30);

        try {
            // Decode the base64 image
            $imageBytes = RekognitionService::decodeImage($request->face_data);

            if (!RekognitionService::validateImageFormat($imageBytes)) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => 'Invalid image format. Please use JPEG or PNG.',
                ], 422);
            }

            // Pre-flight: detect face (only reject if truly no face or multiple people)
            $qualityThreshold = $this->rekognition->getQualityThreshold();
            $detection = $this->rekognition->detectFaceQuality($imageBytes);

            if (!$detection['hasFace']) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'No face detected in the image. Please position your face clearly in the camera.',
                    'error_code' => $detection['error_code'] ?? 'no_face_detected',
                ], 200);
            }

            if (($detection['faceCount'] ?? 1) > 1) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'Multiple faces detected. Please ensure only your face is visible.',
                    'error_code' => 'multiple_faces',
                ], 200);
            }

            // Perform face verification (dual-mode: Rekognition or Local comparison with lighting normalization)
            $verifyResult = $this->rekognition->verifyFace(
                $voter->id,
                $profile->face_id,
                $imageBytes,
                $profile->face_data
            );

            $matchThreshold = $this->rekognition->getMatchThreshold();
            // Convert similarity (0-100) to a 0-1 scale for API consistency
            $matchScore = $verifyResult['similarity'] / 100;
            $threshold = $matchThreshold / 100;
            $verified = $verifyResult['matched'];

            if ($verified) {
                $profile->update([
                    'verification_attempts' => 0, // Reset failed count upon successful match
                    'last_verified_at' => now(),
                ]);

                Log::info('Facial verification passed', [
                    'voter_id' => $voter->id,
                    'similarity' => $verifyResult['similarity'],
                    'mode' => $verifyResult['mode'] ?? 'unknown',
                    'context' => $request->input('context', 'general'),
                ]);

                $sessionToken = $this->generateFaceSessionToken($voter->id);

                return response()->json([
                    'success' => true,
                    'verified' => true,
                    'match_score' => round($matchScore, 4),
                    'threshold' => $threshold,
                    'message' => 'Facial verification successful!',
                    'session_token' => $sessionToken,
                    'session_expires_at' => now()->addMinutes(15)->toIso8601String(),
                    'facial_config' => $voter->fresh()->load('facialProfile')->facial_config,
                ]);
            } else {
                $profile->update([
                    'last_failed_at' => now(),
                ]);

                Log::warning('Facial verification failed', [
                    'voter_id' => $voter->id,
                    'similarity' => $verifyResult['similarity'],
                    'mode' => $verifyResult['mode'] ?? 'unknown',
                ]);

                $failMessage = 'Face does not match registered voter. Please look directly into the camera and try again.';

                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'match_score' => round($matchScore, 4),
                    'threshold' => $threshold,
                    'error' => $failMessage,
                    'error_code' => 'no_match',
                ], 200);
            }
        } catch (\Throwable $e) {
            Log::error('Facial verification error', [
                'voter_id' => $voter->id,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'verified' => false,
                'error' => 'Could not verify face. Please ensure your face is well-lit and try again.',
                'error_code' => 'verification_error',
            ], 200);
        }
    }

    public function resetAttempts(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can reset facial verification attempts'], 403);
        }

        $profile = $voter->facialProfile;
        if ($profile) {
            $profile->update(['verification_attempts' => 0]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification attempts reset.',
            'facial_config' => $voter->fresh()->load('facialProfile')->facial_config,
        ]);
    }

    public function toggle(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can toggle facial verification'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $profile = $voter->facialProfile;
        if (!$profile) {
            return response()->json(['error' => 'No facial profile enrolled. Enroll first before toggling.'], 404);
        }

        $profile->update(['is_enabled' => $request->is_enabled]);

        return response()->json([
            'success' => true,
            'message' => 'Facial verification ' . ($request->is_enabled ? 'enabled' : 'disabled') . '.',
            'facial_config' => $voter->fresh()->load('facialProfile')->facial_config,
        ]);
    }

    public function remove(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can remove facial verification'], 403);
        }

        $profile = $voter->facialProfile;

        if ($profile && !empty($profile->face_id)) {
            try {
                $this->rekognition->removeFace($profile->face_id);
            } catch (\Exception $e) {
                Log::warning('Failed to remove face during profile removal', [
                    'voter_id' => $voter->id,
                    'face_id' => $profile->face_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($profile) {
            $profile->delete();
        }

        Log::info('Facial profile removed', ['voter_id' => $voter->id]);

        return response()->json([
            'success' => true,
            'message' => 'Facial profile removed successfully.',
            'facial_config' => $voter->fresh()->facial_config,
        ]);
    }

    public function validateSession(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['valid' => false, 'error' => 'Invalid user'], 403);
        }

        $token = $request->input('session_token');
        if (!$token) {
            return response()->json(['valid' => false], 200);
        }

        $valid = $this->verifyFaceSessionToken($voter->id, $token);

        return response()->json([
            'valid' => $valid,
        ]);
    }

    private function getVoterOrFail(Request $request): ?Voter
    {
        $user = $request->user();
        return $user instanceof Voter ? $user : null;
    }

    private function generateFaceSessionToken(int $voterId): string
    {
        $payload = base64_encode(json_encode([
            'voter_id' => $voterId,
            'iat' => time(),
            'exp' => time() + (15 * 60),
            'rand' => bin2hex(random_bytes(8)),
        ]));

        $signature = hash_hmac('sha256', $payload, config('app.key') . 'face-verification');

        return $payload . '.' . $signature;
    }

    private function verifyFaceSessionToken(int $voterId, string $token): bool
    {
        if (strpos($token, '.') === false) {
            return false;
        }

        [$payload, $signature] = explode('.', $token, 2);

        $expected = hash_hmac('sha256', $payload, config('app.key') . 'face-verification');
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $data = json_decode(base64_decode($payload), true);
        if (!$data) {
            return false;
        }

        if (($data['voter_id'] ?? 0) !== $voterId) {
            return false;
        }

        if (($data['exp'] ?? 0) < time()) {
            return false;
        }

        return true;
    }
}
