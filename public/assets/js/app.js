/**
 * Kaldis Coffee PLC — Strategic Planning & Reporting System
 * ══════════════════════════════════════════════════════════
 * Frontend Interactions · Theme Controller · Sidebar · Live Search
 * Premium Micro-Animations & UX Enhancements · v2.0
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initTopLoader();
    relocateModalsToBody();
    initTheme();
    initSidebar();
    initLiveSearch();
    initTooltips();
    initPageAnimations();
    initAlertAutoDismiss();
    initNavbarScroll();
    initSearchableSelects();
    initLiveClock();

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.parentElement !== document.body) {
            document.body.appendChild(e.target);
        }
    });

    document.addEventListener('shown.bs.modal', function (e) {
        initSearchableSelects(e.target);
    });

    // Global Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); toggleSidebar(); }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            const searchInput = document.getElementById('sidebarMenuSearch');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});


/* ═══════════════════════════════
   THEME CONTROLLER (PERMANENT LIGHT)
   ═══════════════════════════════ */
function initTheme() {
    try {
        localStorage.removeItem('kaldis_theme');
    } catch (e) {}
    document.documentElement.setAttribute('data-bs-theme', 'light');
}

function toggleTheme() {
    // Dark mode permanently disabled
}

function applyTheme(theme, animate) {
    document.documentElement.setAttribute('data-bs-theme', 'light');
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
    const groupSections = document.querySelectorAll('.sidebar-nav-scroll .nav-group-section');
    const standaloneItems = document.querySelectorAll('.sidebar-nav-scroll .nav-item-standalone');

    if (q === '') {
        // Reset standalone items
        standaloneItems.forEach(item => item.style.display = '');

        // Reset groups and restore their saved / initial state
        groupSections.forEach(section => {
            section.style.display = '';
            const trigger = section.querySelector('.nav-group-trigger');
            const itemsContainer = section.querySelector('.nav-group-items');
            const subItems = section.querySelectorAll('.nav-sub-item');
            
            subItems.forEach(item => item.style.display = '');

            if (trigger && itemsContainer) {
                const groupKey = trigger.getAttribute('data-group');
                const savedState = localStorage.getItem('sidebar-group-' + groupKey);
                const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

                if (savedState === 'expanded' || isExpanded) {
                    itemsContainer.classList.remove('d-none');
                    trigger.setAttribute('aria-expanded', 'true');
                } else if (savedState === 'collapsed') {
                    itemsContainer.classList.add('d-none');
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }
        });
        return;
    }

    // Filter standalone items
    standaloneItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });

    // Filter group sections and sub-items
    groupSections.forEach(section => {
        const trigger = section.querySelector('.nav-group-trigger');
        const itemsContainer = section.querySelector('.nav-group-items');
        const subItems = section.querySelectorAll('.nav-sub-item');
        const triggerText = trigger ? trigger.textContent.toLowerCase() : '';
        const groupMatches = triggerText.includes(q);

        let visibleSubCount = 0;
        subItems.forEach(item => {
            const itemText = item.textContent.toLowerCase();
            const matches = groupMatches || itemText.includes(q);
            item.style.display = matches ? '' : 'none';
            if (matches) visibleSubCount++;
        });

        if (visibleSubCount > 0 || groupMatches) {
            section.style.display = '';
            if (itemsContainer) {
                itemsContainer.classList.remove('d-none');
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'true');
            }
        } else {
            section.style.display = 'none';
        }
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

function relocateModalsToBody() {
    document.querySelectorAll('.modal').forEach(function (modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
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




/* ═══════════════════════════════
   LIVE ETHIOPIAN TIME CLOCK
   ═══════════════════════════════ */
function initLiveClock() {
    function updateClock() {
        const el = document.getElementById('liveEthiopianTime');
        if (!el) return;
        try {
            const now = new Date();
            const options = {
                timeZone: 'Africa/Addis_Ababa',
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric',
                hour12: false
            };
            const parts = new Intl.DateTimeFormat('en-US', options).formatToParts(now);
            let h24 = 0, m = 0, s = 0;
            for (const p of parts) {
                if (p.type === 'hour') h24 = parseInt(p.value, 10);
                if (p.type === 'minute') m = parseInt(p.value, 10);
                if (p.type === 'second') s = parseInt(p.value, 10);
            }
            let ethHour = h24 - 6;
            if (ethHour < 0) ethHour += 12;
            else if (ethHour >= 12) ethHour -= 12;
            ethHour = (ethHour % 12) || 12;

            let periodAm = 'ሌሊት';
            if (h24 >= 6 && h24 <= 11) periodAm = 'ጠዋት';
            else if (h24 >= 12 && h24 <= 17) periodAm = 'ከሰዓት';
            else if (h24 >= 18 && h24 <= 23) periodAm = 'ምሽት';

            const pad = (n) => String(n).padStart(2, '0');
            el.textContent = `${pad(ethHour)}:${pad(m)}:${pad(s)} ${periodAm}`;
        } catch (e) {}
    }
    updateClock();
    setInterval(updateClock, 1000);
}
window.initLiveClock = initLiveClock;

/* ═══════════════════════════════
   TOP LOADING LINE & SPINNER CONTROLLER (ASYNC OPERATIONS)
   ═══════════════════════════════ */
const TopLoader = (function () {
    let lineEl = null;
    let currentProgress = 0;
    let trickleTimer = null;
    let activeRequests = 0;
    let isRunning = false;
    let isInitialized = false;

    function getLine() {
        if (!lineEl) lineEl = document.getElementById('topLoadingLine');
        return lineEl;
    }

    function set(p) {
        const line = getLine();
        currentProgress = Math.max(0, Math.min(100, p));
        if (line) {
            line.style.opacity = '1';
            line.style.width = currentProgress + '%';
        }
    }

    function start() {
        activeRequests++;
        if (isRunning) return;
        isRunning = true;

        clearInterval(trickleTimer);
        set(15);

        trickleTimer = setInterval(function () {
            if (currentProgress < 50) {
                set(currentProgress + (Math.random() * 8 + 4));
            } else if (currentProgress < 85) {
                set(currentProgress + (Math.random() * 4 + 2));
            } else if (currentProgress < 95) {
                set(currentProgress + 0.5);
            }
        }, 250);
    }

    function inc(amount) {
        if (!isRunning) {
            start();
            return;
        }
        const delta = typeof amount === 'number' ? amount : (Math.random() * 5 + 2);
        set(Math.min(95, currentProgress + delta));
    }

    function done(force = false) {
        if (!force && activeRequests > 1) {
            activeRequests--;
            return;
        }
        activeRequests = 0;
        clearInterval(trickleTimer);

        if (!isRunning && currentProgress === 0) return;

        set(100);

        setTimeout(function () {
            const line = getLine();
            if (line) line.style.opacity = '0';

            setTimeout(function () {
                if (line) line.style.width = '0%';
                currentProgress = 0;
                isRunning = false;
            }, 300);
        }, 220);
    }

    function initHooks() {
        if (isInitialized) return;
        isInitialized = true;

        // 1. jQuery AJAX hooks
        if (typeof window.jQuery !== 'undefined') {
            window.jQuery(document).ajaxStart(function () {
                TopLoader.start();
            });
            window.jQuery(document).ajaxStop(function () {
                TopLoader.done();
            });
        }

        // 2. Monkey-patch native window.fetch
        if (typeof window.fetch === 'function') {
            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                TopLoader.start();
                return originalFetch.apply(this, args)
                    .then(function (res) {
                        TopLoader.done();
                        return res;
                    })
                    .catch(function (err) {
                        TopLoader.done();
                        throw err;
                    });
            };
        }

        // 3. Form submissions (unless prevented or target is a new window)
        document.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            const form = e.target;
            if (form && form.tagName === 'FORM' && form.target !== '_blank') {
                TopLoader.start();
            }
        }, true);

        // 4. Page navigation via links
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (a.target && a.target !== '_self') return;
            if (a.hasAttribute('download')) return;
            const href = a.getAttribute('href') || '';
            if (href.startsWith('#') || href.startsWith('javascript:')) return;
            try {
                if (a.origin !== window.location.origin) return;
            } catch (err) {
                return;
            }
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

            TopLoader.start();
        });

        // 5. Page unloading
        window.addEventListener('beforeunload', function () {
            TopLoader.start();
        });
    }

    return {
        start: start,
        set: set,
        inc: inc,
        done: done,
        initHooks: initHooks
    };
})();

window.TopLoader = TopLoader;
function initTopLoader() {
    TopLoader.initHooks();
}
window.initTopLoader = initTopLoader;
