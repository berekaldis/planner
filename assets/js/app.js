/**
 * Kaldis Coffee PLC - Strategic Planning & Reporting System
 * Frontend Interactions, Theme Controller, Sidebar Toggle & Live Search
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Bootstrap Tooltips
    initTooltips();

    // 2. Initialize Theme (Light Mode Default, with Dark Mode Option)
    initTheme();

    // 3. Initialize Sidebar Toggle & State
    initSidebar();

    // 4. Initialize Live Table Search Filters
    initLiveSearch();

    // 5. Global Keyboard Shortcuts (Alt+S for Sidebar, Alt+D for Dark Mode)
    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            toggleSidebar();
        }
        if (e.altKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            toggleTheme();
        }
    });

    // 7. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});

/**
 * Initialize Tooltips
 */
function initTooltips() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Theme Controller (Light Mode Default with Dark Mode Toggle)
 */
function initTheme() {
    const savedTheme = localStorage.getItem('kaldis_theme') || 'light';
    applyTheme(savedTheme);

    const themeToggleBtn = document.getElementById('themeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleTheme);
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
    localStorage.setItem('kaldis_theme', newTheme);
    showToast(`Switched to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'info');
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    const themeIcon = document.getElementById('themeToggleIcon');
    if (themeIcon) {
        if (theme === 'dark') {
            themeIcon.className = 'fas fa-sun text-warning';
        } else {
            themeIcon.className = 'fas fa-moon text-secondary';
        }
    }
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
}

/**
 * Helper to escape HTML characters
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Sidebar Collapse & Expand Controller
 */
function initSidebar() {
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed && window.innerWidth >= 992) {
        document.documentElement.classList.add('sidebar-collapsed');
    }

    const toggleButtons = document.querySelectorAll('#sidebarToggle, #sidebarCollapseBtn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', toggleSidebar);
    });

    // Mobile Backdrop click closes mobile sidebar
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            document.documentElement.classList.remove('mobile-sidebar-open');
        });
    }

    updateSidebarToggleIcon();
}

function toggleSidebar() {
    if (window.innerWidth < 992) {
        // Mobile behavior: toggle drawer
        document.documentElement.classList.toggle('mobile-sidebar-open');
    } else {
        // Desktop behavior: collapse / expand
        const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed);
        updateSidebarToggleIcon();
    }
}

function updateSidebarToggleIcon() {
    const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
    const collapseIcon = document.getElementById('sidebarCollapseIcon');
    if (collapseIcon) {
        if (isCollapsed) {
            collapseIcon.className = 'fas fa-chevron-right';
        } else {
            collapseIcon.className = 'fas fa-chevron-left';
        }
    }
}

/**
 * Live Client-Side Table Search Filter
 */
function initLiveSearch() {
    const searchInputs = document.querySelectorAll('.live-table-search');
    searchInputs.forEach(input => {
        const targetTableSelector = input.getAttribute('data-target-table') || 'table';
        const table = document.querySelector(targetTableSelector);
        if (!table) return;

        input.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr:not(.live-search-no-match)');
            let visibleCount = 0;
            let totalDataRows = 0;

            rows.forEach(row => {
                if (row.classList.contains('no-search-row') || row.cells.length <= 1) {
                    return;
                }
                totalDataRows++;
                const text = row.textContent.toLowerCase();
                if (query === '' || text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle no matching rows indicator
            let noMatchRow = tbody.querySelector('.live-search-no-match');
            if (totalDataRows > 0 && visibleCount === 0 && query !== '') {
                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.className = 'live-search-no-match no-search-row';
                    const colCount = table.querySelectorAll('thead th').length || 8;
                    noMatchRow.innerHTML = `<td colspan="${colCount}" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i> No matching records found for "<strong>${escapeHtml(query)}</strong>"</td>`;
                    tbody.appendChild(noMatchRow);
                } else {
                    noMatchRow.querySelector('strong').textContent = query;
                    noMatchRow.style.display = '';
                }
            } else if (noMatchRow) {
                noMatchRow.style.display = 'none';
            }
        });
    });
}

/**
 * Toast Notification Helper
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1090';
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0 shadow`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle')} me-2"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    container.appendChild(toastEl);
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }
}

/**
 * AJAX Toggle for Monthly Strategy Activation
 */
function toggleStrategyActivation(btn, goalId, deptId, year, month) {
    const currentActive = btn.getAttribute('data-active');
    const newActive = currentActive === 'YES' ? 'NO' : 'YES';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('annual_goal_id', goalId);
    formData.append('department_id', deptId);
    formData.append('year', year);
    formData.append('month', month);
    formData.append('active', newActive);
    formData.append('csrf_token', csrfToken);

    fetch((window.APP_BASE_URL || '/Planner') + '/api/activation.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            btn.setAttribute('data-active', newActive);
            if (newActive === 'YES') {
                btn.className = 'btn btn-sm btn-success btn-toggle-active';
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> YES';
            } else {
                btn.className = 'btn btn-sm btn-outline-secondary btn-toggle-active';
                btn.innerHTML = '<i class="fas fa-times-circle me-1"></i> NO';
            }
            showToast(`Strategy activation updated to ${newActive}`, 'success');
        } else {
            showToast(data.message || 'Failed to update activation status', 'error');
            btn.innerHTML = currentActive === 'YES' ? '<i class="fas fa-check-circle me-1"></i> YES' : '<i class="fas fa-times-circle me-1"></i> NO';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = currentActive === 'YES' ? '<i class="fas fa-check-circle me-1"></i> YES' : '<i class="fas fa-times-circle me-1"></i> NO';
        showToast('Network error while updating activation', 'error');
    });
}


