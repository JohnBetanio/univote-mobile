<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'organization_id',
        'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('organization_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Resolve a Course model matching a voter's registered course string or code.
     * Automatically links or creates the course if necessary.
     */
    public static function resolveForVoter(?string $courseInput, ?int $orgId = null): ?self
    {
        if (empty($courseInput)) {
            return null;
        }

        $input = trim($courseInput);
        if ($input === '' || strcasecmp($input, 'Not Specified') === 0) {
            return null;
        }

        // 1. Try exact match on code (case-insensitive)
        $course = self::whereRaw('LOWER(code) = ?', [strtolower($input)])->first();
        if ($course) {
            if (!$course->organization_id && $orgId) {
                $course->update(['organization_id' => $orgId, 'status' => 'assigned']);
            }
            return $course;
        }

        // 2. Try exact match on name (case-insensitive)
        $course = self::whereRaw('LOWER(name) = ?', [strtolower($input)])->first();
        if ($course) {
            if (!$course->organization_id && $orgId) {
                $course->update(['organization_id' => $orgId, 'status' => 'assigned']);
            }
            return $course;
        }

        // 3. Extract code from parentheses if present, e.g. "Bachelor of Science in Information Technology (BS I.T.)"
        $codeInParen = null;
        $nameWithoutParen = $input;
        if (preg_match('/\(([^)]+)\)/', $input, $matches)) {
            $codeInParen = trim($matches[1]);
            $nameWithoutParen = trim(preg_replace('/\s*\([^)]+\)/', '', $input));

            // Check if code in parentheses matches a Course
            $course = self::whereRaw('LOWER(code) = ?', [strtolower($codeInParen)])->first();
            if ($course) {
                if (!$course->organization_id && $orgId) {
                    $course->update(['organization_id' => $orgId, 'status' => 'assigned']);
                }
                return $course;
            }

            // Check if name without parentheses matches a Course
            $course = self::whereRaw('LOWER(name) = ?', [strtolower($nameWithoutParen)])->first();
            if ($course) {
                if (!$course->organization_id && $orgId) {
                    $course->update(['organization_id' => $orgId, 'status' => 'assigned']);
                }
                return $course;
            }
        }

        // 4. Try fuzzy matching (LIKE)
        $course = self::where(function ($q) use ($input, $codeInParen, $nameWithoutParen) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($nameWithoutParen) . '%'])
              ->orWhereRaw('LOWER(code) LIKE ?', ['%' . strtolower($input) . '%']);
            if ($codeInParen) {
                $q->orWhereRaw('LOWER(code) LIKE ?', ['%' . strtolower($codeInParen) . '%']);
            }
        })->first();

        if ($course) {
            if (!$course->organization_id && $orgId) {
                $course->update(['organization_id' => $orgId, 'status' => 'assigned']);
            }
            return $course;
        }

        // 5. If still no course exists in the database, automatically create a Course record
        // so that this registered user is properly represented with a valid course_id
        $newCode = $codeInParen ?: (strlen($input) <= 20 ? strtoupper($input) : strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $input), 0, 10)));
        $newName = $nameWithoutParen ?: $input;

        return self::create([
            'name' => $newName,
            'code' => $newCode,
            'organization_id' => $orgId,
            'status' => $orgId ? 'assigned' : 'pending',
        ]);
    }

    /**
     * Retrieve all courses available in the system.
     * Auto-syncs any voters with a course text but null course_id.
     */
    public static function getAllCourses()
    {
        // Auto-sync any existing voters that have course string but null course_id
        Voter::whereNotNull('course')
            ->where('course', '!=', '')
            ->where('course', '!=', 'Not Specified')
            ->whereNull('course_id')
            ->chunk(100, function ($voters) {
                foreach ($voters as $voter) {
                    $course = static::resolveForVoter($voter->course, $voter->organization_id);
                    if ($course) {
                        $voter->course_id = $course->id;
                        if (!$voter->organization_id && $course->organization_id) {
                            $voter->organization_id = $course->organization_id;
                        }
                        $voter->save();
                    }
                }
            });

        return static::with('organization:id,name,code')
            ->withCount('voters')
            ->orderBy('name')
            ->get();
    }

    /**
     * Retrieve courses (delegates to getAllCourses to ensure all available courses are selectable).
     */
    public static function getEnrolledCourses()
    {
        return static::getAllCourses();
    }
}

