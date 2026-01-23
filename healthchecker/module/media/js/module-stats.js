/**
 * Health Checker Module - Stats Loading and Refresh
 *
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

(function(window, document) {
    'use strict';

    // Auto-initialize all modules on page
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.mod-healthchecker').forEach(function(moduleEl) {
            const moduleId = moduleEl.id;
            const statsUrl = moduleEl.dataset.statsUrl;
            const lastCheckedText = moduleEl.dataset.lastCheckedText;

            if (!moduleId || !statsUrl) {
                return;
            }

            const loadingEl = document.getElementById(moduleId + '-loading');
            const errorEl = document.getElementById(moduleId + '-error');
            const errorMsgEl = document.getElementById(moduleId + '-error-message');
            const resultsEl = document.getElementById(moduleId + '-results');
            const timestampEl = document.getElementById(moduleId + '-timestamp');

            async function loadStats() {
                try {
                    const response = await fetch(statsUrl, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });

                    // Hide loading state
                    loadingEl.classList.add('d-none');

                    // Check HTTP response status
                    if (!response.ok) {
                        const statusText = response.statusText || 'HTTP Error';
                        throw new Error(`${statusText} (${response.status})`);
                    }

                    // Get response text first to help debug JSON parsing errors
                    const responseText = await response.text();

                    // Try to parse as JSON
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        // Extract useful error info from malformed response
                        const preview = responseText.substring(0, 100).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        throw new Error(`Invalid JSON response: ${preview}${responseText.length > 100 ? '...' : ''}`);
                    }

                    // Check API success status
                    if (!data.success) {
                        errorMsgEl.textContent = data.message || 'Unknown error occurred';
                        errorEl.classList.remove('d-none');
                        return;
                    }

                    // Validate data structure
                    if (!data.data || typeof data.data !== 'object') {
                        throw new Error('Invalid response data structure');
                    }

                    // Update counts
                    const criticalEl = document.getElementById(moduleId + '-critical');
                    const warningEl = document.getElementById(moduleId + '-warning');
                    const goodEl = document.getElementById(moduleId + '-good');

                    if (criticalEl) criticalEl.textContent = data.data.critical || 0;
                    if (warningEl) warningEl.textContent = data.data.warning || 0;
                    if (goodEl) goodEl.textContent = data.data.good || 0;

                    // Show results
                    resultsEl.classList.remove('d-none');

                    // Update timestamp
                    if (data.data.lastRun) {
                        const lastRun = new Date(data.data.lastRun);
                        const formattedTime = lastRun.toLocaleString(undefined, {
                            dateStyle: 'medium',
                            timeStyle: 'medium'
                        });
                        timestampEl.textContent = lastCheckedText.replace('%s', formattedTime);
                        timestampEl.classList.remove('d-none');
                    }

                } catch (error) {
                    // Ensure loading state is hidden
                    loadingEl.classList.add('d-none');

                    // Determine error type and message
                    let errorMessage;
                    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                        errorMessage = 'Network error: Unable to connect to server';
                    } else if (error.message) {
                        errorMessage = error.message;
                    } else {
                        errorMessage = 'An unexpected error occurred';
                    }

                    // Display error
                    errorMsgEl.textContent = errorMessage;
                    errorEl.classList.remove('d-none');

                    // Log to console for debugging
                    console.error('Health Checker Module Error:', error);
                }
            }

            // Handle view transitions for card links
            function setupViewTransitions() {
                const cards = document.querySelectorAll('#' + moduleId + ' .healthchecker-card');
                cards.forEach(card => {
                    card.addEventListener('click', async (e) => {
                        if (!document.startViewTransition) {
                            return; // Fallback to normal navigation
                        }
                        e.preventDefault();
                        const href = card.getAttribute('href');
                        document.startViewTransition(() => {
                            window.location.href = href;
                        });
                    });
                });
            }

            // Handle refresh button
            function setupRefreshButton() {
                const refreshBtn = document.getElementById(moduleId + '-refresh');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', async () => {
                        refreshBtn.disabled = true;
                        const iconEl = refreshBtn.querySelector('.icon-refresh');
                        if (iconEl) iconEl.classList.add('fa-spin');

                        // Hide any existing results and errors
                        resultsEl.classList.add('d-none');
                        errorEl.classList.add('d-none');
                        loadingEl.classList.remove('d-none');

                        try {
                            const clearUrl = statsUrl.replace('task=ajax.stats', 'task=ajax.clearCache');
                            const clearResponse = await fetch(clearUrl);

                            if (!clearResponse.ok) {
                                throw new Error('Failed to clear cache');
                            }

                            await loadStats();
                        } catch (error) {
                            loadingEl.classList.add('d-none');
                            errorMsgEl.textContent = 'Failed to refresh: ' + (error.message || 'Unknown error');
                            errorEl.classList.remove('d-none');
                            console.error('Health Checker Refresh Error:', error);
                        } finally {
                            refreshBtn.disabled = false;
                            if (iconEl) iconEl.classList.remove('fa-spin');
                        }
                    });
                }
            }

            // Initialize this module instance
            loadStats();
            setupViewTransitions();
            setupRefreshButton();
        });
    });
})(window, document);
