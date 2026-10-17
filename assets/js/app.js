/**
 * Kaldis Coffee PLC — Strategic Planning & Reporting System
 * ══════════════════════════════════════════════════════════
 * Frontend Interactions · Theme Controller · Sidebar · Live Search
 * Premium Micro-Animations & UX Enhancements · v2.0
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
    initLiveSearch();
    initTooltips();
    initPageAnimations();
    initAlertAutoDismiss();
    initNavbarScroll();
    initSearchableSelects();

    document.addEventListener('shown.bs.modal', function (e) {
        initSearchableSelects(e.target);
    });

    // Global Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); toggleSidebar(); }
        if (e.altKey && (e.key === 'd' || e.key === 'D')) { e.preventDefault(); toggleTheme(); }
    });
});


/* ═══════════════════════════════
   THEME CONTROLLER
   ═══════════════════════════════ */
function initTheme() {
    const saved = localStorage.getItem('kaldis_theme') || 'light';
    applyTheme(saved, false);

    const btn = document.getElementById('themeToggle');
    if (btn) btn.addEventListener('click', toggleTheme);
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next, true);
    localStorage.setItem('kaldis_theme', next);
}

function applyTheme(theme, animate) {
    if (animate) {
        document.documentElement.style.transition =
            'color .3s ease, background-color .3s ease';
        setTimeout(() => document.documentElement.style.transition = '', 400);
    }
    document.documentElement.setAttribute('data-bs-theme', theme);

    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        icon.className = theme === 'dark'
            ? 'fas fa-sun text-warning'
            : 'fas fa-moon text-secondary';

        if (animate) {
            icon.style.animation = 'none';
            requestAnimationFrame(() => {
                icon.style.animation = 'themeIconSpin .35s cubic-bezier(.34,1.56,.64,1)';
            });
        }
    }

    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
}


/* ═══════════════════════════════
   NAVBAR SCROLL EFFECT
   ═══════════════════════════════ */
function initNavbarScroll() {
    const navbar = document.querySelector('.kaldis-navbar');
    if (!navbar) return;
    window.addEventListener('scroll', () => {
        if (window.scrollY > 8) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }, { passive: true });
}


/* ═══════════════════════════════
   SIDEBAR CONTROLLER
   ═══════════════════════════════ */
function initSidebar() {
    const isDesktop = window.innerWidth >= 992;
    const collapsed = localStorage.getItem('sidebar_collapsed') === 'true';

    if (collapsed && isDesktop) {
        document.documentElement.classList.add('sidebar-collapsed');
        document.body.classList.add('sidebar-collapsed');
    } else {
        document.documentElement.classList.remove('sidebar-collapsed');
        document.body.classList.remove('sidebar-collapsed');
    }

    // Wire all toggle buttons
    document.querySelectorAll('#sidebarToggle, #sidebarCollapseBtn, #sidebarEdgeToggle, #mobileSidebarToggle, #desktopSidebarToggle, .sidebar-toggle-btn')
        .forEach(btn => {
            btn.removeEventListener('click', toggleSidebar);
            btn.addEventListener('click', toggleSidebar);
        });

    const closeBtn = document.getElementById('sidebarCloseBtn') || document.getElementById('sidebarMobileClose');
    if (closeBtn) {
        closeBtn.removeEventListener('click', closeMobileSidebar);
        closeBtn.addEventListener('click', closeMobileSidebar);
    }

    // Backdrop close
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) {
        backdrop.removeEventListener('click', closeMobileSidebar);
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    // ESC closes mobile sidebar
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMobileSidebar();
    });

    // Auto-close on nav link tap (mobile)
    document.querySelectorAll('.kaldis-sidebar .nav-link')
        .forEach(link => link.addEventListener('click', () => {
            if (window.innerWidth < 992) closeMobileSidebar();
        }));

    // Responsive screen resize listener
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            document.documentElement.classList.remove('mobile-sidebar-open');
            document.body.classList.remove('mobile-sidebar-open');
            const savedCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            document.documentElement.classList.toggle('sidebar-collapsed', savedCollapsed);
            document.body.classList.toggle('sidebar-collapsed', savedCollapsed);
        }
        syncHamburger();
    });

    // Sidebar search filter
    const searchInput = document.getElementById('sidebarMenuSearch');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => filterSidebarMenu(this.value), 120);
        });
    }

    updateCollapseIcon();
    syncHamburger();
}

function toggleSidebar(e) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
        e.stopPropagation();
    }

    if (window.innerWidth < 992) {
        const isOpen = document.documentElement.classList.toggle('mobile-sidebar-open');
        document.body.classList.toggle('mobile-sidebar-open', isOpen);
        syncHamburger();
    } else {
        const nowCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        document.body.classList.toggle('sidebar-collapsed', nowCollapsed);
        try {
            localStorage.setItem('sidebar_collapsed', nowCollapsed);
        } catch (err) {}
        updateCollapseIcon();
        syncHamburger();
    }
}

function closeMobileSidebar() {
    document.documentElement.classList.remove('mobile-sidebar-open');
    document.body.classList.remove('mobile-sidebar-open');
    syncHamburger();
}

/* Keep aria-expanded and edge toggle rotation state in sync */
function syncHamburger() {
    const isMobile = window.innerWidth < 992;
    const open = document.documentElement.classList.contains('mobile-sidebar-open');
    const collapsed = document.documentElement.classList.contains('sidebar-collapsed');

    document.querySelectorAll('#sidebarToggle, #mobileSidebarToggle, #desktopSidebarToggle, .sidebar-toggle-btn')
        .forEach(btn => {
            btn.setAttribute('aria-expanded', isMobile ? (open ? 'true' : 'false') : (!collapsed ? 'true' : 'false'));
            btn.classList.toggle('is-collapsed', collapsed);
            btn.setAttribute('title', isMobile ? (open ? 'Close Menu' : 'Open Menu') : (collapsed ? 'Expand Sidebar (Alt+S)' : 'Collapse Sidebar (Alt+S)'));
        });

    const edgeToggle = document.getElementById('sidebarEdgeToggle');
    if (edgeToggle) {
        edgeToggle.classList.toggle('is-collapsed', collapsed);
        edgeToggle.setAttribute('title', collapsed ? 'Expand Sidebar (Alt+S)' : 'Collapse Sidebar (Alt+S)');
        edgeToggle.setAttribute('aria-label', collapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
    }
}

function updateCollapseIcon() {
    const collapsed = document.documentElement.classList.contains('sidebar-collapsed');
    const icon = document.getElementById('sidebarCollapseIcon');
    const label = document.getElementById('sidebarCollapseBtnText');
    if (icon) {
        icon.className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    }
    if (label) {
        label.textContent = collapsed ? 'Show' : 'Collapse';
    }
    syncHamburger();
}

function filterSidebarMenu(query) {
    const q = query.toLowerCase().trim();

    // Show/hide each nav-item based on query match
    document.querySelectorAll('.kaldis-sidebar .nav-item').forEach(item => {
        const text  = item.textContent.toLowerCase();
        const match = q === '' || text.includes(q);
        item.style.display = match ? '' : 'none';
    });

    // Hide section headings whose entire list has no visible items
    document.querySelectorAll('.kaldis-sidebar .sidebar-heading').forEach(heading => {
        // Walk forward siblings to find the nearest <ul>
        let ul = heading.nextElementSibling;
        while (ul && ul.tagName !== 'UL') {
            ul = ul.nextElementSibling;
        }
        if (!ul) {
            heading.style.display = q === '' ? '' : 'none';
            return;
        }
        const allItems     = ul.querySelectorAll('.nav-item');
        const visibleCount = Array.from(allItems).filter(item => item.style.display !== 'none').length;
        heading.style.display = (q === '' || visibleCount > 0) ? '' : 'none';
    });
}


/* ═══════════════════════════════
   BOOTSTRAP TOOLTIPS
   ═══════════════════════════════ */
function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el, { trigger: 'hover' }));
}


/* ═══════════════════════════════
   PAGE ENTRY ANIMATIONS
   ═══════════════════════════════ */
function initPageAnimations() {
    // Staggered card entry
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, i) => {
        card.style.animationDelay = `${i * 0.045}s`;
    });

    // Number counter animation for stat values
    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        animateCounter(el, parseInt(el.getAttribute('data-count'), 10), 900);
    });
}

function animateCounter(el, target, duration) {
    const start = performance.now();
    const update = (time) => {
        const progress = Math.min((time - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // cubic ease-out
        el.textContent = Math.round(ease * target);
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}


/* ═══════════════════════════════
   LIVE TABLE SEARCH
   ═══════════════════════════════ */
function initLiveSearch() {
    document.querySelectorAll('.live-table-search').forEach(input => {
        const tableSelector = input.getAttribute('data-target-table') || 'table';
        const table = document.querySelector(tableSelector);
        if (!table) return;

        let debounce;
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => runTableSearch(this.value, table), 150);
        });
    });
}

function runTableSearch(query, table) {
    const q = query.toLowerCase().trim();
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    let visible = 0;
    tbody.querySelectorAll('tr:not(.live-search-empty)').forEach(row => {
        if (row.cells.length <= 1) return;
        const match = q === '' || row.textContent.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    // Empty state row
    let emptyRow = tbody.querySelector('.live-search-empty');
    if (visible === 0 && q !== '') {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'live-search-empty';
            const cols = table.querySelectorAll('thead th').length || 6;
            emptyRow.innerHTML = `<td colspan="${cols}" class="text-center py-5 text-muted">
                <i class="fas fa-search fa-2x mb-3 d-block opacity-25"></i>
                No results for <strong>"${escapeHtml(q)}"</strong>
            </td>`;
            tbody.appendChild(emptyRow);
        } else {
            emptyRow.querySelector('strong').textContent = `"${q}"`;
            emptyRow.style.display = '';
        }
    } else if (emptyRow) {
        emptyRow.style.display = 'none';
    }
}


/* ═══════════════════════════════
   AUTO-DISMISS ALERTS
   ═══════════════════════════════ */
function initAlertAutoDismiss() {
    document.querySelectorAll('.alert-dismissible:not(.alert-permanent)').forEach(el => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(el)?.close();
            }
        }, 5500);
    });
}


/* ═══════════════════════════════
   TOAST NOTIFICATIONS
   ═══════════════════════════════ */
function showToast(message, type = 'success', duration = 4000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const icons = {
        success: 'fa-check-circle',
        error:   'fa-exclamation-circle',
        danger:  'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle',
    };
    const bgClass = type === 'error' ? 'danger' : type;
    const icon    = icons[type] || 'fa-info-circle';

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${bgClass} border-0 shadow-lg`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="fas ${icon} flex-shrink-0"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;

    container.appendChild(toast);
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}


/* ═══════════════════════════════
   STRATEGY ACTIVATION TOGGLE
   ═══════════════════════════════ */
function toggleStrategyActivation(btn, goalId, deptId, year, month) {
    const current = btn.getAttribute('data-active');
    const next    = current === 'YES' ? 'NO' : 'YES';
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const form = new FormData();
    form.append('annual_goal_id', goalId);
    form.append('department_id',  deptId);
    form.append('year',           year);
    form.append('month',          month);
    form.append('active',         next);
    form.append('csrf_token',     csrf);

    fetch((window.APP_BASE_URL || '/Planner') + '/api/activation.php', {
        method: 'POST', body: form
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            btn.setAttribute('data-active', next);
            if (next === 'YES') {
                btn.className = 'btn btn-sm btn-success btn-toggle-active';
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>YES';
            } else {
                btn.className = 'btn btn-sm btn-outline-secondary btn-toggle-active';
                btn.innerHTML = '<i class="fas fa-times-circle me-1"></i>NO';
            }
            showToast(`Strategy activation set to ${next}`, 'success');
        } else {
            showToast(data.message || 'Update failed', 'error');
            btn.innerHTML = current === 'YES'
                ? '<i class="fas fa-check-circle me-1"></i>YES'
                : '<i class="fas fa-times-circle me-1"></i>NO';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = current === 'YES'
            ? '<i class="fas fa-check-circle me-1"></i>YES'
            : '<i class="fas fa-times-circle me-1"></i>NO';
        showToast('Network error', 'error');
    });
}


/* ═══════════════════════════════
   UTILITIES
   ═══════════════════════════════ */
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}


/* ═══════════════════════════════
   GLOBAL SEARCHABLE SELECTS (TOMSELECT)
   ═══════════════════════════════ */
function initSearchableSelects(root = document) {
    if (typeof TomSelect === 'undefined') return;

    const selects = root.querySelectorAll('select:not([data-no-search]):not(.no-search):not(.tomselected)');
    selects.forEach(select => {
        if (select.closest('.d-none') && !select.closest('.modal')) return;

        try {
            const hasAutoSubmit = select.getAttribute('onchange')?.includes('submit');
            const isMultiple = select.multiple;
            const modalEl = select.closest('.modal');

            // Smart contextual placeholder
            let searchPlaceholder = select.getAttribute('data-search-placeholder');
            if (!searchPlaceholder) {
                const name = (select.getAttribute('name') || '').toLowerCase();
                const id   = (select.getAttribute('id')   || '').toLowerCase();
                const firstOpt = select.options[0]?.text?.toLowerCase() || '';
                if      (name.includes('branch') || id.includes('branch') || firstOpt.includes('branch'))           searchPlaceholder = 'Search branches...';
                else if (name.includes('dept')   || id.includes('dept')   || firstOpt.includes('department'))       searchPlaceholder = 'Search departments...';
                else if (name.includes('user')   || id.includes('user')   || firstOpt.includes('user') || name.includes('head')) searchPlaceholder = 'Search users...';
                else if (name.includes('role')   || id.includes('role')   || firstOpt.includes('role'))             searchPlaceholder = 'Search roles...';
                else if (name.includes('year')   || id.includes('year')   || firstOpt.includes('year'))             searchPlaceholder = 'Search years...';
                else if (name.includes('month')  || id.includes('month')  || firstOpt.includes('month'))            searchPlaceholder = 'Search months...';
                else if (name.includes('status') || id.includes('status') || firstOpt.includes('status'))           searchPlaceholder = 'Search statuses...';
                else if (name.includes('persp')  || id.includes('persp')  || firstOpt.includes('perspective'))      searchPlaceholder = 'Search perspectives...';
                else if (name.includes('freq')   || id.includes('freq')   || firstOpt.includes('frequency'))        searchPlaceholder = 'Search frequencies...';
                else if (name.includes('goal')   || id.includes('goal')   || firstOpt.includes('goal'))             searchPlaceholder = 'Search strategic goals...';
                else if (name.includes('plan')   || id.includes('plan')   || firstOpt.includes('plan'))             searchPlaceholder = 'Search monthly plans...';
                else if (name.includes('reason') || id.includes('reason') || firstOpt.includes('reason'))           searchPlaceholder = 'Search reasons...';
                else                                                                                                 searchPlaceholder = 'Search...';
            }

            // Plugins:
            // Multi-select: clear_button + remove_button for badge removal
            // Single-select: dropdown_input for modern clean popover search (consistent across all options counts)
            const plugins = isMultiple 
                ? ['clear_button', 'remove_button'] 
                : ['dropdown_input'];

            const ts = new TomSelect(select, {
                create:           false,
                allowEmptyOption: true,
                maxOptions:       500,
                plugins:          plugins,
                placeholder:      select.getAttribute('placeholder') ||
                                  select.getAttribute('data-placeholder') ||
                                  (select.options[0]?.value === '' ? select.options[0].text : 'Select...'),
                render: {
                    no_results(data, escape) {
                        return `<div class="no-results p-2 text-muted small"><i class="fas fa-search me-1 opacity-50"></i> No matches for "${escape(data.input)}"</div>`;
                    }
                }
            });

            // Set search-input placeholder
            if (ts.control_input && !isMultiple) {
                ts.control_input.placeholder = searchPlaceholder;
            }

            // Reliable click handler on the control to guarantee open/close toggle
            ts.control.addEventListener('click', function(e) {
                if (e.target.closest('.clear-button') || e.target.closest('.remove')) return;
                if (ts.isOpen) {
                    ts.close();
                } else {
                    ts.open();
                }
            });

            // On dropdown open: ensure parent card/filter-bar overflow is visible, z-index elevated, and modal focus trap doesn't block search
            ts.on('dropdown_open', () => {
                if (modalEl) {
                    modalEl.removeAttribute('tabindex');
                }
                const card = select.closest('.card');
                if (card) {
                    card.style.overflow = 'visible';
                    card.style.zIndex = '1055';
                }
                const filterBar = select.closest('.filter-bar');
                if (filterBar) {
                    filterBar.style.overflow = 'visible';
                    filterBar.style.zIndex = '1065';
                }
                const tableResp = select.closest('.table-responsive');
                if (tableResp) {
                    tableResp.style.overflow = 'visible';
                }
            });

            ts.on('dropdown_close', () => {
                if (modalEl) {
                    modalEl.setAttribute('tabindex', '-1');
                }
                const card = select.closest('.card');
                if (card && !card.querySelector('.ts-wrapper.dropdown-active')) {
                    card.style.overflow = '';
                    card.style.zIndex = '';
                }
                const filterBar = select.closest('.filter-bar');
                if (filterBar && !filterBar.querySelector('.ts-wrapper.dropdown-active')) {
                    filterBar.style.overflow = '';
                    filterBar.style.zIndex = '';
                }
                const tableResp = select.closest('.table-responsive');
                if (tableResp && !tableResp.querySelector('.ts-wrapper.dropdown-active')) {
                    tableResp.style.overflow = '';
                }
            });

            // Fire native 'change' so PHP-form onchange handlers still work
            let isInitializing = true;
            ts.on('change', function(val) {
                if (isInitializing) return;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                if (select.hasAttribute('onchange') && !hasAutoSubmit) {
                    try { (new Function(select.getAttribute('onchange'))).call(select); } catch(e) {}
                }
                if (hasAutoSubmit && select.form) select.form.submit();
            });
            setTimeout(() => { isInitializing = false; }, 50);

            // Sync from native script updates
            select.addEventListener('sync', function() {
                const vals = isMultiple
                    ? Array.from(select.selectedOptions).map(o => o.value)
                    : select.value;
                ts.setValue(vals, true);
            });

            // Support form reset
            if (select.form) {
                select.form.addEventListener('reset', () => {
                    setTimeout(() => {
                        isMultiple ? ts.clear(true) : ts.setValue(select.value, true);
                    }, 20);
                });
            }
        } catch (e) {
            console.debug('TomSelect skipped:', select, e);
        }
    });
}

window.initSearchableSelects = initSearchableSelects;


