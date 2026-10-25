import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  Image,
} from 'react-native';
import { Colors } from '@/constants/Colors';
import { Ionicons } from '@expo/vector-icons';
import { api, API_BASE_URL } from '@/services/api';
import { useRouter } from 'expo-router';

interface ResultCandidate {
  id: number;
  name: string;
  vote_count: number | null;
  percentage: number | null;
  winner: boolean;
  photo?: string | null;
}

interface ResultPosition {
  id: number;
  name: string;
  candidates: ResultCandidate[];
}

interface ResultElection {
  id: number;
  title: string;
  status: string;
  positions: ResultPosition[];
  total_votes: number;
  organization?: string | null;
  results_sealed?: boolean;
}

const AVATAR_BG_COLORS = ['#E8F5E9', '#E3F2FD', '#F3E5F5', '#FFF3E0', '#E0F2F1', '#FBE9E7'];
const AVATAR_TEXT_COLORS = ['#2E7D32', '#1565C0', '#7B1FA2', '#E65100', '#00695C', '#D84315'];

function getAvatarColors(name: string) {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const idx = Math.abs(hash) % AVATAR_BG_COLORS.length;
  return { bg: AVATAR_BG_COLORS[idx], text: AVATAR_TEXT_COLORS[idx] };
}

function getInitials(name: string): string {
  if (!name) return 'C';
  return name
    .trim()
    .split(/\s+/)
    .map((n) => n[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

function WinnerBadge({ isLive }: { isLive?: boolean }) {
  return (
    <View style={isLive ? styles.leaderBadge : styles.winnerBadge}>
      <Ionicons name={isLive ? "trending-up" : "trophy"} size={11} color={isLive ? "#2563EB" : "#B45309"} />
      <Text style={isLive ? styles.leaderText : styles.winnerText}>
        {isLive ? 'Leading' : 'Elected Winner'}
      </Text>
    </View>
  );
}

function CandidateResultCard({
  candidate,
  isLeader,
  rank,
  isLive,
  isSealed,
}: {
  candidate: ResultCandidate;
  isLeader: boolean;
  rank: number;
  isLive?: boolean;
  isSealed?: boolean;
}) {
  const [photoError, setPhotoError] = useState(false);
  const isWinner = !isSealed && (candidate.winner || isLeader);
  const initials = getInitials(candidate.name);
  const avatar = getAvatarColors(candidate.name);
  const percentage = candidate.percentage != null ? Math.min(100, Math.max(0, candidate.percentage)) : 0;

  const photoUri = candidate.photo
    ? candidate.photo.startsWith('http') || candidate.photo.startsWith('data:')
      ? candidate.photo
      : `${API_BASE_URL.replace(/\/api\/?$/, '')}/storage/${candidate.photo}`
    : null;

  return (
    <View style={[styles.candidateCard, isWinner && (isLive ? styles.candidateCardLeader : styles.candidateCardWinner)]}>
      {/* Top Details Row */}
      <View style={styles.candidateTopRow}>
        {/* Rank / Avatar */}
        <View style={styles.avatarWithRank}>
          <View
            style={[
              styles.avatarCircle,
              { backgroundColor: isWinner ? (isLive ? '#EFF6FF' : '#FEF3C7') : avatar.bg },
            ]}
          >
            {photoUri && !photoError ? (
              <Image
                source={{ uri: photoUri }}
                style={styles.avatarImg}
                onError={() => setPhotoError(true)}
              />
            ) : (
              <Text
                style={[
                  styles.avatarText,
                  { color: isWinner ? (isLive ? '#2563EB' : '#B45309') : avatar.text },
                ]}
              >
                {initials}
              </Text>
            )}
          </View>
          {!isSealed && (
            <View
              style={[
                styles.rankBadge,
                isWinner ? (isLive ? styles.rankBadgeLeader : styles.rankBadgeWinner) : styles.rankBadgeNormal,
              ]}
            >
              <Text
                style={[
                  styles.rankBadgeText,
                  isWinner && (isLive ? styles.rankBadgeTextLeader : styles.rankBadgeTextWinner),
                ]}
              >
                #{rank}
              </Text>
            </View>
          )}
        </View>

        {/* Name & Badge Column */}
        <View style={styles.candidateNameCol}>
          <View style={styles.nameBadgeWrap}>
            <Text
              style={[
                styles.candidateNameText,
                isWinner && (isLive ? styles.candidateNameTextLeader : styles.candidateNameTextWinner),
              ]}
              numberOfLines={1}
            >
              {candidate.name}
            </Text>
            {isWinner && <WinnerBadge isLive={isLive} />}
          </View>
          {isSealed ? (
            <View style={styles.sealedVotesPill}>
              <Ionicons name="lock-closed" size={11} color="#6B7280" />
              <Text style={styles.sealedVotesText}>Votes sealed until polls close</Text>
            </View>
          ) : (
            <Text style={styles.candidateVotesText}>
              <Text style={styles.votesBold}>{(candidate.vote_count ?? 0).toLocaleString()}</Text> vote{(candidate.vote_count ?? 0) !== 1 ? 's' : ''}
            </Text>
          )}
        </View>

        {/* Percentage Label */}
        {!isSealed && (
          <View style={styles.percentageColumn}>
            <Text
              style={[
                styles.percentageNumberText,
                isWinner
                  ? (isLive ? styles.percentageTextLeader : styles.percentageTextWinner)
                  : styles.percentageTextNormal,
              ]}
            >
              {percentage}%
            </Text>
          </View>
        )}
      </View>

      {/* Progress Bar (Only visible when unsealed) */}
      {!isSealed && (
        <View style={styles.progressBarTrack}>
          <View
            style={[
              styles.progressBarFill,
              { width: `${percentage}%` },
              isWinner
                ? (isLive ? styles.progressBarFillLeader : styles.progressBarFillWinner)
                : styles.progressBarFillNormal,
            ]}
          />
        </View>
      )}
    </View>
  );
}

export default function VoterResultsScreen() {
  const router = useRouter();
  const [statusFilter, setStatusFilter] = useState<'closed' | 'active'>('closed');
  const [results, setResults] = useState<ResultElection[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedElectionId, setSelectedElectionId] = useState<number | null>(null);

  const mountedRef = React.useRef(true);
  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const loadResults = useCallback(async (filter: 'closed' | 'active' = statusFilter) => {
    try {
      const electionsRes = await api.getElections(filter);
      if (!mountedRef.current) return;
      const elections = Array.isArray(electionsRes.data) ? electionsRes.data : [];
      const fetched: ResultElection[] = [];

      for (const e of elections) {
        const r = await api.getElectionResults(e.id);
        if (!mountedRef.current) return;
        if (r.data) {
          fetched.push({
            id: r.data.election.id,
            title: r.data.election.title,
            status: r.data.election.status,
            positions: r.data.positions || [],
            total_votes: r.data.total_votes || 0,
            organization: (r.data.election as any)?.organization?.name || null,
            results_sealed: (r.data as any)?.results_sealed ?? (r.data.election.status === 'active'),
          });
        }
      }
      if (!mountedRef.current) return;
      setResults(fetched);
      if (fetched.length > 0) {
        setSelectedElectionId(fetched[0].id);
      } else {
        setSelectedElectionId(null);
      }
    } catch (error) {
      console.error('Error loading results:', error);
    }
  }, [statusFilter]);

  useEffect(() => {
    setLoading(true);
    loadResults(statusFilter).finally(() => {
      if (mountedRef.current) setLoading(false);
    });
  }, [statusFilter, loadResults]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadResults(statusFilter);
    setRefreshing(false);
  };

  const switchFilter = (newFilter: 'closed' | 'active') => {
    if (newFilter === statusFilter) return;
    setStatusFilter(newFilter);
  };

  const totalCertifiedVotes = useMemo(() => {
    return results.reduce((acc, el) => acc + (el.total_votes || 0), 0);
  }, [results]);

  const totalElectionsCount = results.length;

  const currentElection = useMemo(() => {
    if (results.length === 0) return null;
    return results.find((e) => e.id === selectedElectionId) || results[0];
  }, [results, selectedElectionId]);

  const isLiveMode = statusFilter === 'active';

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color={Colors.primary} />
          <Text style={styles.loadingTitle}>
            {isLiveMode ? 'Fetching live tally updates...' : 'Tabulating certified results...'}
          </Text>
          <Text style={styles.loadingSubtitle}>
            {isLiveMode ? 'Aggregating current ballots' : 'Fetching audited election counts'}
          </Text>
        </View>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[Colors.primary]} />}
      showsVerticalScrollIndicator={false}
    >
      {/* ── Brand Top Bar ── */}
      <View style={styles.topBar}>
        <View style={styles.brandGroup}>
          <Image source={require('../../../assets/univote-logo.jpg')} style={styles.brandLogo} />
          <View>
            <Text style={styles.brandTitle}>UniVote</Text>
            <Text style={styles.brandSubtitle}>Election Results Center</Text>
          </View>
        </View>
        <TouchableOpacity
          style={styles.refreshBtn}
          onPress={onRefresh}
          activeOpacity={0.7}
          accessibilityLabel="Refresh election results"
        >
          <Ionicons name="sync-outline" size={18} color="#fff" />
        </TouchableOpacity>
      </View>

      {/* ── Hero Banner ── */}
      <View style={styles.heroBanner}>
        <View style={styles.heroContent}>
          <View style={styles.heroBadgeRow}>
            <View style={styles.certifiedTag}>
              <Ionicons
                name={isLiveMode ? "lock-closed-outline" : "ribbon-outline"}
                size={13}
                color={isLiveMode ? "#93C5FD" : "#A7F3D0"}
              />
              <Text style={styles.certifiedTagText}>
                {isLiveMode ? 'Ongoing Elections (Tallies Sealed)' : 'Official Certified Outcomes'}
              </Text>
            </View>
            <View style={isLiveMode ? styles.liveHeaderPill : styles.closedPill}>
              <Ionicons
                name={isLiveMode ? "time-outline" : "lock-closed"}
                size={10}
                color={isLiveMode ? "#60A5FA" : "#D1D5DB"}
              />
              <Text style={isLiveMode ? styles.liveHeaderPillText : styles.closedPillText}>
                {isLiveMode ? 'In Progress' : 'Audited'}
              </Text>
            </View>
          </View>

          <Text style={styles.heroTitle}>Election Results</Text>
          <Text style={styles.heroSubtitle}>
            {isLiveMode
              ? 'Candidate vote counts are strictly sealed during active voting to protect election integrity.'
              : 'Certified tally counts and elected student leaders from concluded elections.'}
          </Text>

          {/* Mode Switcher Tabs */}
          <View style={styles.modeSwitcherContainer}>
            <TouchableOpacity
              style={[
                styles.modeTabBtn,
                !isLiveMode && styles.modeTabBtnActive,
              ]}
              onPress={() => switchFilter('closed')}
              activeOpacity={0.8}
            >
              <Ionicons
                name="checkmark-circle-outline"
                size={13}
                color={!isLiveMode ? Colors.primary : 'rgba(255,255,255,0.85)'}
              />
              <Text
                style={[
                  styles.modeTabText,
                  !isLiveMode && styles.modeTabTextActive,
                ]}
              >
                Concluded (Official)
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[
                styles.modeTabBtn,
                isLiveMode && styles.modeTabBtnActive,
              ]}
              onPress={() => switchFilter('active')}
              activeOpacity={0.8}
            >
              <Ionicons
                name="time-outline"
                size={13}
                color={isLiveMode ? Colors.primary : 'rgba(255,255,255,0.85)'}
              />
              <Text
                style={[
                  styles.modeTabText,
                  isLiveMode && styles.modeTabTextActive,
                ]}
              >
                Active Elections
              </Text>
            </TouchableOpacity>
          </View>

          {/* Quick Stats Row */}
          <View style={styles.statPillsRow}>
            <View style={styles.statPill}>
              <Ionicons name="podium-outline" size={15} color="#FDE047" />
              <Text style={styles.statPillVal}>{totalElectionsCount}</Text>
              <Text style={styles.statPillLabel}>{isLiveMode ? 'Active' : 'Elections'}</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statPill}>
              <Ionicons name="checkbox-outline" size={15} color="#fff" />
              <Text style={styles.statPillVal}>{totalCertifiedVotes.toLocaleString()}</Text>
              <Text style={styles.statPillLabel}>{isLiveMode ? 'Cast So Far' : 'Total Votes'}</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statPill}>
              <Ionicons name="shield-checkmark-outline" size={14} color="#fff" />
              <Text style={styles.statPillVal}>100%</Text>
              <Text style={styles.statPillLabel}>Encrypted</Text>
            </View>
          </View>
        </View>
      </View>

      {/* ── Empty State ── */}
      {results.length === 0 ? (
        <View style={styles.emptyCard}>
          <View style={[styles.emptyIconCircle, isLiveMode && { backgroundColor: '#EFF6FF' }]}>
            <Ionicons
              name={isLiveMode ? "pulse-outline" : "podium-outline"}
              size={44}
              color={isLiveMode ? "#2563EB" : Colors.primary}
            />
          </View>
          <Text style={styles.emptyTitle}>
            {isLiveMode ? 'No Active Elections' : 'No Concluded Elections'}
          </Text>
          <Text style={styles.emptyDesc}>
            {isLiveMode
              ? 'There are currently no active elections underway. Check concluded results to see previous winners.'
              : 'Official certified outcomes will appear here once elections conclude. You can check live tallies for ongoing votes.'}
          </Text>
          <TouchableOpacity
            style={[styles.voteNowBtn, isLiveMode && { backgroundColor: '#2563EB' }]}
            onPress={() => {
              if (!isLiveMode) {
                switchFilter('active');
              } else {
                router.push('/(tabs)/(voter)/VoterVote');
              }
            }}
            activeOpacity={0.85}
          >
            <Ionicons name={!isLiveMode ? "pulse-outline" : "checkbox-outline"} size={18} color="#fff" />
            <Text style={styles.voteNowBtnText}>
              {!isLiveMode ? 'Check Live Tallies' : 'Go to Active Ballots'}
            </Text>
            <Ionicons name="arrow-forward" size={16} color="#fff" />
          </TouchableOpacity>
        </View>
      ) : (
        <View style={styles.resultsMainWrap}>
          {/* Election Selector Segment (if multiple elections exist) */}
          {results.length > 1 && (
            <View style={styles.electionPickerWrap}>
              <Text style={styles.pickerSectionLabel}>
                SELECT {isLiveMode ? 'ACTIVE ELECTION' : 'CONCLUDED ELECTION'}
              </Text>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.pickerScroll}
              >
                {results.map((e) => {
                  const isSelected = e.id === currentElection?.id;
                  return (
                    <TouchableOpacity
                      key={e.id}
                      style={[
                        styles.pickerTab,
                        isSelected && styles.pickerTabActive,
                      ]}
                      onPress={() => setSelectedElectionId(e.id)}
                      activeOpacity={0.75}
                    >
                      <Ionicons
                        name={isLiveMode ? "pulse-outline" : "trophy-outline"}
                        size={14}
                        color={isSelected ? Colors.primary : '#6B7280'}
                      />
                      <Text
                        style={[
                          styles.pickerTabText,
                          isSelected && styles.pickerTabTextActive,
                        ]}
                        numberOfLines={1}
                      >
                        {e.title}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            </View>
          )}

          {/* Current Election Details */}
          {currentElection && (
            <View style={styles.electionSheet}>
              {/* Election Summary Card */}
              <View style={styles.electionHeaderCard}>
                <View style={styles.electionTopRow}>
                  <View style={[styles.trophyIconWrap, isLiveMode && styles.liveIconWrap]}>
                    <Ionicons
                      name={isLiveMode ? "pulse" : "trophy"}
                      size={20}
                      color={isLiveMode ? "#2563EB" : "#D97706"}
                    />
                  </View>
                  <View style={styles.electionHeaderInfo}>
                    {currentElection.organization ? (
                      <Text style={styles.orgTagText}>{currentElection.organization.toUpperCase()}</Text>
                    ) : null}
                    <Text style={styles.electionSheetTitle}>{currentElection.title}</Text>
                  </View>
                  {isLiveMode ? (
                    <View style={styles.sealedBadge}>
                      <Ionicons name="lock-closed" size={11} color="#2563EB" />
                      <Text style={styles.sealedBadgeText}>Tallies Sealed</Text>
                    </View>
                  ) : (
                    <View style={styles.certifiedBadge}>
                      <Ionicons name="checkmark-done" size={12} color="#15803D" />
                      <Text style={styles.certifiedBadgeText}>Certified</Text>
                    </View>
                  )}
                </View>

                {/* Turnout ribbon */}
                <View style={styles.turnoutRibbon}>
                  <View style={styles.turnoutItem}>
                    <Ionicons name="people" size={13} color="#6B7280" />
                    <Text style={styles.turnoutLabel}>
                      {isLiveMode ? 'Total Ballots Cast:' : 'Total Ballots Counted:'}
                    </Text>
                    <Text style={styles.turnoutVal}>{currentElection.total_votes.toLocaleString()}</Text>
                  </View>
                  <View style={styles.turnoutItem}>
                    <Ionicons name="layers-outline" size={13} color="#6B7280" />
                    <Text style={styles.turnoutLabel}>Contested Positions:</Text>
                    <Text style={styles.turnoutVal}>{currentElection.positions.length}</Text>
                  </View>
                </View>

                {/* Security Sealed Notice Box for Active Elections */}
                {isLiveMode && (
                  <View style={styles.sealedBannerBox}>
                    <Ionicons name="shield-checkmark" size={16} color="#1D4ED8" />
                    <View style={{ flex: 1, marginLeft: 8 }}>
                      <Text style={styles.sealedBannerTitle}>Candidate Tallies Strictly Sealed</Text>
                      <Text style={styles.sealedBannerDesc}>
                        To prevent voting bias, individual candidate vote counts and rankings remain sealed until this election officially concludes.
                      </Text>
                    </View>
                  </View>
                )}
              </View>

              {/* Positions & Tally Breakdown */}
              {currentElection.positions.map((pos, pIdx) => {
                const isSealed = isLiveMode || currentElection.status === 'active' || !!currentElection.results_sealed;
                const sortedCandidates = isSealed
                  ? [...pos.candidates].sort((a, b) => a.name.localeCompare(b.name))
                  : [...pos.candidates].sort((a, b) => (b.vote_count ?? 0) - (a.vote_count ?? 0));

                return (
                  <View key={pos.id} style={styles.positionWrapper}>
                    {/* Position Section Header */}
                    <View style={styles.positionHeaderRow}>
                      <View style={styles.positionTitleLeft}>
                        <View style={styles.positionIndexPill}>
                          <Text style={styles.positionIndexText}>{pIdx + 1}</Text>
                        </View>
                        <Text style={styles.positionTitleText}>{pos.name}</Text>
                      </View>
                      <View style={styles.candidateCountBadge}>
                        <Text style={styles.candidateCountBadgeText}>
                          {sortedCandidates.length} candidate{sortedCandidates.length !== 1 ? 's' : ''}
                        </Text>
                      </View>
                    </View>

                    {/* Candidate Cards */}
                    <View style={styles.candidatesBlock}>
                      {sortedCandidates.map((c, cIdx) => {
                        const isLeader = !isSealed && cIdx === 0 && (c.vote_count ?? 0) > 0;
                        return (
                          <CandidateResultCard
                            key={c.id}
                            candidate={c}
                            isLeader={isLeader}
                            rank={cIdx + 1}
                            isLive={isLiveMode}
                            isSealed={isSealed}
                          />
                        );
                      })}
                    </View>
                  </View>
                );
              })}
            </View>
          )}
        </View>
      )}

      {/* ── Footer Assurance ── */}
      {results.length > 0 && (
        <View style={styles.securityFooter}>
          <Ionicons name="shield-checkmark" size={13} color="#9CA3AF" />
          <Text style={styles.securityFooterText}>
            {isLiveMode
              ? 'Preliminary counts update automatically as verified ballots are received.'
              : 'Results officially audited and certified by the University Commission on Elections.'}
          </Text>
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  content: {
    paddingBottom: 40,
  },

  // Loading
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    padding: 24,
  },
  loadingBox: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 32,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 10,
    elevation: 3,
    maxWidth: 320,
    width: '100%',
  },
  loadingTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#111827',
    marginTop: 16,
    marginBottom: 4,
    textAlign: 'center',
  },
  loadingSubtitle: {
    fontSize: 13,
    color: '#6B7280',
    textAlign: 'center',
  },

  // Top Bar
  topBar: {
    backgroundColor: Colors.primary,
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  brandGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  brandLogo: {
    width: 34,
    height: 34,
    borderRadius: 8,
  },
  brandTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#fff',
    letterSpacing: 0.3,
  },
  brandSubtitle: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.75)',
  },
  refreshBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.18)',
    justifyContent: 'center',
    alignItems: 'center',
  },

  // Hero Banner
  heroBanner: {
    backgroundColor: Colors.primary,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 4,
  },
  heroContent: {
    position: 'relative',
    zIndex: 1,
  },
  heroBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  certifiedTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderRadius: 14,
    paddingHorizontal: 9,
    paddingVertical: 3,
    gap: 5,
  },
  certifiedTagText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#fff',
  },
  closedPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#064E3B',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 3,
    gap: 4,
  },
  closedPillText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#A7F3D0',
    letterSpacing: 0.3,
  },
  liveHeaderPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239, 68, 68, 0.25)',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 3,
    gap: 4,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
  },
  liveHeaderPillText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#FCA5A5',
    letterSpacing: 0.3,
  },
  heroTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: '#fff',
    letterSpacing: -0.3,
    marginBottom: 4,
  },
  heroSubtitle: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.85)',
    lineHeight: 18,
    marginBottom: 14,
  },

  // Mode Switcher Tabs
  modeSwitcherContainer: {
    flexDirection: 'row',
    backgroundColor: 'rgba(0,0,0,0.18)',
    borderRadius: 14,
    padding: 3,
    marginBottom: 14,
    gap: 4,
  },
  modeTabBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
    borderRadius: 11,
    gap: 6,
  },
  modeTabBtnActive: {
    backgroundColor: '#fff',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
    elevation: 2,
  },
  modeTabText: {
    fontSize: 12,
    fontWeight: '700',
    color: 'rgba(255,255,255,0.85)',
  },
  modeTabTextActive: {
    color: Colors.primary,
    fontWeight: '800',
  },

  // Stats
  statPillsRow: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderRadius: 16,
    paddingVertical: 10,
    paddingHorizontal: 12,
    alignItems: 'center',
    justifyContent: 'space-around',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  statPill: {
    alignItems: 'center',
    gap: 2,
    flex: 1,
  },
  statPillVal: {
    fontSize: 15,
    fontWeight: '800',
    color: '#fff',
  },
  statPillLabel: {
    fontSize: 10,
    color: 'rgba(255,255,255,0.8)',
    fontWeight: '600',
  },
  statDivider: {
    width: 1,
    height: 24,
    backgroundColor: 'rgba(255,255,255,0.25)',
  },

  // Results Layout
  resultsMainWrap: {
    marginTop: 14,
  },

  // Election Picker
  electionPickerWrap: {
    marginHorizontal: 16,
    marginBottom: 12,
  },
  pickerSectionLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#9CA3AF',
    letterSpacing: 0.6,
    marginBottom: 8,
    marginLeft: 4,
  },
  pickerScroll: {
    gap: 8,
    paddingVertical: 2,
  },
  pickerTab: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    gap: 6,
  },
  pickerTabActive: {
    backgroundColor: '#ECFDF5',
    borderColor: Colors.primary,
  },
  pickerTabText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#4B5563',
    maxWidth: 180,
  },
  pickerTabTextActive: {
    color: Colors.primary,
    fontWeight: '800',
  },

  // Election Sheet
  electionSheet: {
    paddingHorizontal: 16,
  },
  electionHeaderCard: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  electionTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    marginBottom: 12,
  },
  trophyIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: '#FEF3C7',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  liveIconWrap: {
    backgroundColor: '#EFF6FF',
    borderColor: '#BFDBFE',
  },
  electionHeaderInfo: {
    flex: 1,
  },
  orgTagText: {
    fontSize: 10,
    fontWeight: '800',
    color: Colors.primary,
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  electionSheetTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
    lineHeight: 22,
  },
  certifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  certifiedBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#15803D',
  },
  liveBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  liveBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#DC2626',
  },
  sealedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#BFDBFE',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  sealedBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#1D4ED8',
  },
  sealedBannerBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#BFDBFE',
    borderRadius: 12,
    padding: 12,
    marginTop: 12,
  },
  sealedBannerTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#1E40AF',
    marginBottom: 2,
  },
  sealedBannerDesc: {
    fontSize: 11,
    lineHeight: 16,
    color: '#2563EB',
  },
  sealedVotesPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: '#F3F4F6',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    alignSelf: 'flex-start',
    marginTop: 3,
  },
  sealedVotesText: {
    fontSize: 11,
    color: '#4B5563',
    fontWeight: '600',
  },
  turnoutRibbon: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#F9FAFB',
    borderRadius: 12,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderWidth: 1,
    borderColor: '#F3F4F6',
  },
  turnoutItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  turnoutLabel: {
    fontSize: 11,
    color: '#6B7280',
    fontWeight: '500',
  },
  turnoutVal: {
    fontSize: 11,
    fontWeight: '800',
    color: '#111827',
  },

  // Positions
  positionWrapper: {
    marginBottom: 18,
  },
  positionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
    paddingHorizontal: 2,
  },
  positionTitleLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  positionIndexPill: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: '#E5E7EB',
    justifyContent: 'center',
    alignItems: 'center',
  },
  positionIndexText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#4B5563',
  },
  positionTitleText: {
    fontSize: 15,
    fontWeight: '800',
    color: '#111827',
  },
  candidateCountBadge: {
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  candidateCountBadgeText: {
    fontSize: 10,
    fontWeight: '600',
    color: '#4B5563',
  },
  candidatesBlock: {
    gap: 10,
  },

  // Candidate Card
  candidateCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 1,
  },
  candidateCardWinner: {
    borderColor: '#FDE68A',
    backgroundColor: '#FFFDF7',
    shadowColor: '#F59E0B',
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 2,
  },
  candidateCardLeader: {
    borderColor: '#BFDBFE',
    backgroundColor: '#F8FAFC',
    shadowColor: '#3B82F6',
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 2,
  },
  candidateTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
  },
  avatarWithRank: {
    position: 'relative',
    marginRight: 10,
  },
  avatarCircle: {
    width: 38,
    height: 38,
    borderRadius: 19,
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
  },
  avatarImg: {
    width: 38,
    height: 38,
    borderRadius: 19,
  },
  avatarText: {
    fontSize: 13,
    fontWeight: '800',
  },
  rankBadge: {
    position: 'absolute',
    bottom: -4,
    right: -4,
    paddingHorizontal: 5,
    paddingVertical: 1,
    borderRadius: 6,
    borderWidth: 1,
  },
  rankBadgeWinner: {
    backgroundColor: '#FEF3C7',
    borderColor: '#F59E0B',
  },
  rankBadgeLeader: {
    backgroundColor: '#DBEAFE',
    borderColor: '#3B82F6',
  },
  rankBadgeNormal: {
    backgroundColor: '#F3F4F6',
    borderColor: '#D1D5DB',
  },
  rankBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#6B7280',
  },
  rankBadgeTextWinner: {
    color: '#B45309',
  },
  rankBadgeTextLeader: {
    color: '#1D4ED8',
  },
  candidateNameCol: {
    flex: 1,
    marginRight: 8,
  },
  nameBadgeWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 2,
  },
  candidateNameText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1F2937',
  },
  candidateNameTextWinner: {
    color: '#111827',
  },
  candidateNameTextLeader: {
    color: '#1E3A8A',
  },
  winnerBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  winnerText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#B45309',
  },
  leaderBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#DBEAFE',
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  leaderText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#1D4ED8',
  },
  candidateVotesText: {
    fontSize: 11,
    color: '#6B7280',
  },
  votesBold: {
    fontWeight: '700',
    color: '#374151',
  },
  percentageColumn: {
    alignItems: 'flex-end',
  },
  percentageNumberText: {
    fontSize: 15,
    fontWeight: '800',
  },
  percentageTextWinner: {
    color: '#B45309',
  },
  percentageTextLeader: {
    color: '#1D4ED8',
  },
  percentageTextNormal: {
    color: '#4B5563',
  },

  // Progress Bar
  progressBarTrack: {
    height: 8,
    backgroundColor: '#E5E7EB',
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    borderRadius: 4,
  },
  progressBarFillWinner: {
    backgroundColor: '#10B981',
  },
  progressBarFillLeader: {
    backgroundColor: '#3B82F6',
  },
  progressBarFillNormal: {
    backgroundColor: '#94A3B8',
  },

  // Empty State
  emptyCard: {
    backgroundColor: '#fff',
    borderRadius: 24,
    marginHorizontal: 16,
    marginTop: 24,
    padding: 32,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  emptyIconCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
    marginBottom: 8,
  },
  emptyDesc: {
    fontSize: 13,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 20,
    maxWidth: 280,
  },
  voteNowBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.primary,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 14,
    gap: 8,
    shadowColor: Colors.primary,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  voteNowBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#fff',
  },

  // Security Footer
  securityFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginTop: 20,
    paddingHorizontal: 20,
  },
  securityFooterText: {
    fontSize: 11,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 15,
  },
});
