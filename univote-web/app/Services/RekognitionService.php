<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class RekognitionService
{
    private ?RekognitionClient $client = null;
    private array $config;
    private string $driver;
    private string $collectionId;
    private float $matchThreshold;
    private float $qualityThreshold;

    public function __construct()
    {
        $this->config = config('services.rekognition', []);
        $this->driver = strtolower($this->config['driver'] ?? 'auto');
        $this->collectionId = $this->config['collection_id'] ?? 'campusvote-voters';
        $this->matchThreshold = (float) ($this->config['match_threshold'] ?? 75);
        $this->qualityThreshold = (float) ($this->config['quality_threshold'] ?? 70);
    }

    /**
     * Determine if real (non-placeholder) AWS credentials are configured.
     */
    public function hasValidAwsCredentials(): bool
    {
        $key = trim($this->config['key'] ?? '');
        $secret = trim($this->config['secret'] ?? '');

        if (empty($key) || empty($secret)) {
            return false;
        }

        $placeholders = [
            'your_actual_access_key',
            'your_actual_secret_key',
            'your_aws_access_key',
            'your_aws_secret_key',
            'your_access_key',
            'your_secret_key',
            'null',
            'none',
        ];

        if (in_array(strtolower($key), $placeholders) || in_array(strtolower($secret), $placeholders)) {
            return false;
        }

        if (strlen($key) < 16 || strlen($secret) < 16) {
            return false;
        }

        return true;
    }

    /**
     * Check if AWS mode should be attempted.
     */
    public function isAwsMode(): bool
    {
        if ($this->driver === 'local') {
            return false;
        }

        if ($this->driver === 'rekognition') {
            return true;
        }

        // 'auto' mode: only attempt AWS if credentials look real
        return $this->hasValidAwsCredentials();
    }

    /**
     * Get or initialize the RekognitionClient instance.
     */
    public function getClient(): ?RekognitionClient
    {
        if ($this->client === null && $this->isAwsMode()) {
            $this->client = new RekognitionClient([
                'version' => 'latest',
                'region' => $this->config['region'] ?? 'ap-southeast-1',
                'credentials' => [
                    'key' => $this->config['key'] ?? '',
                    'secret' => $this->config['secret'] ?? '',
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Create the Rekognition Collection if it doesn't already exist (idempotent).
     */
    public function ensureCollectionExists(): array
    {
        if (!$this->isAwsMode()) {
            return [
                'exists' => true,
                'created' => false,
                'collection_id' => 'local',
                'face_count' => 0,
                'arn' => 'local',
                'mode' => 'local',
            ];
        }

        try {
            $client = $this->getClient();
            $result = $client->describeCollection([
                'CollectionId' => $this->collectionId,
            ]);

            Log::info('Rekognition collection already exists', [
                'collection_id' => $this->collectionId,
                'face_count' => $result['FaceCount'] ?? 0,
            ]);

            return [
                'exists' => true,
                'created' => false,
                'collection_id' => $this->collectionId,
                'face_count' => $result['FaceCount'] ?? 0,
                'arn' => $result['CollectionARN'] ?? null,
                'mode' => 'rekognition',
            ];
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                Log::info('Creating Rekognition collection', [
                    'collection_id' => $this->collectionId,
                ]);

                $client = $this->getClient();
                $result = $client->createCollection([
                    'CollectionId' => $this->collectionId,
                ]);

                return [
                    'exists' => false,
                    'created' => true,
                    'collection_id' => $this->collectionId,
                    'arn' => $result['CollectionArn'] ?? null,
                    'mode' => 'rekognition',
                ];
            }

            Log::error('Failed to describe/create Rekognition collection', [
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Pre-flight face detection: verifies an image contains exactly one face
     * and that its quality meets minimum thresholds.
     */
    /**
     * Pre-flight face detection: verifies an image contains exactly one face
     * and that its quality meets minimum thresholds (no face, blur, half-capture).
     */
    public function detectFaceQuality(string $imageBytes): array
    {
        if (!self::validateImageFormat($imageBytes)) {
            return [
                'hasFace' => false,
                'faceCount' => 0,
                'isBlurry' => false,
                'isHalfCaptured' => false,
                'quality' => ['brightness' => 0, 'sharpness' => 0],
                'confidence' => 0,
                'boundingBox' => null,
                'error_code' => 'invalid_format',
                'error' => 'Invalid image format. Please use JPEG or PNG.',
                'mode' => $this->getActiveDriver(),
            ];
        }

        // Try AWS Rekognition if AWS mode is enabled
        if ($this->isAwsMode()) {
            try {
                $client = $this->getClient();
                $result = $client->detectFaces([
                    'Image' => ['Bytes' => $imageBytes],
                    'Attributes' => ['ALL'],
                ]);

                $faces = $result['FaceDetails'] ?? [];
                $faceCount = count($faces);

                if ($faceCount === 0) {
                    return [
                        'hasFace' => false,
                        'faceCount' => 0,
                        'isBlurry' => false,
                        'isHalfCaptured' => false,
                        'quality' => ['brightness' => 0, 'sharpness' => 0],
                        'confidence' => 0,
                        'boundingBox' => null,
                        'error_code' => 'no_face_detected',
                        'error' => 'No face detected in the image. Please position your face clearly in the camera.',
                        'mode' => 'rekognition',
                    ];
                }

                if ($faceCount > 1) {
                    return [
                        'hasFace' => true,
                        'faceCount' => $faceCount,
                        'isBlurry' => false,
                        'isHalfCaptured' => false,
                        'quality' => ['brightness' => 0, 'sharpness' => 0],
                        'confidence' => 0,
                        'boundingBox' => null,
                        'error_code' => 'multiple_faces',
                        'error' => 'Multiple faces detected. Please ensure only one person is in front of the camera.',
                        'mode' => 'rekognition',
                    ];
                }

                $face = $faces[0];
                $brightness = round($face['Quality']['Brightness'] ?? 0, 2);
                $sharpness = round($face['Quality']['Sharpness'] ?? 0, 2);
                $confidence = round($face['Confidence'] ?? 0, 2);
                $box = $face['BoundingBox'] ?? null;
                $pose = $face['Pose'] ?? [];

                $yaw = abs($pose['Yaw'] ?? 0);
                $pitch = abs($pose['Pitch'] ?? 0);
                $roll = abs($pose['Roll'] ?? 0);

                // Boundary clipping / half-capture checks
                $isCutOff = false;
                if ($box) {
                    $left = $box['Left'] ?? 0;
                    $top = $box['Top'] ?? 0;
                    $width = $box['Width'] ?? 0;
                    $height = $box['Height'] ?? 0;

                    // Face touches or exceeds the frame edge (cut off)
                    if ($left < 0.03 || ($left + $width) > 0.97 || $top < 0.03 || ($top + $height) > 0.97) {
                        $isCutOff = true;
                    }

                    // Face is too far away (tiny fraction of frame) or too close (clipping)
                    if ($width < 0.18 || $height < 0.20 || $width > 0.88 || $height > 0.92) {
                        $isCutOff = true;
                    }
                }

                $isSideAngle = ($yaw > 25) || ($pitch > 25) || ($roll > 25);
                $isHalfCaptured = $isCutOff || $isSideAngle;
                $isBlurry = $sharpness < $this->qualityThreshold;

                $errorCode = null;
                $errorMessage = null;

                if ($confidence < 80) {
                    $errorCode = 'no_face_detected';
                    $errorMessage = 'Clear face could not be detected. Please face the camera directly.';
                } elseif ($isHalfCaptured) {
                    $errorCode = 'half_face_detected';
                    $errorMessage = 'Face is only partially captured or cut off. Please center your full face inside the oval frame.';
                } elseif ($isBlurry) {
                    $errorCode = 'face_blurred';
                    $errorMessage = 'Image is blurry. Please hold your device steady and try again.';
                }


                return [
                    'hasFace' => $confidence >= 80,
                    'faceCount' => 1,
                    'isBlurry' => $isBlurry,
                    'isHalfCaptured' => $isHalfCaptured,
                    'quality' => [
                        'brightness' => $brightness,
                        'sharpness' => $sharpness,
                    ],
                    'confidence' => $confidence,
                    'boundingBox' => $box ? [
                        'left' => $box['Left'],
                        'top' => $box['Top'],
                        'width' => $box['Width'],
                        'height' => $box['Height'],
                    ] : null,
                    'error_code' => $errorCode,
                    'error' => $errorMessage,
                    'mode' => 'rekognition',
                ];
            } catch (\Throwable $e) {
                Log::warning('AWS Rekognition detectFaces failed, falling back to local analysis', [
                    'error' => $e->getMessage(),
                ]);

                if ($this->driver === 'rekognition') {
                    throw $e;
                }
                // In 'auto' mode: fall through to local analysis
            }
        }

        // Local face & quality analysis
        return $this->detectLocalFaceQuality($imageBytes);
    }

    /**
     * Local face detection, blur analysis, and half-capture detection using GD.
     */
    private function detectLocalFaceQuality(string $imageBytes): array
    {
        $len = strlen($imageBytes);
        // Genuine photo captures from mobile are typically >= 4KB
        if ($len < 4000 || !function_exists('imagecreatefromstring')) {
            return [
                'hasFace' => false,
                'faceCount' => 0,
                'isBlurry' => false,
                'isHalfCaptured' => false,
                'quality' => ['brightness' => 0, 'sharpness' => 0],
                'confidence' => 0,
                'boundingBox' => null,
                'error_code' => 'no_face_detected',
                'error' => 'No face detected. Please position your face clearly in the camera.',
                'mode' => 'local',
            ];
        }

        $srcImg = @imagecreatefromstring($imageBytes);
        if (!$srcImg) {
            return [
                'hasFace' => false,
                'faceCount' => 0,
                'isBlurry' => false,
                'isHalfCaptured' => false,
                'quality' => ['brightness' => 0, 'sharpness' => 0],
                'confidence' => 0,
                'boundingBox' => null,
                'error_code' => 'no_face_detected',
                'error' => 'Could not process image. Please try again.',
                'mode' => 'local',
            ];
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        if ($srcW < 80 || $srcH < 80) {
            imagedestroy($srcImg);
            return [
                'hasFace' => false,
                'faceCount' => 0,
                'isBlurry' => true,
                'isHalfCaptured' => true,
                'quality' => ['brightness' => 0, 'sharpness' => 0],
                'confidence' => 0,
                'boundingBox' => null,
                'error_code' => 'no_face_detected',
                'error' => 'Image resolution is too low.',
                'mode' => 'local',
            ];
        }

        // Downsample to 120x120 for fast and standardized spatial metric analysis
        $targetSize = 120;
        $workImg = imagecreatetruecolor($targetSize, $targetSize);
        imagecopyresampled($workImg, $srcImg, 0, 0, 0, 0, $targetSize, $targetSize, $srcW, $srcH);
        imagedestroy($srcImg);

        // Build Luminance & Chrominance (YCbCr) maps
        $lumMap = [];
        $skinMask = [];
        $totalLum = 0.0;
        $skinPixelCount = 0;
        $centerSkinCount = 0;
        $leftSkinCount = 0;
        $rightSkinCount = 0;
        $topSkinCount = 0;
        $bottomSkinCount = 0;

        $minSkinX = $targetSize;
        $maxSkinX = 0;
        $minSkinY = $targetSize;
        $maxSkinY = 0;

        $centerMin = (int) ($targetSize * 0.20);
        $centerMax = (int) ($targetSize * 0.80);
        $midX = (int) ($targetSize / 2);
        $midY = (int) ($targetSize / 2);

        $sumSkinX = 0;
        $sumSkinY = 0;

        for ($y = 0; $y < $targetSize; $y++) {
            $lumMap[$y] = [];
            $skinMask[$y] = [];
            for ($x = 0; $x < $targetSize; $x++) {
                $rgb = imagecolorat($workImg, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // ITU-R BT.601 Luminance
                $lum = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
                $lumMap[$y][$x] = $lum;
                $totalLum += $lum;

                // Chrominance Cb & Cr
                $cb = 128.0 - (0.168736 * $r) - (0.331264 * $g) + (0.5 * $b);
                $cr = 128.0 + (0.5 * $r) - (0.418688 * $g) - (0.081312 * $b);

                // Universal human skin locus (YCbCr + RGB chromaticity)
                $isSkin = ($cb >= 65.0 && $cb <= 142.0 && $cr >= 126.0 && $cr <= 184.0)
                    && ($r > $b) && ($g > 15) && (($r - $g) >= 1 || $cr >= 130);

                $skinMask[$y][$x] = $isSkin;

                if ($isSkin) {
                    $skinPixelCount++;
                    $sumSkinX += $x;
                    $sumSkinY += $y;

                    if ($x < $minSkinX) $minSkinX = $x;
                    if ($x > $maxSkinX) $maxSkinX = $x;
                    if ($y < $minSkinY) $minSkinY = $y;
                    if ($y > $maxSkinY) $maxSkinY = $y;

                    if ($x >= $centerMin && $x <= $centerMax && $y >= $centerMin && $y <= $centerMax) {
                        $centerSkinCount++;
                    }

                    if ($x < $midX) $leftSkinCount++;
                    else $rightSkinCount++;

                    if ($y < $midY) $topSkinCount++;
                    else $bottomSkinCount++;
                }
            }
        }

        $totalPixels = $targetSize * $targetSize;
        $centerPixels = ($centerMax - $centerMin + 1) * ($centerMax - $centerMin + 1);
        $avgLum = $totalLum / $totalPixels;

        // Brightness Evaluation
        if ($avgLum < 30) {
            $brightness = ($avgLum / 30.0) * 45.0; // too dark
        } elseif ($avgLum > 235) {
            $brightness = max(10.0, 100.0 - (($avgLum - 235.0) / 20.0) * 80.0); // overexposed
        } else {
            $brightness = 78.0 + (sin(($avgLum - 30) / 205.0 * M_PI) * 18.0);
        }

        // Laplacian Variance & Sobel Gradient for Sharpness / Blur Detection
        $laplacianVals = [];
        $totalLaplacian = 0.0;
        $totalGradient = 0.0;
        $gradSamples = 0;

        for ($y = 1; $y < $targetSize - 1; $y++) {
            for ($x = 1; $x < $targetSize - 1; $x++) {
                // Discrete 4-neighbor Laplacian
                $lap = $lumMap[$y][$x + 1] + $lumMap[$y][$x - 1]
                     + $lumMap[$y + 1][$x] + $lumMap[$y - 1][$x]
                     - (4.0 * $lumMap[$y][$x]);

                $laplacianVals[] = $lap;
                $totalLaplacian += $lap;

                // Sobel gradient magnitude
                $gx = abs($lumMap[$y][$x + 1] - $lumMap[$y][$x - 1]);
                $gy = abs($lumMap[$y + 1][$x] - $lumMap[$y - 1][$x]);
                $totalGradient += ($gx + $gy);
                $gradSamples++;
            }
        }

        imagedestroy($workImg);

        $lapCount = count($laplacianVals);
        $meanLap = $lapCount > 0 ? ($totalLaplacian / $lapCount) : 0;
        $lapSumSq = 0.0;
        foreach ($laplacianVals as $val) {
            $lapSumSq += pow($val - $meanLap, 2);
        }
        $laplacianVariance = $lapCount > 0 ? ($lapSumSq / $lapCount) : 0;
        $avgGradient = $gradSamples > 0 ? ($totalGradient / $gradSamples) : 0;

        // Standard camera face photos have laplacian variance >= 60 and avgGradient >= 10
        // Blurry, out-of-focus, or motion-blurred photos have low variance (< 35) and low gradient (< 6.5)
        if ($laplacianVariance < 18.0) {
            $sharpness = ($laplacianVariance / 18.0) * 35.0; // severe blur
        } elseif ($laplacianVariance < 55.0) {
            $sharpness = 35.0 + (($laplacianVariance - 18.0) / 37.0) * 30.0; // moderate blur (35% - 65%)
        } else {
            $sharpness = min(98.0, 70.0 + (min(200.0, $laplacianVariance) / 200.0) * 26.0); // sharp (70% - 96%)
        }

        // Eye / Feature contrast check within upper-middle face
        $featureContrast = 0.0;
        $eyeZoneContrast = 0.0;
        for ($y = (int)($targetSize * 0.30); $y < (int)($targetSize * 0.55); $y++) {
            for ($x = (int)($targetSize * 0.25); $x < (int)($targetSize * 0.75); $x++) {
                $eyeZoneContrast += abs($lumMap[$y][$x] - $avgLum);
            }
        }
        $eyeZoneSamples = ((int)($targetSize * 0.55) - (int)($targetSize * 0.30)) * ((int)($targetSize * 0.75) - (int)($targetSize * 0.25));
        $avgEyeContrast = $eyeZoneSamples > 0 ? ($eyeZoneContrast / $eyeZoneSamples) : 0;

        // Center skin ratio
        $centerSkinRatio = $centerPixels > 0 ? ($centerSkinCount / $centerPixels) : 0;
        $totalSkinRatio = $totalPixels > 0 ? ($skinPixelCount / $totalPixels) : 0;

        // 1. NO FACE DETECTED CHECKS:
        // A genuine face must have decent skin presence in the center region and natural feature contrast
        $hasFace = true;
        $noFaceReason = null;

        if ($centerSkinRatio < 0.03 && $totalSkinRatio < 0.03) {
            $hasFace = false;
            $noFaceReason = 'No face detected. Please position your face clearly within the oval guide.';
        } elseif ($centerSkinRatio > 0.92 && $avgEyeContrast < 8.0) {
            // Screen or camera completely covered by finger/solid uniform color
            $hasFace = false;
            $noFaceReason = 'Camera appears obstructed. Please remove any obstruction and face the camera.';
        } elseif ($avgEyeContrast < 5.0 && $laplacianVariance < 20.0) {
            // Flat, non-face surface (desk, wall, blank paper)
            $hasFace = false;
            $noFaceReason = 'No face detected. Please position your face clearly within the camera frame.';
        }

        // 2. HALF CAPTURE / PARTIAL FACE CHECKS (Generous tolerance):
        $isHalfCaptured = false;
        $halfCaptureReason = null;

        if ($hasFace && $skinPixelCount > 0) {
            $normMinX = $minSkinX / (float) $targetSize;
            $normMaxX = $maxSkinX / (float) $targetSize;
            $normMinY = $minSkinY / (float) $targetSize;
            $normMaxY = $maxSkinY / (float) $targetSize;

            $centerX = $sumSkinX / (float) ($skinPixelCount * $targetSize);
            $centerY = $sumSkinY / (float) ($skinPixelCount * $targetSize);

            // Only flag if severely cut off (> 80% out of bounds)
            if ($normMinX <= 0.01 && $normMaxX < 0.40) {
                $isHalfCaptured = true;
                $halfCaptureReason = 'Face is cut off on the left. Please center your face in the oval frame.';
            } elseif ($normMaxX >= 0.99 && $normMinX > 0.60) {
                $isHalfCaptured = true;
                $halfCaptureReason = 'Face is cut off on the right. Please center your face in the oval frame.';
            } elseif ($normMinY <= 0.01 && $normMaxY < 0.40) {
                $isHalfCaptured = true;
                $halfCaptureReason = 'Top of face is cut off. Please tilt your camera to show your full face.';
            } elseif (abs($centerX - 0.5) > 0.38 || abs($centerY - 0.5) > 0.40) {
                $isHalfCaptured = true;
                $halfCaptureReason = 'Face is far off-center. Please position your face inside the oval frame.';
            }
        }

        // 3. BLUR CHECK (Only reject on severe blur, e.g. sharpness < 25):
        $isBlurry = $sharpness < 25.0;

        $errorCode = null;
        $errorMessage = null;

        if (!$hasFace) {
            $errorCode = 'no_face_detected';
            $errorMessage = $noFaceReason ?: 'No face detected. Please position your face clearly in the camera.';
        } elseif ($isHalfCaptured) {
            $errorCode = 'half_face_detected';
            $errorMessage = $halfCaptureReason ?: 'Face is only partially captured. Please center your face inside the oval frame.';
        } elseif ($isBlurry) {
            $errorCode = 'face_blurred';
            $errorMessage = 'Image is blurry. Please hold steady and try again.';
        } elseif ($brightness < 10.0) {
            $errorCode = 'poor_lighting';
            $errorMessage = 'Lighting is too dark. Please move to an area with light.';
        }

        $boxWidth = max(0.1, ($maxSkinX - $minSkinX) / (float) $targetSize);
        $boxHeight = max(0.1, ($maxSkinY - $minSkinY) / (float) $targetSize);

        return [
            'hasFace' => $hasFace,
            'faceCount' => $hasFace ? 1 : 0,
            'isBlurry' => $isBlurry,
            'isHalfCaptured' => $isHalfCaptured,
            'quality' => [
                'brightness' => round($brightness, 2),
                'sharpness' => round($sharpness, 2),
            ],
            'confidence' => $hasFace ? 95.0 : 0.0,
            'boundingBox' => $hasFace ? [
                'left' => round($minSkinX / (float) $targetSize, 2),
                'top' => round($minSkinY / (float) $targetSize, 2),
                'width' => round($boxWidth, 2),
                'height' => round($boxHeight, 2),
            ] : null,
            'error_code' => $errorCode,
            'error' => $errorMessage,
            'mode' => 'local',
        ];
    }

    /**
     * Enroll a voter's face into the collection (or local storage).
     */
    public function enrollFace(int $voterId, string $imageBytes): array
    {
        $externalImageId = "voter_{$voterId}";

        if ($this->isAwsMode()) {
            try {
                $client = $this->getClient();
                $result = $client->indexFaces([
                    'CollectionId' => $this->collectionId,
                    'Image' => ['Bytes' => $imageBytes],
                    'ExternalImageId' => $externalImageId,
                    'MaxFaces' => 1,
                    'QualityFilter' => 'AUTO',
                    'DetectionAttributes' => ['ALL'],
                ]);

                $indexedFaces = $result['FaceRecords'] ?? [];

                if (!empty($indexedFaces)) {
                    $faceRecord = $indexedFaces[0];
                    $faceId = $faceRecord['Face']['FaceId'];
                    $faceDetail = $faceRecord['FaceDetail'] ?? [];

                    $brightness = $faceDetail['Quality']['Brightness'] ?? 80;
                    $sharpness = $faceDetail['Quality']['Sharpness'] ?? 80;

                    Log::info('Rekognition face enrolled', [
                        'voter_id' => $voterId,
                        'face_id' => $faceId,
                        'brightness' => $brightness,
                        'sharpness' => $sharpness,
                    ]);

                    return [
                        'face_id' => $faceId,
                        'external_image_id' => $externalImageId,
                        'quality' => [
                            'brightness' => round($brightness, 2),
                            'sharpness' => round($sharpness, 2),
                        ],
                        'indexed' => true,
                        'mode' => 'rekognition',
                    ];
                }
            } catch (AwsException $e) {
                // If collection doesn't exist, create it and retry once
                if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                    Log::info('Rekognition collection not found on indexFaces, creating and retrying...', [
                        'voter_id' => $voterId,
                    ]);
                    try {
                        $this->ensureCollectionExists();
                        return $this->enrollFace($voterId, $imageBytes);
                    } catch (\Exception $retryEx) {
                        Log::error('Collection creation retry failed', ['error' => $retryEx->getMessage()]);
                    }
                }

                Log::warning('Rekognition enrollFace failed, falling back to local enrollment', [
                    'voter_id' => $voterId,
                    'error' => $e->getMessage(),
                    'code' => $e->getAwsErrorCode(),
                ]);

                if ($this->driver === 'rekognition') {
                    throw $e;
                }
                // Fall through to local enrollment in auto mode
            }
        }

        // Local Enrollment
        $qualityInfo = $this->detectLocalFaceQuality($imageBytes);
        $faceId = "local_face_{$voterId}_" . bin2hex(random_bytes(8));

        Log::info('Local face enrolled', [
            'voter_id' => $voterId,
            'face_id' => $faceId,
            'brightness' => $qualityInfo['quality']['brightness'],
            'sharpness' => $qualityInfo['quality']['sharpness'],
        ]);

        return [
            'face_id' => $faceId,
            'external_image_id' => $externalImageId,
            'quality' => $qualityInfo['quality'],
            'indexed' => true,
            'mode' => 'local',
        ];
    }

    /**
     * Verify a captured face against the voter's enrolled profile.
     *
     * @param int $voterId The voter's database ID
     * @param string|null $storedFaceId The enrolled face_id (AWS UUID or local identifier)
     * @param string $imageBytes Raw captured image bytes
     * @param string|null $enrolledFaceData Stored base64 or raw face data for local verification
     * @return array{matched: bool, similarity: float, faceMatches: array, mode: string, error_code?: string|null, error?: string|null}
     */
    public function verifyFace(int $voterId, ?string $storedFaceId, string $imageBytes, ?string $enrolledFaceData = null): array
    {
        $isLocalFaceId = empty($storedFaceId) || str_starts_with($storedFaceId, 'local_');

        // Pre-flight check on current capture (only reject if truly NO face detected)
        $qualityCheck = $this->detectFaceQuality($imageBytes);
        if (!$qualityCheck['hasFace']) {
            return [
                'matched' => false,
                'similarity' => 0.0,
                'faceMatches' => [],
                'mode' => $this->getActiveDriver(),
                'error_code' => $qualityCheck['error_code'] ?? 'no_face_detected',
                'error' => $qualityCheck['error'] ?? 'No face detected in the image. Please position your face clearly in the camera.',
            ];
        }

        // Try AWS Rekognition only if in AWS mode AND face was enrolled in AWS
        if ($this->isAwsMode() && !$isLocalFaceId) {
            try {
                $client = $this->getClient();

                // 1. First try direct 1-to-1 face comparison if enrolled photo is stored
                if (!empty($enrolledFaceData)) {
                    try {
                        $enrolledBytes = self::decodeImage($enrolledFaceData);
                        $compareResult = $client->compareFaces([
                            'SourceImage' => ['Bytes' => $enrolledBytes],
                            'TargetImage' => ['Bytes' => $imageBytes],
                            'SimilarityThreshold' => 50,
                        ]);

                        $matches = $compareResult['FaceMatches'] ?? [];
                        if (!empty($matches)) {
                            $similarity = round($matches[0]['Similarity'] ?? 0, 2);
                            $matched = $similarity >= $this->matchThreshold;

                            Log::info('Rekognition CompareFaces result', [
                                'voter_id' => $voterId,
                                'similarity' => $similarity,
                                'matched' => $matched,
                                'threshold' => $this->matchThreshold,
                            ]);

                            return [
                                'matched' => $matched,
                                'similarity' => $similarity,
                                'faceMatches' => [['face_id' => $storedFaceId, 'similarity' => $similarity]],
                                'mode' => 'rekognition',
                                'error_code' => $matched ? null : 'no_match',
                                'error' => $matched ? null : "Face does not match registered voter. Please try again.",
                            ];
                        }
                    } catch (\Throwable $compEx) {
                        Log::warning('Rekognition compareFaces fallback to searchFacesByImage', ['error' => $compEx->getMessage()]);
                    }
                }

                // 2. Search against Rekognition Collection
                $result = $client->searchFacesByImage([
                    'CollectionId' => $this->collectionId,
                    'Image' => ['Bytes' => $imageBytes],
                    'MaxFaces' => 10,
                    'FaceMatchThreshold' => 50,
                ]);

                $faceMatches = $result['FaceMatches'] ?? [];
                $ownMatch = null;

                foreach ($faceMatches as $match) {
                    if (($match['Face']['FaceId'] ?? null) === $storedFaceId) {
                        $ownMatch = $match;
                        break;
                    }
                }

                if ($ownMatch !== null) {
                    $similarity = round($ownMatch['Similarity'] ?? 0, 2);
                    $matched = $similarity >= $this->matchThreshold;

                    Log::info('Rekognition verify result', [
                        'voter_id' => $voterId,
                        'similarity' => $similarity,
                        'matched' => $matched,
                        'threshold' => $this->matchThreshold,
                    ]);

                    return [
                        'matched' => $matched,
                        'similarity' => $similarity,
                        'faceMatches' => array_map(fn($m) => [
                            'face_id' => $m['Face']['FaceId'] ?? null,
                            'similarity' => round($m['Similarity'] ?? 0, 2),
                        ], $faceMatches),
                        'mode' => 'rekognition',
                        'error_code' => $matched ? null : 'no_match',
                        'error' => $matched ? null : "Face does not match registered voter. Please try again.",
                    ];
                }

                Log::info('Rekognition verify: face did not match enrolled FaceId, falling back to local verification', [
                    'voter_id' => $voterId,
                    'stored_face_id' => $storedFaceId,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Rekognition verifyFace error, falling back to local verification', [
                    'voter_id' => $voterId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Local Verification (Face-only, lighting-invariant)
        return $this->verifyLocalFace($voterId, $imageBytes, $enrolledFaceData);
    }

    /**
     * Locate face bounding box (strictly forehead to chin, cheek to cheek; excluding body and background).
     */
    public function locateFaceBounds(string $imageBytes): ?array
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $srcImg = @imagecreatefromstring($imageBytes);
        if (!$srcImg) {
            return null;
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        if ($srcW < 60 || $srcH < 60) {
            imagedestroy($srcImg);
            return null;
        }

        $gridW = 120;
        $scale = $srcW / (float) $gridW;
        $gridH = (int) round($srcH / $scale);

        $workImg = imagecreatetruecolor($gridW, $gridH);
        imagecopyresampled($workImg, $srcImg, 0, 0, 0, 0, $gridW, $gridH, $srcW, $srcH);
        imagedestroy($srcImg);

        $rowSkin = array_fill(0, $gridH, 0);
        $skinGrid = [];

        for ($y = 0; $y < $gridH; $y++) {
            $skinGrid[$y] = [];
            for ($x = 0; $x < $gridW; $x++) {
                $rgb = imagecolorat($workImg, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $cb = 128.0 - (0.168736 * $r) - (0.331264 * $g) + (0.5 * $b);
                $cr = 128.0 + (0.5 * $r) - (0.418688 * $g) - (0.081312 * $b);

                // True human skin chromaticity (excluding neutral grays, white walls, and bright clothing)
                $isSkin = ($cb >= 70.0 && $cb <= 142.0 && $cr >= 130.0 && $cr <= 185.0)
                    && ($cr > ($cb + 2.0))
                    && ($r > $b) && ($r >= ($g - 5)) && (($r - $b) >= 10)
                    && (($r + $g + $b) < 725) && (($r + $g + $b) > 55);

                $skinGrid[$y][$x] = $isSkin;
                if ($isSkin) {
                    $rowSkin[$y]++;
                }
            }
        }

        imagedestroy($workImg);

        $maxRowSkin = max($rowSkin);
        if ($maxRowSkin < 6) {
            return null;
        }

        // Find vertical face extent using row projection density
        $minY = 0;
        while ($minY < $gridH && $rowSkin[$minY] < ($maxRowSkin * 0.20)) {
            $minY++;
        }

        $maxY = $gridH - 1;
        while ($maxY > $minY && $rowSkin[$maxY] < ($maxRowSkin * 0.20)) {
            $maxY--;
        }

        // Find horizontal face extent across the face rows
        $minX = $gridW;
        $maxX = 0;
        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = 0; $x < $gridW; $x++) {
                if (!empty($skinGrid[$y][$x])) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                }
            }
        }

        if ($minX >= $maxX || $minY >= $maxY) {
            return null;
        }

        $faceW = max(10, $maxX - $minX);
        // Human face aspect ratio (forehead to chin): bounded at 1.25x the face width.
        // This strictly cuts off neck, shoulders, and clothes below the chin!
        $maxFaceH = (int) round($faceW * 1.25);
        $faceH = min($maxY - $minY, $maxFaceH);

        $cropX = (int) max(0, round($minX * $scale));
        $cropY = (int) max(0, round($minY * $scale));
        $cropW = (int) min($srcW - $cropX, round($faceW * $scale));
        $cropH = (int) min($srcH - $cropY, round($faceH * $scale));

        return [
            'x' => $cropX,
            'y' => $cropY,
            'width' => max(40, $cropW),
            'height' => max(40, $cropH),
            'orig_w' => $srcW,
            'orig_h' => $srcH,
        ];
    }


    /**
     * Extract a contrast-normalized, lighting-invariant face patch with LBP and HOG features.
     */
    public function extractNormalizedFacePatch(string $imageBytes, int $size = 64): ?array
    {
        $bounds = $this->locateFaceBounds($imageBytes);
        $srcImg = @imagecreatefromstring($imageBytes);
        if (!$srcImg) {
            return null;
        }

        if ($bounds && function_exists('imagecrop')) {
            $cropped = @imagecrop($srcImg, [
                'x' => $bounds['x'],
                'y' => $bounds['y'],
                'width' => $bounds['width'],
                'height' => $bounds['height'],
            ]);
            if ($cropped) {
                imagedestroy($srcImg);
                $srcImg = $cropped;
            }
        } else {
            // Fallback: center-upper crop to focus on face and avoid body/shoulders
            $srcW = imagesx($srcImg);
            $srcH = imagesy($srcImg);
            $cropW = (int) round($srcW * 0.72);
            $cropH = (int) round($cropW * 1.25);
            $cropX = (int) max(0, round(($srcW - $cropW) / 2));
            $cropY = (int) max(0, round($srcH * 0.10));
            if (function_exists('imagecrop')) {
                $cropped = @imagecrop($srcImg, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => min($cropH, $srcH - $cropY)]);
                if ($cropped) {
                    imagedestroy($srcImg);
                    $srcImg = $cropped;
                }
            }
        }

        $patchImg = imagecreatetruecolor($size, $size);
        imagecopyresampled($patchImg, $srcImg, 0, 0, 0, 0, $size, $size, imagesx($srcImg), imagesy($srcImg));
        imagedestroy($srcImg);

        // 1. Extract Luminance
        $rawLum = [];
        for ($y = 0; $y < $size; $y++) {
            $rawLum[$y] = [];
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($patchImg, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $rawLum[$y][$x] = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
            }
        }
        imagedestroy($patchImg);

        // 2. Lighting Invariance: Histogram Equalization across face patch
        $totalPixels = $size * $size;
        $hist = array_fill(0, 256, 0);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $val = max(0, min(255, (int) round($rawLum[$y][$x])));
                $hist[$val]++;
            }
        }

        $cdf = [];
        $cum = 0;
        $minCdf = -1;
        for ($i = 0; $i < 256; $i++) {
            $cum += $hist[$i];
            $cdf[$i] = $cum;
            if ($cum > 0 && $minCdf === -1) {
                $minCdf = $cum;
            }
        }

        $normLum = [];
        $denom = max(1, $totalPixels - $minCdf);
        for ($y = 0; $y < $size; $y++) {
            $normLum[$y] = [];
            for ($x = 0; $x < $size; $x++) {
                $val = max(0, min(255, (int) round($rawLum[$y][$x])));
                $normLum[$y][$x] = round((($cdf[$val] - $minCdf) / (float) $denom) * 255.0);
            }
        }

        // 3. Multi-Block Uniform LBP (4x4 cells of 16x16)
        $gridCells = 4;
        $cellLen = (int) ($size / $gridCells);
        $lbpCellHists = [];

        for ($gy = 0; $gy < $gridCells; $gy++) {
            for ($gx = 0; $gx < $gridCells; $gx++) {
                $cellHist = array_fill(0, 16, 0);
                $startX = $gx * $cellLen;
                $startY = $gy * $cellLen;

                for ($y = $startY + 1; $y < $startY + $cellLen - 1; $y++) {
                    for ($x = $startX + 1; $x < $startX + $cellLen - 1; $x++) {
                        $center = $normLum[$y][$x];
                        $code = 0;
                        if ($normLum[$y-1][$x-1] >= $center) $code |= 1;
                        if ($normLum[$y-1][$x]   >= $center) $code |= 2;
                        if ($normLum[$y-1][$x+1] >= $center) $code |= 4;
                        if ($normLum[$y][$x+1]   >= $center) $code |= 8;
                        if ($normLum[$y+1][$x+1] >= $center) $code |= 16;
                        if ($normLum[$y+1][$x]   >= $center) $code |= 32;
                        if ($normLum[$y+1][$x-1] >= $center) $code |= 64;
                        if ($normLum[$y][$x-1]   >= $center) $code |= 128;

                        $cellHist[$code % 16]++;
                    }
                }

                $cellSum = array_sum($cellHist);
                if ($cellSum > 0) {
                    foreach ($cellHist as $b => $cnt) {
                        $cellHist[$b] = $cnt / (float) $cellSum;
                    }
                }
                $lbpCellHists[$gy][$gx] = $cellHist;
            }
        }

        // 4. Gradient Orientations (HOG) across 4 horizontal zones (Forehead, Eyes, Nose, Mouth)
        $zoneHog = [];
        for ($z = 0; $z < 4; $z++) {
            $zoneHog[$z] = array_fill(0, 8, 0.0);
            $yStart = max(1, $z * 16);
            $yEnd = min($size - 2, ($z + 1) * 16);

            for ($y = $yStart; $y < $yEnd; $y++) {
                for ($x = 1; $x < $size - 1; $x++) {
                    $gx = $normLum[$y][$x+1] - $normLum[$y][$x-1];
                    $gy = $normLum[$y+1][$x] - $normLum[$y-1][$x];
                    $mag = sqrt(($gx * $gx) + ($gy * $gy));

                    if ($mag > 2.5) {
                        $angle = atan2($gy, $gx);
                        if ($angle < 0) $angle += (2.0 * M_PI);
                        $bin = (int) floor(($angle / (2.0 * M_PI)) * 8.0) % 8;
                        $zoneHog[$z][$bin] += $mag;
                    }
                }
            }

            $zSum = array_sum($zoneHog[$z]);
            if ($zSum > 0.001) {
                foreach ($zoneHog[$z] as $b => $v) {
                    $zoneHog[$z][$b] = $v / $zSum;
                }
            }
        }

        return [
            'norm_lum' => $normLum,
            'lbp_cells' => $lbpCellHists,
            'zone_hog' => $zoneHog,
            'bounds' => $bounds,
        ];
    }

    /**
     * Compare two normalized face patches.
     *
     * Strategy:
     * - Primary (60%): Global best-shift structural Pearson correlation — strongest discriminator,
     *   gives high scores for same face and low for different faces.
     * - Secondary (30%): Direct per-cell LBP histogram intersection — illumination-invariant texture.
     * - Tertiary (10%): Zone HOG cosine similarity — edge orientation.
     */
    public function compareFacePatches(array $p1, array $p2): float
    {
        $size = 64;
        $lum1 = $p1['norm_lum'];
        $lum2 = $p2['norm_lum'];

        // 1. Global best-shift structural Pearson correlation (shift ±4px to tolerate slight head tilt/zoom)
        $bestCos = -1.0;
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $num = 0.0; $d1 = 0.0; $d2 = 0.0;
                for ($y = 8; $y < $size - 8; $y += 2) {
                    $y2 = $y + $dy;
                    if ($y2 < 8 || $y2 >= $size - 8) continue;
                    for ($x = 8; $x < $size - 8; $x += 2) {
                        $x2 = $x + $dx;
                        if ($x2 < 8 || $x2 >= $size - 8) continue;
                        $v1 = $lum1[$y][$x] - 128.0;
                        $v2 = $lum2[$y2][$x2] - 128.0;
                        $num += ($v1 * $v2);
                        $d1 += ($v1 * $v1);
                        $d2 += ($v2 * $v2);
                    }
                }
                $denom = sqrt($d1 * $d2);
                $cos = ($denom > 0.0001) ? ($num / $denom) : 0.0;
                if ($cos > $bestCos) {
                    $bestCos = $cos;
                }
            }
        }
        $structScore = max(0.0, $bestCos) * 100.0;

        // 2. Direct per-cell LBP histogram intersection (cell-to-cell, same position only)
        $gridCells = 4;
        $totalLbp = 0.0;
        for ($gy = 0; $gy < $gridCells; $gy++) {
            for ($gx = 0; $gx < $gridCells; $gx++) {
                $h1 = $p1['lbp_cells'][$gy][$gx];
                $h2 = $p2['lbp_cells'][$gy][$gx];
                $intersect = 0.0;
                for ($b = 0; $b < 16; $b++) {
                    $intersect += min($h1[$b] ?? 0.0, $h2[$b] ?? 0.0);
                }
                $totalLbp += $intersect;
            }
        }
        $lbpScore = ($totalLbp / (float) ($gridCells * $gridCells)) * 100.0;

        // 3. Zone HOG cosine similarity
        $totalHogSim = 0.0;
        for ($z = 0; $z < 4; $z++) {
            $z1 = $p1['zone_hog'][$z];
            $z2 = $p2['zone_hog'][$z];
            $dot = 0.0; $len1 = 0.0; $len2 = 0.0;
            for ($b = 0; $b < 8; $b++) {
                $v1 = $z1[$b] ?? 0.0;
                $v2 = $z2[$b] ?? 0.0;
                $dot += ($v1 * $v2);
                $len1 += ($v1 * $v1);
                $len2 += ($v2 * $v2);
            }
            $denom = sqrt($len1 * $len2);
            $totalHogSim += ($denom > 0.0001) ? max(0.0, $dot / $denom) : 0.0;
        }
        $hogScore = ($totalHogSim / 4.0) * 100.0;

        // Composite: structural Pearson is strongest discriminator, LBP is illumination-invariant texture
        $composite = ($structScore * 0.60) + ($lbpScore * 0.30) + ($hogScore * 0.10);

        return round(max(5.0, min(99.0, $composite)), 2);
    }

    /**
     * Local face verification comparing current capture with enrolled face data using
     * face-only cropping, contrast normalization, and lighting-invariant feature extraction.
     */
    private function verifyLocalFace(int $voterId, string $capturedBytes, ?string $enrolledFaceData): array
    {
        if (empty($enrolledFaceData)) {
            return [
                'matched' => false,
                'similarity' => 0.0,
                'faceMatches' => [],
                'mode' => 'local',
                'error_code' => 'no_enrolled_data',
                'error' => 'No enrolled facial data available for comparison.',
            ];
        }

        try {
            $enrolledBytes = self::decodeImage($enrolledFaceData);
        } catch (\Throwable $e) {
            $enrolledBytes = $enrolledFaceData;
        }

        if ($capturedBytes === $enrolledBytes || md5($capturedBytes) === md5($enrolledBytes)) {
            return [
                'matched' => true,
                'similarity' => 100.0,
                'faceMatches' => [['face_id' => "local_{$voterId}", 'similarity' => 100.0]],
                'mode' => 'local',
                'error_code' => null,
                'error' => null,
            ];
        }

        $patchCap = $this->extractNormalizedFacePatch($capturedBytes, 64);
        $patchEnr = $this->extractNormalizedFacePatch($enrolledBytes, 64);

        if (!$patchCap || !$patchEnr) {
            return [
                'matched' => false,
                'similarity' => 0.0,
                'faceMatches' => [],
                'mode' => 'local',
                'error_code' => 'invalid_image',
                'error' => 'Could not process facial features for verification.',
            ];
        }

        $compositeSimilarity = $this->compareFacePatches($patchCap, $patchEnr);

        // 75% threshold from .env (AWS_REKOGNITION_MATCH_THRESHOLD=75)
        $threshold = $this->matchThreshold > 0 ? $this->matchThreshold : 75.0;
        $matched = $compositeSimilarity >= $threshold;

        Log::info('Local face verification calculated', [
            'voter_id' => $voterId,
            'similarity' => $compositeSimilarity,
            'matched' => $matched,
            'threshold' => $threshold,
        ]);

        return [
            'matched' => $matched,
            'similarity' => $compositeSimilarity,
            'faceMatches' => [
                ['face_id' => "local_{$voterId}", 'similarity' => $compositeSimilarity]
            ],
            'mode' => 'local',
            'error_code' => $matched ? null : 'no_match',
            'error' => $matched ? null : 'Face does not match registered voter. Please try again.',
        ];
    }

    /**
     * Remove a face from the collection.
     */
    public function removeFace(?string $faceId): bool
    {
        if (empty($faceId) || str_starts_with($faceId, 'local_')) {
            return true;
        }

        if (!$this->isAwsMode()) {
            return true;
        }

        try {
            $client = $this->getClient();
            $result = $client->deleteFaces([
                'CollectionId' => $this->collectionId,
                'FaceIds' => [$faceId],
            ]);

            $deletedCount = count($result['DeletedFaces'] ?? []);
            return $deletedCount > 0;
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                return false;
            }
            Log::warning('Rekognition removeFace error', [
                'face_id' => $faceId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove all faces for a voter by their external image ID.
     */
    public function removeFacesByVoterId(int $voterId): int
    {
        if (!$this->isAwsMode()) {
            return 0;
        }

        $externalImageId = "voter_{$voterId}";

        try {
            $client = $this->getClient();
            $result = $client->listFaces([
                'CollectionId' => $this->collectionId,
                'MaxResults' => 100,
            ]);

            $faceIds = [];
            foreach ($result['Faces'] ?? [] as $face) {
                if (($face['ExternalImageId'] ?? '') === $externalImageId) {
                    $faceIds[] = $face['FaceId'];
                }
            }

            if (empty($faceIds)) {
                return 0;
            }

            $deleteResult = $client->deleteFaces([
                'CollectionId' => $this->collectionId,
                'FaceIds' => $faceIds,
            ]);

            return count($deleteResult['DeletedFaces'] ?? []);
        } catch (AwsException $e) {
            Log::warning('Rekognition removeFacesByVoterId error', [
                'voter_id' => $voterId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Strip data-URI prefix from base64 string and decode to raw bytes.
     */
    public static function decodeImage(string $base64): string
    {
        if (str_contains($base64, ';base64,')) {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        return $decoded;
    }

    /**
     * Validate that raw bytes look like a JPEG or PNG image.
     */
    public static function validateImageFormat(string $bytes): bool
    {
        $len = strlen($bytes);
        if ($len < 4) {
            return false;
        }

        // JPEG magic: FF D8 FF
        if (ord($bytes[0]) === 0xFF && ord($bytes[1]) === 0xD8 && ord($bytes[2]) === 0xFF) {
            return true;
        }

        // PNG magic: 89 50 4E 47
        if (ord($bytes[0]) === 0x89 && $bytes[1] === 'P' && $bytes[2] === 'N' && $bytes[3] === 'G') {
            return true;
        }

        return false;
    }

    public function getMatchThreshold(): float
    {
        return $this->matchThreshold;
    }

    public function getQualityThreshold(): float
    {
        return $this->qualityThreshold;
    }

    public function getCollectionId(): string
    {
        return $this->collectionId;
    }

    public function getActiveDriver(): string
    {
        return $this->isAwsMode() ? 'rekognition' : 'local';
    }
}
