<template>
    <section class="organizations-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title-section">
                    <div class="header-icon-wrap">
                        <svg class="header-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6.75h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="page-title">Organizations</h1>
                        <p class="page-subtitle">Manage departments, course lists, and organizational units</p>
                    </div>
                </div>
                <div class="header-action-group">
                    <button class="btn-add-org" @click="toggleCreateForm" type="button">
                        <svg class="btn-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ showCreateForm ? 'Cancel' : 'New Organization' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div v-if="organizations.length > 0" class="stats-summary">
            <div class="stat-card stat-total">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6.75h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ organizations.length }}</div>
                    <div class="stat-label">Total Organizations</div>
                </div>
            </div>
            <div class="stat-card stat-voters">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ totalVoters }}</div>
                    <div class="stat-label">Total Voters</div>
                </div>
            </div>
            <div class="stat-card stat-courses">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ totalCourses }}</div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            <div class="stat-card stat-largest">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0 1 16.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.01 6.01 0 0 1-3.77 1.522m0 0a6.01 6.01 0 0 1-3.77-1.522" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ largestOrgVoters }}</div>
                    <div class="stat-label">Largest Org</div>
                </div>
            </div>
        </div>

        <!-- Create Organization Panel -->
        <transition name="slide-fade">
            <div v-if="showCreateForm" class="create-panel">
                <div class="create-panel-header">
                    <svg class="create-panel-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div>
                        <h3 class="create-panel-title">Add New Organization</h3>
                        <p class="create-panel-desc">Create a new organization that can be assigned to voters and elections.</p>
                    </div>
                </div>
                <form class="create-form" @submit.prevent="createOrganization">
                    <div class="form-group">
                        <label for="orgName" class="form-label">Organization Name</label>
                        <input id="orgName" v-model="newName" class="form-input" placeholder="e.g. College of Computer Studies" required />
                    </div>
                    <div class="form-group">
                        <label for="orgCode" class="form-label">Organization Code</label>
                        <input id="orgCode" v-model="newCode" class="form-input code-input" placeholder="e.g. CCS" maxlength="20" required />
                        <span class="form-hint">Unique short code, max 20 characters</span>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-create-submit" type="submit" :disabled="loading">
                            <svg v-if="!loading" class="btn-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span v-if="loading" class="spinner"></span>
                            {{ loading ? 'Creating...' : 'Create Organization' }}
                        </button>
                        <button class="btn btn-cancel" type="button" @click="showCreateForm = false">Cancel</button>
                    </div>
                </form>
            </div>
        </transition>

        <!-- Search & Filter Bar -->
        <div v-if="organizations.length > 0" class="filter-bar">
            <div class="filter-search">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input v-model="searchQuery" type="search" class="search-input" placeholder="Search organizations by name or code..." />
            </div>
            <div class="filter-sort">
                <select v-model="sortBy" class="sort-select">
                    <option value="name">Sort by Name</option>
                    <option value="voters_desc">Most Voters</option>
                    <option value="voters_asc">Fewest Voters</option>
                    <option value="code">Sort by Code</option>
                </select>
            </div>
            <span class="results-badge">{{ filteredOrganizations.length }} of {{ organizations.length }}</span>
        </div>

        <!-- Organization Cards Grid -->
        <div v-if="filteredOrganizations.length > 0" class="org-grid">
            <article
                v-for="organization in filteredOrganizations"
                :key="organization.id"
                class="org-card"
                :class="{ 'org-card-editing': editingId === organization.id }"
            >
                <!-- Edit Mode -->
                <div v-if="editingId === organization.id" class="org-card-edit">
                    <div class="edit-header">
                        <svg class="edit-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <span class="edit-title">Editing Organization</span>
                    </div>
                    <div class="edit-fields">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input v-model="editingName" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Code</label>
                            <input v-model="editingCode" class="form-input code-input" maxlength="20" />
                        </div>
                    </div>
                    <div class="edit-actions">
                        <button class="btn btn-save" @click="saveOrganization(organization)" type="button">
                            <svg class="btn-icon-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Save Changes
                        </button>
                        <button class="btn btn-cancel-edit" @click="editingId = null" type="button">Cancel</button>
                    </div>
                </div>

                <!-- View Mode -->
                <div v-else class="org-card-view">
                    <!-- Card Top: Avatar + Info + Actions -->
                    <div class="org-card-top">
                        <div class="org-avatar" :style="getAvatarStyle(organization)">
                            {{ getInitials(organization.code) }}
                        </div>
                        <div class="org-info">
                            <h3 class="org-name">{{ organization.name }}</h3>
                            <span class="org-code-badge">{{ organization.code }}</span>
                        </div>
                        <div class="org-top-actions">
                            <button class="btn btn-edit" @click="startEditing(organization)" type="button" title="Edit organization">
                                <svg class="btn-icon-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </button>
                            <button class="btn btn-delete" @click="removeOrganization(organization)" type="button" title="Delete organization">
                                <svg class="btn-icon-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="org-card-stats">
                        <div class="org-stat">
                            <svg class="org-stat-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            <span class="org-stat-value">{{ organization.voters_count || 0 }}</span>
                            <span class="org-stat-label">voters</span>
                        </div>
                        <div class="org-stat-divider"></div>
                        <div class="org-stat">
                            <svg class="org-stat-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                            <span class="org-stat-value">{{ (organization.courses || []).length }}</span>
                            <span class="org-stat-label">courses</span>
                        </div>
                        <div class="org-stat-divider"></div>
                        <div class="org-stat">
                            <svg class="org-stat-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            <span class="org-stat-value status-active-text">Active</span>
                        </div>
                    </div>

                    <!-- ═══ Courses Section ═══ -->
                    <div class="courses-section">
                        <div class="courses-section-header">
                            <div class="courses-section-title">
                                <svg class="courses-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                                <span>Course Lists</span>
                                <span class="courses-count-badge">{{ (organization.courses || []).length }}</span>
                            </div>
                            <button
                                class="btn-add-course"
                                type="button"
                                @click="openAddCourse(organization)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Course
                            </button>
                        </div>

                        <!-- Course List -->
                        <div v-if="(organization.courses || []).length > 0" class="course-list">
                            <div
                                v-for="course in organization.courses"
                                :key="course.id"
                                class="course-item"
                            >
                                <div class="course-item-left">
                                    <span class="course-code-pill">{{ course.code }}</span>
                                    <div class="course-item-info">
                                        <span class="course-item-name">{{ course.name }}</span>
                                        <span class="course-item-voters">{{ course.voters_count || 0 }} voters</span>
                                    </div>
                                </div>
                                <button
                                    class="btn-delete-course"
                                    type="button"
                                    @click="removeCourse(organization, course)"
                                    title="Delete course"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="courses-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:32px;height:32px;color:#ccc;margin-bottom:6px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                            <p>No courses yet. Click <strong>Add Course</strong> to create one.</p>
                        </div>
                    </div>
                    <!-- ═══ End Courses Section ═══ -->
                </div>
            </article>
        </div>

        <!-- Empty State -->
        <div v-if="filteredOrganizations.length === 0 && !initialLoading" class="empty-state-card">
            <div class="empty-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6.75h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h3 class="empty-title" v-if="searchQuery">No organizations match "{{ searchQuery }}"</h3>
            <h3 class="empty-title" v-else>No organizations yet</h3>
            <p class="empty-desc" v-if="searchQuery">Try adjusting your search terms or clear the filter.</p>
            <p class="empty-desc" v-else>Create your first organization to start assigning voters and managing elections by department.</p>
            <button v-if="!searchQuery && !showCreateForm" class="btn btn-empty-action" @click="showCreateForm = true" type="button">
                <svg class="btn-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create First Organization
            </button>
        </div>

        <!-- Initial Loading -->
        <div v-if="initialLoading" class="loading-state">
            <div class="loading-spinner-lg"></div>
            <p class="loading-text">Loading organizations...</p>
        </div>

        <!-- ═══ Add Course Modal ═══ -->
        <transition name="modal-fade">
            <div v-if="addCourseModal.show" class="modal-overlay" @click.self="closeAddCourse">
                <div class="modal-box">
                    <div class="modal-header">
                        <div class="modal-header-left">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:22px;height:22px;color:#16a34a">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                            <div>
                                <h3 class="modal-title">Add Course</h3>
                                <p class="modal-subtitle">Adding to: <strong>{{ addCourseModal.org?.name }}</strong></p>
                            </div>
                        </div>
                        <button class="modal-close" type="button" @click="closeAddCourse">✕</button>
                    </div>

                    <form @submit.prevent="submitAddCourse" class="modal-form">
                        <div class="modal-form-group">
                            <label class="modal-label">Course Name <span class="req">*</span></label>
                            <input
                                v-model="addCourseModal.name"
                                class="modal-input"
                                placeholder="e.g. Bachelor of Science in Information Technology"
                                required
                                autofocus
                            />
                        </div>
                        <div class="modal-form-group">
                            <label class="modal-label">Course Code <span class="req">*</span></label>
                            <input
                                v-model="addCourseModal.code"
                                class="modal-input code-input"
                                placeholder="e.g. BSIT"
                                maxlength="20"
                                required
                            />
                            <span class="modal-hint">Unique short code within this organization, max 20 chars</span>
                        </div>
                        <div class="modal-form-group">
                            <label class="modal-label">Description <span class="optional">(optional)</span></label>
                            <input
                                v-model="addCourseModal.description"
                                class="modal-input"
                                placeholder="Short description of this course"
                            />
                        </div>
                        <div class="modal-actions">
                            <button type="submit" class="btn btn-modal-submit" :disabled="addCourseModal.loading">
                                <span v-if="addCourseModal.loading" class="spinner"></span>
                                {{ addCourseModal.loading ? 'Adding...' : 'Add Course' }}
                            </button>
                            <button type="button" class="btn btn-modal-cancel" @click="closeAddCourse">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </section>
</template>

<script>
import { adminAPI } from "../../services/api.js";
import { useNotification } from "../../composables/useNotification.js";
import { useConfirmDialog } from "../../composables/useConfirmDialog.js";

const AVATAR_COLORS = [
    { bg: '#e8f5e9', text: '#2e7d32' },
    { bg: '#e3f2fd', text: '#1565c0' },
    { bg: '#fce4ec', text: '#c62828' },
    { bg: '#fff3e0', text: '#e65100' },
    { bg: '#f3e5f5', text: '#7b1fa2' },
    { bg: '#e0f2f1', text: '#00695c' },
    { bg: '#fff8e1', text: '#f57f17' },
    { bg: '#e8eaf6', text: '#283593' },
    { bg: '#fbe9e7', text: '#bf360c' },
    { bg: '#e0f7fa', text: '#00838f' },
];

export default {
    name: "AdminOrganizations",
    setup() {
        const { error: showError, success: showSuccess } = useNotification();
        const { confirmDangerous: showConfirmDangerous } = useConfirmDialog();
        return { showError, showSuccess, showConfirmDangerous };
    },
    data() {
        return {
            organizations: [],
            newName: "",
            newCode: "",
            editingId: null,
            editingName: "",
            editingCode: "",
            loading: false,
            initialLoading: true,
            showCreateForm: false,
            searchQuery: "",
            sortBy: "name",
            // Add Course Modal state
            addCourseModal: {
                show: false,
                org: null,
                name: "",
                code: "",
                description: "",
                loading: false,
            },
        };
    },
    computed: {
        totalVoters() {
            return this.organizations.reduce((sum, o) => sum + (o.voters_count || 0), 0);
        },
        totalCourses() {
            return this.organizations.reduce((sum, o) => sum + ((o.courses || []).length), 0);
        },
        avgVoters() {
            if (!this.organizations.length) return 0;
            return Math.round(this.totalVoters / this.organizations.length);
        },
        largestOrgVoters() {
            if (!this.organizations.length) return 0;
            return Math.max(...this.organizations.map(o => o.voters_count || 0));
        },
        filteredOrganizations() {
            let list = [...this.organizations];
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.trim().toLowerCase();
                list = list.filter(
                    o => o.name.toLowerCase().includes(q) || o.code.toLowerCase().includes(q)
                );
            }
            switch (this.sortBy) {
                case "name":    list.sort((a, b) => a.name.localeCompare(b.name)); break;
                case "code":    list.sort((a, b) => a.code.localeCompare(b.code)); break;
                case "voters_desc": list.sort((a, b) => (b.voters_count || 0) - (a.voters_count || 0)); break;
                case "voters_asc":  list.sort((a, b) => (a.voters_count || 0) - (b.voters_count || 0)); break;
            }
            return list;
        },
    },
    async mounted() {
        await this.loadOrganizations();
    },
    methods: {
        getInitials(code) {
            return (code || "??").substring(0, 3).toUpperCase();
        },
        getAvatarStyle(organization) {
            const idx = organization.id % AVATAR_COLORS.length;
            const color = AVATAR_COLORS[idx];
            return { background: color.bg, color: color.text };
        },
        toggleCreateForm() {
            this.showCreateForm = !this.showCreateForm;
            if (this.showCreateForm) {
                this.newName = "";
                this.newCode = "";
            }
        },
        async loadOrganizations() {
            this.initialLoading = true;
            try {
                const response = await adminAPI.getOrganizations();
                this.organizations = response.data.organizations || [];
            } catch (error) {
                console.error("Error loading organizations:", error);
                this.showError("Failed to load organizations.");
            } finally {
                this.initialLoading = false;
            }
        },
        async createOrganization() {
            this.loading = true;
            try {
                const response = await adminAPI.createOrganization({ name: this.newName, code: this.newCode });
                const org = response.data.organization;
                org.courses = [];
                org.voters_count = 0;
                this.organizations.push(org);
                this.newName = "";
                this.newCode = "";
                this.showCreateForm = false;
                this.showSuccess("Organization created successfully!");
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message || "Failed to create organization";
                this.showError(errorMessage);
            } finally {
                this.loading = false;
            }
        },
        startEditing(organization) {
            this.editingId = organization.id;
            this.editingName = organization.name;
            this.editingCode = organization.code;
        },
        async saveOrganization(organization) {
            try {
                const response = await adminAPI.updateOrganization(organization.id, { name: this.editingName, code: this.editingCode });
                Object.assign(organization, response.data.organization);
                this.editingId = null;
                this.showSuccess("Organization updated successfully!");
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message || "Failed to update organization";
                this.showError(errorMessage);
            }
        },
        async removeOrganization(organization) {
            const confirmed = await this.showConfirmDangerous(
                "Are you sure you want to delete this organization? This action cannot be undone.",
                { title: "Delete Organization", confirmText: "Delete" }
            );
            if (!confirmed) return;
            try {
                await adminAPI.deleteOrganization(organization.id);
                this.organizations = this.organizations.filter((item) => item.id !== organization.id);
                this.showSuccess("Organization deleted successfully!");
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message || "Failed to delete organization";
                this.showError(errorMessage);
            }
        },

        // ─── Course Management ───────────────────────────────────────────────
        openAddCourse(organization) {
            this.addCourseModal.show = true;
            this.addCourseModal.org = organization;
            this.addCourseModal.name = "";
            this.addCourseModal.code = "";
            this.addCourseModal.description = "";
            this.addCourseModal.loading = false;
        },
        closeAddCourse() {
            this.addCourseModal.show = false;
            this.addCourseModal.org = null;
        },
        async submitAddCourse() {
            if (!this.addCourseModal.name.trim() || !this.addCourseModal.code.trim()) {
                this.showError("Course name and code are required.");
                return;
            }
            this.addCourseModal.loading = true;
            try {
                const orgId = this.addCourseModal.org.id;
                const response = await adminAPI.createOrganizationCourse(orgId, {
                    name: this.addCourseModal.name.trim(),
                    code: this.addCourseModal.code.trim(),
                    description: this.addCourseModal.description.trim() || null,
                });
                // Push course into the local org object
                const org = this.organizations.find(o => o.id === orgId);
                if (org) {
                    if (!org.courses) org.courses = [];
                    org.courses.push(response.data.course);
                    org.courses_count = (org.courses_count || 0) + 1;
                }
                this.showSuccess(`Course "${response.data.course.code}" added successfully!`);
                this.closeAddCourse();
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message || "Failed to add course";
                this.showError(errorMessage);
            } finally {
                this.addCourseModal.loading = false;
            }
        },
        async removeCourse(organization, course) {
            const confirmed = await this.showConfirmDangerous(
                `Delete course "${course.name} (${course.code})"? This cannot be undone.`,
                { title: "Delete Course", confirmText: "Delete" }
            );
            if (!confirmed) return;
            try {
                await adminAPI.deleteOrganizationCourse(organization.id, course.id);
                const org = this.organizations.find(o => o.id === organization.id);
                if (org) {
                    org.courses = org.courses.filter(c => c.id !== course.id);
                    org.courses_count = Math.max(0, (org.courses_count || 1) - 1);
                }
                this.showSuccess(`Course "${course.code}" deleted.`);
            } catch (error) {
                const errorMessage = error.response?.data?.message || error.message || "Failed to delete course";
                this.showError(errorMessage);
            }
        },
    },
};
</script>

<style scoped>
/* =========================================================
   Base
   ========================================================= */
.organizations-page { padding: 0; }

/* =========================================================
   Page Header
   ========================================================= */
.page-header {
    background: linear-gradient(135deg, #116b27 0%, #1e8c38 60%, #2ea44e 100%);
    border-radius: 18px;
    padding: 36px 40px;
    margin-bottom: 28px;
    box-shadow: 0 8px 32px rgba(17, 107, 39, 0.22);
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
}
.page-header::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -20px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
}
.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    position: relative;
    z-index: 1;
}
.header-title-section {
    display: flex;
    align-items: center;
    gap: 18px;
}
.header-icon-wrap {
    width: 56px; height: 56px;
    background: rgba(255,255,255,0.18);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    flex-shrink: 0;
    color: white;
}
.header-icon-svg { width: 32px; height: 32px; }
.page-title {
    font-size: 28px; font-weight: 800;
    margin: 0 0 4px; color: #fff; letter-spacing: -0.5px;
}
.page-subtitle { font-size: 14px; color: rgba(255,255,255,0.75); margin: 0; }
.header-action-group {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.btn-add-org {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15); color: #fff;
    border: 1.5px solid rgba(255,255,255,0.4);
    border-radius: 12px; padding: 12px 24px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.22s ease;
    white-space: nowrap; backdrop-filter: blur(4px); font-family: inherit;
}
.btn-add-org:hover {
    background: rgba(255,255,255,0.28);
    border-color: rgba(255,255,255,0.7);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.btn-icon-svg { width: 20px; height: 20px; }

/* =========================================================
   Stats Summary
   ========================================================= */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .stats-summary { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .stats-summary { grid-template-columns: 1fr; } }

.stat-card {
    background: #fff; border-radius: 14px; padding: 20px 18px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    border-bottom: 3px solid transparent;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.stat-total    { border-bottom-color: #1976d2; }
.stat-voters   { border-bottom-color: #2e7d32; }
.stat-courses  { border-bottom-color: #7b1fa2; }
.stat-largest  { border-bottom-color: #e65100; }
.stat-icon-wrap { width: 42px; height: 42px; flex-shrink: 0; color: #666; }
.stat-icon-wrap svg { width: 100%; height: 100%; }
.stat-value { font-size: 30px; font-weight: 800; color: #1a2a3a; line-height: 1; }
.stat-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-top: 2px; }

/* =========================================================
   Create Panel
   ========================================================= */
.create-panel {
    background: #fff; border: 2px solid #bbf7d0;
    border-radius: 16px; padding: 28px 32px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(17, 107, 39, 0.08);
}
.create-panel-header {
    display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px;
}
.create-panel-icon { width: 36px; height: 36px; color: #16a34a; flex-shrink: 0; margin-top: 2px; }
.create-panel-title { margin: 0 0 4px; font-size: 18px; font-weight: 800; color: #1a2a3a; }
.create-panel-desc { margin: 0; font-size: 13.5px; color: #666; line-height: 1.4; }
.create-form {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 16px;
    align-items: end;
}
@media (max-width: 768px) { .create-form { grid-template-columns: 1fr; } }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
.form-input {
    padding: 12px 16px; border: 1.5px solid #e0e0e0;
    border-radius: 10px; font-size: 14px; color: #333;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit; width: 100%; box-sizing: border-box;
}
.form-input:focus { border-color: #116b27; box-shadow: 0 0 0 3px rgba(17,107,39,0.1); }
.code-input { text-transform: uppercase; max-width: 200px; font-weight: 700; letter-spacing: 1px; }
.form-hint { font-size: 11px; color: #999; }
.form-actions { display: flex; gap: 10px; align-items: flex-end; padding-bottom: 1px; }

/* =========================================================
   Buttons
   ========================================================= */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    border: none; border-radius: 10px; padding: 11px 20px;
    font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all 0.2s ease;
    font-family: inherit; white-space: nowrap;
}
.btn-create-submit { background: #116b27; color: #fff; padding: 12px 24px; }
.btn-create-submit:hover { background: #0b511c; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(17, 107, 39, 0.3); }
.btn-create-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-cancel { background: #f1f5f9; color: #64748b; }
.btn-cancel:hover { background: #e2e8f0; color: #475569; }
.btn-edit {
    background: #f0fdf4; color: #16a34a;
    padding: 7px 10px; font-size: 12px;
    border-radius: 8px;
}
.btn-edit:hover { background: #dcfce7; color: #15803d; }
.btn-delete {
    background: #fef2f2; color: #dc2626;
    padding: 7px 10px; font-size: 12px;
    border-radius: 8px;
}
.btn-delete:hover { background: #fecaca; color: #b91c1c; }
.btn-save { background: #116b27; color: #fff; }
.btn-save:hover { background: #0b511c; }
.btn-cancel-edit { background: #f1f5f9; color: #64748b; }
.btn-cancel-edit:hover { background: #e2e8f0; }
.btn-empty-action {
    display: inline-flex; align-items: center; gap: 8px;
    background: #116b27; color: #fff; border: none;
    border-radius: 12px; padding: 14px 28px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.22s ease;
    font-family: inherit; margin-top: 8px;
}
.btn-empty-action:hover { background: #0b511c; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(17, 107, 39, 0.25); }
.btn-icon-sm { width: 16px; height: 16px; }

/* =========================================================
   Filter Bar
   ========================================================= */
.filter-bar {
    background: #fff; border-radius: 14px; padding: 16px 20px;
    margin-bottom: 24px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06); flex-wrap: wrap;
}
.filter-search { position: relative; flex: 1; min-width: 200px; }
.search-icon {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%); width: 18px; height: 18px; color: #999;
}
.search-input {
    width: 100%; padding: 10px 14px 10px 38px;
    border: 1.5px solid #e0e0e0; border-radius: 10px;
    font-size: 14px; color: #333; outline: none;
    transition: border-color 0.2s; box-sizing: border-box; font-family: inherit;
}
.search-input:focus { border-color: #116b27; box-shadow: 0 0 0 3px rgba(17,107,39,0.1); }
.sort-select {
    padding: 10px 14px; border: 1.5px solid #e0e0e0;
    border-radius: 10px; font-size: 13px; color: #444;
    background: #f8f9fa; outline: none; cursor: pointer;
    transition: border-color 0.2s; min-width: 160px; font-family: inherit;
}
.sort-select:focus { border-color: #116b27; box-shadow: 0 0 0 3px rgba(17,107,39,0.1); }
.results-badge {
    display: inline-block; background: #f0fdf4; color: #16a34a;
    font-size: 12px; font-weight: 700; padding: 6px 14px;
    border-radius: 20px; white-space: nowrap;
}

/* =========================================================
   Organization Cards Grid
   ========================================================= */
.org-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
@media (max-width: 768px) { .org-grid { grid-template-columns: 1fr; } }

.org-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s;
    border: 1px solid #f0f0f0;
}
.org-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
.org-card-editing { border: 2px solid #16a34a; box-shadow: 0 4px 20px rgba(22, 163, 74, 0.15); }
.org-card-editing:hover { transform: none; }

/* Card View Mode */
.org-card-view { display: flex; flex-direction: column; }

.org-card-top {
    display: flex; align-items: center; gap: 14px;
    padding: 18px 18px 14px;
}
.org-avatar {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800; letter-spacing: 0.5px;
    flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.org-info { flex: 1; min-width: 0; }
.org-name { margin: 0 0 5px; font-size: 15px; font-weight: 800; color: #1a2a3a; word-break: break-word; }
.org-code-badge {
    display: inline-block; padding: 2px 9px; border-radius: 20px;
    background: #f0fdf4; color: #16a34a;
    font-size: 11px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;
}
.org-top-actions { display: flex; gap: 6px; flex-shrink: 0; }

/* Stats Row */
.org-card-stats {
    display: flex; align-items: center;
    padding: 10px 18px;
    border-top: 1px solid #f3f3f3;
    border-bottom: 1px solid #f3f3f3;
    gap: 0;
}
.org-stat { flex: 1; display: flex; align-items: center; gap: 6px; justify-content: center; }
.org-stat-icon { width: 16px; height: 16px; color: #999; flex-shrink: 0; }
.org-stat-value { font-size: 15px; font-weight: 800; color: #1a2a3a; }
.status-active-text { color: #16a34a; font-size: 12px; }
.org-stat-label { font-size: 11px; color: #999; font-weight: 600; }
.org-stat-divider { width: 1px; height: 24px; background: #eee; margin: 0 4px; }

/* Card Edit Mode */
.org-card-edit { padding: 22px; }
.edit-header {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #f0f0f0;
}
.edit-icon { width: 20px; height: 20px; color: #16a34a; }
.edit-title { font-size: 14px; font-weight: 800; color: #1a2a3a; text-transform: uppercase; letter-spacing: 0.3px; }
.edit-fields { display: grid; grid-template-columns: 1fr auto; gap: 14px; margin-bottom: 18px; }
@media (max-width: 480px) { .edit-fields { grid-template-columns: 1fr; } }
.edit-actions { display: flex; gap: 10px; justify-content: flex-end; }

/* =========================================================
   Courses Section (inside org card)
   ========================================================= */
.courses-section {
    padding: 14px 18px 16px;
    border-top: 1px solid #f3f3f3;
    background: #fafcfa;
}
.courses-section-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 12px;
}
.courses-section-title {
    display: flex; align-items: center; gap: 7px;
    font-size: 12px; font-weight: 700; color: #555;
    text-transform: uppercase; letter-spacing: 0.4px;
}
.courses-icon { width: 15px; height: 15px; color: #16a34a; }
.courses-count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: #dcfce7; color: #15803d;
    font-size: 11px; font-weight: 800;
    min-width: 20px; height: 20px; border-radius: 10px;
    padding: 0 6px;
}
.btn-add-course {
    display: inline-flex; align-items: center; gap: 5px;
    background: #116b27; color: #fff;
    border: none; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-add-course:hover { background: #0b511c; transform: translateY(-1px); }

/* Course list items */
.course-list { display: flex; flex-direction: column; gap: 6px; }
.course-item {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff; border: 1px solid #e8f5e9;
    border-radius: 9px; padding: 8px 10px;
    transition: border-color 0.2s;
}
.course-item:hover { border-color: #86efac; }
.course-item-left { display: flex; align-items: center; gap: 9px; min-width: 0; }
.course-code-pill {
    display: inline-block;
    background: #f0fdf4; color: #15803d;
    font-size: 10px; font-weight: 800;
    letter-spacing: 0.5px; text-transform: uppercase;
    padding: 3px 8px; border-radius: 6px;
    flex-shrink: 0;
}
.course-item-info { display: flex; flex-direction: column; min-width: 0; }
.course-item-name {
    font-size: 13px; font-weight: 700; color: #1a2a3a;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.course-item-voters { font-size: 11px; color: #999; font-weight: 600; }
.btn-delete-course {
    background: #fef2f2; color: #ef4444;
    border: none; border-radius: 7px;
    padding: 5px; cursor: pointer;
    transition: background 0.18s;
    display: flex; align-items: center; flex-shrink: 0;
}
.btn-delete-course:hover { background: #fecaca; }

.courses-empty {
    display: flex; flex-direction: column; align-items: center;
    padding: 16px 10px; color: #bbb;
    font-size: 12px; text-align: center; line-height: 1.5;
}
.courses-empty strong { color: #16a34a; }

/* =========================================================
   Empty / Loading
   ========================================================= */
.empty-state-card {
    background: #fff; border-radius: 18px; padding: 60px 40px;
    text-align: center; box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    border: 2px dashed #e0e0e0;
}
.empty-icon-wrap { width: 80px; height: 80px; margin: 0 auto 20px; color: #c0c0c0; }
.empty-icon-wrap svg { width: 100%; height: 100%; }
.empty-title { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #1a2a3a; }
.empty-desc { margin: 0 0 24px; font-size: 14px; color: #888; max-width: 420px; margin-left: auto; margin-right: auto; line-height: 1.5; }
.loading-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; }
.loading-spinner-lg {
    width: 40px; height: 40px;
    border: 3px solid #e0e0e0; border-top-color: #16a34a;
    border-radius: 50%; animation: spin 0.8s linear infinite;
}
.spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff; border-radius: 50%;
    animation: spin 0.8s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
.loading-text { margin-top: 16px; font-size: 14px; color: #888; font-weight: 600; }

/* =========================================================
   Add Course Modal
   ========================================================= */
.modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.modal-box {
    background: #fff; border-radius: 18px;
    width: 100%; max-width: 480px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.22);
    overflow: hidden;
}
.modal-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 24px 28px 16px;
    border-bottom: 1px solid #f0f0f0;
}
.modal-header-left { display: flex; gap: 14px; align-items: flex-start; }
.modal-title { margin: 0 0 3px; font-size: 17px; font-weight: 800; color: #1a2a3a; }
.modal-subtitle { margin: 0; font-size: 12.5px; color: #888; }
.modal-close {
    background: #f1f5f9; border: none; border-radius: 8px;
    width: 32px; height: 32px; cursor: pointer;
    font-size: 14px; color: #64748b; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s; flex-shrink: 0;
}
.modal-close:hover { background: #e2e8f0; }
.modal-form { padding: 20px 28px 28px; }
.modal-form-group { margin-bottom: 18px; }
.modal-label {
    display: block; font-size: 12px; font-weight: 700; color: #555;
    text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 7px;
}
.req { color: #ef4444; }
.optional { color: #aaa; font-weight: 500; text-transform: none; letter-spacing: 0; }
.modal-input {
    width: 100%; padding: 11px 15px;
    border: 1.5px solid #e0e0e0; border-radius: 10px;
    font-size: 14px; color: #333; outline: none;
    transition: border-color 0.2s; font-family: inherit;
    box-sizing: border-box;
}
.modal-input:focus { border-color: #116b27; box-shadow: 0 0 0 3px rgba(17,107,39,0.1); }
.modal-hint { font-size: 11px; color: #999; display: block; margin-top: 5px; }
.modal-actions { display: flex; gap: 10px; margin-top: 24px; }
.btn-modal-submit {
    flex: 1; background: #116b27; color: #fff;
    border: none; border-radius: 10px;
    padding: 12px 20px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    font-family: inherit; display: flex; align-items: center;
    justify-content: center; gap: 8px;
}
.btn-modal-submit:hover:not(:disabled) { background: #0b511c; }
.btn-modal-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-modal-cancel {
    background: #f1f5f9; color: #64748b;
    border: none; border-radius: 10px;
    padding: 12px 20px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: background 0.2s; font-family: inherit;
}
.btn-modal-cancel:hover { background: #e2e8f0; }

/* =========================================================
   Transitions
   ========================================================= */
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s ease-in; }
.slide-fade-enter-from { transform: translateY(-12px); opacity: 0; }
.slide-fade-leave-to { transform: translateY(-8px); opacity: 0; }

.modal-fade-enter-active { transition: opacity 0.22s ease; }
.modal-fade-leave-active { transition: opacity 0.18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* =========================================================
   Responsive
   ========================================================= */
@media (max-width: 640px) {
    .page-header { padding: 24px 20px; }
    .page-title { font-size: 22px; }
    .header-content { flex-direction: column; align-items: flex-start; }
    .header-title-section { gap: 12px; }
    .header-icon-wrap { width: 44px; height: 44px; border-radius: 12px; }
    .header-icon-svg { width: 24px; height: 24px; }
    .create-panel { padding: 20px; }
    .filter-bar { flex-direction: column; align-items: stretch; }
    .org-grid { grid-template-columns: 1fr; }
}
</style>
