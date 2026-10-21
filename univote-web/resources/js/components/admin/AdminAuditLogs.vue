<template>
    <div class="audit-logs-page">
        <!-- Summary Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-total">
                    <svg class="svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ summary.total || 0 }}</span>
                    <span class="stat-label">Total Audit Events</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-mobile">
                    <svg class="svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ summary.mobile || 0 }}</span>
                    <span class="stat-label">Mobile App Events</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-web">
                    <svg class="svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ summary.web || 0 }}</span>
                    <span class="stat-label">Web Browser Events</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-vote">
                    <svg class="svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ summary.votes_cast || 0 }}</span>
                    <span class="stat-label">Votes Cast (VOTE_CAST)</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card filter-card">
            <div class="filter-grid">
                <!-- Search -->
                <div class="filter-item filter-search">
                    <label>Search Logs</label>
                    <div class="search-input-wrap">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input
                            type="text"
                            v-model="filters.search"
                            @input="debounceFetch"
                            placeholder="Search actor, action, model, IP..."
                            class="form-control with-icon"
                        />
                    </div>
                </div>

                <!-- Organization -->
                <div class="filter-item">
                    <label>Organization</label>
                    <select v-model="filters.organization_id" @change="fetchLogs(1)" class="form-control">
                        <option value="">All Organizations</option>
                        <option v-for="org in organizations" :key="org.id" :value="org.id">
                            {{ org.name }}
                        </option>
                    </select>
                </div>

                <!-- Action Type -->
                <div class="filter-item">
                    <label>Action Type</label>
                    <select v-model="filters.action" @change="fetchLogs(1)" class="form-control">
                        <option value="">All Actions</option>
                        <option v-for="actionOpt in availableActions" :key="actionOpt" :value="actionOpt">
                            {{ actionOpt }}
                        </option>
                    </select>
                </div>

                <!-- Platform -->
                <div class="filter-item">
                    <label>Platform</label>
                    <select v-model="filters.platform" @change="fetchLogs(1)" class="form-control">
                        <option value="">All Platforms</option>
                        <option value="Mobile App">Mobile App</option>
                        <option value="Web Browser">Web Browser</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div class="filter-item filter-sort">
                    <label>Sort By</label>
                    <div class="sort-control-wrapper">
                        <select v-model="filters.sort_by" @change="fetchLogs(1)" class="form-control sort-select">
                            <option value="created_at">Timestamp</option>
                            <option value="organization">Organization</option>
                            <option value="action">Action</option>
                            <option value="platform">Platform</option>
                            <option value="id">ID</option>
                        </select>
                        <button
                            type="button"
                            class="btn-sort-toggle"
                            @click="toggleSortOrder"
                            :title="filters.sort_order === 'asc' ? 'Click for Descending' : 'Click for Ascending'"
                        >
                            {{ filters.sort_order === 'asc' ? '▲ ASC' : '▼ DESC' }}
                        </button>
                    </div>
                </div>

                <!-- Start Date -->
                <div class="filter-item">
                    <label>From Date</label>
                    <input type="date" v-model="filters.start_date" @change="fetchLogs(1)" class="form-control" />
                </div>

                <!-- End Date -->
                <div class="filter-item">
                    <label>To Date</label>
                    <input type="date" v-model="filters.end_date" @change="fetchLogs(1)" class="form-control" />
                </div>

                <!-- Action Buttons -->
                <div class="filter-item filter-actions">
                    <label class="invisible-label">&nbsp;</label>
                    <div class="btn-group">
                        <button @click="resetFilters" class="btn btn-secondary">Reset</button>
                        <button @click="fetchLogs(1)" class="btn btn-primary">Refresh</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table Card -->
        <div class="card table-card">
            <div class="card-header">
                <div class="header-left">
                    <h3>Centralized Audit Logs</h3>
                    <span class="showing-text" v-if="pagination.total">
                        Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} records
                    </span>
                </div>
                <div class="header-actions">
                    <button
                        v-if="selectedLogIds.length > 0"
                        @click="confirmDeleteSelected"
                        class="btn btn-danger-subtle"
                        :disabled="deleting"
                        title="Delete selected audit logs"
                    >
                        <svg class="btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Delete Selected ({{ selectedLogIds.length }})
                    </button>
                    <button
                        @click="openGroupDeleteModal"
                        class="btn btn-warning-subtle"
                        :disabled="deleting"
                        title="Delete audit logs by organization, action, or date range"
                    >
                        <svg class="btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"></path>
                        </svg>
                        Delete by Group
                    </button>
                    <button
                        @click="confirmDeleteAll"
                        class="btn btn-danger"
                        :disabled="deleting || pagination.total === 0"
                        title="Permanently wipe all audit logs"
                    >
                        <svg class="btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        Delete All
                    </button>
                </div>
            </div>

            <!-- Action Status Alert -->
            <div v-if="actionNotice" :class="['action-notice', actionNotice.type]">
                <span>{{ actionNotice.message }}</span>
                <button @click="actionNotice = null" class="notice-close">&times;</button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading audit log entries...</p>
            </div>

            <div v-else-if="logs.length === 0" class="empty-state">
                <div class="empty-icon-wrapper">
                    <svg class="svg-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="3"/>
                        <line x1="14.2" y1="15.2" x2="16.5" y2="17.5"/>
                    </svg>
                </div>
                <p>No audit log records found matching the criteria.</p>
            </div>

            <div v-else class="table-wrapper">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    title="Select all on this page"
                                />
                            </th>
                            <th class="col-id">ID</th>
                            <th class="col-time">Timestamp</th>
                            <th class="col-org">Organization</th>
                            <th class="col-actor">Actor / User</th>
                            <th class="col-action">Action</th>
                            <th class="col-model">Model / Entity</th>
                            <th class="col-platform">Platform</th>
                            <th class="col-ip">IP Address</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs" :key="log.id" :class="{ 'row-selected': selectedLogIds.includes(log.id) }">
                            <td class="col-check">
                                <input
                                    type="checkbox"
                                    :value="log.id"
                                    v-model="selectedLogIds"
                                />
                            </td>
                            <td class="col-id">#{{ log.id }}</td>
                            <td class="col-time">
                                <div class="time-cell">
                                    <span class="time-date">{{ formatDateDate(log.created_at) }}</span>
                                    <span class="time-clock">{{ formatDateTime(log.created_at) }}</span>
                                </div>
                            </td>
                            <td class="col-org">
                                <span class="org-badge" v-if="log.organization" :title="log.organization.name">
                                    {{ log.organization.code || log.organization.name }}
                                </span>
                                <span class="muted-text" v-else>General</span>
                            </td>
                            <td class="col-actor">
                                <div class="actor-cell">
                                    <span class="actor-name">{{ log.actor_name || 'System / Guest' }}</span>
                                    <span class="actor-type" v-if="log.user_type">{{ formatUserType(log.user_type) }}</span>
                                </div>
                            </td>
                            <td class="col-action">
                                <span :class="['badge', getActionBadgeClass(log.action)]">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="col-model">
                                <span class="model-text" v-if="log.model_type">
                                    {{ formatModelName(log.model_type) }}
                                    <strong v-if="log.model_id">#{{ log.model_id }}</strong>
                                </span>
                                <span class="muted-text" v-else>—</span>
                            </td>
                            <td class="col-platform">
                                <span :class="['platform-badge', log.platform === 'Mobile App' ? 'platform-mobile' : 'platform-web']">
                                    <span class="platform-icon">
                                        <svg v-if="log.platform === 'Mobile App'" class="badge-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                                        </svg>
                                        <svg v-else class="badge-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="2" y1="12" x2="22" y2="12"/>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                    </span>
                                    {{ log.platform || 'Unknown' }}
                                </span>
                            </td>
                            <td class="col-ip"><code>{{ log.ip_address || 'N/A' }}</code></td>
                            <td class="col-actions">
                                <div class="action-btn-group">
                                    <button @click="openDetailModal(log)" class="btn-inspect" title="Inspect log details">
                                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Inspect</span>
                                    </button>
                                    <button @click="confirmDeleteSingle(log)" class="btn-row-delete" title="Delete this audit log entry">
                                        <svg class="action-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-bar" v-if="pagination.last_page > 1">
                <button
                    :disabled="pagination.current_page === 1"
                    @click="fetchLogs(pagination.current_page - 1)"
                    class="btn-page"
                >
                    &laquo; Previous
                </button>

                <span class="page-info">
                    Page <strong>{{ pagination.current_page }}</strong> of <strong>{{ pagination.last_page }}</strong>
                </span>

                <button
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="fetchLogs(pagination.current_page + 1)"
                    class="btn-page"
                >
                    Next &raquo;
                </button>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="selectedLog" class="modal-backdrop" @click.self="closeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h4>Audit Log Details #{{ selectedLog.id }}</h4>
                    <button @click="closeModal" class="close-btn">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <label>Action:</label>
                            <span :class="['badge', getActionBadgeClass(selectedLog.action)]">{{ selectedLog.action }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Organization:</label>
                            <span>{{ selectedLog.organization ? selectedLog.organization.name : 'General / System' }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Platform:</label>
                            <span class="flex-align">
                                <svg v-if="selectedLog.platform === 'Mobile App'" class="inline-meta-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                                </svg>
                                <svg v-else class="inline-meta-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="2" y1="12" x2="22" y2="12"/>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                                {{ selectedLog.platform || 'Unknown' }}
                            </span>
                        </div>
                        <div class="meta-item">
                            <label>Actor:</label>
                            <span>{{ selectedLog.actor_name || 'N/A' }}</span>
                        </div>
                        <div class="meta-item">
                            <label>IP Address:</label>
                            <span><code>{{ selectedLog.ip_address || 'N/A' }}</code></span>
                        </div>
                        <div class="meta-item">
                            <label>Model Affected:</label>
                            <span>{{ selectedLog.model_type || 'N/A' }} (ID: {{ selectedLog.model_id || 'N/A' }})</span>
                        </div>
                        <div class="meta-item">
                            <label>Timestamp:</label>
                            <span>{{ formatDate(selectedLog.created_at) }}</span>
                        </div>
                    </div>

                    <div class="user-agent-section" v-if="selectedLog.user_agent">
                        <label>User Agent Header:</label>
                        <div class="ua-box"><code>{{ selectedLog.user_agent }}</code></div>
                    </div>

                    <div class="diff-section">
                        <div class="diff-box" v-if="selectedLog.old_values">
                            <h5 class="diff-title old-title">State Before Change (old_values)</h5>
                            <pre class="json-code">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                        </div>
                        <div class="diff-box" v-if="selectedLog.new_values">
                            <h5 class="diff-title new-title">State After Change (new_values)</h5>
                            <pre class="json-code">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                        </div>
                        <div class="diff-box full-width" v-if="!selectedLog.old_values && !selectedLog.new_values">
                            <p class="muted-text">No JSON diff payload recorded for this event.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closeModal" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>

        <!-- Delete by Group Modal -->
        <div v-if="showGroupModal" class="modal-backdrop" @click.self="showGroupModal = false">
            <div class="modal-card modal-group-delete">
                <div class="modal-header">
                    <h4>Delete Audit Logs by Group</h4>
                    <button @click="showGroupModal = false" class="close-btn">&times;</button>
                </div>

                <div class="modal-body">
                    <p class="modal-description">
                        Select one or more group criteria below to delete all matching audit logs in bulk.
                    </p>

                    <div class="group-form-grid">
                        <div class="form-item">
                            <label>By Organization</label>
                            <select v-model="groupForm.organization_id" class="form-control">
                                <option value="">Any / Not Specified</option>
                                <option v-for="org in organizations" :key="org.id" :value="org.id">
                                    {{ org.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label>By Action Type</label>
                            <select v-model="groupForm.action" class="form-control">
                                <option value="">Any Action</option>
                                <option v-for="actionOpt in availableActions" :key="actionOpt" :value="actionOpt">
                                    {{ actionOpt }}
                                </option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label>By Platform</label>
                            <select v-model="groupForm.platform" class="form-control">
                                <option value="">Any Platform</option>
                                <option value="Mobile App">Mobile App</option>
                                <option value="Web Browser">Web Browser</option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label>From Date</label>
                            <input type="date" v-model="groupForm.start_date" class="form-control" />
                        </div>

                        <div class="form-item">
                            <label>To Date</label>
                            <input type="date" v-model="groupForm.end_date" class="form-control" />
                        </div>
                    </div>

                    <div class="group-warning-box">
                        <svg class="warning-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <div>
                            <strong>Warning:</strong> All audit records matching the specified criteria will be permanently deleted. This action cannot be reversed.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="showGroupModal = false" class="btn btn-secondary" :disabled="deleting">Cancel</button>
                    <button @click="executeGroupDelete" class="btn btn-danger" :disabled="deleting || !hasGroupCriteria">
                        {{ deleting ? 'Deleting...' : 'Delete Matching Group Logs' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "AdminAuditLogs",
    data() {
        return {
            logs: [],
            organizations: [],
            summary: {
                total: 0,
                mobile: 0,
                web: 0,
                votes_cast: 0,
            },
            availableActions: [],
            pagination: {
                current_page: 1,
                last_page: 1,
                from: 0,
                to: 0,
                total: 0,
            },
            filters: {
                search: "",
                organization_id: "",
                platform: "",
                action: "",
                start_date: "",
                end_date: "",
                sort_by: "created_at",
                sort_order: "desc",
            },
            selectedLogIds: [],
            showGroupModal: false,
            groupForm: {
                organization_id: "",
                action: "",
                platform: "",
                start_date: "",
                end_date: "",
            },
            deleting: false,
            actionNotice: null,
            loading: false,
            selectedLog: null,
            searchTimer: null,
        };
    },
    computed: {
        isAllSelected() {
            if (!this.logs.length) return false;
            return this.logs.every((log) => this.selectedLogIds.includes(log.id));
        },
        hasGroupCriteria() {
            return (
                !!this.groupForm.organization_id ||
                !!this.groupForm.action ||
                !!this.groupForm.platform ||
                !!this.groupForm.start_date ||
                !!this.groupForm.end_date
            );
        },
    },
    mounted() {
        this.fetchLogs();
    },
    methods: {
        async fetchLogs(page = 1) {
            this.loading = true;
            try {
                const response = await axios.get("/admin/audit-logs", {
                    params: {
                        page: page,
                        search: this.filters.search,
                        organization_id: this.filters.organization_id,
                        platform: this.filters.platform,
                        action: this.filters.action,
                        start_date: this.filters.start_date,
                        end_date: this.filters.end_date,
                        sort_by: this.filters.sort_by,
                        sort_order: this.filters.sort_order,
                    },
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (response.data) {
                    this.logs = response.data.logs.data || [];
                    this.pagination = {
                        current_page: response.data.logs.current_page || 1,
                        last_page: response.data.logs.last_page || 1,
                        from: response.data.logs.from || 0,
                        to: response.data.logs.to || 0,
                        total: response.data.logs.total || 0,
                    };
                    if (response.data.summary) {
                        this.summary = response.data.summary;
                    }
                    if (response.data.available_actions) {
                        this.availableActions = response.data.available_actions;
                    }
                    if (response.data.organizations) {
                        this.organizations = response.data.organizations;
                    }
                    this.selectedLogIds = [];
                }
            } catch (err) {
                console.error("Failed to load audit logs:", err);
            } finally {
                this.loading = false;
            }
        },
        debounceFetch() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.fetchLogs(1);
            }, 350);
        },
        toggleSortOrder() {
            this.filters.sort_order = this.filters.sort_order === "asc" ? "desc" : "asc";
            this.fetchLogs(1);
        },
        toggleSelectAll() {
            if (this.isAllSelected) {
                const pageIds = this.logs.map((l) => l.id);
                this.selectedLogIds = this.selectedLogIds.filter((id) => !pageIds.includes(id));
            } else {
                const pageIds = this.logs.map((l) => l.id);
                const union = new Set([...this.selectedLogIds, ...pageIds]);
                this.selectedLogIds = Array.from(union);
            }
        },
        resetFilters() {
            this.filters = {
                search: "",
                organization_id: "",
                platform: "",
                action: "",
                start_date: "",
                end_date: "",
                sort_by: "created_at",
                sort_order: "desc",
            };
            this.fetchLogs(1);
        },
        openDetailModal(log) {
            this.selectedLog = log;
        },
        closeModal() {
            this.selectedLog = null;
        },
        openGroupDeleteModal() {
            this.groupForm = {
                organization_id: this.filters.organization_id || "",
                action: this.filters.action || "",
                platform: this.filters.platform || "",
                start_date: this.filters.start_date || "",
                end_date: this.filters.end_date || "",
            };
            this.showGroupModal = true;
        },
        async confirmDeleteSingle(log) {
            const orgText = log.organization ? ` (${log.organization.name})` : "";
            if (!confirm(`Are you sure you want to delete audit log entry #${log.id} [${log.action}]${orgText}? This cannot be undone.`)) {
                return;
            }
            this.deleting = true;
            try {
                const response = await axios.delete(`/admin/audit-logs/${log.id}`);
                this.setNotice(response.data.message || `Audit log #${log.id} deleted successfully.`, "success");
                await this.fetchLogs(this.pagination.current_page);
            } catch (err) {
                console.error("Delete failed:", err);
                this.setNotice(err.response?.data?.message || "Failed to delete audit log.", "error");
            } finally {
                this.deleting = false;
            }
        },
        async confirmDeleteSelected() {
            const count = this.selectedLogIds.length;
            if (!count) return;
            if (!confirm(`Are you sure you want to delete ${count} selected audit log record(s)? This cannot be undone.`)) {
                return;
            }
            this.deleting = true;
            try {
                const response = await axios.post("/admin/audit-logs/delete-group", {
                    ids: this.selectedLogIds,
                });
                this.setNotice(response.data.message || `${count} audit logs deleted.`, "success");
                this.selectedLogIds = [];
                await this.fetchLogs(1);
            } catch (err) {
                console.error("Group delete failed:", err);
                this.setNotice(err.response?.data?.message || "Failed to delete selected audit logs.", "error");
            } finally {
                this.deleting = false;
            }
        },
        async executeGroupDelete() {
            if (!this.hasGroupCriteria) return;
            if (!confirm("Are you sure you want to delete all audit logs matching the chosen group criteria? This cannot be undone.")) {
                return;
            }
            this.deleting = true;
            try {
                const response = await axios.post("/admin/audit-logs/delete-group", this.groupForm);
                this.setNotice(response.data.message || "Group logs deleted successfully.", "success");
                this.showGroupModal = false;
                await this.fetchLogs(1);
            } catch (err) {
                console.error("Group delete failed:", err);
                this.setNotice(err.response?.data?.message || "Failed to delete group logs.", "error");
            } finally {
                this.deleting = false;
            }
        },
        async confirmDeleteAll() {
            const confirmed = prompt(
                "WARNING: This will permanently delete ALL audit log events in the entire system.\n\nType DELETE to confirm:"
            );
            if (confirmed !== "DELETE") {
                if (confirmed !== null) {
                    alert("Confirmation text did not match. Deletion cancelled.");
                }
                return;
            }
            this.deleting = true;
            try {
                const response = await axios.delete("/admin/audit-logs/delete-all");
                this.setNotice(response.data.message || "All audit logs cleared successfully.", "success");
                this.selectedLogIds = [];
                await this.fetchLogs(1);
            } catch (err) {
                console.error("Delete all failed:", err);
                this.setNotice(err.response?.data?.message || "Failed to clear all audit logs.", "error");
            } finally {
                this.deleting = false;
            }
        },
        setNotice(message, type = "success") {
            this.actionNotice = { message, type };
            setTimeout(() => {
                if (this.actionNotice && this.actionNotice.message === message) {
                    this.actionNotice = null;
                }
            }, 5000);
        },
        formatDate(dateStr) {
            if (!dateStr) return "N/A";
            const date = new Date(dateStr);
            return date.toLocaleString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
        },
        formatDateDate(dateStr) {
            if (!dateStr) return "N/A";
            const date = new Date(dateStr);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        },
        formatDateTime(dateStr) {
            if (!dateStr) return "";
            const date = new Date(dateStr);
            return date.toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
        },
        formatModelName(modelPath) {
            if (!modelPath) return "N/A";
            const parts = modelPath.split("\\");
            return parts[parts.length - 1];
        },
        formatUserType(userType) {
            if (!userType) return "";
            const parts = userType.split("\\");
            return parts[parts.length - 1];
        },
        getActionBadgeClass(action) {
            if (!action) return "badge-gray";
            if (action === "VOTE_CAST") return "badge-vote";
            if (action.startsWith("CREATE_")) return "badge-green";
            if (action.startsWith("UPDATE_")) return "badge-blue";
            if (action.startsWith("DELETE_")) return "badge-red";
            if (action === "LOGIN_SUCCESS") return "badge-green";
            if (action === "LOGIN_FAILED") return "badge-orange";
            return "badge-gray";
        },
    },
};
</script>

<style scoped>
.audit-logs-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
}

/* SVG Vector Icons */
.svg-icon {
    width: 22px;
    height: 22px;
}

.svg-icon-lg {
    width: 36px;
    height: 36px;
    color: #68756d;
}

.badge-svg {
    width: 14px;
    height: 14px;
    display: inline-block;
    vertical-align: middle;
}

.inline-meta-svg {
    width: 16px;
    height: 16px;
    margin-right: 4px;
}

.flex-align {
    display: inline-flex;
    align-items: center;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #dce5de;
    border-radius: 8px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-total {
    background: #e7efe8;
    color: #146c3a;
}

.icon-mobile {
    background: #f3e5f5;
    color: #7b1fa2;
}

.icon-web {
    background: #e0f7fa;
    color: #00796b;
}

.icon-vote {
    background: #e3f2fd;
    color: #1565c0;
}

.stat-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #17231d;
    line-height: 1.2;
}

.stat-label {
    font-size: 13px;
    color: #68756d;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Card Base */
.card {
    background: #ffffff;
    border: 1px solid #dce5de;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    width: 100%;
    box-sizing: border-box;
}

/* Filters Layout: Grid */
.filter-card {
    overflow: visible;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    align-items: flex-end;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-search {
    grid-column: span 2;
    min-width: 280px;
}

@media (max-width: 768px) {
    .filter-search {
        grid-column: span 1;
        min-width: unset;
    }
}

.filter-sort {
    min-width: 220px;
}

.filter-actions {
    min-width: 170px;
}

.filter-item label {
    font-size: 12px;
    font-weight: 700;
    color: #17231d;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.invisible-label {
    visibility: hidden;
}

.search-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 10px;
    width: 16px;
    height: 16px;
    color: #8c9890;
    pointer-events: none;
}

.form-control {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 12px;
    border: 1px solid #c8d4cb;
    border-radius: 6px;
    font-size: 13px;
    color: #17231d;
    outline: none;
    background: #ffffff;
    transition: border-color 0.2s;
    height: 38px;
}

.form-control.with-icon {
    padding-left: 34px;
}

.form-control:focus {
    border-color: #146c3a;
}

.sort-control-wrapper {
    display: flex;
    gap: 6px;
    align-items: center;
}

.sort-select {
    flex: 1;
}

.btn-sort-toggle {
    height: 38px;
    padding: 0 12px;
    background: #f4f7f4;
    border: 1px solid #c8d4cb;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #146c3a;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-sort-toggle:hover {
    background: #e1ede3;
    border-color: #146c3a;
}

.btn-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
    box-sizing: border-box;
}

.btn-primary {
    background: #146c3a;
    color: #ffffff;
}

.btn-primary:hover {
    background: #0f522c;
}

.btn-secondary {
    background: #e7efe8;
    color: #17231d;
}

.btn-secondary:hover {
    background: #d4e2d6;
}

/* Header Actions */
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #edf2ee;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #17231d;
}

.showing-text {
    font-size: 12px;
    color: #68756d;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.btn-danger {
    background: #d32f2f;
    color: #ffffff;
}

.btn-danger:hover:not(:disabled) {
    background: #b71c1c;
}

.btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-danger-subtle {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.btn-danger-subtle:hover:not(:disabled) {
    background: #ffcdd2;
}

.btn-warning-subtle {
    background: #fff8e1;
    color: #b78103;
    border: 1px solid #ffe082;
}

.btn-warning-subtle:hover:not(:disabled) {
    background: #ffecb3;
}

/* Action Notice Alert */
.action-notice {
    padding: 10px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
}

.action-notice.success {
    background: #e8f5e9;
    color: #1b5e20;
    border: 1px solid #a5d6a7;
}

.action-notice.error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.notice-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: inherit;
    line-height: 1;
}

/* Table Card & Responsive Scrolling */
.table-card {
    padding: 18px 20px;
    box-sizing: border-box;
    overflow: hidden;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #e5ebe6;
    border-radius: 8px;
    background: #ffffff;
    position: relative;
}

.audit-table {
    width: 100%;
    min-width: 960px;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
    background: #ffffff;
}

.audit-table th {
    background: #f7faf7;
    color: #17231d;
    font-weight: 700;
    text-align: left;
    padding: 11px 12px;
    border-bottom: 2px solid #dce5de;
    white-space: nowrap;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    box-sizing: border-box;
}

.audit-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #edf2ee;
    vertical-align: middle;
    box-sizing: border-box;
}

.audit-table tbody tr:hover {
    background-color: #fbfdfb;
}

/* Column Dimensions & Content Alignment */
.col-check {
    width: 38px;
    min-width: 38px;
    max-width: 38px;
    text-align: center;
    padding-left: 8px !important;
    padding-right: 8px !important;
}

.col-check input {
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #146c3a;
    display: block;
    margin: 0 auto;
}

.col-id {
    width: 55px;
    min-width: 55px;
    white-space: nowrap;
    font-weight: 600;
    color: #68756d;
    font-size: 12px;
}

.col-time {
    width: 115px;
    min-width: 115px;
    white-space: nowrap;
}

.time-cell {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.time-date {
    font-weight: 600;
    color: #17231d;
    font-size: 12px;
    white-space: nowrap;
}

.time-clock {
    color: #68756d;
    font-size: 11px;
    white-space: nowrap;
}

.col-org {
    width: 110px;
    min-width: 100px;
    max-width: 130px;
}

.col-actor {
    width: 130px;
    min-width: 120px;
    max-width: 160px;
}

.col-action {
    width: 110px;
    min-width: 100px;
    white-space: nowrap;
}

.col-model {
    width: 105px;
    min-width: 95px;
}

.col-platform {
    width: 95px;
    min-width: 90px;
    white-space: nowrap;
}

.col-ip {
    width: 100px;
    min-width: 90px;
    white-space: nowrap;
}

.col-ip code {
    font-size: 11px;
    background: #f4f6f4;
    padding: 2px 5px;
    border-radius: 3px;
    display: inline-block;
}

/* Sticky Actions Column */
.audit-table th.col-actions,
.audit-table td.col-actions {
    position: sticky;
    right: 0;
    background: #ffffff;
    z-index: 10;
    box-shadow: -4px 0 8px rgba(0, 0, 0, 0.08);
    border-left: 1px solid #e0e6e1;
    width: 145px;
    min-width: 145px;
    text-align: right;
    white-space: nowrap;
    padding-left: 8px;
    padding-right: 14px;
}

.audit-table th.col-actions {
    background: #f7faf7;
    z-index: 12;
    border-bottom: 2px solid #dce5de;
}

.audit-table tbody tr:hover td.col-actions {
    background-color: #fbfdfb;
}

.audit-table tbody tr.row-selected td.col-actions {
    background-color: #f0f7f2 !important;
}

.row-selected {
    background-color: #f0f7f2 !important;
}

/* Organization Badge */
.org-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #eef2f6;
    color: #1e3a8a;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.actor-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.actor-name {
    font-weight: 700;
    color: #17231d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.actor-type {
    font-size: 11px;
    color: #68756d;
}

.muted-text {
    color: #8c9890;
    font-style: italic;
}

.model-text {
    color: #17231d;
    font-size: 12px;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.badge-vote {
    background: #e3f2fd;
    color: #0d47a1;
    border: 1px solid #bbdefb;
}

.badge-green {
    background: #e8f5e9;
    color: #1b5e20;
    border: 1px solid #c8e6c9;
}

.badge-blue {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

.badge-orange {
    background: #fff3e0;
    color: #e65100;
    border: 1px solid #ffe0b2;
}

.badge-red {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.badge-gray {
    background: #f5f5f5;
    color: #616161;
    border: 1px solid #e0e0e0;
}

/* Platform badge */
.platform-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.platform-mobile {
    background: #f3e5f5;
    color: #6a1b9a;
}

.platform-web {
    background: #e0f7fa;
    color: #006064;
}

/* Action Buttons inside rows */
.action-btn-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
    flex-wrap: nowrap;
    flex-shrink: 0;
}

.btn-inspect {
    background: #146c3a;
    border: 1px solid #146c3a;
    color: #ffffff;
    padding: 0 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    height: 28px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    flex-shrink: 0;
    box-shadow: 0 1px 2px rgba(20, 108, 58, 0.2);
}

.btn-inspect:hover {
    background: #0f522c;
    border-color: #0f522c;
    color: #ffffff;
}

.btn-inspect .action-icon {
    width: 13px;
    height: 13px;
    stroke: currentColor;
    flex-shrink: 0;
}

.btn-row-delete {
    background: #fff5f5;
    border: 1px solid #ffcdd2;
    color: #d32f2f;
    padding: 0 8px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    height: 28px;
    flex-shrink: 0;
}

.btn-row-delete:hover {
    background: #d32f2f;
    color: #ffffff;
    border-color: #d32f2f;
}

.action-svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

/* Pagination */
.pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid #edf2ee;
    flex-wrap: wrap;
    gap: 10px;
}

.page-info {
    font-size: 13px;
    color: #4a5750;
}

.btn-page {
    padding: 6px 14px;
    background: #ffffff;
    border: 1px solid #dce5de;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    color: #17231d;
    transition: background 0.2s;
}

.btn-page:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-page:hover:not(:disabled) {
    background: #f4f7f4;
}

/* Modal Styling */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    padding: 20px;
}

.modal-card {
    background: #ffffff;
    border-radius: 8px;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
    padding: 16px 24px;
    border-bottom: 1px solid #dce5de;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #17231d;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #68756d;
    line-height: 1;
}

.modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    background: #f8faf8;
    padding: 14px;
    border-radius: 6px;
    border: 1px solid #e2ece4;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 13px;
}

.meta-item label {
    font-size: 11px;
    font-weight: 700;
    color: #68756d;
    text-transform: uppercase;
}

.user-agent-section {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-agent-section label {
    font-size: 12px;
    font-weight: 700;
    color: #17231d;
}

.ua-box {
    background: #272822;
    color: #f8f8f2;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 12px;
    word-break: break-all;
}

.diff-section {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.diff-box {
    flex: 1;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.diff-title {
    margin: 0;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.old-title {
    color: #c62828;
}

.new-title {
    color: #1b5e20;
}

.json-code {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 12px;
    max-height: 250px;
    overflow-y: auto;
    margin: 0;
}

.modal-footer {
    padding: 14px 24px;
    border-top: 1px solid #dce5de;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.loading-state,
.empty-state {
    padding: 40px;
    text-align: center;
    color: #68756d;
}

.empty-icon-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 12px;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e7efe8;
    border-top-color: #146c3a;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Group Delete Modal */
.modal-group-delete {
    max-width: 600px;
}

.modal-description {
    font-size: 13px;
    color: #4a5750;
    margin-top: 0;
    margin-bottom: 14px;
    line-height: 1.5;
}

.group-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.form-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form-item label {
    font-size: 11px;
    font-weight: 700;
    color: #17231d;
    text-transform: uppercase;
}

.group-warning-box {
    margin-top: 14px;
    padding: 12px 14px;
    background: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 6px;
    color: #8c6200;
    font-size: 12px;
    line-height: 1.5;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.warning-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    color: #d97706;
}
</style>
