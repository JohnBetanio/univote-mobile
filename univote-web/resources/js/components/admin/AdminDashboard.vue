<template>
    <section class="admin-dashboard">
        <header class="dashboard-heading">
            <div>
                <p class="eyebrow">Election operations</p>
                <h1>Control center</h1>
                <p class="heading-copy">Monitor participation, keep elections moving, and act on the work that needs attention.</p>
            </div>
            <div class="heading-actions">
                <button class="refresh-button" type="button" :disabled="loading" @click="loadDashboard">{{ loading ? "Refreshing..." : "Refresh data" }}</button>
                <router-link to="/admin/elections/create" class="create-button">+ Create election</router-link>
            </div>
        </header>

        <section class="metrics-grid" aria-label="Election metrics">
            <article class="metric-card metric-green"><p>Total elections</p><strong>{{ stats.total_elections }}</strong><span>{{ activeElections.length }} currently active</span></article>
            <article class="metric-card metric-blue"><p>Registered voters</p><strong>{{ stats.total_voters }}</strong><span>{{ participationRate }}% participation</span></article>
            <article class="metric-card metric-gold"><p>Votes recorded</p><strong>{{ stats.total_votes }}</strong><span>{{ votesPerElection }} average per election</span></article>
            <article class="metric-card metric-ink"><p>Election health</p><strong>{{ activeElections.length ? "Live" : "Ready" }}</strong><span>{{ activeElections.length ? "Monitoring active polls" : "No election in progress" }}</span></article>
        </section>

        <!-- ===== LIVE ELECTION POLLS ===== -->
        <section class="polls-section" aria-label="Current election polls">
            <div class="polls-section-header">
                <div class="polls-title-group">
                    <span class="live-badge">
                        <span class="pulse-dot"></span>
                        LIVE ELECTION POLLS
                    </span>
                    <h2 class="polls-heading">Current election standings by organization</h2>
                    <p class="polls-subtext">Real-time candidate vote tallies from all active elections.</p>
                </div>
                <div class="polls-controls">
                    <label for="poll-org-filter" class="filter-label">Filter by organization</label>
                    <select id="poll-org-filter" v-model="selectedOrgFilter" class="org-filter-select">
                        <option value="all">All Organizations</option>
                        <option value="general">Campus-wide / General</option>
                        <option v-for="org in availablePollOrgs" :key="org.id" :value="org.id">
                            {{ org.name }} ({{ org.code }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="polls-loading">
                <div class="spinner"></div>
                <p>Loading live poll data…</p>
            </div>

            <!-- No active polls empty state -->
            <div v-else-if="!filteredPolls.length" class="polls-empty">
                <div class="polls-empty-icon">🗳️</div>
                <h3>No active elections</h3>
                <p>Start an election to see live candidate standings here.</p>
                <router-link to="/admin/elections/create" class="create-button">+ Create election</router-link>
            </div>

            <!-- Election poll cards -->
            <div v-else class="polls-grid">
                <article v-for="poll in filteredPolls" :key="poll.id" class="poll-card">
                    <!-- Card header -->
                    <div class="poll-card-header">
                        <div class="poll-card-meta">
                            <span class="org-badge">
                                🏛️ {{ poll.organization ? `${poll.organization.name} (${poll.organization.code})` : 'Campus-wide / General' }}
                            </span>
                            <span class="poll-live-chip">● Live</span>
                        </div>
                        <h3 class="poll-title">{{ poll.title }}</h3>
                        <p v-if="poll.description" class="poll-description">{{ poll.description }}</p>
                        <div class="poll-stats-row">
                            <span class="stat-chip">🗳️ {{ poll.total_votes }} ballots cast</span>
                            <span class="stat-chip">📋 {{ poll.positions.length }} position{{ poll.positions.length !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>

                    <!-- Positions -->
                    <div class="positions-list">
                        <div v-for="position in poll.positions" :key="position.id" class="position-block">
                            <div class="position-header">
                                <span class="position-title">{{ position.name }}</span>
                                <span class="position-votes">{{ position.total_votes }} vote{{ position.total_votes !== 1 ? 's' : '' }}</span>
                            </div>

                            <!-- No candidates state -->
                            <p v-if="!position.candidates.length" class="no-candidates">No candidates registered.</p>

                            <!-- Candidate rows -->
                            <div v-else class="candidates-list">
                                <div
                                    v-for="candidate in position.candidates"
                                    :key="candidate.id"
                                    class="candidate-row"
                                    :class="{ 'candidate-leading': candidate.is_leading }"
                                >
                                    <span class="rank-badge" :class="`rank-${candidate.rank}`">{{ getRankLabel(candidate.rank) }}</span>

                                    <!-- Avatar / photo -->
                                    <div class="candidate-avatar">
                                        <img v-if="candidate.photo" :src="candidate.photo" :alt="candidate.name" class="candidate-photo" />
                                        <span v-else class="candidate-initials">{{ getInitials(candidate.name) }}</span>
                                    </div>

                                    <!-- Info -->
                                    <div class="candidate-info">
                                        <div class="candidate-name-row">
                                            <span class="candidate-name">{{ candidate.name }}</span>
                                            <span v-if="candidate.is_leading" class="leader-badge">👑 Leader</span>
                                        </div>
                                        <span v-if="candidate.description" class="candidate-desc">{{ candidate.description }}</span>
                                    </div>

                                    <!-- Vote count + percentage -->
                                    <div class="candidate-result">
                                        <span class="vote-count">{{ candidate.vote_count }}</span>
                                        <span class="vote-pct">{{ candidate.percentage }}%</span>
                                    </div>

                                    <!-- Progress bar -->
                                    <div class="progress-wrap">
                                        <div
                                            class="progress-fill"
                                            :class="{ 'progress-leading': candidate.is_leading }"
                                            :style="{ width: `${candidate.percentage}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="poll-card-footer">
                        <router-link :to="`/admin/elections/${poll.id}/edit`" class="poll-manage-link">Manage election →</router-link>
                        <router-link :to="`/admin/results/${poll.id}`" class="poll-results-link">View results →</router-link>
                    </div>
                </article>
            </div>
        </section>

        <!-- Organization Voter Statistics & Dynamic Charts -->
        <section class="org-stats-section" aria-label="Organization voter statistics">
            <AdminVoterCharts :organization-stats="organizationStats" />

            <!-- Organization Statistics Table Breakdown -->
            <article class="panel org-table-panel">
                <div class="panel-heading">
                    <div>
                        <p class="panel-kicker">Data Breakdown</p>
                        <h2>Organization voter statistics</h2>
                    </div>
                    <span class="badge-count">{{ organizationStats.length }} Organizations</span>
                </div>

                <div v-if="loading" class="panel-loading">Loading organization voter data...</div>
                <div v-else-if="organizationStats.length" class="table-responsive">
                    <table class="org-table">
                        <thead>
                            <tr>
                                <th>Organization</th>
                                <th class="text-center">Total Voters</th>
                                <th class="text-center">Voted</th>
                                <th class="text-center">Not Voted</th>
                                <th>Turnout</th>
                                <th class="text-center">Male</th>
                                <th class="text-center">Female</th>
                                <th class="text-center">Other</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="org in organizationStats" :key="org.id ?? 'unassigned'">
                                <td class="org-name-cell">
                                    <strong>{{ org.name }}</strong>
                                    <span class="org-code-pill">{{ org.code }}</span>
                                </td>
                                <td class="text-center font-bold">{{ org.total_voters }}</td>
                                <td class="text-center text-voted font-bold">{{ org.voted }}</td>
                                <td class="text-center text-not-voted font-bold">{{ org.not_voted }}</td>
                                <td>
                                    <div class="table-progress">
                                        <div class="table-progress-bar" :style="{ width: `${org.participation_percentage}%` }"></div>
                                        <span class="table-progress-text">{{ org.participation_percentage }}%</span>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge-male">{{ org.male_voters }}</span></td>
                                <td class="text-center"><span class="badge-female">{{ org.female_voters }}</span></td>
                                <td class="text-center"><span class="badge-other">{{ org.other_voters }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="empty-operations">
                    <p>No organization voter data found in the database.</p>
                </div>
            </article>
        </section>

        <section class="dashboard-grid">
            <article class="panel live-panel">
                <div class="panel-heading"><div><p class="panel-kicker">Live oversight</p><h2>Election activity</h2></div><router-link to="/admin/elections" class="panel-link">Manage elections</router-link></div>
                <div v-if="loading" class="panel-loading">Loading election records...</div>
                <div v-else-if="activeElections.length" class="election-list">
                    <article v-for="election in activeElections" :key="election.id" class="election-row">
                        <div class="election-status" aria-hidden="true"></div>
                        <div class="election-details"><h3>{{ election.title }}</h3><p>{{ election.positions_count || 0 }} positions · {{ election.votes_count || 0 }} votes recorded</p></div>
                        <router-link :to="`/admin/elections/${election.id}/edit`" class="row-action">Open</router-link>
                    </article>
                </div>
                <div v-else class="empty-operations"><p>No active election is running.</p><router-link to="/admin/elections/create">Create an election</router-link></div>
            </article>

            <article class="panel participation-panel">
                <div class="panel-heading"><div><p class="panel-kicker">Participation</p><h2>Engagement snapshot</h2></div><span class="percentage">{{ participationRate }}%</span></div>
                <div class="progress-track" aria-hidden="true"><div class="progress-value" :style="{ width: `${participationRate}%` }"></div></div>
                <dl class="snapshot-list"><div><dt>Eligible voters</dt><dd>{{ stats.total_voters }}</dd></div><div><dt>Votes cast</dt><dd>{{ stats.total_votes }}</dd></div><div><dt>Completed elections</dt><dd>{{ completedElections }}</dd></div></dl>
            </article>

            <article class="panel action-panel">
                <div class="panel-heading"><div><p class="panel-kicker">Operations</p><h2>Next actions</h2></div></div>
                <router-link to="/admin/elections/create" class="task-link"><span>Create a new election</span><small>Set positions and candidates</small></router-link>
                <router-link to="/admin/voters" class="task-link"><span>Review voter registry</span><small>{{ stats.total_voters }} voters on record</small></router-link>
                <router-link to="/admin/announcements" class="task-link"><span>Publish an announcement</span><small>Keep voters informed</small></router-link>
            </article>

            <article class="panel recent-panel">
                <div class="panel-heading"><div><p class="panel-kicker">Recent records</p><h2>Latest elections</h2></div><router-link to="/admin/results" class="panel-link">View results</router-link></div>
                <div v-if="recentElections.length" class="recent-list">
                    <div v-for="election in recentElections" :key="election.id" class="recent-row"><div><h3>{{ election.title }}</h3><p>Created {{ formatDate(election.created_at) }}</p></div><span :class="['status-pill', `status-${election.status}`]">{{ election.status }}</span></div>
                </div>
                <div v-else class="panel-loading">No elections created yet.</div>
            </article>
        </section>
    </section>
</template>

<script>
import { useElectionStore } from "../../stores/electionStore.js";
import { adminAPI } from "../../services/api.js";
import AdminVoterCharts from "./AdminVoterCharts.vue";

export default {
    name: "AdminDashboard",
    components: {
        AdminVoterCharts,
    },
    setup() {
        const electionStore = useElectionStore();
        return { electionStore };
    },
    data() {
        return {
            stats: { total_elections: 0, active_elections: 0, total_voters: 0, total_votes: 0 },
            organizationStats: [],
            electionPolls: [],
            selectedOrgFilter: "all",
        };
    },
    computed: {
        loading() { return this.electionStore.isLoading; },
        elections() { return this.electionStore.elections || []; },
        activeElections() { return this.elections.filter((e) => e.status === "active"); },
        completedElections() { return this.elections.filter((e) => e.status !== "active").length; },
        recentElections() { return [...this.elections].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 4); },
        participationRate() { return this.stats.total_voters ? Math.min(100, Math.round((this.stats.total_votes / this.stats.total_voters) * 100)) : 0; },
        votesPerElection() { return this.stats.total_elections ? Math.round(this.stats.total_votes / this.stats.total_elections) : 0; },

        availablePollOrgs() {
            const seen = new Map();
            this.electionPolls.forEach((poll) => {
                if (poll.organization && !seen.has(poll.organization.id)) {
                    seen.set(poll.organization.id, poll.organization);
                }
            });
            return Array.from(seen.values());
        },

        filteredPolls() {
            if (this.selectedOrgFilter === "all") return this.electionPolls;
            if (this.selectedOrgFilter === "general") return this.electionPolls.filter((p) => !p.organization);
            return this.electionPolls.filter((p) => p.organization && p.organization.id === this.selectedOrgFilter);
        },
    },
    async mounted() { await this.loadDashboard(); },
    methods: {
        async loadDashboard() {
            try {
                const dashboardResponse = await adminAPI.getDashboard();
                if (dashboardResponse.data) {
                    if (dashboardResponse.data.stats) {
                        this.stats = { ...this.stats, ...dashboardResponse.data.stats };
                    }
                    if (dashboardResponse.data.organization_stats) {
                        this.organizationStats = dashboardResponse.data.organization_stats;
                    }
                    if (dashboardResponse.data.election_polls) {
                        this.electionPolls = dashboardResponse.data.election_polls;
                    }
                }

                const electionsResponse = await this.electionStore.loadAdminElections();
                const elections = electionsResponse.elections || [];
                this.stats.total_elections = elections.length;
                this.stats.active_elections = elections.filter((e) => e.status === "active").length;
                this.stats.total_votes = elections.reduce((t, e) => t + (e.votes_count || 0), 0);
            } catch (error) {
                console.error("Error loading dashboard:", error);
            }
        },
        formatDate(date) {
            if (!date) return "recently";
            return new Date(date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
        },
        getInitials(name) {
            if (!name) return "?";
            return name.split(" ").map((n) => n[0]).slice(0, 2).join("").toUpperCase();
        },
        getRankLabel(rank) {
            if (rank === 1) return "1st";
            if (rank === 2) return "2nd";
            if (rank === 3) return "3rd";
            return `${rank}th`;
        },
    },
};
</script>

<style scoped>
/* ─── Base ─────────────────────────────────────────────── */
.admin-dashboard { color: #17231d; }
.dashboard-heading { display: flex; justify-content: space-between; gap: 24px; align-items: end; margin-bottom: 30px; }
.eyebrow, .panel-kicker { margin: 0 0 7px; color: #146c3a; font-size: 11px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
h1, h2, h3, p { margin-top: 0; } h1 { margin-bottom: 8px; font-size: 34px; line-height: 1.1; letter-spacing: 0; } .heading-copy { max-width: 590px; margin-bottom: 0; color: #68756d; font-size: 15px; line-height: 1.55; }
.heading-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.refresh-button, .create-button { min-height: 40px; border-radius: 6px; padding: 0 14px; font: inherit; font-size: 13px; font-weight: 750; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
.refresh-button { border: 1px solid #cdd8cf; background: #fff; color: #294033; }
.refresh-button:disabled { cursor: wait; opacity: .6; }
.create-button { background: #146c3a; border: 1px solid #146c3a; color: #fff; }

/* ─── Metric Cards ─────────────────────────────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
.metric-card { min-height: 156px; padding: 20px; border: 1px solid #dce5de; border-radius: 8px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; }
.metric-card p, .metric-card span { color: #68756d; font-size: 13px; font-weight: 650; }
.metric-card p { margin-bottom: 10px; }
.metric-card strong { font-size: 35px; line-height: 1; letter-spacing: 0; }
.metric-green { border-top: 4px solid #146c3a; } .metric-blue { border-top: 4px solid #2d6a94; } .metric-gold { border-top: 4px solid #b7791f; } .metric-ink { border-top: 4px solid #17231d; }

/* ─── Polls Section ────────────────────────────────────── */
.polls-section { margin-bottom: 28px; }

.polls-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.polls-title-group { flex: 1; min-width: 0; }
.polls-heading { font-size: 22px; font-weight: 800; margin: 6px 0 4px; letter-spacing: 0; color: #17231d; }
.polls-subtext { margin: 0; font-size: 13px; color: #68756d; }

.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #0f4024;
    color: #6ee7a0;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .09em;
    padding: 5px 12px;
    border-radius: 999px;
}
.pulse-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.5);
    animation: pulse 1.8s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.5); }
    70% { box-shadow: 0 0 0 8px rgba(74, 222, 128, 0); }
    100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
}

/* Org filter */
.polls-controls { display: flex; flex-direction: column; gap: 5px; }
.filter-label { font-size: 12px; font-weight: 700; color: #68756d; }
.org-filter-select {
    height: 38px; padding: 0 12px; border: 1px solid #cdd8cf; border-radius: 7px;
    background: #fff; color: #17231d; font: inherit; font-size: 13px; font-weight: 650; cursor: pointer;
    min-width: 240px;
}
.org-filter-select:focus { outline: none; border-color: #146c3a; box-shadow: 0 0 0 3px rgba(20,108,58,.12); }

/* Loading */
.polls-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; padding: 60px 20px; color: #68756d; font-size: 14px; }
.spinner { width: 32px; height: 32px; border: 3px solid #dce5de; border-top-color: #146c3a; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty */
.polls-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 60px 20px; text-align: center; }
.polls-empty-icon { font-size: 48px; line-height: 1; }
.polls-empty h3 { margin: 0; font-size: 20px; color: #17231d; }
.polls-empty p { margin: 0; font-size: 14px; color: #68756d; }

/* Polls grid */
.polls-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(480px, 1fr)); gap: 18px; }

/* Poll card */
.poll-card {
    border: 1px solid #dce5de;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.poll-card:hover { box-shadow: 0 6px 28px rgba(20,108,58,.09); transform: translateY(-2px); }

.poll-card-header {
    padding: 20px 22px 16px;
    border-bottom: 1px solid #edf1ed;
    background: linear-gradient(135deg, #f7fbf8 0%, #fff 100%);
}
.poll-card-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
.org-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #e3f3e7; color: #0e5930; font-size: 12px; font-weight: 700;
    padding: 4px 11px; border-radius: 999px;
}
.poll-live-chip {
    background: #fff0f0; color: #c0392b; font-size: 11px; font-weight: 800;
    padding: 3px 10px; border-radius: 999px; letter-spacing: .04em;
    animation: fade-live 2s ease-in-out infinite;
}
@keyframes fade-live { 0%,100% { opacity: 1; } 50% { opacity: 0.55; } }
.poll-title { font-size: 18px; font-weight: 800; margin: 0 0 5px; color: #17231d; }
.poll-description { font-size: 13px; color: #68756d; margin: 0 0 12px; line-height: 1.45; }
.poll-stats-row { display: flex; gap: 10px; flex-wrap: wrap; }
.stat-chip { background: #f4f7f5; color: #43544b; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; }

/* Positions */
.positions-list { padding: 0 22px 8px; }
.position-block { padding: 16px 0; border-bottom: 1px solid #f0f4f1; }
.position-block:last-child { border-bottom: none; }
.position-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.position-title { font-size: 13px; font-weight: 800; color: #146c3a; text-transform: uppercase; letter-spacing: .06em; }
.position-votes { font-size: 12px; font-weight: 700; color: #68756d; background: #f4f7f5; padding: 3px 9px; border-radius: 6px; }
.no-candidates { font-size: 13px; color: #9eada6; padding: 8px 0; }

/* Candidates */
.candidates-list { display: grid; gap: 10px; }
.candidate-row {
    display: grid;
    grid-template-columns: 40px 42px 1fr auto;
    grid-template-rows: auto auto;
    column-gap: 12px;
    row-gap: 5px;
    align-items: center;
    padding: 12px 14px;
    border-radius: 8px;
    background: #fafcfa;
    border: 1px solid #edf1ed;
    transition: background 0.15s ease;
}
.candidate-row:hover { background: #f3f8f4; }
.candidate-leading { background: linear-gradient(135deg, #f0faf4 0%, #e8f7ee 100%); border-color: #b2dfc2; }

/* Rank badge */
.rank-badge {
    grid-column: 1; grid-row: 1;
    align-self: center;
    display: inline-flex; justify-content: center; align-items: center;
    font-size: 10px; font-weight: 900; min-width: 34px; height: 22px;
    border-radius: 5px; text-align: center;
}
.rank-1 { background: #fef9c3; color: #92400e; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #fef3e2; color: #9a3412; }
.rank-badge:not(.rank-1):not(.rank-2):not(.rank-3) { background: #f4f7f5; color: #68756d; }

/* Avatar */
.candidate-avatar {
    grid-column: 2; grid-row: 1;
    width: 40px; height: 40px; border-radius: 50%; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    background: #e3f3e7; flex-shrink: 0;
}
.candidate-photo { width: 100%; height: 100%; object-fit: cover; }
.candidate-initials { font-size: 14px; font-weight: 800; color: #146c3a; }

/* Info */
.candidate-info { grid-column: 3; grid-row: 1; min-width: 0; }
.candidate-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.candidate-name { font-size: 14px; font-weight: 750; color: #17231d; }
.leader-badge { font-size: 11px; font-weight: 800; background: #fef9c3; color: #92400e; padding: 2px 8px; border-radius: 999px; }
.candidate-desc { font-size: 12px; color: #68756d; display: block; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }

/* Vote count */
.candidate-result { grid-column: 4; grid-row: 1; text-align: right; white-space: nowrap; }
.vote-count { display: block; font-size: 16px; font-weight: 900; color: #17231d; line-height: 1.2; }
.vote-pct { display: block; font-size: 12px; font-weight: 700; color: #68756d; }

/* Progress bar */
.progress-wrap {
    grid-column: 2 / -1; grid-row: 2;
    height: 6px; background: #e8eeea; border-radius: 999px; overflow: hidden;
}
.progress-fill {
    height: 100%; border-radius: inherit;
    background: linear-gradient(90deg, #5cb483, #146c3a);
    transition: width 0.6s cubic-bezier(.4,0,.2,1);
    min-width: 2px;
}
.progress-leading { background: linear-gradient(90deg, #34d399, #059669); }

/* Card footer */
.poll-card-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 14px;
    padding: 14px 22px;
    border-top: 1px solid #edf1ed;
    background: #fafcfa;
}
.poll-manage-link, .poll-results-link {
    font-size: 13px; font-weight: 750; text-decoration: none; color: #146c3a;
    transition: color 0.15s ease;
}
.poll-manage-link:hover, .poll-results-link:hover { color: #0e4f2b; }
.poll-results-link { color: #68756d; }
.poll-results-link:hover { color: #294033; }

/* ─── Org Stats Table ─────────────────────────────────── */
.org-stats-section { margin-bottom: 24px; }
.org-table-panel { margin-top: 20px; }
.badge-count { font-size: 12px; font-weight: 700; color: #146c3a; background: #e3f3e7; padding: 4px 10px; border-radius: 999px; }
.table-responsive { width: 100%; overflow-x: auto; }
.org-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.org-table th { padding: 12px 14px; text-align: left; font-weight: 700; color: #43544b; border-bottom: 2px solid #edf1ed; background: #fafcfa; }
.org-table td { padding: 12px 14px; border-bottom: 1px solid #edf1ed; color: #17231d; }
.org-table tbody tr:hover { background: #fbfdfb; }
.org-name-cell { display: flex; align-items: center; gap: 8px; }
.org-code-pill { background: #e8eeea; color: #294033; font-size: 11px; font-weight: 750; padding: 2px 7px; border-radius: 4px; }
.text-center { text-align: center; }
.font-bold { font-weight: 750; }
.text-voted { color: #10b981; }
.text-not-voted { color: #f59e0b; }
.table-progress { display: flex; align-items: center; gap: 10px; min-width: 140px; }
.table-progress-bar { height: 7px; background: #146c3a; border-radius: 999px; transition: width 0.3s ease; }
.table-progress-text { font-size: 12px; font-weight: 700; color: #146c3a; }
.badge-male { display: inline-block; padding: 2px 7px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 12px; }
.badge-female { display: inline-block; padding: 2px 7px; border-radius: 4px; background: #fdf2f8; color: #be185d; font-weight: 700; font-size: 12px; }
.badge-other { display: inline-block; padding: 2px 7px; border-radius: 4px; background: #f5f3ff; color: #6d28d9; font-weight: 700; font-size: 12px; }

/* ─── Bottom Dashboard Grid ───────────────────────────── */
.dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(300px, .9fr); gap: 18px; }
.panel { min-width: 0; padding: 22px; border: 1px solid #dce5de; border-radius: 8px; background: #fff; }
.panel-heading { display: flex; align-items: start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.panel-heading h2 { margin-bottom: 0; font-size: 18px; letter-spacing: 0; }
.panel-link { color: #146c3a; font-size: 13px; font-weight: 750; text-decoration: none; white-space: nowrap; }
.election-list, .recent-list { display: grid; gap: 3px; }
.election-row, .recent-row { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-top: 1px solid #edf1ed; }
.election-status { width: 9px; height: 9px; border-radius: 50%; background: #4f9d69; box-shadow: 0 0 0 4px #e5f3e8; }
.election-details, .recent-row > div { flex: 1; min-width: 0; }
.election-details h3, .recent-row h3 { margin-bottom: 4px; font-size: 14px; }
.election-details p, .recent-row p { margin-bottom: 0; color: #68756d; font-size: 12px; }
.row-action { border: 1px solid #cdd8cf; border-radius: 5px; padding: 7px 10px; color: #294033; font-size: 12px; font-weight: 700; text-decoration: none; }
.empty-operations, .panel-loading { padding: 26px 0 6px; color: #68756d; font-size: 14px; }
.empty-operations p { margin-bottom: 8px; }
.empty-operations a { color: #146c3a; font-weight: 750; }
.percentage { color: #146c3a; font-size: 28px; font-weight: 800; line-height: 1; }
.progress-track { height: 9px; overflow: hidden; border-radius: 999px; background: #e8eeea; }
.progress-value { height: 100%; border-radius: inherit; background: #146c3a; transition: width .3s ease; }
.snapshot-list { margin: 22px 0 0; display: grid; gap: 12px; }
.snapshot-list div { display: flex; justify-content: space-between; color: #68756d; font-size: 13px; }
.snapshot-list dd { margin: 0; color: #17231d; font-weight: 800; }
.action-panel { display: grid; align-content: start; }
.task-link { padding: 14px 0; border-top: 1px solid #edf1ed; color: #17231d; text-decoration: none; }
.task-link span, .task-link small { display: block; }
.task-link span { margin-bottom: 4px; font-size: 14px; font-weight: 750; }
.task-link small { color: #68756d; font-size: 12px; }
.task-link:hover span { color: #146c3a; }
.status-pill { padding: 5px 8px; border-radius: 999px; font-size: 11px; font-weight: 750; text-transform: capitalize; }
.status-active { background: #e3f3e7; color: #146c3a; }
.status-ended, .status-closed { background: #eef0ee; color: #59665e; }
.status-draft { background: #fff2d6; color: #93620d; }

/* ─── Responsive ──────────────────────────────────────── */
@media (max-width: 1120px) {
    .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .polls-grid { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
    .dashboard-heading { align-items: start; flex-direction: column; }
    .dashboard-grid { grid-template-columns: 1fr; }
    .polls-section-header { flex-direction: column; align-items: flex-start; }
    .heading-actions { width: 100%; }
    .heading-actions > * { flex: 1; }
    h1 { font-size: 29px; }
    .org-filter-select { min-width: 100%; }
    .candidate-row { grid-template-columns: 36px 38px 1fr auto; }
}
@media (max-width: 460px) {
    .metrics-grid { grid-template-columns: 1fr; }
    .panel, .metric-card { padding: 17px; }
    .recent-row { align-items: start; flex-direction: column; gap: 8px; }
    .polls-grid { grid-template-columns: 1fr; }
}
</style>
