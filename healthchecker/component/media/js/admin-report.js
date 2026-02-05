/**
 * Health Checker Component - Report Admin JavaScript
 *
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

(function(window, document) {
    'use strict';

    /**
     * Initialize the health checker report
     * @param {Object} options Configuration options
     * @param {string} options.metadataUrl AJAX endpoint for metadata
     * @param {string} options.checkUrl AJAX endpoint for single check
     * @param {string} options.categoryUrl AJAX endpoint for category checks
     * @param {Object} options.translations Text translations
     */
    window.HealthCheckerReport = {
        init: function(options) {
            const metadataUrl = options.metadataUrl;
            const checkUrl = options.checkUrl;
            const categoryUrl = options.categoryUrl;
            const STORAGE_KEY = 'healthchecker_filters';
            const translations = options.translations || {};

            let healthCheckData = {
                categories: {},
                providers: {},
                checks: [],
                results: {}
            };

            let checksCompleted = 0;
            let checksTotal = 0;
            const cacheBuster = Date.now();

            function saveFilters() {
                const filters = {
                    search: document.getElementById('filter_search').value,
                    status: document.getElementById('filter_status').value,
                    category: document.getElementById('filter_category').value
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(filters));

                // Update URL without reload
                const url = new URL(window.location);
                if (filters.search) {
                    url.searchParams.set('search', filters.search);
                } else {
                    url.searchParams.delete('search');
                }
                if (filters.status) {
                    url.searchParams.set('status', filters.status);
                } else {
                    url.searchParams.delete('status');
                }
                if (filters.category) {
                    url.searchParams.set('category', filters.category);
                } else {
                    url.searchParams.delete('category');
                }
                window.history.replaceState({}, '', url);
            }

            function restoreFilters() {
                // Priority: URL params > localStorage
                const url = new URL(window.location);
                const urlSearch = url.searchParams.get('search');
                const urlStatus = url.searchParams.get('status');
                const urlCategory = url.searchParams.get('category');

                let filters = { search: '', status: '', category: '' };

                // Try localStorage first
                const stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    try {
                        filters = JSON.parse(stored);
                    } catch (e) {}
                }

                // URL params override localStorage
                if (urlSearch !== null) filters.search = urlSearch;
                if (urlStatus !== null) filters.status = urlStatus;
                if (urlCategory !== null) filters.category = urlCategory;

                // Apply to form elements
                document.getElementById('filter_search').value = filters.search || '';
                document.getElementById('filter_status').value = filters.status || '';
                // Category will be set after metadata loads
                window._pendingCategoryFilter = filters.category || '';
            }

            async function runHealthChecks() {
                const loadingEl = document.getElementById('health-check-loading');
                const errorEl = document.getElementById('health-check-error');
                const resultsEl = document.getElementById('health-check-results');
                const progressEl = document.getElementById('loading-progress');

                loadingEl.classList.remove('d-none');
                errorEl.classList.add('d-none');
                resultsEl.classList.add('d-none');

                document.getElementById('critical-count').textContent = '-';
                document.getElementById('warning-count').textContent = '-';
                document.getElementById('good-count').textContent = '-';

                healthCheckData = {
                    categories: {},
                    providers: {},
                    checks: [],
                    results: {}
                };
                checksCompleted = 0;
                checksTotal = 0;

                try {
                    // First, fetch metadata (categories, providers, check list)
                    progressEl.textContent = translations.loading || 'Loading...';
                    const metaResponse = await fetch(metadataUrl + '&_=' + cacheBuster, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    const metaData = await metaResponse.json();

                    if (!metaData.success) {
                        throw new Error(metaData.message || 'Failed to load metadata');
                    }

                    healthCheckData.categories = metaData.data.categories;
                    healthCheckData.providers = metaData.data.providers;
                    healthCheckData.checks = metaData.data.checks;
                    checksTotal = healthCheckData.checks.length;

                    populateCategoryFilter(healthCheckData.categories);

                    // Show the results container and render initial structure
                    resultsEl.classList.remove('d-none');
                    renderCategories();
                    renderProviders(healthCheckData.providers);

                    // Run checks by category (8 requests instead of 126+)
                    const categories = Object.keys(healthCheckData.categories);
                    const categoriesTotal = categories.length;
                    progressEl.textContent = `0 / ${categoriesTotal} categories`;

                    const categoryPromises = categories.map((categorySlug, index) =>
                        runCategoryChecks(categorySlug, index + 1, categoriesTotal, progressEl)
                    );
                    await Promise.all(categoryPromises);

                    loadingEl.classList.add('d-none');

                    // Final render with all results
                    renderCategories();
                    updateSummaryCounts();

                    document.getElementById('last-checked').textContent =
                        (translations.lastChecked || 'Last checked: %s').replace('%s', new Date().toLocaleString());

                } catch (error) {
                    loadingEl.classList.add('d-none');
                    errorEl.classList.remove('d-none');
                    document.getElementById('health-check-error-message').textContent = error.message || 'Network error';
                }
            }

            async function runCategoryChecks(categorySlug, categoryNumber, categoriesTotal, progressEl) {
                try {
                    progressEl.textContent = `Running ${healthCheckData.categories[categorySlug].label || categorySlug} (${categoryNumber}/${categoriesTotal})`;

                    const formData = new FormData();
                    formData.append('category', categorySlug);

                    const response = await fetch(categoryUrl + '&_=' + cacheBuster, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });
                    const data = await response.json();

                    if (data.success && data.data && data.data.results) {
                        // Update all results from this category
                        Object.entries(data.data.results).forEach(([checkSlug, result]) => {
                            healthCheckData.results[checkSlug] = result;
                            updateCheckRow(checkSlug, result);
                        });
                        updateSummaryCounts();
                    }
                } catch (error) {
                    console.error(`Failed to run category ${categorySlug}:`, error);
                }
            }

            async function runSingleCheck(slug, progressEl) {
                try {
                    const response = await fetch(`${checkUrl}&slug=${encodeURIComponent(slug)}&_=${cacheBuster}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    const data = await response.json();

                    if (data.success && data.data) {
                        healthCheckData.results[slug] = data.data;
                        updateCheckRow(slug, data.data);
                        updateSummaryCounts();
                    } else {
                        healthCheckData.results[slug] = {
                            slug: slug,
                            status: 'warning',
                            title: slug,
                            description: data.message || 'Check failed',
                            category: getCheckCategory(slug),
                            provider: 'core'
                        };
                        updateCheckRow(slug, healthCheckData.results[slug]);
                    }
                } catch (error) {
                    healthCheckData.results[slug] = {
                        slug: slug,
                        status: 'warning',
                        title: slug,
                        description: 'Error: ' + (error.message || 'Unknown error'),
                        category: getCheckCategory(slug),
                        provider: 'core'
                    };
                    updateCheckRow(slug, healthCheckData.results[slug]);
                }

                checksCompleted++;
                if (progressEl) {
                    progressEl.textContent = `${checksCompleted} / ${checksTotal}`;
                }
            }

            function getCheckCategory(slug) {
                const check = healthCheckData.checks.find(c => c.slug === slug);
                return check ? check.category : 'system';
            }

            function updateCheckRow(slug, result) {
                const row = document.getElementById(`check-row-${slug.replace(/\./g, '-')}`);
                if (!row) return;

                const statusInfo = getStatusInfo(result.status);
                const provider = healthCheckData.providers[result.provider];
                const isThirdParty = result.provider !== 'core';
                let providerBadge = '';
                if (isThirdParty && provider) {
                    providerBadge = `<span class="badge bg-secondary hasTooltip" title="${escapeHtml(provider.name + (provider.version ? ' v' + provider.version : ''))}">${escapeHtml(provider.name)}</span>`;
                }

                // Build action buttons (action + docs) for the right side
                let actionButtonsHtml = '';
                if (result.actionUrl) {
                    actionButtonsHtml += `<a href="${escapeHtml(result.actionUrl)}" class="btn btn-sm btn-outline-primary">${translations.explore || 'Explore'}</a>`;
                }
                if (result.docsUrl) {
                    actionButtonsHtml += `<a href="${escapeHtml(result.docsUrl)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary ms-1 healthchecker-no-external-icon">${translations.docs || 'Docs'}</a>`;
                }

                row.innerHTML = `
                    <td>
                        <span class="badge ${statusInfo.badgeClass}">
                            <span class="fa ${statusInfo.icon}" aria-hidden="true"></span>
                            ${statusInfo.label}
                        </span>
                    </td>
                    <td>${escapeHtml(result.title)}</td>
                    <td>${providerBadge}</td>
                    <td class="healthchecker-description">${result.description}</td>
                    <td class="text-end text-nowrap">${actionButtonsHtml}</td>
                `;

                // Update card border based on results
                updateCategoryCardBorder(result.category);
            }

            function updateCategoryCardBorder(categorySlug) {
                const card = document.getElementById(`category-card-${categorySlug}`);
                if (!card) return;

                const categoryResults = Object.values(healthCheckData.results).filter(r => r.category === categorySlug);
                const hasCritical = categoryResults.some(r => r.status === 'critical');
                const hasWarning = categoryResults.some(r => r.status === 'warning');

                card.classList.remove('border-danger', 'border-warning', 'border-success');
                if (hasCritical) {
                    card.classList.add('border-danger');
                } else if (hasWarning) {
                    card.classList.add('border-warning');
                } else if (categoryResults.length > 0) {
                    card.classList.add('border-success');
                }

                // Update badges in header
                updateCategoryBadges(categorySlug);
            }

            function updateCategoryBadges(categorySlug) {
                const badgeContainer = document.getElementById(`category-badges-${categorySlug}`);
                if (!badgeContainer) return;

                const categoryResults = Object.values(healthCheckData.results).filter(r => r.category === categorySlug);
                const criticalCount = categoryResults.filter(r => r.status === 'critical').length;
                const warningCount = categoryResults.filter(r => r.status === 'warning').length;
                const goodCount = categoryResults.filter(r => r.status === 'good').length;

                let badgesHtml = '';
                if (criticalCount > 0) badgesHtml += `<span class="badge bg-danger">${criticalCount} ${translations.critical || 'Critical'}</span> `;
                if (warningCount > 0) badgesHtml += `<span class="badge bg-warning text-dark">${warningCount} ${translations.warning || 'Warning'}</span> `;
                if (goodCount > 0) badgesHtml += `<span class="badge bg-success">${goodCount} ${translations.good || 'Good'}</span>`;

                badgeContainer.innerHTML = badgesHtml;
            }

            function updateSummaryCounts() {
                const results = Object.values(healthCheckData.results);
                const critical = results.filter(r => r.status === 'critical').length;
                const warning = results.filter(r => r.status === 'warning').length;
                const good = results.filter(r => r.status === 'good').length;

                document.getElementById('critical-count').textContent = critical;
                document.getElementById('warning-count').textContent = warning;
                document.getElementById('good-count').textContent = good;
            }

            function populateCategoryFilter(categories) {
                const select = document.getElementById('filter_category');

                while (select.options.length > 1) {
                    select.remove(1);
                }

                Object.values(categories).sort((a, b) => a.sortOrder - b.sortOrder).forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.slug;
                    option.textContent = cat.label;
                    select.appendChild(option);
                });

                // Restore pending category filter after options are populated
                if (window._pendingCategoryFilter) {
                    select.value = window._pendingCategoryFilter;
                    window._pendingCategoryFilter = '';
                }
            }

            function renderCategories() {
                const container = document.getElementById('healthCheckCategories');
                container.innerHTML = '';

                const searchFilter = document.getElementById('filter_search').value.toLowerCase().trim();
                const statusFilter = document.getElementById('filter_status').value;
                const categoryFilter = document.getElementById('filter_category').value;

                const sortedCategories = Object.values(healthCheckData.categories).sort((a, b) => a.sortOrder - b.sortOrder);

                sortedCategories.forEach(category => {
                    if (categoryFilter && category.slug !== categoryFilter) {
                        return;
                    }

                    // Get checks for this category
                    let categoryChecks = healthCheckData.checks.filter(c => c.category === category.slug);

                    if (categoryChecks.length === 0) {
                        return;
                    }

                    // Get results for this category (if any)
                    const categoryResults = Object.values(healthCheckData.results).filter(r => r.category === category.slug);

                    // Apply status filter to results
                    let filteredResults = categoryResults;
                    if (statusFilter) {
                        if (statusFilter === 'hide_good') {
                            filteredResults = categoryResults.filter(r => r.status !== 'good');
                        } else {
                            filteredResults = categoryResults.filter(r => r.status === statusFilter);
                        }
                        // If filtering by status and no results match, skip this category
                        if (filteredResults.length === 0 && categoryResults.length > 0) {
                            return;
                        }
                    }

                    // Apply search filter
                    if (searchFilter) {
                        filteredResults = filteredResults.filter(r =>
                            r.title.toLowerCase().includes(searchFilter) ||
                            r.description.toLowerCase().includes(searchFilter) ||
                            r.slug.toLowerCase().includes(searchFilter)
                        );
                        if (filteredResults.length === 0) {
                            return;
                        }
                    }

                    const criticalCount = categoryResults.filter(r => r.status === 'critical').length;
                    const warningCount = categoryResults.filter(r => r.status === 'warning').length;
                    const goodCount = categoryResults.filter(r => r.status === 'good').length;

                    let borderClass = '';
                    if (criticalCount > 0) borderClass = 'border-danger';
                    else if (warningCount > 0) borderClass = 'border-warning';
                    else if (goodCount > 0) borderClass = 'border-success';

                    let badgesHtml = '';
                    if (criticalCount > 0) badgesHtml += `<span class="badge bg-danger">${criticalCount} ${translations.critical || 'Critical'}</span> `;
                    if (warningCount > 0) badgesHtml += `<span class="badge bg-warning text-dark">${warningCount} ${translations.warning || 'Warning'}</span> `;
                    if (goodCount > 0) badgesHtml += `<span class="badge bg-success">${goodCount} ${translations.good || 'Good'}</span>`;

                    // Filter checks to show based on filters
                    let checksToShow = categoryChecks;
                    if (statusFilter || searchFilter) {
                        const matchingSlugs = filteredResults.map(r => r.slug);
                        checksToShow = categoryChecks.filter(c => {
                            const result = healthCheckData.results[c.slug];
                            if (!result) return false;

                            let matchesStatus = true;
                            if (statusFilter) {
                                if (statusFilter === 'hide_good') {
                                    matchesStatus = result.status !== 'good';
                                } else {
                                    matchesStatus = result.status === statusFilter;
                                }
                            }
                            let matchesSearch = !searchFilter ||
                                result.title.toLowerCase().includes(searchFilter) ||
                                result.description.toLowerCase().includes(searchFilter) ||
                                result.slug.toLowerCase().includes(searchFilter);

                            return matchesStatus && matchesSearch;
                        });
                    }

                    let rowsHtml = '';
                    checksToShow.forEach(check => {
                        const result = healthCheckData.results[check.slug];
                        const rowId = `check-row-${check.slug.replace(/\./g, '-')}`;

                        if (result) {
                            const statusInfo = getStatusInfo(result.status);
                            const provider = healthCheckData.providers[result.provider];
                            const isThirdParty = result.provider !== 'core';
                            let providerBadge = '';
                            if (isThirdParty && provider) {
                                providerBadge = `<span class="badge bg-secondary hasTooltip" title="${escapeHtml(provider.name + (provider.version ? ' v' + provider.version : ''))}">${escapeHtml(provider.name)}</span>`;
                            }

                            // Build action buttons (action + docs) for the right side
                            let actionButtonsHtml = '';
                            if (result.actionUrl) {
                                actionButtonsHtml += `<a href="${escapeHtml(result.actionUrl)}" class="btn btn-sm btn-outline-primary">${translations.explore || 'Explore'}</a>`;
                            }
                            if (result.docsUrl) {
                                actionButtonsHtml += `<a href="${escapeHtml(result.docsUrl)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary ms-1 healthchecker-no-external-icon">${translations.docs || 'Docs'}</a>`;
                            }

                            rowsHtml += `
                                <tr id="${rowId}">
                                    <td>
                                        <span class="badge ${statusInfo.badgeClass}">
                                            <span class="fa ${statusInfo.icon}" aria-hidden="true"></span>
                                            ${statusInfo.label}
                                        </span>
                                    </td>
                                    <td>${escapeHtml(result.title)}</td>
                                    <td>${providerBadge}</td>
                                    <td class="healthchecker-description">${result.description}</td>
                                    <td class="text-end text-nowrap">${actionButtonsHtml}</td>
                                </tr>
                            `;
                        } else {
                            // Show pending state
                            rowsHtml += `
                                <tr id="${rowId}">
                                    <td>
                                        <span class="badge bg-secondary">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                    </td>
                                    <td>${escapeHtml(check.title)}</td>
                                    <td></td>
                                    <td class="text-muted">${translations.runningChecks || 'Running checks...'}</td>
                                    <td></td>
                                </tr>
                            `;
                        }
                    });

                    const categoryLabel = category.label;

                    let categoryIconHtml = '';
                    if (category.logoUrl) {
                        categoryIconHtml = `<img src="${escapeHtml(category.logoUrl)}" alt="${escapeHtml(categoryLabel)} icon" class="me-2" style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px;">`;
                    } else if (category.icon) {
                        categoryIconHtml = `<span class="fa ${category.icon} me-2" aria-hidden="true"></span>`;
                    }

                    // Check if this category should be collapsed (from localStorage)
                    const isCollapsed = localStorage.getItem(`healthchecker-category-${category.slug}-collapsed`) === 'true';
                    const collapseClass = isCollapsed ? '' : 'show';
                    const chevronClass = isCollapsed ? 'fa-chevron-right' : 'fa-chevron-down';

                    container.innerHTML += `
                        <div class="card mb-3 ${borderClass}" id="category-card-${category.slug}">
                            <div class="card-header d-flex justify-content-between align-items-center"
                                 style="cursor: pointer;"
                                 role="button"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#category-collapse-${category.slug}"
                                 aria-expanded="${!isCollapsed}"
                                 aria-controls="category-collapse-${category.slug}">
                                <h5 class="mb-0 d-flex align-items-center">
                                    <span class="fa ${chevronClass} me-2" aria-hidden="true" id="category-chevron-${category.slug}"></span>
                                    ${categoryIconHtml}
                                    ${escapeHtml(categoryLabel)}
                                </h5>
                                <span id="category-badges-${category.slug}">${badgesHtml}</span>
                            </div>
                            <div class="collapse ${collapseClass}" id="category-collapse-${category.slug}">
                                <div class="card-body p-0">
                                    <table class="table table-striped mb-0">
                                        <colgroup>
                                            <col style="width: 100px;">
                                            <col style="width: 300px;">
                                            <col style="width: 150px;">
                                            <col>
                                            <col style="width: 140px;">
                                        </colgroup>
                                        <tbody>${rowsHtml}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                });

                new bootstrap.Tooltip(document.body, {
                    selector: '.hasTooltip'
                });

                // Add event listeners for collapse state persistence
                sortedCategories.forEach(category => {
                    const collapseElement = document.getElementById(`category-collapse-${category.slug}`);
                    const chevronElement = document.getElementById(`category-chevron-${category.slug}`);

                    if (collapseElement && chevronElement) {
                        collapseElement.addEventListener('shown.bs.collapse', function () {
                            localStorage.setItem(`healthchecker-category-${category.slug}-collapsed`, 'false');
                            chevronElement.classList.remove('fa-chevron-right');
                            chevronElement.classList.add('fa-chevron-down');
                        });

                        collapseElement.addEventListener('hidden.bs.collapse', function () {
                            localStorage.setItem(`healthchecker-category-${category.slug}-collapsed`, 'true');
                            chevronElement.classList.remove('fa-chevron-down');
                            chevronElement.classList.add('fa-chevron-right');
                        });
                    }
                });
            }

            function renderProviders(providers) {
                const thirdParty = Object.values(providers).filter(p => p.slug !== 'core');
                const container = document.getElementById('third-party-providers');
                const list = document.getElementById('providers-list');

                if (thirdParty.length === 0) {
                    container.classList.add('d-none');
                    return;
                }

                container.classList.remove('d-none');
                list.innerHTML = '';

                thirdParty.forEach(provider => {
                    let iconHtml = '';
                    if (provider.logoUrl) {
                        iconHtml = `<img src="${escapeHtml(provider.logoUrl)}" alt="${escapeHtml(provider.name)} logo" class="me-2" style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px;">`;
                    } else if (provider.icon) {
                        iconHtml = `<span class="fa ${provider.icon} me-2 fs-4" aria-hidden="true"></span>`;
                    }

                    let nameHtml = escapeHtml(provider.name);
                    if (provider.url) {
                        nameHtml = `<a href="${escapeHtml(provider.url)}" target="_blank" rel="noopener">${nameHtml} <span class="icon-out-2 small" aria-hidden="true"></span></a>`;
                    }

                    list.innerHTML += `
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-start">
                                ${iconHtml}
                                <div>
                                    <strong>${nameHtml}</strong>
                                    ${provider.version ? `<span class="text-muted">v${escapeHtml(provider.version)}</span>` : ''}
                                    ${provider.description ? `<div class="small text-muted">${escapeHtml(provider.description)}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            function applyFilters() {
                saveFilters();
                updateCardStates();
                renderCategories();
            }

            function updateCardStates() {
                const statusFilter = document.getElementById('filter_status').value;
                document.querySelectorAll('.status-filter-card').forEach(card => {
                    const cardStatus = card.dataset.status;
                    card.classList.remove('active', 'dimmed');
                    if (statusFilter) {
                        if (statusFilter === cardStatus) {
                            card.classList.add('active');
                        } else if (statusFilter !== 'hide_good' || cardStatus === 'good') {
                            card.classList.add('dimmed');
                        }
                        // For hide_good, dim only the good card
                        if (statusFilter === 'hide_good' && cardStatus === 'good') {
                            card.classList.add('dimmed');
                        }
                    }
                });
            }

            function getStatusInfo(status) {
                const statusMap = {
                    critical: {
                        label: translations.critical || 'Critical',
                        icon: 'fa-times-circle',
                        badgeClass: 'bg-danger'
                    },
                    warning: {
                        label: translations.warning || 'Warning',
                        icon: 'fa-exclamation-triangle',
                        badgeClass: 'bg-warning text-dark'
                    },
                    good: {
                        label: translations.good || 'Good',
                        icon: 'fa-check-circle',
                        badgeClass: 'bg-success'
                    }
                };
                return statusMap[status] || statusMap.good;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Start initialization
            restoreFilters();
            updateCardStates();
            runHealthChecks();

            // Set up event listeners
            document.getElementById('filter_search').addEventListener('input', applyFilters);
            document.getElementById('filter_status').addEventListener('change', applyFilters);
            document.getElementById('filter_category').addEventListener('change', applyFilters);

            // Click on status cards to filter
            document.querySelectorAll('.status-filter-card').forEach(card => {
                card.addEventListener('click', function() {
                    const status = this.dataset.status;
                    const filterSelect = document.getElementById('filter_status');
                    // Toggle: if already selected, clear filter
                    if (filterSelect.value === status) {
                        filterSelect.value = '';
                    } else {
                        filterSelect.value = status;
                    }
                    applyFilters();
                });
            });

            // Retry button
            const retryBtn = document.getElementById('retry-health-check');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    runHealthChecks();
                });
            }

            // Expose runHealthChecks globally for toolbar button
            window.runHealthChecks = runHealthChecks;
        }
    };

    // Auto-initialization on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('adminForm');
        if (!form) return;

        const metadataUrl = form.dataset.metadataUrl;
        const checkUrl = form.dataset.checkUrl;
        const categoryUrl = form.dataset.categoryUrl;

        if (!metadataUrl || !checkUrl || !categoryUrl) return;

        window.HealthCheckerReport.init({
            metadataUrl: metadataUrl,
            checkUrl: checkUrl,
            categoryUrl: categoryUrl,
            translations: {
                loading: form.dataset.textLoading || 'Loading...',
                lastChecked: form.dataset.textLastChecked || 'Last checked: %s',
                runningChecks: form.dataset.textRunningChecks || 'Running checks...',
                critical: form.dataset.textCritical || 'Critical',
                warning: form.dataset.textWarning || 'Warning',
                good: form.dataset.textGood || 'Good',
                viewDocs: form.dataset.textViewDocs || 'View Documentation'
            }
        });
    });
})(window, document);
