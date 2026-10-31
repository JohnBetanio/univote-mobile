<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'start_date',
        'end_date',
        'organization_id',
        'election_type',
        'course_id',
        'winners',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function candidates()
    {
        return $this->hasManyThrough(Candidate::class, Position::class);
    }

    public function isCourseScoped(): bool
    {
        return $this->election_type === 'course' || !empty($this->course_id);
    }

    public function isOrganizationScoped(): bool
    {
        return $this->election_type === 'organization' || (!empty($this->organization_id) && !$this->isCourseScoped());
    }

    public function isCampusWide(): bool
    {
        return !$this->isCourseScoped() && !$this->isOrganizationScoped();
    }

    public function isEligibleVoter(?Voter $voter): bool
    {
        if (!$voter) {
            return false;
        }

        // Campus-wide election: all registered voters are eligible
        if ($this->isCampusWide()) {
            return true;
        }

        // Course-scoped election: strictly eligible ONLY if voter is enrolled in this course
        if ($this->isCourseScoped()) {
            if (!$this->course_id) {
                return false;
            }

            // Direct course_id match
            if ($voter->course_id && (int) $voter->course_id === (int) $this->course_id) {
                return true;
            }

            // Fallback match on voter's course text vs course code/name
            if (!empty($voter->course) && $voter->course !== 'Not Specified') {
                $courseObj = $this->course ?? Course::find($this->course_id);
                if ($courseObj) {
                    $voterCourse = strtolower(trim($voter->course));
                    $code = strtolower(trim($courseObj->code ?? ''));
                    $name = strtolower(trim($courseObj->name ?? ''));

                    if (($code && $voterCourse === $code) || ($name && $voterCourse === $name)) {
                        return true;
                    }

                    // Auto-sync course_id on the voter if matches
                    $resolved = Course::resolveForVoter($voter->course, $voter->organization_id);
                    if ($resolved && (int) $resolved->id === (int) $this->course_id) {
                        return true;
                    }
                }
            }

            return false;
        }

        // Organization-scoped election: voter must belong to this organization
        if ($this->isOrganizationScoped()) {
            return !empty($this->organization_id) && (int) $voter->organization_id === (int) $this->organization_id;
        }

        return false;
    }
}
