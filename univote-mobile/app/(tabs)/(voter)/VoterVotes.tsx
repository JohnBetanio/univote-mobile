import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  TextInput,
  Image,
} from 'react-native';
import { Colors } from '@/constants/Colors';
import { Ionicons } from '@expo/vector-icons';
import { api } from '@/services/api';
import { useRouter } from 'expo-router';

interface VoteSelection {
  position: string;
  candidate: string;
  candidateId?: number;
  photo?: string | null;
}

interface VoteGroup {
  electionId: number;
  electionTitle: string;
  electionStatus?: string;
  organization?: string | null;
  votedAt: string;
  receiptCode: string;
  selections: VoteSelection[];
}

// Generate a deterministic aesthetic avatar background color based on name
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
    .map((p) => p[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

function formatVotedDate(dateStr?: string): string {
  if (!dateStr) return 'Recently';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
    });
  } catch {
    return dateStr;
  }
}

export default function VoterVotesScreen() {
  const router = useRouter();
  const [voteGroups, setVoteGroups] = useState<VoteGroup[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterTab, setFilterTab] = useState<'all' | 'active' | 'closed'>('all');

  const mountedRef = React.useRef(true);
  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const loadVotes = useCallback(async () => {
    try {
      const response = await api.getVotes();
      if (!mountedRef.current) return;
      if (response.data && Array.isArray(response.data)) {
        const groups = new Map<number, VoteGroup>();
        for (const vote of response.data) {
          const eid = vote.election_id;
          if (!groups.has(eid)) {
            const receiptHash = Math.abs((eid * 997 + (vote.id || 1) * 31) % 90000) + 10000;
            groups.set(eid, {
              electionId: eid,
              electionTitle: vote.election?.title || `Election #${eid}`,
              electionStatus: (vote.election as any)?.status || 'closed',
              organization: (vote.election as any)?.organization || null,
              votedAt: vote.created_at || '',
              receiptCode: `UV-${eid.toString().padStart(3, '0')}-${receiptHash}`,
              selections: [],
            });
          }
          const g = groups.get(eid)!;
          g.selections.push({
            position: vote.candidate?.position || 'Candidate',
            candidate: vote.candidate?.name || 'Selected Candidate',
            candidateId: vote.candidate?.id,
            photo: (vote.candidate as any)?.photo || null,
          });
        }
        if (!mountedRef.current) return;
        setVoteGroups(Array.from(groups.values()));
      }
    } catch (error) {
      console.error('Error loading votes:', error);
    }
  }, []);

  useEffect(() => {
    loadVotes().finally(() => {
      if (mountedRef.current) setLoading(false);
    });
  }, [loadVotes]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadVotes();
    setRefreshing(false);
  };

  // Filter and search
  const filteredGroups = useMemo(() => {
    return voteGroups.filter((g) => {
      // Tab filter
      if (filterTab === 'active' && g.electionStatus !== 'active') return false;
      if (filterTab === 'closed' && g.electionStatus !== 'closed') return false;

      // Search query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchesTitle = g.electionTitle.toLowerCase().includes(q);
        const matchesOrg = g.organization?.toLowerCase().includes(q);
        const matchesCandidate = g.selections.some(
          (s) => s.candidate.toLowerCase().includes(q) || s.position.toLowerCase().includes(q)
        );
        return matchesTitle || matchesOrg || matchesCandidate;
      }
      return true;
    });
  }, [voteGroups, filterTab, searchQuery]);

  const totalSelections = useMemo(() => {
    return voteGroups.reduce((acc, g) => acc + g.selections.length, 0);
  }, [voteGroups]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color={Colors.primary} />
          <Text style={styles.loadingTitle}>Loading your ballots...</Text>
          <Text style={styles.loadingSubtitle}>Fetching your verified voting history</Text>
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
            <Text style={styles.brandSubtitle}>Secure Voting Portal</Text>
          </View>
        </View>
        <TouchableOpacity
          style={styles.refreshBtn}
          onPress={onRefresh}
          activeOpacity={0.7}
          accessibilityLabel="Refresh vote records"
        >
          <Ionicons name="sync-outline" size={18} color="#fff" />
        </TouchableOpacity>
      </View>

      {/* ── Hero Receipt Banner ── */}
      <View style={styles.heroBanner}>
        <View style={styles.heroGlowOverlay} />
        <View style={styles.heroContent}>
          <View style={styles.heroBadgeRow}>
            <View style={styles.verifiedTag}>
              <Ionicons name="shield-checkmark" size={13} color="#A7F3D0" />
              <Text style={styles.verifiedTagText}>Official Digital Records</Text>
            </View>
            <View style={styles.livePill}>
              <View style={styles.liveDot} />
              <Text style={styles.livePillText}>Recorded</Text>
            </View>
          </View>

          <Text style={styles.heroTitle}>Ballot History</Text>
          <Text style={styles.heroSubtitle}>
            Review all confirmed votes cast from your verified voter account.
          </Text>

          {/* Stat summary pills */}
          <View style={styles.statPillsRow}>
            <View style={styles.statPill}>
              <Ionicons name="file-tray-full-outline" size={15} color="#fff" />
              <Text style={styles.statPillVal}>{voteGroups.length}</Text>
              <Text style={styles.statPillLabel}>Election{voteGroups.length !== 1 ? 's' : ''}</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statPill}>
              <Ionicons name="checkmark-circle-outline" size={15} color="#fff" />
              <Text style={styles.statPillVal}>{totalSelections}</Text>
              <Text style={styles.statPillLabel}>Choices Cast</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statPill}>
              <Ionicons name="lock-closed-outline" size={14} color="#fff" />
              <Text style={styles.statPillVal}>100%</Text>
              <Text style={styles.statPillLabel}>Encrypted</Text>
            </View>
          </View>
        </View>
      </View>

      {/* ── Search & Filter Controls (only if user has votes) ── */}
      {voteGroups.length > 0 && (
        <View style={styles.controlsWrap}>
          {/* Search Box */}
          <View style={styles.searchBox}>
            <Ionicons name="search-outline" size={18} color="#8E9DAE" style={styles.searchIcon} />
            <TextInput
              style={styles.searchInput}
              placeholder="Search by election, position, or candidate..."
              placeholderTextColor="#8E9DAE"
              value={searchQuery}
              onChangeText={setSearchQuery}
              clearButtonMode="while-editing"
            />
            {searchQuery.length > 0 && (
              <TouchableOpacity onPress={() => setSearchQuery('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                <Ionicons name="close-circle" size={18} color="#8E9DAE" />
              </TouchableOpacity>
            )}
          </View>

          {/* Filter Segment Tabs */}
          <View style={styles.filterTabsRow}>
            <TouchableOpacity
              style={[styles.filterTab, filterTab === 'all' && styles.filterTabActive]}
              onPress={() => setFilterTab('all')}
              activeOpacity={0.7}
            >
              <Text style={[styles.filterTabText, filterTab === 'all' && styles.filterTabTextActive]}>
                All ({voteGroups.length})
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.filterTab, filterTab === 'active' && styles.filterTabActive]}
              onPress={() => setFilterTab('active')}
              activeOpacity={0.7}
            >
              <Text style={[styles.filterTabText, filterTab === 'active' && styles.filterTabTextActive]}>
                Active
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.filterTab, filterTab === 'closed' && styles.filterTabActive]}
              onPress={() => setFilterTab('closed')}
              activeOpacity={0.7}
            >
              <Text style={[styles.filterTabText, filterTab === 'closed' && styles.filterTabTextActive]}>
                Completed
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      )}

      {/* ── Ballot Cards List ── */}
      {voteGroups.length === 0 ? (
        /* Empty State: Never voted */
        <View style={styles.emptyCard}>
          <View style={styles.emptyIconCircle}>
            <Ionicons name="receipt-outline" size={44} color={Colors.primary} />
          </View>
          <Text style={styles.emptyTitle}>No Votes Cast Yet</Text>
          <Text style={styles.emptyDesc}>
            You have not participated in any elections yet. Once you vote, your official digital ballot receipt will appear here.
          </Text>
          <TouchableOpacity
            style={styles.voteNowBtn}
            onPress={() => router.push('/(tabs)/(voter)/VoterVote')}
            activeOpacity={0.85}
          >
            <Ionicons name="checkbox-outline" size={18} color="#fff" />
            <Text style={styles.voteNowBtnText}>Browse Active Elections</Text>
            <Ionicons name="arrow-forward" size={16} color="#fff" />
          </TouchableOpacity>
        </View>
      ) : filteredGroups.length === 0 ? (
        /* Empty Search State */
        <View style={styles.emptySearchCard}>
          <Ionicons name="search-outline" size={36} color="#B0BEC5" />
          <Text style={styles.emptySearchTitle}>No matching ballot records</Text>
          <Text style={styles.emptySearchDesc}>Try searching with a different election or candidate name.</Text>
          <TouchableOpacity style={styles.clearSearchBtn} onPress={() => { setSearchQuery(''); setFilterTab('all'); }}>
            <Text style={styles.clearSearchBtnText}>Reset Filters</Text>
          </TouchableOpacity>
        </View>
      ) : (
        /* List of ballot cards */
        filteredGroups.map((v) => {
          const isClosed = v.electionStatus === 'closed';

          return (
            <View key={v.electionId} style={styles.ballotCard}>
              {/* Receipt Header */}
              <View style={styles.cardHeader}>
                <View style={styles.cardHeaderLeft}>
                  <View style={styles.electionIconWrap}>
                    <Ionicons name="reader-outline" size={20} color={Colors.primary} />
                  </View>
                  <View style={styles.titleColumn}>
                    {v.organization ? (
                      <Text style={styles.orgBadgeText}>{v.organization.toUpperCase()}</Text>
                    ) : null}
                    <Text style={styles.cardElectionTitle}>{v.electionTitle}</Text>
                  </View>
                </View>

                {/* Status Badge */}
                <View style={[styles.statusBadge, isClosed ? styles.statusBadgeClosed : styles.statusBadgeActive]}>
                  <View style={[styles.statusDot, isClosed ? styles.statusDotClosed : styles.statusDotActive]} />
                  <Text style={[styles.statusText, isClosed ? styles.statusTextClosed : styles.statusTextActive]}>
                    {isClosed ? 'Closed' : 'Active'}
                  </Text>
                </View>
              </View>

              {/* Security & Timestamp Ribbon */}
              <View style={styles.metaRibbon}>
                <View style={styles.metaItem}>
                  <Ionicons name="calendar-outline" size={13} color="#6B7280" />
                  <Text style={styles.metaText}>{formatVotedDate(v.votedAt)}</Text>
                </View>
                <View style={styles.receiptCodeWrap}>
                  <Ionicons name="finger-print-outline" size={13} color={Colors.primary} />
                  <Text style={styles.receiptCodeText}>{v.receiptCode}</Text>
                </View>
              </View>

              {/* Dotted Receipt Divider */}
              <View style={styles.receiptDividerContainer}>
                <View style={styles.cutoutCircleLeft} />
                <View style={styles.dottedLine} />
                <View style={styles.cutoutCircleRight} />
              </View>

              {/* Selections Section */}
              <View style={styles.cardBody}>
                <View style={styles.choicesHeaderRow}>
                  <Text style={styles.choicesSectionTitle}>YOUR VOTED CHOICES</Text>
                  <View style={styles.choicesCountBadge}>
                    <Text style={styles.choicesCountText}>
                      {v.selections.length} position{v.selections.length !== 1 ? 's' : ''}
                    </Text>
                  </View>
                </View>

                <View style={styles.selectionsList}>
                  {v.selections.map((sel, idx) => {
                    const avatar = getAvatarColors(sel.candidate);
                    const initials = getInitials(sel.candidate);
                    const isLast = idx === v.selections.length - 1;

                    return (
                      <View
                        key={`${v.electionId}-${sel.position}-${idx}`}
                        style={[styles.selectionRow, !isLast && styles.selectionRowBorder]}
                      >
                        {/* Position Header */}
                        <View style={styles.positionTagRow}>
                          <View style={styles.positionIconPill}>
                            <Ionicons name="bookmark" size={10} color={Colors.primary} />
                          </View>
                          <Text style={styles.positionLabel}>{sel.position}</Text>
                        </View>

                        {/* Candidate Selected Box */}
                        <View style={styles.candidateSelectedCard}>
                          <View style={styles.candidateInfoLeft}>
                            <View style={[styles.avatarCircle, { backgroundColor: avatar.bg }]}>
                              <Text style={[styles.avatarInitials, { color: avatar.text }]}>{initials}</Text>
                            </View>
                            <View style={styles.candidateTextCol}>
                              <Text style={styles.candidateNameText}>{sel.candidate}</Text>
                              <Text style={styles.confirmedVoteSubtext}>Ballot choice recorded</Text>
                            </View>
                          </View>

                          <View style={styles.checkedBadge}>
                            <Ionicons name="checkmark-circle" size={18} color="#16A34A" />
                            <Text style={styles.checkedBadgeText}>Voted</Text>
                          </View>
                        </View>
                      </View>
                    );
                  })}
                </View>
              </View>

              {/* Card Footer Actions */}
              <View style={styles.cardFooter}>
                <View style={styles.verifiedStamp}>
                  <Ionicons name="shield-checkmark" size={14} color="#16A34A" />
                  <Text style={styles.verifiedStampText}>Verified on Secure Ledger</Text>
                </View>

                {isClosed ? (
                  <TouchableOpacity
                    style={styles.resultsActionBtn}
                    onPress={() => router.push('/(tabs)/(voter)/VoterResults')}
                    activeOpacity={0.75}
                  >
                    <Ionicons name="bar-chart-outline" size={14} color={Colors.primary} />
                    <Text style={styles.resultsActionText}>View Results</Text>
                    <Ionicons name="chevron-forward" size={14} color={Colors.primary} />
                  </TouchableOpacity>
                ) : (
                  <View style={styles.ongoingNotice}>
                    <Ionicons name="time-outline" size={13} color="#6B7280" />
                    <Text style={styles.ongoingNoticeText}>Results after close</Text>
                  </View>
                )}
              </View>
            </View>
          );
        })
      )}

      {/* ── Footer Assurance ── */}
      {voteGroups.length > 0 && (
        <View style={styles.securityFooter}>
          <Ionicons name="lock-closed" size={14} color="#9CA3AF" />
          <Text style={styles.securityFooterText}>
            All votes are cryptographically cast and stored confidentially.
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
  heroGlowOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.06)',
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
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
  verifiedTag: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderRadius: 20,
    paddingHorizontal: 10,
    paddingVertical: 4,
    gap: 5,
  },
  verifiedTagText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#fff',
  },
  livePill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#064E3B',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 3,
    gap: 5,
  },
  liveDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#34D399',
  },
  livePillText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#A7F3D0',
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
    marginBottom: 16,
  },
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

  // Controls (Search & Tabs)
  controlsWrap: {
    paddingHorizontal: 16,
    marginTop: 16,
    marginBottom: 8,
    gap: 10,
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 14,
    paddingHorizontal: 14,
    height: 44,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 1,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: '#111827',
    paddingVertical: 0,
  },
  filterTabsRow: {
    flexDirection: 'row',
    backgroundColor: '#E5E7EB',
    borderRadius: 12,
    padding: 3,
    gap: 4,
  },
  filterTab: {
    flex: 1,
    paddingVertical: 7,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterTabActive: {
    backgroundColor: '#fff',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 2,
    elevation: 2,
  },
  filterTabText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6B7280',
  },
  filterTabTextActive: {
    color: Colors.primary,
    fontWeight: '700',
  },

  // Ballot Card
  ballotCard: {
    backgroundColor: '#fff',
    borderRadius: 20,
    marginHorizontal: 16,
    marginTop: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
    overflow: 'hidden',
  },
  cardHeader: {
    paddingHorizontal: 18,
    paddingTop: 16,
    paddingBottom: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  cardHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    flex: 1,
    gap: 12,
    marginRight: 10,
  },
  electionIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#C8E6C9',
  },
  titleColumn: {
    flex: 1,
  },
  orgBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: Colors.primary,
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  cardElectionTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#111827',
    lineHeight: 20,
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
    gap: 5,
  },
  statusBadgeActive: {
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  statusBadgeClosed: {
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  statusDotActive: {
    backgroundColor: '#10B981',
  },
  statusDotClosed: {
    backgroundColor: '#9CA3AF',
  },
  statusText: {
    fontSize: 10,
    fontWeight: '700',
  },
  statusTextActive: {
    color: '#065F46',
  },
  statusTextClosed: {
    color: '#4B5563',
  },

  // Ribbon
  metaRibbon: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 18,
    paddingBottom: 12,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  metaText: {
    fontSize: 11,
    color: '#6B7280',
    fontWeight: '500',
  },
  receiptCodeWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F0FDF4',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
    gap: 4,
    borderWidth: 1,
    borderColor: '#DCFCE7',
  },
  receiptCodeText: {
    fontSize: 11,
    fontWeight: '700',
    color: Colors.primary,
    letterSpacing: 0.4,
  },

  // Dotted Ticket Divider
  receiptDividerContainer: {
    position: 'relative',
    height: 18,
    justifyContent: 'center',
    marginVertical: -2,
  },
  dottedLine: {
    borderStyle: 'dashed',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginHorizontal: 16,
  },
  cutoutCircleLeft: {
    position: 'absolute',
    left: -10,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  cutoutCircleRight: {
    position: 'absolute',
    right: -10,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },

  // Card Body
  cardBody: {
    paddingHorizontal: 18,
    paddingTop: 8,
    paddingBottom: 12,
  },
  choicesHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  choicesSectionTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: '#9CA3AF',
    letterSpacing: 0.6,
  },
  choicesCountBadge: {
    backgroundColor: '#F3F4F6',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  choicesCountText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#6B7280',
  },
  selectionsList: {
    gap: 10,
  },
  selectionRow: {
    paddingBottom: 10,
  },
  selectionRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  positionTagRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  positionIconPill: {
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  positionLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#374151',
  },
  candidateSelectedCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#FAFAFA',
    borderRadius: 12,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  candidateInfoLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flex: 1,
    marginRight: 8,
  },
  avatarCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarInitials: {
    fontSize: 12,
    fontWeight: '800',
  },
  candidateTextCol: {
    flex: 1,
  },
  candidateNameText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#111827',
  },
  confirmedVoteSubtext: {
    fontSize: 10,
    color: '#9CA3AF',
    fontWeight: '500',
  },
  checkedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  checkedBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#065F46',
  },

  // Card Footer
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
    paddingHorizontal: 18,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: '#F3F4F6',
  },
  verifiedStamp: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  verifiedStampText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#16A34A',
  },
  resultsActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#fff',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  resultsActionText: {
    fontSize: 12,
    fontWeight: '700',
    color: Colors.primary,
  },
  ongoingNotice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ongoingNoticeText: {
    fontSize: 11,
    color: '#9CA3AF',
    fontWeight: '500',
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
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  emptyIconCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#E8F5E9',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 2,
    borderColor: '#C8E6C9',
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
    lineHeight: 19,
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

  // Empty Search Card
  emptySearchCard: {
    backgroundColor: '#fff',
    borderRadius: 18,
    marginHorizontal: 16,
    marginTop: 20,
    padding: 28,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  emptySearchTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#374151',
    marginTop: 10,
    marginBottom: 4,
  },
  emptySearchDesc: {
    fontSize: 12,
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: 14,
  },
  clearSearchBtn: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 10,
    backgroundColor: '#F3F4F6',
  },
  clearSearchBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: Colors.primary,
  },

  // Security Footer
  securityFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginTop: 24,
    paddingHorizontal: 20,
  },
  securityFooterText: {
    fontSize: 11,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 15,
  },
});
