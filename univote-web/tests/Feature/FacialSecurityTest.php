<?php

namespace Tests\Feature;

use App\Models\Voter;
use App\Models\FacialProfile;
use App\Models\Election;
use App\Models\Position;
use App\Models\Candidate;
use App\Models\Organization;
use App\Services\RekognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacialSecurityTest extends TestCase
{
    use RefreshDatabase;

    private RekognitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RekognitionService::class);
    }

    /**
     * Helper to create a synthetic image using GD.
     */
    private function createSyntheticImage(string $type = 'face', int $w = 200, int $h = 240): string
    {
        $img = imagecreatetruecolor($w, $h);

        if ($type === 'blank') {
            // Flat gray wall - no face, no skin
            $bg = imagecolorallocate($img, 120, 120, 120);
            imagefill($img, 0, 0, $bg);
        } elseif ($type === 'blurry') {
            // Uniformly smoothed/blurred surface with no sharp edges
            $bg = imagecolorallocate($img, 205, 160, 130);
            imagefill($img, 0, 0, $bg);
            for ($i = 0; $i < 10; $i++) {
                imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
            }
        } elseif ($type === 'half_face') {
            // Skin tone only on the extreme left border, right side dark/empty (cut off)
            $bg = imagecolorallocate($img, 30, 30, 30);
            imagefill($img, 0, 0, $bg);
            $skin = imagecolorallocate($img, 215, 160, 120);
            imagefilledrectangle($img, 0, (int)($h * 0.2), (int)($w * 0.2), (int)($h * 0.8), $skin);
        } else {
            // Centered clear face with skin tone and distinct facial features
            $bg = imagecolorallocate($img, 230, 235, 240);
            imagefill($img, 0, 0, $bg);

            // Centered skin oval (face)
            $skin = imagecolorallocate($img, 215, 160, 125);
            $cx = (int)($w / 2);
            $cy = (int)($h / 2);
            imagefilledellipse($img, $cx, $cy, (int)($w * 0.58), (int)($h * 0.68), $skin);

            // Eyebrows & Eyes (dark contrast valleys)
            $dark = imagecolorallocate($img, 40, 25, 20);
            imagefilledellipse($img, $cx - (int)($w * 0.14), $cy - (int)($h * 0.12), (int)($w * 0.12), (int)($h * 0.06), $dark);
            imagefilledellipse($img, $cx + (int)($w * 0.14), $cy - (int)($h * 0.12), (int)($w * 0.12), (int)($h * 0.06), $dark);

            // Nose bridge
            $nose = imagecolorallocate($img, 180, 130, 100);
            imagefilledrectangle($img, $cx - 2, $cy - (int)($h * 0.04), $cx + 2, $cy + (int)($h * 0.08), $nose);

            // Lips / Mouth
            $lips = imagecolorallocate($img, 175, 75, 75);
            imagefilledellipse($img, $cx, $cy + (int)($h * 0.18), (int)($w * 0.22), (int)($h * 0.07), $lips);

            // High frequency texture / detail to guarantee sharpness
            $detail = imagecolorallocate($img, 50, 40, 30);
            for ($x = $cx - 30; $x <= $cx + 30; $x += 4) {
                imagesetpixel($img, $x, $cy - (int)($h * 0.18), $detail);
            }
        }

        ob_start();
        imagejpeg($img, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_no_face_detected_is_rejected(): void
    {
        $blankBytes = $this->createSyntheticImage('blank');
        $result = $this->service->detectFaceQuality($blankBytes);

        $this->assertFalse($result['hasFace'], 'Blank image should not be recognized as a face.');
        $this->assertEquals('no_face_detected', $result['error_code']);
    }

    public function test_half_captured_face_is_rejected(): void
    {
        $halfBytes = $this->createSyntheticImage('half_face');
        $result = $this->service->detectFaceQuality($halfBytes);

        $isRejected = !$result['hasFace'] || $result['isHalfCaptured'];
        $this->assertTrue($isRejected, 'Half-captured face must be rejected or marked as half-captured.');
    }

    public function test_clear_centered_face_passes_detection(): void
    {
        $faceBytes = $this->createSyntheticImage('face');
        $result = $this->service->detectFaceQuality($faceBytes);

        $this->assertTrue($result['hasFace'], 'Valid centered face must be recognized.');
        $this->assertFalse($result['isHalfCaptured'], 'Centered face should not be marked as cut off.');
        $this->assertFalse($result['isBlurry'], 'Sharp face should not be marked blurry.');
    }

    private function createTestVoter(array $attributes = []): Voter
    {
        static $counter = 1;
        $counter++;
        return Voter::create(array_merge([
            'name' => "Test Voter {$counter}",
            'email' => "voter{$counter}@snsu.edu.ph",
            'password' => bcrypt('password123'),
            'age' => 20,
            'sex' => 'Male',
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'is_verified' => true,
        ], $attributes));
    }

    public function test_enroll_endpoint_rejects_no_face(): void
    {
        $voter = $this->createTestVoter();
        $blankBytes = $this->createSyntheticImage('blank');

        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($blankBytes),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error_code' => 'no_face_detected']);
    }

    public function test_enroll_endpoint_accepts_valid_face_and_enables_profile(): void
    {
        $voter = $this->createTestVoter();
        $faceBytes = $this->createSyntheticImage('face');

        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($faceBytes),
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'session_token', 'facial_config']);

        $voter->refresh();
        $this->assertNotNull($voter->facialProfile);
        $this->assertTrue($voter->facialProfile->is_enrolled);
    }

    public function test_verify_rejects_unregistered_or_dissimilar_faces(): void
    {
        $voter = $this->createTestVoter();
        $face1 = $this->createSyntheticImage('face');

        // Enroll face 1
        $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($face1),
        ]);

        // Attempt verify with completely blank image
        $blankBytes = $this->createSyntheticImage('blank');
        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($blankBytes),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['verified' => false]);
    }

    public function test_verify_accepts_matching_face(): void
    {
        $voter = $this->createTestVoter();
        $faceBytes = $this->createSyntheticImage('face');

        // Enroll
        $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($faceBytes),
        ]);

        // Verify with matching face
        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($faceBytes),
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['verified' => true]);
        $this->assertNotEmpty($response->json('session_token'));
    }

    public function test_vote_requires_facial_session_token(): void
    {
        $org = Organization::create(['name' => 'Org 1', 'code' => 'ORG1']);
        $voter = $this->createTestVoter(['organization_id' => $org->id]);
        $election = Election::create([
            'title' => 'Student Council Election',
            'status' => 'active',
            'organization_id' => $org->id,
        ]);
        $position = Position::create([
            'election_id' => $election->id,
            'name' => 'President',
            'order' => 1,
            'max_votes' => 1,
        ]);
        $candidate = Candidate::create([
            'position_id' => $position->id,
            'name' => 'Jane Doe',
        ]);

        // 1. Cannot vote without face enrollment
        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error_code' => 'facial_enrollment_required']);

        // 2. Enroll face
        $faceBytes = $this->createSyntheticImage('face');
        $enrollResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($faceBytes),
        ]);
        $enrollResp->assertStatus(201);

        // 3. Attempt vote without facial_session_token
        $responseWithoutToken = $this->actingAs($voter, 'sanctum')->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
        ]);
        $responseWithoutToken->assertStatus(403);
        $responseWithoutToken->assertJsonFragment(['error_code' => 'facial_verification_required']);

        // 4. Attempt vote with invalid token
        $responseBadToken = $this->actingAs($voter, 'sanctum')->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
            'facial_session_token' => 'invalid.token.signature',
        ]);
        $responseBadToken->assertStatus(403);
        $responseBadToken->assertJsonFragment(['error_code' => 'facial_session_invalid']);

        // 5. Verify face and get valid session token
        $verifyResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($faceBytes),
        ]);
        $verifyResp->assertStatus(200);
        $validToken = $verifyResp->json('session_token');

        // 6. Successfully cast vote with valid token
        $voteSuccess = $this->actingAs($voter, 'sanctum')->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
            'facial_session_token' => $validToken,
        ]);
        $voteSuccess->assertStatus(201);
        $voteSuccess->assertJsonFragment(['message' => 'Votes submitted successfully']);
    }
    public function test_enroll_rejects_blurry_face(): void
    {
        $voter = $this->createTestVoter();
        $blurryBytes = $this->createSyntheticImage('blurry');

        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($blurryBytes),
        ]);

        $response->assertStatus(422);
        $this->assertContains($response->json('error_code'), ['face_blurred', 'poor_lighting', 'no_face_detected']);
    }

    public function test_enroll_rejects_half_captured_face(): void
    {
        $voter = $this->createTestVoter();
        $halfBytes = $this->createSyntheticImage('half_face');

        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($halfBytes),
        ]);

        $response->assertStatus(422);
        $this->assertContains($response->json('error_code'), ['half_face_detected', 'no_face_detected']);
    }

    public function test_verify_rejects_different_person(): void
    {
        $voter = $this->createTestVoter();
        $face1 = $this->createSyntheticImage('face', 200, 240);

        // Create a different person's face with inverted color and different dimensions
        $img = imagecreatetruecolor(200, 240);
        $bg = imagecolorallocate($img, 10, 10, 10);
        imagefill($img, 0, 0, $bg);
        $skin = imagecolorallocate($img, 130, 80, 50); // very dark skin vs light skin
        imagefilledrectangle($img, 60, 40, 140, 200, $skin);
        ob_start();
        imagejpeg($img, null, 80);
        $diffPersonBytes = ob_get_clean();
        imagedestroy($img);

        // Enroll face 1
        $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($face1),
        ]);

        // Verify with completely different face
        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($diffPersonBytes),
        ]);

        // Must reject: either 401 (mismatch) or 422 (non-face)
        $this->assertTrue(in_array($response->status(), [401, 422]));
        $this->assertFalse($response->json('verified', false));
    }

    public function test_same_face_in_different_places_and_backgrounds_succeeds(): void
    {
        $voter = $this->createTestVoter();

        // 1. Enrolled in Place A (bright indoor room, white background rgb(240, 245, 250))
        $w = 200; $h = 240;
        $imgA = imagecreatetruecolor($w, $h);
        $bgA = imagecolorallocate($imgA, 240, 245, 250);
        imagefill($imgA, 0, 0, $bgA);
        // Face features
        $skinA = imagecolorallocate($imgA, 215, 160, 125);
        $cxA = (int)($w / 2); $cyA = (int)($h / 2);
        imagefilledellipse($imgA, $cxA, $cyA, (int)($w * 0.58), (int)($h * 0.68), $skinA);
        $darkA = imagecolorallocate($imgA, 40, 25, 20);
        imagefilledellipse($imgA, $cxA - 28, $cyA - 28, 24, 14, $darkA);
        imagefilledellipse($imgA, $cxA + 28, $cyA - 28, 24, 14, $darkA);
        $noseA = imagecolorallocate($imgA, 180, 130, 100);
        imagefilledrectangle($imgA, $cxA - 2, $cyA - 10, $cxA + 2, $cyA + 18, $noseA);
        $lipsA = imagecolorallocate($imgA, 175, 75, 75);
        imagefilledellipse($imgA, $cxA, $cyA + 42, 44, 16, $lipsA);
        $detailA = imagecolorallocate($imgA, 50, 40, 30);
        for ($x = $cxA - 30; $x <= $cxA + 30; $x += 4) {
            imagesetpixel($imgA, $x, $cyA - 42, $detailA);
        }
        ob_start();
        imagejpeg($imgA, null, 90);
        $bytesPlaceA = ob_get_clean();
        imagedestroy($imgA);

        // Enroll face in Place A
        $enrollResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($bytesPlaceA),
        ]);
        $enrollResp->assertStatus(201);

        // 2. Verified in Place B (outdoor / green / completely different background rgb(30, 85, 45) + warmer lighting)
        $imgB = imagecreatetruecolor($w, $h);
        $bgB = imagecolorallocate($imgB, 30, 85, 45); // completely different background!
        imagefill($imgB, 0, 0, $bgB);
        // Same face, slightly shifted position (+2px) and warm ambient tint
        $skinB = imagecolorallocate($imgB, 222, 165, 120);
        $cxB = $cxA + 2; $cyB = $cyA + 1;
        imagefilledellipse($imgB, $cxB, $cyB, (int)($w * 0.58), (int)($h * 0.68), $skinB);
        $darkB = imagecolorallocate($imgB, 45, 28, 18);
        imagefilledellipse($imgB, $cxB - 28, $cyB - 28, 24, 14, $darkB);
        imagefilledellipse($imgB, $cxB + 28, $cyB - 28, 24, 14, $darkB);
        $noseB = imagecolorallocate($imgB, 185, 134, 98);
        imagefilledrectangle($imgB, $cxB - 2, $cyB - 10, $cxB + 2, $cyB + 18, $noseB);
        $lipsB = imagecolorallocate($imgB, 180, 78, 72);
        imagefilledellipse($imgB, $cxB, $cyB + 42, 44, 16, $lipsB);
        $detailB = imagecolorallocate($imgB, 55, 42, 28);
        for ($x = $cxB - 30; $x <= $cxB + 30; $x += 4) {
            imagesetpixel($imgB, $x, $cyB - 42, $detailB);
        }
        ob_start();
        imagejpeg($imgB, null, 90);
        $bytesPlaceB = ob_get_clean();
        imagedestroy($imgB);

        // Verify face in Place B: must succeed because algorithm focuses ONLY on the face!
        $verifyResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($bytesPlaceB),
        ]);

        $verifyResp->assertStatus(200);
        $this->assertTrue($verifyResp->json('verified'));
        $this->assertNotEmpty($verifyResp->json('session_token'));
    }

    public function test_no_face_in_circle_cannot_enroll(): void
    {
        $voter = $this->createTestVoter();

        // 1. Pure dark room / empty background
        $blankBytes = $this->createSyntheticImage('blank');
        $resp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($blankBytes),
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonFragment(['error_code' => 'no_face_detected']);

        // Verify no profile was created
        $voter->refresh();
        $this->assertNull($voter->facialProfile);

        // 2. Non-face object (e.g. wall/surface with random colors but no face in circle)
        $w = 200; $h = 240;
        $img = imagecreatetruecolor($w, $h);
        $blueBg = imagecolorallocate($img, 60, 90, 150);
        imagefill($img, 0, 0, $blueBg);
        ob_start();
        imagejpeg($img, null, 80);
        $noFaceBytes = ob_get_clean();
        imagedestroy($img);

        $resp2 = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($noFaceBytes),
        ]);
        $resp2->assertStatus(422);
        $resp2->assertJsonFragment(['error_code' => 'no_face_detected']);

        $voter->refresh();
        $this->assertNull($voter->facialProfile);
    }

    public function test_warm_surface_or_room_background_without_face_is_rejected(): void
    {
        $voter = $this->createTestVoter();

        // 1. Warm-toned wooden table / beige wall (where chrominance falls into skin range, but NO eyes/face present)
        $w = 200; $h = 240;
        $img = imagecreatetruecolor($w, $h);
        $woodBg = imagecolorallocate($img, 210, 155, 115);
        imagefill($img, 0, 0, $woodBg);
        // Add some table grain / surface noise
        $noise = imagecolorallocate($img, 195, 140, 100);
        for ($i = 0; $i < 100; $i++) {
            imageline($img, 0, rand(0, $h), $w, rand(0, $h), $noise);
        }
        ob_start();
        imagejpeg($img, null, 85);
        $woodBytes = ob_get_clean();
        imagedestroy($img);

        $quality = $this->service->detectFaceQuality($woodBytes);
        $this->assertFalse($quality['hasFace'], 'Warm wooden surface or wall must NOT be approved as a face.');
        $this->assertEquals('no_face_detected', $quality['error_code']);

        $resp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($woodBytes),
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonFragment(['error_code' => 'no_face_detected']);

        $voter->refresh();
        $this->assertNull($voter->facialProfile);
    }

    public function test_setup_profile_without_password_succeeds(): void
    {
        $voter = $this->createTestVoter(['is_profile_completed' => false]);
        $org = \App\Models\Organization::firstOrCreate(
            ['code' => 'CITE'],
            ['name' => 'College of Information Technology Education', 'description' => 'Test']
        );

        $response = $this->actingAs($voter, 'sanctum')->postJson('/api/auth/setup-profile', [
            'name' => 'Juan Dela Cruz',
            'age' => 20,
            'sex' => 'Male',
            'course' => 'BS Information Technology',
            'year_level' => '3rd Year',
            'organization_id' => $org->id,
            // Notice: NO password provided
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.is_profile_completed', true);

        $voter->refresh();
        $this->assertTrue($voter->is_profile_completed);
        $this->assertEquals('Juan Dela Cruz', $voter->name);
        $this->assertEquals($org->id, $voter->organization_id);
    }

    public function test_detect_quality_endpoint_validates_faces_properly(): void
    {
        $voter = $this->createTestVoter();

        // 1. Blank image
        $blankBytes = $this->createSyntheticImage('blank');
        $blankResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/detect-quality', [
            'face_data' => base64_encode($blankBytes),
        ]);
        $blankResp->assertStatus(200);
        $this->assertFalse($blankResp->json('valid'));
        $this->assertEquals('no_face_detected', $blankResp->json('error_code'));

        // 2. Blurry image
        $blurryBytes = $this->createSyntheticImage('blurry');
        $blurryResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/detect-quality', [
            'face_data' => base64_encode($blurryBytes),
        ]);
        $blurryResp->assertStatus(200);
        $this->assertFalse($blurryResp->json('valid'));

        // 3. Clear face
        $faceBytes = $this->createSyntheticImage('face');
        $faceResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/detect-quality', [
            'face_data' => base64_encode($faceBytes),
        ]);
        $faceResp->assertStatus(200);
        $this->assertTrue($faceResp->json('valid'));
        $this->assertTrue($faceResp->json('has_face'));
        $this->assertEquals(1, $faceResp->json('face_count'));
    }

    public function test_verify_endpoint_distinguishes_quality_issues_from_identity_mismatches(): void
    {
        $voter = $this->createTestVoter();
        $clearFace = $this->createSyntheticImage('face');

        // Enroll clear face
        $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($clearFace),
        ])->assertStatus(201);

        // Verify with blurry image: must return 422 with is_quality_issue = true, NOT 401 mismatch!
        $blurryBytes = $this->createSyntheticImage('blurry');
        $blurryResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($blurryBytes),
        ]);
        $blurryResp->assertStatus(422);
        $this->assertTrue($blurryResp->json('is_quality_issue'));
        $this->assertContains($blurryResp->json('error_code'), ['face_blurred', 'poor_lighting', 'no_face_detected']);

        // Verify that verification_attempts was NOT incremented on a quality issue
        $voter->refresh();
        $this->assertEquals(0, $voter->facialProfile->verification_attempts);

        // Verify with no face (blank): must return 422 with is_quality_issue = true
        $blankBytes = $this->createSyntheticImage('blank');
        $blankResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($blankBytes),
        ]);
        $blankResp->assertStatus(422);
        $this->assertTrue($blankResp->json('is_quality_issue'));

        // Verify with missing profile (voter without enrolled face)
        $unregisteredVoter = $this->createTestVoter();
        $missingResp = $this->actingAs($unregisteredVoter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($clearFace),
        ]);
        $missingResp->assertStatus(404);
        $this->assertEquals('not_enrolled', $missingResp->json('error_code'));
    }

    public function test_front_camera_mirroring_succeeds_in_verification(): void
    {
        $voter = $this->createTestVoter();
        $faceBytes = $this->createSyntheticImage('face', 200, 240);

        // Enroll standard orientation
        $this->actingAs($voter, 'sanctum')->postJson('/api/facial/enroll', [
            'face_data' => base64_encode($faceBytes),
        ])->assertStatus(201);

        // Create horizontally mirrored version of the same face (simulating front camera selfie mirror)
        $src = imagecreatefromstring($faceBytes);
        $w = imagesx($src);
        $h = imagesy($src);
        $mirrored = imagecreatetruecolor($w, $h);
        imagecopyresampled($mirrored, $src, 0, 0, $w - 1, 0, $w, $h, -$w, $h);
        imagedestroy($src);
        ob_start();
        imagejpeg($mirrored, null, 90);
        $mirroredBytes = ob_get_clean();
        imagedestroy($mirrored);

        // Verification must pass because algorithm handles front-camera mirroring!
        $verifyResp = $this->actingAs($voter, 'sanctum')->postJson('/api/facial/verify', [
            'face_data' => base64_encode($mirroredBytes),
        ]);

        $verifyResp->assertStatus(200);
        $this->assertTrue($verifyResp->json('verified'));
        $this->assertNotEmpty($verifyResp->json('session_token'));
    }
}
