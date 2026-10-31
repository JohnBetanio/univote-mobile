<template>
    <div class="results-page">
        <!-- Page Header -->
        <header class="page-header">
            <div class="header-content">
                <div class="header-icon-wrap">
                    <svg class="header-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <div>
                    <div class="header-badge-row">
                        <span class="header-seal-pill">
                            <span class="seal-dot"></span>
                            Concluded Elections Archive
                        </span>
                    </div>
                    <h1 class="page-title">Finished Election Results</h1>
                    <p class="page-subtitle">
                        Official immutable archive of completed elections, declared winners, and voter turnout records
                    </p>
                </div>
            </div>
        </header>

        <!-- Stats Overview Row -->
        <section v-if="results.length > 0" class="archive-stats-grid">
            <div class="stat-card stat-elections">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-num">{{ results.length }}</span>
                    <span class="stat-label">Concluded Elections</span>
                </div>
            </div>

            <div class="stat-card stat-voters">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-num">{{ totalVotersParticipated }}</span>
                    <span class="stat-label">Total Voters Who Voted</span>
                </div>
            </div>

            <div class="stat-card stat-votes">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-num">{{ totalVotesRecorded }}</span>
                    <span class="stat-label">Total Ballots Cast</span>
                </div>
            </div>

            <div class="stat-card stat-winners">
                <div class="stat-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.982-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.25h13.5v1.986m-13.5 0A48.667 48.667 0 0112 3.75c2.348 0 4.654.167 6.75.486m0 0c.982.143 1.954.317 2.916.52a6.003 6.003 0 01-5.396 4.972m2.48-5.972V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.01 6.01 0 01-3.77 1.347 6.01 6.01 0 01-3.77-1.347" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-num">{{ totalDeclaredWinners }}</span>
                    <span class="stat-label">Declared Winners</span>
                </div>
            </div>
        </section>

        <!-- Filter, Sort & Search Toolbar -->
        <div v-if="results.length > 0" class="archive-toolbar">
            <!-- Search -->
            <div class="search-box">
                <svg class="search-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search finished elections, winners, positions, org..."
                    class="search-input"
                />
                <button v-if="searchQuery" @click="searchQuery = ''" class="clear-search-btn" title="Clear search">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>

            <!-- Sort By Organization (User explicit requirement) -->
            <div class="toolbar-group">
                <label for="sortSelector" class="toolbar-label">
                    <svg class="label-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                    </svg>
                    Sort By:
                </label>
                <select id="sortSelector" v-model="sortBy" class="toolbar-select">
                    <option value="org_asc">Organization (A &rarr; Z)</option>
                    <option value="org_desc">Organization (Z &rarr; A)</option>
                    <option value="newest">Ended Date (Newest First)</option>
                    <option value="oldest">Ended Date (Oldest First)</option>
                    <option value="turnout">Highest Voter Turnout</option>
                </select>
            </div>

            <!-- Scope Filter -->
            <div class="toolbar-group">
                <label for="scopeFilterSelector" class="toolbar-label">
                    <svg class="label-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    Filter Scope:
                </label>
                <select id="scopeFilterSelector" v-model="selectedScopeFilter" class="toolbar-select">
                    <option value="all">All Scopes</option>
                    <option value="campus">Campus-Wide</option>
                    <option value="organization">By Organization</option>
                    <option value="course">By Course</option>
                </select>
            </div>

            <!-- Organization Filter -->
            <div v-if="selectedScopeFilter !== 'course'" class="toolbar-group">
                <label for="orgFilterSelector" class="toolbar-label">
                    <svg class="label-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.315 48.315 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                    </svg>
                    Filter Org:
                </label>
                <select id="orgFilterSelector" v-model="selectedOrgFilter" class="toolbar-select">
                    <option value="all">All Organizations ({{ results.length }})</option>
                    <option v-for="org in availableOrgs" :key="org.id" :value="org.id">
                        {{ org.name }} ({{ org.code }}) - {{ countElectionsForOrg(org.id) }}
                    </option>
                    <option value="general">University-wide / General ({{ countGeneralElections }})</option>
                </select>
            </div>

            <!-- Course Filter -->
            <div v-if="selectedScopeFilter === 'course' || (availableCourses.length > 0 && selectedCourseFilter !== 'all')" class="toolbar-group">
                <label for="courseFilterSelector" class="toolbar-label">
                    <svg class="label-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                    Filter Course:
                </label>
                <select id="courseFilterSelector" v-model="selectedCourseFilter" class="toolbar-select">
                    <option value="all">All Courses</option>
                    <option v-for="c in availableCourses" :key="c.id" :value="c.id">
                        {{ c.name }} ({{ c.code }})
                    </option>
                </select>
            </div>

            <!-- View Layout Toggle -->
            <div class="view-toggle-group">
                <button
                    :class="['toggle-btn', viewMode === 'grouped' && 'toggle-btn-active']"
                    @click="viewMode = 'grouped'"
                    title="Group by Organization"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    Group by Org
                </button>
                <button
                    :class="['toggle-btn', viewMode === 'list' && 'toggle-btn-active']"
                    @click="viewMode = 'list'"
                    title="List View"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    All Cards
                </button>
            </div>
        </div>

        <!-- Quick Filter Category Pills -->
        <div v-if="results.length > 0 && availableOrgs.length > 0" class="org-pills-row">
            <button
                :class="['org-pill', selectedOrgFilter === 'all' && 'org-pill-active']"
                @click="selectedOrgFilter = 'all'"
            >
                All Organizations ({{ results.length }})
            </button>
            <button
                v-for="org in availableOrgs"
                :key="org.id"
                :class="['org-pill', selectedOrgFilter == org.id && 'org-pill-active']"
                :style="selectedOrgFilter == org.id ? { backgroundColor: getOrgColor(org), borderColor: getOrgColor(org), color: '#fff' } : {}"
                @click="selectedOrgFilter = org.id"
            >
                <span class="pill-dot" :style="{ backgroundColor: getOrgColor(org) }"></span>
                {{ org.code || org.name }}
                <span class="pill-count">{{ countElectionsForOrg(org.id) }}</span>
            </button>
            <button
                v-if="countGeneralElections > 0"
                :class="['org-pill', selectedOrgFilter === 'general' && 'org-pill-active']"
                @click="selectedOrgFilter = 'general'"
            >
                University-wide ({{ countGeneralElections }})
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>Loading finished elections archive...</p>
        </div>

        <!-- =========================================================
             VIEW MODE 1: GROUPED BY ORGANIZATION
             ========================================================= -->
        <div v-else-if="filteredResults.length > 0 && viewMode === 'grouped'" class="grouped-results">
            <div
                v-for="group in groupedElections"
                :key="group.orgId"
                class="org-group-section"
            >
                <!-- Organization Group Header -->
                <div class="org-group-header" :style="{ borderLeftColor: group.color }">
                    <div class="org-group-title-wrap">
                        <div class="org-group-icon" :style="{ backgroundColor: group.color }">
                            {{ (group.code || group.name).substring(0, 3).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="org-group-name">{{ group.name }}</h2>
                            <p class="org-group-sub">
                                <span v-if="group.code" class="code-pill">{{ group.code }}</span>
                                {{ group.elections.length }} concluded {{ group.elections.length === 1 ? 'election' : 'elections' }} archived
                            </p>
                        </div>
                    </div>
                    <div class="org-group-stats">
                        <span class="badge-total-voters">
                            <svg class="badge-user-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.813.813 0 01-.634 1.052H14.5z" />
                            </svg>
                            {{ group.totalVoters }} Voters Participated
                        </span>
                    </div>
                </div>

                <!-- Elections in this Org Group -->
                <div class="group-elections-grid">
                    <article
                        v-for="election in group.elections"
                        :key="election.id"
                        class="archive-card"
                    >
                        <!-- Card Banner -->
                        <div class="archive-card-banner">
                            <div class="banner-left">
                                <span class="seal-badge">
                                    <svg class="seal-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Official Concluded Archive
                                </span>
                                <span class="immutable-pill" title="This concluded election is an immutable record and cannot be edited">
                                    <svg class="lock-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                    </svg>
                                    Non-Editable Record
                                </span>
                            </div>
                            <div class="banner-right">
                                <!-- Delete Archive Action (Admin delete permission) -->
                                <button
                                    @click="deleteArchivedElection(election)"
                                    class="btn-delete-archive"
                                    title="Delete this finished election permanently"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Delete Archive
                                </button>
                            </div>
                        </div>

                        <!-- Card Header Body -->
                        <div class="card-head-content">
                            <h3 class="election-heading">{{ election.title }}</h3>
                            <p v-if="election.description" class="election-summary">{{ election.description }}</p>

                            <div class="meta-row">
                                <span class="meta-item">
                                    <strong>Concluded:</strong> {{ formatDateTime(election.end_date_formatted || election.end_date) }}
                                </span>
                                <span class="meta-sep">&bull;</span>
                                <span class="meta-item">
                                    <strong>Created:</strong> {{ formatDateTime(election.created_at_formatted || election.created_at) }}
                                </span>
                            </div>

                            <div class="scope-pill-row">
                                <span v-if="election.election_type === 'course' || election.course" class="scope-pill scope-pill-course">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.394 2.08a1 1 0 0 0-.788 0l-7 3a1 1 0 0 0 0 1.84L5.25 8.051a10.99 10.99 0 0 0-.25 2.31c0 .61.057 1.206.166 1.783L3.106 13.08a1 1 0 0 0 0 1.84l7 3a1 1 0 0 0 .788 0l7-3a1 1 0 0 0 0-1.84l-2.06-1.02a11.025 11.025 0 0 0 .166-1.783c0-.79-.088-1.56-.25-2.31l2.644-1.131a1 1 0 0 0 0-1.84l-7-3Z" />
                                    </svg>
                                    Course: {{ election.course ? (election.course.code || election.course.name) : 'Specific Course' }}
                                </span>
                                <span v-else-if="election.organization" class="scope-pill scope-pill-org">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v2a1 1 0 0 1-1 1H4a1 1 0 1 1 0-2V4Zm3 1h2v2H7V5Zm2 4H7v2h2V9Zm2-4h2v2h-2V5Zm2 4h-2v2h2V9Z" />
                                    </svg>
                                    Org: {{ election.organization.code || election.organization.name }}
                                </span>
                                <span v-else class="scope-pill scope-pill-global">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16ZM4.205 11.5a5.955 5.955 0 0 1-.2-1.5c0-.508.063-1.002.182-1.478l4.475 2.237a.5.5 0 0 1 .274.453v3.743a5.98 5.98 0 0 1-4.731-3.455ZM10 17.94V14.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H11a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 1 .5-.5h1.341c.21 0 .408.1.536.265l1.621 2.08A5.982 5.982 0 0 1 10 17.94ZM16.326 9H15a.5.5 0 0 1-.5-.5V7a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 1-.5-.5V4.707a5.98 5.98 0 0 1 4.826 4.293Z" />
                                    </svg>
                                    Campus-Wide
                                </span>
                            </div>
                        </div>

                        <!-- Turnout & Voter Statistics Strip (Primary requirement: total voter who voted) -->
                        <div class="voter-turnout-strip">
                            <div class="turnout-box primary-turnout">
                                <div class="turnout-icon-wrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                </div>
                                <div class="turnout-details">
                                    <div class="turnout-top-row">
                                        <span class="turnout-big-number">{{ election.total_voters_who_voted ?? 0 }}</span>
                                        <span v-if="election.turnout_percentage !== undefined && election.turnout_percentage !== null" class="turnout-pct-badge">
                                            {{ election.turnout_percentage }}% Turnout
                                        </span>
                                    </div>
                                    <span class="turnout-sublabel">Total Voters Who Voted</span>
                                </div>
                            </div>

                            <div class="turnout-box">
                                <span class="metric-num">{{ election.total_votes_cast ?? countTotalVotes(election) }}</span>
                                <span class="metric-lbl">Total Ballots Cast</span>
                            </div>

                            <div class="turnout-box">
                                <span class="metric-num">{{ election.positions?.length || 0 }}</span>
                                <span class="metric-lbl">Positions Contested</span>
                            </div>

                            <div class="turnout-box">
                                <span class="metric-num">{{ (election.winners?.length || countWinners(election)) }}</span>
                                <span class="metric-lbl">Officials Elected</span>
                            </div>
                        </div>

                        <!-- Declared Winners Showcase (User requirement: view winners) -->
                        <div class="winners-showcase-panel">
                            <div class="winners-panel-header">
                                <div class="winners-title-left">
                                    <div class="trophy-icon-badge">
                                        <svg class="trophy-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.25 14.25v1.611A4.5 4.5 0 006.002 20.25a.75.75 0 00.748.75h10.5a.75.75 0 00.748-.75 4.5 4.5 0 00-3.248-4.389V14.25a6.707 6.707 0 01-1.142-2.426 6.73 6.73 0 002.743-1.346 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.388.268 2.715.756 3.931a5.253 5.253 0 01-4.248-4.145 48.401 48.401 0 013.492-.544v.758zM18.834 6c1.174.15 2.34.332 3.492.544a5.253 5.253 0 01-4.248 4.145c.488-1.216.756-2.543.756-3.93v-.759z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h4 class="winners-title">Declared Winners & Elected Officials</h4>
                                </div>
                                <span class="winners-count-badge">
                                    {{ (election.winners?.length || countWinners(election)) }} of {{ election.positions?.length || 0 }} Positions Decided
                                </span>
                            </div>

                            <div v-if="getElectionWinners(election).length > 0" class="winners-grid">
                                <div
                                    v-for="winner in getElectionWinners(election)"
                                    :key="winner.position_id"
                                    class="winner-card"
                                    :class="{ 'is-tied': winner.is_tie }"
                                >
                                    <div class="winner-card-top">
                                        <span class="winner-position-label">{{ winner.position_name }}</span>
                                        <span v-if="winner.is_tie" class="tie-badge">TIED RESULT</span>
                                        <span v-else class="winner-chip">ELECTED</span>
                                    </div>
                                    <div class="winner-person-row">
                                        <div class="winner-avatar">
                                            {{ getInitials(winner.candidate_name) }}
                                        </div>
                                        <div class="winner-person-info">
                                            <strong class="winner-name">{{ winner.candidate_name }}</strong>
                                            <div class="winner-stats-line">
                                                <span class="winner-votes">{{ winner.vote_count }} {{ winner.vote_count === 1 ? 'vote' : 'votes' }}</span>
                                                <span class="winner-pct">({{ Math.round(winner.percentage || 0) }}%)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="no-winners-box">
                                <p>No votes were recorded in this election.</p>
                            </div>
                        </div>

                        <!-- Detailed Position Tally (Collapsible for cleanliness) -->
                        <div class="detailed-tally-section">
                            <button
                                @click="toggleDetails(election.id)"
                                class="btn-toggle-tally"
                            >
                                <span>{{ expandedElections[election.id] ? 'Hide Full Candidate Tallies' : 'View Full Candidate Vote Tallies' }}</span>
                                <svg
                                    :class="['arrow-icon', expandedElections[election.id] && 'arrow-up']"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div v-if="expandedElections[election.id]" class="positions-detail-container">
                                <div
                                    v-for="position in election.positions"
                                    :key="position.id"
                                    class="position-detail-card"
                                >
                                    <div class="position-detail-header">
                                        <h5 class="position-title">{{ position.name }}</h5>
                                        <span class="position-total-votes">
                                            {{ positionTotalVotes(position) }} total {{ positionTotalVotes(position) === 1 ? 'vote' : 'votes' }}
                                        </span>
                                    </div>

                                    <div class="candidates-tally-list">
                                        <div
                                            v-for="(candidate, cIndex) in position.candidates"
                                            :key="candidate.id"
                                            :class="['candidate-tally-row', isTopCandidate(candidate, cIndex) && 'is-winner-row']"
                                        >
                                            <div class="cand-row-info">
                                                <div class="cand-name-wrap">
                                                    <span class="cand-rank">#{{ cIndex + 1 }}</span>
                                                    <span class="cand-name">{{ candidate.name }}</span>
                                                    <span v-if="isTopCandidate(candidate, cIndex)" class="elected-tag">
                                                        <svg class="winner-check-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                        </svg>
                                                        Elected Winner
                                                    </span>
                                                </div>
                                                <span class="cand-votes">
                                                    <strong>{{ candidate.vote_count }}</strong> {{ candidate.vote_count === 1 ? 'vote' : 'votes' }}
                                                    <span class="cand-pct">({{ Math.round(candidate.percentage || 0) }}%)</span>
                                                </span>
                                            </div>

                                            <div class="cand-progress-track">
                                                <div
                                                    class="cand-progress-fill"
                                                    :class="{ 'fill-winner': isTopCandidate(candidate, cIndex) }"
                                                    :style="{ width: (candidate.percentage || 0) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <!-- =========================================================
             VIEW MODE 2: FLAT LIST / ALL CARDS VIEW
             ========================================================= -->
        <div v-else-if="filteredResults.length > 0 && viewMode === 'list'" class="results-list-container">
            <article
                v-for="election in filteredResults"
                :key="election.id"
                class="archive-card"
            >
                <!-- Card Banner -->
                <div class="archive-card-banner">
                    <div class="banner-left">
                        <span
                            v-if="election.election_type === 'course' || election.course"
                            class="org-pill-badge org-course"
                        >
                            Course: {{ election.course ? (election.course.code || election.course.name) : 'Specific Course' }}
                        </span>
                        <span
                            v-else-if="election.organization"
                            class="org-pill-badge"
                            :style="{ backgroundColor: getOrgColor(election.organization) }"
                        >
                            {{ election.organization.name }} ({{ election.organization.code }})
                        </span>
                        <span v-else class="org-pill-badge org-general">
                            University-wide
                        </span>

                        <span class="seal-badge">
                            <svg class="seal-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            Concluded Archive
                        </span>

                        <span class="immutable-pill" title="Immutable Record">
                            <svg class="lock-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                            Non-Editable
                        </span>
                    </div>

                    <div class="banner-right">
                        <!-- Admin Delete Action -->
                        <button
                            @click="deleteArchivedElection(election)"
                            class="btn-delete-archive"
                            title="Delete this finished election"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Delete Archive
                        </button>
                    </div>
                </div>

                <!-- Card Header Body -->
                <div class="card-head-content">
                    <h3 class="election-heading">{{ election.title }}</h3>
                    <p v-if="election.description" class="election-summary">{{ election.description }}</p>

                    <div class="meta-row">
                        <span class="meta-item">
                            <strong>Concluded:</strong> {{ formatDateTime(election.end_date_formatted || election.end_date) }}
                        </span>
                        <span class="meta-sep">&bull;</span>
                        <span class="meta-item">
                            <strong>Created:</strong> {{ formatDateTime(election.created_at_formatted || election.created_at) }}
                        </span>
                    </div>

                    <div class="scope-pill-row">
                        <span v-if="election.election_type === 'course' || election.course" class="scope-pill scope-pill-course">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.394 2.08a1 1 0 0 0-.788 0l-7 3a1 1 0 0 0 0 1.84L5.25 8.051a10.99 10.99 0 0 0-.25 2.31c0 .61.057 1.206.166 1.783L3.106 13.08a1 1 0 0 0 0 1.84l7 3a1 1 0 0 0 .788 0l7-3a1 1 0 0 0 0-1.84l-2.06-1.02a11.025 11.025 0 0 0 .166-1.783c0-.79-.088-1.56-.25-2.31l2.644-1.131a1 1 0 0 0 0-1.84l-7-3Z" />
                            </svg>
                            Course: {{ election.course ? (election.course.code || election.course.name) : 'Specific Course' }}
                        </span>
                        <span v-else-if="election.organization" class="scope-pill scope-pill-org">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v2a1 1 0 0 1-1 1H4a1 1 0 1 1 0-2V4Zm3 1h2v2H7V5Zm2 4H7v2h2V9Zm2-4h2v2h-2V5Zm2 4h-2v2h2V9Z" />
                            </svg>
                            Org: {{ election.organization.code || election.organization.name }}
                        </span>
                        <span v-else class="scope-pill scope-pill-global">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16ZM4.205 11.5a5.955 5.955 0 0 1-.2-1.5c0-.508.063-1.002.182-1.478l4.475 2.237a.5.5 0 0 1 .274.453v3.743a5.98 5.98 0 0 1-4.731-3.455ZM10 17.94V14.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H11a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 1 .5-.5h1.341c.21 0 .408.1.536.265l1.621 2.08A5.982 5.982 0 0 1 10 17.94ZM16.326 9H15a.5.5 0 0 1-.5-.5V7a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 1-.5-.5V4.707a5.98 5.98 0 0 1 4.826 4.293Z" />
                            </svg>
                            Campus-Wide
                        </span>
                    </div>
                </div>

                <!-- Turnout & Voter Statistics Strip -->
                <div class="voter-turnout-strip">
                    <div class="turnout-box primary-turnout">
                        <div class="turnout-icon-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </div>
                        <div class="turnout-details">
                            <div class="turnout-top-row">
                                <span class="turnout-big-number">{{ election.total_voters_who_voted ?? 0 }}</span>
                                <span v-if="election.turnout_percentage !== undefined && election.turnout_percentage !== null" class="turnout-pct-badge">
                                    {{ election.turnout_percentage }}% Turnout
                                </span>
                            </div>
                            <span class="turnout-sublabel">Total Voters Who Voted</span>
                        </div>
                    </div>

                    <div class="turnout-box">
                        <span class="metric-num">{{ election.total_votes_cast ?? countTotalVotes(election) }}</span>
                        <span class="metric-lbl">Total Ballots Cast</span>
                    </div>

                    <div class="turnout-box">
                        <span class="metric-num">{{ election.positions?.length || 0 }}</span>
                        <span class="metric-lbl">Positions Contested</span>
                    </div>

                    <div class="turnout-box">
                        <span class="metric-num">{{ (election.winners?.length || countWinners(election)) }}</span>
                        <span class="metric-lbl">Officials Elected</span>
                    </div>
                </div>

                <!-- Declared Winners Showcase -->
                <div class="winners-showcase-panel">
                    <div class="winners-panel-header">
                        <div class="winners-title-left">
                            <div class="trophy-icon-badge">
                                <svg class="trophy-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.25 14.25v1.611A4.5 4.5 0 006.002 20.25a.75.75 0 00.748.75h10.5a.75.75 0 00.748-.75 4.5 4.5 0 00-3.248-4.389V14.25a6.707 6.707 0 01-1.142-2.426 6.73 6.73 0 002.743-1.346 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.388.268 2.715.756 3.931a5.253 5.253 0 01-4.248-4.145 48.401 48.401 0 013.492-.544v.758zM18.834 6c1.174.15 2.34.332 3.492.544a5.253 5.253 0 01-4.248 4.145c.488-1.216.756-2.543.756-3.93v-.759z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h4 class="winners-title">Declared Winners & Elected Officials</h4>
                        </div>
                        <span class="winners-count-badge">
                            {{ (election.winners?.length || countWinners(election)) }} of {{ election.positions?.length || 0 }} Positions Decided
                        </span>
                    </div>

                    <div v-if="getElectionWinners(election).length > 0" class="winners-grid">
                        <div
                            v-for="winner in getElectionWinners(election)"
                            :key="winner.position_id"
                            class="winner-card"
                            :class="{ 'is-tied': winner.is_tie }"
                        >
                            <div class="winner-card-top">
                                <span class="winner-position-label">{{ winner.position_name }}</span>
                                <span v-if="winner.is_tie" class="tie-badge">TIED RESULT</span>
                                <span v-else class="winner-chip">ELECTED</span>
                            </div>
                            <div class="winner-person-row">
                                <div class="winner-avatar">
                                    {{ getInitials(winner.candidate_name) }}
                                </div>
                                <div class="winner-person-info">
                                    <strong class="winner-name">{{ winner.candidate_name }}</strong>
                                    <div class="winner-stats-line">
                                        <span class="winner-votes">{{ winner.vote_count }} {{ winner.vote_count === 1 ? 'vote' : 'votes' }}</span>
                                        <span class="winner-pct">({{ Math.round(winner.percentage || 0) }}%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="no-winners-box">
                        <p>No votes were recorded in this election.</p>
                    </div>
                </div>

                <!-- Detailed Position Tally -->
                <div class="detailed-tally-section">
                    <button
                        @click="toggleDetails(election.id)"
                        class="btn-toggle-tally"
                    >
                        <span>{{ expandedElections[election.id] ? 'Hide Full Candidate Tallies' : 'View Full Candidate Vote Tallies' }}</span>
                        <svg
                            :class="['arrow-icon', expandedElections[election.id] && 'arrow-up']"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-if="expandedElections[election.id]" class="positions-detail-container">
                        <div
                            v-for="position in election.positions"
                            :key="position.id"
                            class="position-detail-card"
                        >
                            <div class="position-detail-header">
                                <h5 class="position-title">{{ position.name }}</h5>
                                <span class="position-total-votes">
                                    {{ positionTotalVotes(position) }} total {{ positionTotalVotes(position) === 1 ? 'vote' : 'votes' }}
                                </span>
                            </div>

                            <div class="candidates-tally-list">
                                <div
                                    v-for="(candidate, cIndex) in position.candidates"
                                    :key="candidate.id"
                                    :class="['candidate-tally-row', isTopCandidate(candidate, cIndex) && 'is-winner-row']"
                                >
                                    <div class="cand-row-info">
                                        <div class="cand-name-wrap">
                                            <span class="cand-rank">#{{ cIndex + 1 }}</span>
                                            <span class="cand-name">{{ candidate.name }}</span>
                                            <span v-if="isTopCandidate(candidate, cIndex)" class="elected-tag">
                                                <svg class="winner-check-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                </svg>
                                                Elected Winner
                                            </span>
                                        </div>
                                        <span class="cand-votes">
                                            <strong>{{ candidate.vote_count }}</strong> {{ candidate.vote_count === 1 ? 'vote' : 'votes' }}
                                            <span class="cand-pct">({{ Math.round(candidate.percentage || 0) }}%)</span>
                                        </span>
                                    </div>

                                    <div class="cand-progress-track">
                                        <div
                                            class="cand-progress-fill"
                                            :class="{ 'fill-winner': isTopCandidate(candidate, cIndex) }"
                                            :style="{ width: (candidate.percentage || 0) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <div class="empty-icon-wrapper">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    class="empty-icon"
                >
                    <path
                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                    />
                </svg>
            </div>
            <h2 class="empty-title">
                {{ searchQuery || selectedOrgFilter !== 'all' ? 'No Archived Elections Match Filter' : 'No Finished Elections in Archive' }}
            </h2>
            <p class="empty-message">
                {{ searchQuery || selectedOrgFilter !== 'all'
                    ? 'Try clearing your search or selecting a different organization.'
                    : 'When elections conclude on the Manage Elections page, they are automatically archived here with official winner declarations and voter turnout.' }}
            </p>
            <div v-if="searchQuery || selectedOrgFilter !== 'all'" class="empty-actions">
                <button @click="resetFilters" class="btn-clear-filter">Reset Filters</button>
            </div>
            <div v-else class="empty-actions">
                <router-link to="/admin/elections" class="btn-manage-link">
                    Go to Manage Elections &rarr;
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { useElectionStore } from "../../stores/electionStore.js";
import { adminAPI } from "../../services/api.js";
import { useNotification } from "../../composables/useNotification.js";
import { useConfirmDialog } from "../../composables/useConfirmDialog.js";

const ORG_COLORS = [
    '#116b27', '#1e40af', '#6b21a8', '#b91c1c', '#0f766e',
    '#c2410c', '#0369a1', '#4d7c0f', '#a21caf', '#374151',
];

export default {
    name: "AdminResults",
    setup() {
        const electionStore = useElectionStore();
        const { error: showError, success: showSuccess, warning: showWarning } = useNotification();
        const { confirmDangerous: showConfirmDangerous } = useConfirmDialog();
        return { electionStore, showError, showSuccess, showWarning, showConfirmDangerous };
    },
    data() {
        return {
            searchQuery: "",
            selectedScopeFilter: "all",
            selectedOrgFilter: "all",
            selectedCourseFilter: "all",
            sortBy: "org_asc", // default: sort by org per user request
            viewMode: "grouped", // 'grouped' by org, or 'list'
            organizationOptions: [],
            expandedElections: {},
        };
    },
    computed: {
        results() {
            return this.electionStore.results || [];
        },
        loading() {
            return this.electionStore.isLoading;
        },
        availableOrgs() {
            const orgMap = {};
            this.results.forEach((e) => {
                if (e.organization && e.organization.id) {
                    orgMap[e.organization.id] = e.organization;
                }
            });
            return Object.values(orgMap).sort((a, b) => a.name.localeCompare(b.name));
        },
        availableCourses() {
            const courseMap = {};
            this.results.forEach((e) => {
                if (e.course && e.course.id) {
                    courseMap[e.course.id] = e.course;
                }
            });
            return Object.values(courseMap).sort((a, b) => (a.code || a.name).localeCompare(b.code || b.name));
        },
        countGeneralElections() {
            return this.results.filter((e) => !e.organization_id && !e.course_id).length;
        },
        totalVotersParticipated() {
            return this.results.reduce((sum, e) => sum + (e.total_voters_who_voted || 0), 0);
        },
        totalVotesRecorded() {
            return this.results.reduce((sum, e) => sum + (e.total_votes_cast || this.countTotalVotes(e)), 0);
        },
        totalDeclaredWinners() {
            return this.results.reduce((sum, e) => sum + this.getElectionWinners(e).length, 0);
        },
        filteredResults() {
            let list = [...this.results];

            // 1. Scope filter
            if (this.selectedScopeFilter !== "all") {
                if (this.selectedScopeFilter === "campus") {
                    list = list.filter((e) => e.election_type === "all" || (!e.organization_id && !e.course_id));
                } else if (this.selectedScopeFilter === "organization") {
                    list = list.filter((e) => e.election_type === "organization" || (e.organization_id && !e.course_id));
                } else if (this.selectedScopeFilter === "course") {
                    list = list.filter((e) => e.election_type === "course" || e.course_id);
                }
            }

            // 2. Organization filter
            if (this.selectedOrgFilter === "general") {
                list = list.filter((e) => !e.organization_id && !e.course_id);
            } else if (this.selectedOrgFilter !== "all") {
                list = list.filter((e) => Number(e.organization_id) === Number(this.selectedOrgFilter));
            }

            // 3. Course filter
            if (this.selectedCourseFilter !== "all") {
                list = list.filter((e) => Number(e.course_id) === Number(this.selectedCourseFilter) || (e.course && Number(e.course.id) === Number(this.selectedCourseFilter)));
            }

            // 4. Search query filter
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.trim().toLowerCase();
                list = list.filter((e) => {
                    const matchTitle = (e.title || "").toLowerCase().includes(q);
                    const matchDesc = (e.description || "").toLowerCase().includes(q);
                    const matchOrg = (e.organization?.name || "").toLowerCase().includes(q) ||
                                     (e.organization?.code || "").toLowerCase().includes(q);
                    const matchCourse = (e.course?.name || "").toLowerCase().includes(q) ||
                                        (e.course?.code || "").toLowerCase().includes(q);

                    // Also search candidates & positions
                    const matchPositions = (e.positions || []).some((p) =>
                        p.name.toLowerCase().includes(q) ||
                        (p.candidates || []).some((c) => c.name.toLowerCase().includes(q))
                    );

                    return matchTitle || matchDesc || matchOrg || matchCourse || matchPositions;
                });
            }

            // 5. Sorting (Sort by organization by default!)
            list.sort((a, b) => {
                if (this.sortBy === "org_asc" || this.sortBy === "org_desc") {
                    const nameA = a.organization?.name ? a.organization.name.toLowerCase() : "zzz_general";
                    const nameB = b.organization?.name ? b.organization.name.toLowerCase() : "zzz_general";
                    const comp = nameA.localeCompare(nameB);
                    if (comp !== 0) {
                        return this.sortBy === "org_asc" ? comp : -comp;
                    }
                    // Secondary sort: newest concluded first
                    return new Date(b.end_date || b.created_at) - new Date(a.end_date || a.created_at);
                }

                if (this.sortBy === "newest") {
                    return new Date(b.end_date || b.created_at) - new Date(a.end_date || a.created_at);
                }

                if (this.sortBy === "oldest") {
                    return new Date(a.end_date || a.created_at) - new Date(b.end_date || b.created_at);
                }

                if (this.sortBy === "turnout") {
                    const turnoutA = a.total_voters_who_voted || 0;
                    const turnoutB = b.total_voters_who_voted || 0;
                    return turnoutB - turnoutA;
                }

                return 0;
            });

            return list;
        },
        groupedElections() {
            const groups = {};

            this.filteredResults.forEach((election) => {
                const orgId = election.organization_id || "general";
                const orgName = election.organization?.name || "University-wide / General";
                const orgCode = election.organization?.code || "GENERAL";
                const color = election.organization ? this.getOrgColor(election.organization) : "#374151";

                if (!groups[orgId]) {
                    groups[orgId] = {
                        orgId,
                        name: orgName,
                        code: orgCode,
                        color,
                        elections: [],
                        totalVoters: 0,
                    };
                }

                groups[orgId].elections.push(election);
                groups[orgId].totalVoters += (election.total_voters_who_voted || 0);
            });

            // Sort groups alphabetically by name, keeping general at the end
            return Object.values(groups).sort((a, b) => {
                if (a.orgId === "general") return 1;
                if (b.orgId === "general") return -1;
                return a.name.localeCompare(b.name);
            });
        },
    },
    async mounted() {
        await Promise.all([this.loadResults(), this.loadOrganizations()]);
    },
    methods: {
        async loadResults() {
            try {
                await this.electionStore.loadAdminResults({ sort: "org" });
            } catch (error) {
                console.error("Error loading results:", error);
                this.showError("Failed to load finished elections archive.");
            }
        },
        async loadOrganizations() {
            try {
                const response = await adminAPI.getOrganizations();
                this.organizationOptions = response.data.organizations || [];
            } catch (error) {
                console.error("Error loading organizations:", error);
            }
        },
        countElectionsForOrg(orgId) {
            return this.results.filter((e) => Number(e.organization_id) === Number(orgId)).length;
        },
        countTotalVotes(election) {
            if (!election.positions) return 0;
            return election.positions.reduce((sum, p) => {
                return sum + (p.candidates || []).reduce((cSum, c) => cSum + (c.vote_count || 0), 0);
            }, 0);
        },
        countWinners(election) {
            return this.getElectionWinners(election).length;
        },
        getElectionWinners(election) {
            if (election.winners && election.winners.length > 0) {
                return election.winners;
            }

            // Fallback calculation from positions
            const winners = [];
            if (!election.positions) return winners;

            election.positions.forEach((pos) => {
                const candidates = pos.candidates || [];
                if (candidates.length > 0) {
                    const top = candidates[0];
                    if (top.vote_count > 0) {
                        const isTie = candidates.length > 1 && candidates[1].vote_count === top.vote_count;
                        winners.push({
                            position_id: pos.id,
                            position_name: pos.name,
                            candidate_id: top.id,
                            candidate_name: top.name,
                            vote_count: top.vote_count,
                            percentage: top.percentage,
                            is_tie: isTie,
                        });
                    }
                }
            });

            return winners;
        },
        isTopCandidate(candidate, index) {
            return index === 0 && candidate.vote_count > 0;
        },
        positionTotalVotes(position) {
            return (position.candidates || []).reduce((sum, c) => sum + (c.vote_count || 0), 0);
        },
        toggleDetails(electionId) {
            this.expandedElections = {
                ...this.expandedElections,
                [electionId]: !this.expandedElections[electionId],
            };
        },
        getOrgColor(org) {
            if (!org) return "#116b27";
            const idx = (org.id || 1) % ORG_COLORS.length;
            return ORG_COLORS[idx];
        },
        resetFilters() {
            this.searchQuery = "";
            this.selectedScopeFilter = "all";
            this.selectedOrgFilter = "all";
            this.selectedCourseFilter = "all";
            this.sortBy = "org_asc";
        },
        formatDateTime(dateStr) {
            if (!dateStr) return "—";
            if (typeof dateStr === "string" && dateStr.includes("—")) {
                return dateStr;
            }
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;

            return date.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            });
        },
        getInitials(name) {
            if (!name) return "?";
            const parts = name.trim().split(" ");
            if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        },
        async deleteArchivedElection(election) {
            const confirmed = await this.showConfirmDangerous(
                `Are you sure you want to permanently delete the archive for "${election.title}"? All official results, candidate tallies, and vote records will be permanently removed. This action cannot be undone.`,
                {
                    title: "Delete Election Archive",
                    confirmText: "Delete Archive",
                }
            );

            if (!confirmed) return;

            try {
                await this.electionStore.deleteElection(election.id);
                this.showSuccess(`Archive for "${election.title}" was deleted successfully.`);
            } catch (error) {
                this.showError(error.response?.data?.message || error.message || "Failed to delete election archive.");
            }
        },
    },
};
</script>

<style scoped>
/* =========================================================
   Base Layout
   ========================================================= */
.results-page {
    padding: 0;
    max-width: 1400px;
    margin: 0 auto;
    color: #17231d;
}

/* =========================================================
   Page Header (Archive Hero)
   ========================================================= */
.page-header {
    background: linear-gradient(135deg, #0e4f1d 0%, #166534 50%, #15803d 100%);
    border-radius: 18px;
    padding: 34px 40px;
    margin-bottom: 26px;
    box-shadow: 0 8px 30px rgba(14, 79, 29, 0.22);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-icon-wrap {
    width: 62px;
    height: 62px;
    background: rgba(255,255,255,0.18);
    border: 1.5px solid rgba(255,255,255,0.35);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    backdrop-filter: blur(4px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}

.header-icon-svg {
    width: 32px;
    height: 32px;
}

.header-badge-row {
    margin-bottom: 6px;
}

.header-seal-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.18);
    color: #fef08a;
    border: 1px solid rgba(254, 240, 138, 0.4);
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.seal-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #facc15;
}

.page-title {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 4px;
    color: #fff;
    letter-spacing: -0.5px;
}

.page-subtitle {
    font-size: 13.5px;
    color: rgba(255,255,255,0.8);
    margin: 0;
}

/* =========================================================
   Archive Stats Grid
   ========================================================= */
.archive-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.07);
}

.stat-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon-wrap svg {
    width: 24px;
    height: 24px;
}

.stat-elections .stat-icon-wrap {
    background: #f0fdf4;
    color: #16a34a;
}

.stat-voters .stat-icon-wrap {
    background: #eff6ff;
    color: #2563eb;
}

.stat-votes .stat-icon-wrap {
    background: #faf5ff;
    color: #9333ea;
}

.stat-winners .stat-icon-wrap {
    background: #fffbeb;
    color: #d97706;
}

.stat-num {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    margin-top: 2px;
}

/* =========================================================
   Archive Toolbar (Sort, Filter, Search)
   ========================================================= */
.archive-toolbar {
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 280px;
}

.search-svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #94a3b8;
}

.search-input {
    width: 100%;
    height: 42px;
    padding: 0 36px 0 40px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    background: #f8fafc;
}

.search-input:focus {
    background: #fff;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
}

.clear-search-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: #e2e8f0;
    border: none;
    color: #64748b;
    cursor: pointer;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 0.15s ease;
}

.clear-search-btn:hover {
    background: #cbd5e1;
    color: #1e293b;
}

.clear-search-btn svg {
    width: 14px;
    height: 14px;
}

.toolbar-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toolbar-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.label-icon {
    width: 15px;
    height: 15px;
    color: #64748b;
}

.toolbar-select {
    height: 40px;
    padding: 0 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    background: #fff;
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s ease;
}

.toolbar-select:focus {
    border-color: #16a34a;
}

.view-toggle-group {
    display: flex;
    background: #f1f5f9;
    border-radius: 9px;
    padding: 3px;
    gap: 2px;
}

.toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    border-radius: 7px;
    padding: 7px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.18s ease;
}

.toggle-btn svg {
    width: 15px;
    height: 15px;
}

.toggle-btn-active {
    background: #fff;
    color: #0f172a;
    font-weight: 700;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

/* =========================================================
   Quick Category Pills
   ========================================================= */
.org-pills-row {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 6px;
    margin-bottom: 24px;
}

.org-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    padding: 7px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.org-pill:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.org-pill-active {
    background: #0e4f1d;
    border-color: #0e4f1d;
    color: #fff;
}

.pill-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.pill-count {
    background: rgba(0,0,0,0.06);
    padding: 1px 6px;
    border-radius: 10px;
    font-size: 11px;
}

.org-pill-active .pill-count {
    background: rgba(255,255,255,0.22);
    color: #fff;
}

/* =========================================================
   Grouped By Organization Layout
   ========================================================= */
.grouped-results {
    display: flex;
    flex-direction: column;
    gap: 36px;
}

.org-group-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 24px 28px;
}

.org-group-header {
    border-left: 5px solid #16a34a;
    padding-left: 16px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.org-group-title-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
}

.org-group-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
}

.org-group-name {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.org-group-sub {
    font-size: 13px;
    color: #64748b;
    margin: 2px 0 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.code-pill {
    background: #e2e8f0;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 6px;
}

.badge-total-voters {
    background: #dbeafe;
    color: #1e40af;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
}

.group-elections-grid,
.results-list-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* =========================================================
   Archive Card (Finished Election Card)
   ========================================================= */
.archive-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 3px 12px rgba(0,0,0,0.04);
    padding: 26px 28px;
    transition: all 0.22s ease;
}

.archive-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: #cbd5e1;
}

/* Banner */
.archive-card-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
}

.banner-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.org-pill-badge {
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.org-pill-badge.org-general {
    background: #475569;
}

.seal-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #dcfce7;
    color: #15803d;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
}

.seal-svg {
    width: 14px;
    height: 14px;
    color: #16a34a;
}

.immutable-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.lock-svg {
    width: 12px;
    height: 12px;
    color: #64748b;
}

.btn-delete-archive {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    color: #dc2626;
    border: 1.5px solid #fecaca;
    border-radius: 9px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-delete-archive:hover {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
    box-shadow: 0 3px 10px rgba(220, 38, 38, 0.2);
}

.btn-delete-archive svg {
    width: 14px;
    height: 14px;
}

/* Head content */
.card-head-content {
    margin-bottom: 20px;
}

.election-heading {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px;
}

.election-summary {
    font-size: 13.5px;
    color: #64748b;
    margin: 0 0 10px;
    line-height: 1.45;
}

.meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
    flex-wrap: wrap;
}

.meta-item strong {
    color: #334155;
}

.meta-sep {
    color: #cbd5e1;
}

/* =========================================================
   Voter Turnout & Participation Strip
   ========================================================= */
.voter-turnout-strip {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 22px;
}

.turnout-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 4px 8px;
}

.primary-turnout {
    flex-direction: row;
    align-items: center;
    gap: 14px;
    border-right: 1.5px solid #e2e8f0;
    padding-right: 18px;
}

.turnout-icon-wrap {
    width: 44px;
    height: 44px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.turnout-icon-wrap svg {
    width: 24px;
    height: 24px;
}

.turnout-top-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.turnout-big-number {
    font-size: 26px;
    font-weight: 900;
    color: #1e3a8a;
    line-height: 1;
}

.turnout-pct-badge {
    background: #dcfce7;
    color: #15803d;
    font-size: 11.5px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 12px;
}

.turnout-sublabel {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    margin-top: 2px;
    display: block;
}

.metric-num {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}

.metric-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-top: 2px;
}

/* =========================================================
   Winners Showcase Panel (View Winners)
   ========================================================= */
.winners-showcase-panel {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fde68a;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 22px;
}

.winners-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}

.winners-title-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.trophy-icon-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #fef08a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #b45309;
    flex-shrink: 0;
}

.trophy-svg {
    width: 18px;
    height: 18px;
}

.badge-user-svg {
    width: 15px;
    height: 15px;
    color: #15803d;
}

.winner-check-svg {
    width: 13px;
    height: 13px;
    margin-right: 3px;
}

.winners-title {
    font-size: 16px;
    font-weight: 800;
    color: #92400e;
    margin: 0;
}

.winners-count-badge {
    background: #fef08a;
    color: #854d0e;
    font-size: 11.5px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 14px;
}

.winners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px;
}

.winner-card {
    background: #fff;
    border-radius: 11px;
    border: 1.5px solid #fde68a;
    padding: 14px 16px;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.08);
}

.winner-card.is-tied {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.winner-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.winner-position-label {
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #b45309;
}

.winner-chip {
    background: #15803d;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 8px;
    letter-spacing: 0.4px;
}

.tie-badge {
    background: #64748b;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 8px;
}

.winner-person-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.winner-avatar {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #d97706, #b45309);
    border-radius: 50%;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.winner-name {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    display: block;
    line-height: 1.25;
}

.winner-stats-line {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.winner-votes {
    font-weight: 700;
    color: #047857;
}

.no-winners-box {
    padding: 12px;
    text-align: center;
    font-size: 13px;
    color: #92400e;
}

/* =========================================================
   Detailed Position Tally
   ========================================================= */
.detailed-tally-section {
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
}

.btn-toggle-tally {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-toggle-tally:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.arrow-icon {
    width: 16px;
    height: 16px;
    transition: transform 0.2s ease;
}

.arrow-up {
    transform: rotate(180deg);
}

.positions-detail-container {
    margin-top: 18px;
    display: grid;
    gap: 18px;
}

.position-detail-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
}

.position-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.position-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.position-total-votes {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}

.candidates-tally-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.candidate-tally-row {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 12px 14px;
}

.candidate-tally-row.is-winner-row {
    border-color: #86efac;
    background: #f0fdf4;
}

.cand-row-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.cand-name-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cand-rank {
    font-size: 12px;
    font-weight: 800;
    color: #94a3b8;
}

.cand-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
}

.elected-tag {
    background: #16a34a;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    padding: 1px 7px;
    border-radius: 6px;
}

.cand-votes {
    font-size: 12.5px;
    color: #475569;
}

.cand-progress-track {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.cand-progress-fill {
    height: 100%;
    background: #94a3b8;
    border-radius: 4px;
    transition: width 0.4s ease;
}

.cand-progress-fill.fill-winner {
    background: linear-gradient(90deg, #16a34a, #22c55e);
}

/* =========================================================
   Empty State
   ========================================================= */
.empty-state {
    background: #fff;
    border-radius: 16px;
    border: 1.5px dashed #cbd5e1;
    padding: 60px 40px;
    text-align: center;
    max-width: 600px;
    margin: 30px auto;
}

.empty-icon-wrapper {
    width: 72px;
    height: 72px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    color: #64748b;
}

.empty-icon {
    width: 36px;
    height: 36px;
}

.empty-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px;
}

.empty-message {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
    margin: 0 0 22px;
}

.btn-clear-filter {
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 10px 22px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
}

.btn-manage-link {
    display: inline-block;
    background: #16a34a;
    color: #fff;
    border-radius: 9px;
    padding: 10px 22px;
    font-size: 13.5px;
    font-weight: 700;
    text-decoration: none;
}

/* Loading */
.loading-container {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #16a34a;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .archive-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .voter-turnout-strip {
        grid-template-columns: 1fr 1fr;
    }
    .primary-turnout {
        border-right: none;
        border-bottom: 1.5px solid #e2e8f0;
        grid-column: span 2;
        padding-bottom: 12px;
    }
}

@media (max-width: 640px) {
    .archive-stats-grid {
        grid-template-columns: 1fr;
    }
    .voter-turnout-strip {
        grid-template-columns: 1fr;
    }
    .primary-turnout {
        grid-column: span 1;
    }
    .page-header {
        padding: 24px 20px;
    }
}

/* ==================== Scope Pills ==================== */
.scope-pill-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.scope-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.scope-pill svg {
    width: 13px;
    height: 13px;
}
.scope-pill-course {
    background: #ede9fe;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
}
.scope-pill-org {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.scope-pill-global {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.org-course {
    background: #7c3aed !important;
}
</style>
