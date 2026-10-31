<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Election;
use App\Models\Organization;
use App\Models\Voter;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->expectsJson()) {
            return view('index');
        }

        // Only query finished elections (exclude draft, upcoming, active, cancelled)
        $query = Election::with(['organization', 'course', 'positions.candidates.votes', 'votes'])
            ->where(function ($q) {
                $q->whereIn('status', ['ended', 'closed'])
                    ->orWhere(function ($sub) {
                        $sub->whereNotIn('status', ['draft', 'upcoming', 'active', 'cancelled'])
                            ->whereNotNull('end_date')
                            ->where('end_date', '<=', now());
                    });
            });

        // Scope filter
        if ($request->filled('scope') && $request->query('scope') !== 'all') {
            $scope = $request->query('scope');
            if ($scope === 'campus') {
                $query->where(function ($q) {
                    $q->where('election_type', 'all')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('organization_id')->whereNull('course_id');
                      });
                });
            } elseif ($scope === 'organization') {
                $query->where(function ($q) {
                    $q->where('election_type', 'organization')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('organization_id')->whereNull('course_id');
                      });
                });
            } elseif ($scope === 'course') {
                $query->where(function ($q) {
                    $q->where('election_type', 'course')
                      ->orWhereNotNull('course_id');
                });
            }
        }

        if ($request->filled('organization_id')) {
            $orgId = $request->query('organization_id');
            if ($orgId === 'general' || $orgId === 'unassigned') {
                $query->whereNull('organization_id')->whereNull('course_id');
            } else {
                $query->where('organization_id', $orgId);
            }
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        $elections = $query->latest('end_date')->latest('created_at')->get();

        // Sort by org if requested (default is org sorting)
        $sortBy = $request->query('sort', 'org');
        $elections = $elections->sort(function ($a, $b) use ($sortBy) {
            if ($sortBy === 'newest') {
                return ($b->end_date ?? $b->created_at) <=> ($a->end_date ?? $a->created_at);
            }
            if ($sortBy === 'turnout') {
                return $b->votes->count() <=> $a->votes->count();
            }
            // Sort by org name (A-Z)
            $orgA = $a->organization ? strtolower($a->organization->name) : 'zzz';
            $orgB = $b->organization ? strtolower($b->organization->name) : 'zzz';
            if ($orgA === $orgB) {
                return ($b->end_date ?? $b->created_at) <=> ($a->end_date ?? $a->created_at);
            }
            return $orgA <=> $orgB;
        })->values();

        $results = $elections->map(function ($election) {
            $positions = $election->positions->map(function ($position) {
                $candidates = $position->candidates()->withCount('votes')->get();
                $totalVotes = $candidates->sum('votes_count');

                return [
                    'id' => $position->id,
                    'name' => $position->name,
                    'candidates' => $candidates->map(function ($candidate) use ($totalVotes) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->name,
                            'vote_count' => $candidate->votes_count,
                            'percentage' => $totalVotes > 0 ? round(($candidate->votes_count / $totalVotes) * 100, 2) : 0,
                        ];
                    })->sortByDesc('vote_count')->values(),
                ];
            })->values();

            // Total unique voters who actually cast a ballot in this election
            $totalVotersWhoVoted = $election->votes()->distinct('voter_id')->count('voter_id');
            $totalVotesCast = $election->votes()->count();

            // Calculate eligible voters accurately based on scope
            $electionType = $election->election_type ?? ($election->course_id ? 'course' : ($election->organization_id ? 'organization' : 'all'));
            if ($electionType === 'course' || $election->course_id) {
                $totalEligibleVoters = Voter::where('course_id', $election->course_id)->count();
                if ($totalEligibleVoters === 0 && $election->course) {
                    $totalEligibleVoters = Voter::where(function ($q) use ($election) {
                        $q->where('course', $election->course->name)
                          ->orWhere('course', $election->course->code);
                    })->count();
                }
            } elseif ($election->organization_id) {
                $totalEligibleVoters = Voter::where('organization_id', $election->organization_id)->count();
            } else {
                $totalEligibleVoters = Voter::count();
            }

            $turnoutPercentage = $totalEligibleVoters > 0
                ? round(($totalVotersWhoVoted / $totalEligibleVoters) * 100, 1)
                : 0.0;

            // Compute declared winners per position
            $winners = [];
            foreach ($positions as $pos) {
                $cands = $pos['candidates'];
                if ($cands->isNotEmpty()) {
                    $top = $cands->first();
                    if ($top['vote_count'] > 0) {
                        $isTie = $cands->count() > 1 && $cands[1]['vote_count'] === $top['vote_count'];
                        $winners[] = [
                            'position_id' => $pos['id'],
                            'position_name' => $pos['name'],
                            'candidate_id' => $top['id'],
                            'candidate_name' => $top['name'],
                            'vote_count' => $top['vote_count'],
                            'percentage' => $top['percentage'],
                            'is_tie' => $isTie,
                        ];
                    }
                }
            }

            $endedDate = $election->end_date ?? $election->updated_at;

            return [
                'id' => $election->id,
                'title' => $election->title,
                'description' => $election->description,
                'status' => $election->status,
                'election_type' => $electionType,
                'organization_id' => $election->organization_id,
                'course_id' => $election->course_id,
                'organization' => $election->organization ? [
                    'id' => $election->organization->id,
                    'name' => $election->organization->name,
                    'code' => $election->organization->code,
                ] : null,
                'course' => $election->course ? [
                    'id' => $election->course->id,
                    'name' => $election->course->name,
                    'code' => $election->course->code,
                ] : null,
                'created_at' => $election->created_at?->toIso8601String(),
                'end_date' => $endedDate ? $endedDate->toIso8601String() : null,
                'created_at_formatted' => $election->created_at ? $election->created_at->format('F j, Y — g:i A') : null,
                'end_date_formatted' => $endedDate ? $endedDate->format('F j, Y — g:i A') : null,
                'total_voters_who_voted' => $totalVotersWhoVoted,
                'total_votes_cast' => $totalVotesCast,
                'total_eligible_voters' => $totalEligibleVoters,
                'turnout_percentage' => $turnoutPercentage,
                'winners' => $winners,
                'positions' => $positions,
            ];
        });

        $organizations = Organization::orderBy('name')->get(['id', 'name', 'code']);
        $allCourses = Course::getAllCourses();

        return response()->json([
            'results' => $results,
            'organizations' => $organizations,
            'courses' => $allCourses,
            'enrolled_courses' => $allCourses,
        ]);
    }

    public function show(Election $election)
    {
        $election->load(['organization', 'course', 'positions.candidates.votes', 'votes']);

        $positions = $election->positions->map(function ($position) {
            $candidates = $position->candidates()->withCount('votes')->get();
            $totalVotes = $candidates->sum('votes_count');

            return [
                'id' => $position->id,
                'name' => $position->name,
                'candidates' => $candidates->map(function ($candidate) use ($totalVotes) {
                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'vote_count' => $candidate->votes_count,
                        'percentage' => $totalVotes > 0 ? round(($candidate->votes_count / $totalVotes) * 100, 2) : 0,
                    ];
                })->sortByDesc('vote_count')->values(),
            ];
        })->values();

        $totalVotersWhoVoted = $election->votes()->distinct('voter_id')->count('voter_id');
        $totalVotesCast = $election->votes()->count();

        $electionType = $election->election_type ?? ($election->course_id ? 'course' : ($election->organization_id ? 'organization' : 'all'));
        if ($electionType === 'course' || $election->course_id) {
            $totalEligibleVoters = Voter::where('course_id', $election->course_id)->count();
            if ($totalEligibleVoters === 0 && $election->course) {
                $totalEligibleVoters = Voter::where(function ($q) use ($election) {
                    $q->where('course', $election->course->name)
                      ->orWhere('course', $election->course->code);
                })->count();
            }
        } elseif ($election->organization_id) {
            $totalEligibleVoters = Voter::where('organization_id', $election->organization_id)->count();
        } else {
            $totalEligibleVoters = Voter::count();
        }

        $turnoutPercentage = $totalEligibleVoters > 0
            ? round(($totalVotersWhoVoted / $totalEligibleVoters) * 100, 1)
            : 0.0;

        $winners = [];
        foreach ($positions as $pos) {
            $cands = $pos['candidates'];
            if ($cands->isNotEmpty()) {
                $top = $cands->first();
                if ($top['vote_count'] > 0) {
                    $isTie = $cands->count() > 1 && $cands[1]['vote_count'] === $top['vote_count'];
                    $winners[] = [
                        'position_id' => $pos['id'],
                        'position_name' => $pos['name'],
                        'candidate_id' => $top['id'],
                        'candidate_name' => $top['name'],
                        'vote_count' => $top['vote_count'],
                        'percentage' => $top['percentage'],
                        'is_tie' => $isTie,
                    ];
                }
            }
        }

        $endedDate = $election->end_date ?? $election->updated_at;

        return response()->json([
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'description' => $election->description,
                'status' => $election->status,
                'election_type' => $electionType,
                'organization_id' => $election->organization_id,
                'course_id' => $election->course_id,
                'organization' => $election->organization ? [
                    'id' => $election->organization->id,
                    'name' => $election->organization->name,
                    'code' => $election->organization->code,
                ] : null,
                'course' => $election->course ? [
                    'id' => $election->course->id,
                    'name' => $election->course->name,
                    'code' => $election->course->code,
                ] : null,
                'created_at' => $election->created_at?->toIso8601String(),
                'end_date' => $endedDate ? $endedDate->toIso8601String() : null,
                'created_at_formatted' => $election->created_at ? $election->created_at->format('F j, Y — g:i A') : null,
                'end_date_formatted' => $endedDate ? $endedDate->format('F j, Y — g:i A') : null,
                'total_voters_who_voted' => $totalVotersWhoVoted,
                'total_votes_cast' => $totalVotesCast,
                'total_eligible_voters' => $totalEligibleVoters,
                'turnout_percentage' => $turnoutPercentage,
                'winners' => $winners,
                'positions' => $positions,
            ],
        ]);
    }
}
