<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Voter extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'age',
        'sex',
        'email',
        'campus_email',
        'password',
        'course',
        'course_id',
        'year_level',
        'organization_id',
        'google_id',
        'student_id',
        'avatar',
        'is_verified',
        'is_profile_completed',
        'password_manually_set',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_profile_completed' => 'boolean',
        'password_manually_set' => 'boolean',
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected static function booted()
    {
        static::saving(function ($voter) {
            if (!empty($voter->course) && $voter->course !== 'Not Specified' && empty($voter->course_id)) {
                $course = Course::resolveForVoter($voter->course, $voter->organization_id);
                if ($course) {
                    $voter->course_id = $course->id;
                    if (empty($voter->organization_id) && $course->organization_id) {
                        $voter->organization_id = $course->organization_id;
                    }
                }
            }
        });
    }

    /**
     * Full URL for voter's avatar / profile picture.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'data:')) {
            return $this->avatar;
        }

        return request()->getSchemeAndHttpHost()
            ? request()->getSchemeAndHttpHost() . '/storage/' . ltrim($this->avatar, '/')
            : asset('storage/' . ltrim($this->avatar, '/'));
    }

    // Relationship with votes
    public function votes()
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function courseRelation()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // Check if voter has voted in specific election
    public function hasVotedIn($electionId)
    {
        return $this->votes()->where('election_id', $electionId)->exists();
    }

    public function facialProfile()
    {
        return $this->hasOne(FacialProfile::class);
    }

    public function getFacialConfigAttribute(): array
    {
        $profile = $this->facialProfile;

        return [
            'is_enrolled' => $profile ? $profile->is_enrolled : false,
            'is_verified' => $profile ? $profile->is_verified : false,
            'is_enabled' => $profile ? $profile->is_enabled : false,
            'status' => $profile ? $profile->status : 'not_enrolled',
            'verification_attempts' => $profile ? $profile->verification_attempts : 0,
            'last_verified_at' => $profile ? optional($profile->last_verified_at)->toIso8601String() : null,
            'last_failed_at' => $profile ? optional($profile->last_failed_at)->toIso8601String() : null,
            'updated_at' => $profile ? optional($profile->updated_at)->toIso8601String() : null,
        ];
    }

    public function isFacialVerificationRequired(): bool
    {
        $profile = $this->facialProfile;
        if (!$profile) {
            return true;
        }
        return $profile->is_enabled;
    }

    public function needsProfileSetup(): bool
    {
        return !$this->is_profile_completed || !$this->organization_id || !($this->facial_config['is_enrolled'] ?? false);
    }
}
