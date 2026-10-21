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

    public function detectQuality(Request $request)
    {
        $voter = $this->getVoterOrFail($request);
        if (!$voter) {
            return response()->json(['error' => 'Only voters can test face quality'], 403);
        }

        $validator = Validator::make($request->all(), [
            'face_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $imageBytes = RekognitionService::decodeImage($request->face_data);
            if (!RekognitionService::validateImageFormat($imageBytes)) {
                return response()->json([
                    'valid' => false,
                    'has_face' => false,
                    'error' => 'Invalid image format. Please use JPEG or PNG.',
                    'error_code' => 'invalid_format',
                ], 422);
            }

            $detection = $this->rekognition->detectFaceQuality($imageBytes);

            $isValid = $detection['hasFace']
                && empty($detection['isBlurry'])
                && empty($detection['isHalfCaptured'])
                && (($detection['faceCount'] ?? 1) === 1)
                && empty($detection['error_code']);

            return response()->json([
                'valid' => $isValid,
                'has_face' => $detection['hasFace'],
                'is_blurry' => $detection['isBlurry'] ?? false,
                'is_half_captured' => $detection['isHalfCaptured'] ?? false,
                'face_count' => $detection['faceCount'] ?? 0,
                'quality' => $detection['quality'] ?? ['brightness' => 0, 'sharpness' => 0],
                'error_code' => $detection['error_code'] ?? null,
                'error' => $detection['error'] ?? null,
                'message' => $isValid ? 'Clear face detected.' : ($detection['error'] ?? 'Face validation failed.'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'has_face' => false,
                'error' => 'Could not process image quality: ' . $e->getMessage(),
                'error_code' => 'processing_error',
            ], 422);
        }
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

            // Pre-flight: detect face (strictly enforce face inside circle guide, not cut off, not blurry)
            $qualityThreshold = $this->rekognition->getQualityThreshold();
            $detection = $this->rekognition->detectFaceQuality($imageBytes);

            if (!$detection['hasFace']) {
                return response()->json([
                    'error' => $detection['error'] ?? 'No face detected in the circle guide. Please position your face clearly within the oval frame.',
                    'error_code' => $detection['error_code'] ?? 'no_face_detected',
                    'quality_score' => 0,
                    'min_required' => $qualityThreshold / 100,
                ], 422);
            }

            if (!empty($detection['isHalfCaptured'])) {
                return response()->json([
                    'error' => $detection['error'] ?? 'Face is only partially captured or cut off. Please center your full face inside the circle guide.',
                    'error_code' => $detection['error_code'] ?? 'half_face_detected',
                    'quality_score' => 0,
                    'min_required' => $qualityThreshold / 100,
                ], 422);
            }

            if (!empty($detection['isBlurry'])) {
                return response()->json([
                    'error' => $detection['error'] ?? 'Image is blurry. Please hold steady and try again.',
                    'error_code' => $detection['error_code'] ?? 'face_blurred',
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

            // One-time registration policy: if voter already has an enrolled profile, reject re-enrollment
            $existingProfile = FacialProfile::where('voter_id', $voter->id)->first();
            if ($existingProfile && $existingProfile->is_enrolled) {
                return response()->json([
                    'error' => 'Facial registration is one-time only and has already been completed for your account.',
                    'error_code' => 'already_registered',
                ], 422);
            }
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
                'error' => config('app.debug')
                    ? 'Failed to enroll face: ' . $e->getMessage()
                    : 'Failed to enroll face. Please try again.',
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

            // Pre-flight: detect face and ensure image is clear and usable before matching
            $qualityThreshold = $this->rekognition->getQualityThreshold();
            $detection = $this->rekognition->detectFaceQuality($imageBytes);

            if (!$detection['hasFace']) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'No face detected in the circle guide. Please position your face clearly in the circle.',
                    'error_code' => $detection['error_code'] ?? 'no_face_detected',
                    'is_quality_issue' => true,
                ], 422);
            }

            if (!empty($detection['isHalfCaptured'])) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'Face is only partially captured or cut off. Please center your full face inside the circle guide.',
                    'error_code' => $detection['error_code'] ?? 'half_face_detected',
                    'is_quality_issue' => true,
                ], 422);
            }

            if (!empty($detection['isBlurry'])) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'Image is blurry. Please hold steady and try again.',
                    'error_code' => 'face_blurred',
                    'is_quality_issue' => true,
                ], 422);
            }

            if (($detection['error_code'] ?? null) === 'poor_lighting') {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'Lighting is too dark or overexposed. Please move to a well-lit area.',
                    'error_code' => 'poor_lighting',
                    'is_quality_issue' => true,
                ], 422);
            }

            if (($detection['faceCount'] ?? 1) > 1) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $detection['error'] ?? 'Multiple faces detected. Please ensure only your face is visible.',
                    'error_code' => 'multiple_faces',
                    'is_quality_issue' => true,
                ], 422);
            }

            // Perform face verification against authenticated voter's registered profile
            $verifyResult = $this->rekognition->verifyFace(
                $voter->id,
                $profile->face_id,
                $imageBytes,
                $profile->face_data
            );

            // If verifyFace flagged a quality issue on the capture
            if (!empty($verifyResult['is_quality_issue'])) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'match_score' => 0.0,
                    'threshold' => round(($verifyResult['threshold'] ?? $this->rekognition->getMatchThreshold()) / 100, 4),
                    'error' => $verifyResult['error'] ?? 'Image unusable for verification. Please scan again in good light.',
                    'error_code' => $verifyResult['error_code'] ?? 'quality_rejected',
                    'is_quality_issue' => true,
                ], 422);
            }

            // If an external service/network error occurred
            if (($verifyResult['error_code'] ?? null) === 'api_error') {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'match_score' => 0.0,
                    'threshold' => round(($verifyResult['threshold'] ?? $this->rekognition->getMatchThreshold()) / 100, 4),
                    'error' => $verifyResult['error'] ?? 'Facial verification service temporarily unavailable. Please try again.',
                    'error_code' => 'api_error',
                ], 503);
            }

            $matchThreshold = $verifyResult['threshold'] ?? $this->rekognition->getMatchThreshold();
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
                    'threshold' => round($threshold, 4),
                    'message' => 'Facial verification successful!',
                    'session_token' => $sessionToken,
                    'session_expires_at' => now()->addMinutes(15)->toIso8601String(),
                    'facial_config' => $voter->fresh()->load('facialProfile')->facial_config,
                ]);
            } else {
                $profile->increment('verification_attempts');
                $profile->update([
                    'last_failed_at' => now(),
                ]);

                Log::warning('Facial verification failed: identity mismatch', [
                    'voter_id' => $voter->id,
                    'similarity' => $verifyResult['similarity'],
                    'threshold' => $matchThreshold,
                    'mode' => $verifyResult['mode'] ?? 'unknown',
                ]);

                $failMessage = 'Face does not match registered voter. Please look directly into the camera and try again.';

                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'match_score' => round($matchScore, 4),
                    'threshold' => round($threshold, 4),
                    'error' => $failMessage,
                    'error_code' => 'no_match',
                ], 401);
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
                'error' => 'Could not verify face due to a processing error. Please try again.',
                'error_code' => 'verification_error',
            ], 422);
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

        if ($profile && $profile->is_enrolled) {
            return response()->json([
                'error' => 'Facial registration is permanent and one-time only. Contact an administrator to reset facial data.',
                'error_code' => 'removal_not_permitted',
            ], 403);
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
