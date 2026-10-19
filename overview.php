<?php
/**
 * Legacy Overview Redirect
 * Forward to Laravel modern overview
 */
header('Location: /overview');
exit;


$currentUser = Auth::user();
$pageTitle   = 'Corporate Overview';
$period      = get_ethiopian_period();
$year        = app_config('current_planning_year', $period['year'] . ' E.C.');
$month       = app_config('current_planning_month', $period['month']);
$week        = (int)($period['week'] ?? 1);

$db = Database::getConnection();

// Fetch General Manager
$gmUser = $db->query("
    SELECT u.*, r.display_name as role_title 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.name IN ('gm_management', 'gm') 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Fetch all 13 active departments with their heads
$deptRows = $db->query("
    SELECT d.*, u.full_name as head_name, u.email as head_email 
    FROM departments d 
    LEFT JOIN users u ON d.head_user_id = u.id 
    WHERE d.active = 1 
    ORDER BY d.id
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* ── Floating & Transition Animation for Overview Logo ── */
    @keyframes kaldisLogoFloat {
        0% {
            transform: translateY(0px) rotate(0deg);
            box-shadow: 0 10px 25px -4px rgba(217, 119, 6, 0.28), 0 6px 12px -4px rgba(0, 0, 0, 0.08);
        }
        50% {
            transform: translateY(-12px) rotate(0.8deg);
            box-shadow: 0 24px 38px -6px rgba(217, 119, 6, 0.38), 0 12px 18px -4px rgba(0, 0, 0, 0.12);
        }
        100% {
            transform: translateY(0px) rotate(0deg);
            box-shadow: 0 10px 25px -4px rgba(217, 119, 6, 0.28), 0 6px 12px -4px rgba(0, 0, 0, 0.08);
        }
    }

    .kaldis-floating-logo-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px;
        border-radius: 50%;
        background: #ffffff;
        border: 3.5px solid rgba(217, 119, 6, 0.35);
        animation: kaldisLogoFloat 4.2s ease-in-out infinite;
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .kaldis-floating-logo-wrap:hover {
        transform: scale(1.1) translateY(-14px);
        border-color: #d97706;
        box-shadow: 0 28px 45px -8px rgba(217, 119, 6, 0.45);
    }

    .kaldis-overview-logo {
        width: 120px;
        height: 120px;
        object-fit: contain;
        transition: transform 0.35s ease;
    }

    .kaldis-floating-logo-wrap:hover .kaldis-overview-logo {
        transform: rotate(-3deg) scale(1.04);
    }

    /* ── Org Tree Hierarchy Connections ── */
    .org-tree-stem {
        width: 3px;
        height: 38px;
        background: linear-gradient(180deg, #d97706 0%, rgba(217, 119, 6, 0.4) 100%);
        margin: 0 auto;
    }

    .org-tree-hub {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #d97706;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.3);
        margin: 0 auto;
    }

    .dept-card {
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.22s ease;
        background: var(--kaldis-card-bg);
        border: 1.5px solid rgba(0, 0, 0, 0.06) !important;
        border-radius: 14px;
    }

    .dept-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 30px -10px rgba(0, 0, 0, 0.12) !important;
        border-color: rgba(217, 119, 6, 0.4) !important;
    }
</style>

<div class="container-fluid py-2" style="max-width: 1320px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════
         1. SMART HERO WELCOME SECTION (WITH ENLARGED FLOATING LOGO)
    ══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-5 overflow-hidden" style="background: var(--kaldis-card-bg); border-radius: 18px;">
        <div class="card-body p-4 p-md-5 text-center position-relative">
            <!-- Decorative Subtle Accent Glows -->
            <div style="position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(217,119,6,0.14) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
            <div style="position: absolute; bottom: -60px; left: -60px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(37,99,235,0.09) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

            <!-- Live Calendar & Ethiopian Time Window Pills -->
            <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center mb-4">
                <span class="status-pill status-done shadow-xs py-1.5 px-3">
                    <span class="status-dot dot-done status-dot-pulse"></span>
                    <span class="fw-semibold">Active Operating Period</span>
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3">
                    <i class="far fa-calendar-alt text-warning me-1"></i>
                    <?= e($period['month']) ?> <?= e($period['year']) ?> &bull; Week <?= e($week) ?>
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3" title="Ethiopian Time (East Africa Time)">
                    <i class="far fa-clock text-warning me-1"></i>
                    <?= format_ethiopian_time() ?>
                </span>
            </div>

            <!-- Enlarged Brand Identity Logo with Smooth Floating Transition -->
            <div class="d-block mb-4">
                <div class="kaldis-floating-logo-wrap" title="Kaldis Coffee PLC">
                    <img src="<?= e(asset_url('assets/images/logo.png')) ?>" 
                         alt="Kaldis Coffee PLC" 
                         class="kaldis-overview-logo"
                         onerror="this.src='<?= e(asset_url('images/logo.png')) ?>'">
                </div>
            </div>

            <!-- Personalized Executive Greeting & Subtitle -->
            <h1 class="fw-extrabold text-dark mb-2" style="font-size: clamp(1.8rem, 3.8vw, 2.5rem); letter-spacing: -0.025em;">
                <?= get_greeting() ?>, <?= e(explode(' ', $currentUser['full_name'] ?? $currentUser['username'])[0]) ?>!
            </h1>
            <p class="text-muted mx-auto mb-4" style="max-width: 680px; font-size: 1.05rem; line-height: 1.6;">
                Welcome to the <strong>Kaldis Coffee PLC</strong> Enterprise Planning &amp; Operational Governance Portal. Overseeing strategic execution across corporate leadership and all 13 operating departments.
            </p>

            <!-- User Role & Department Capsules -->
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--k-gold-soft); color: var(--k-gold); border: 1px solid rgba(245,158,11,.3); font-size: 0.85rem; border-radius: 9999px;">
                    <i class="fas fa-shield-halved"></i>
                    <span><?= e(Auth::roleDisplayName()) ?></span>
                </span>
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--kaldis-card-bg); color: var(--kaldis-text-secondary); border: 1px solid var(--kaldis-border); font-size: 0.85rem; border-radius: 9999px;">
                    <i class="fas fa-building text-secondary"></i>
                    <span><?= e(Auth::userDepartmentName()) ?></span>
                </span>
                <?php if (!empty($currentUser['telegram_chat_id'])): ?>
                    <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(14,165,233,0.1); color: #0284c7; border: 1px solid rgba(14,165,233,0.3); font-size: 0.85rem; border-radius: 9999px;">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram Linked</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         2. COMPANY ORGANIZATIONAL STRUCTURE
            Top: General Manager
            Under GM: All 13 Department Heads with their names
    ══════════════════════════════════════════════════════════════ -->
    <div class="mb-5">
        <!-- Section Header -->
        <div class="text-center mb-4">
            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1.5 rounded-pill fw-bold text-uppercase mb-2" style="font-size: 0.76rem; letter-spacing: 0.05em;">
                <i class="fas fa-sitemap me-1"></i> Governance Hierarchy
            </span>
            <h3 class="fw-bold text-dark mb-1">Company Organizational Structure</h3>
            <p class="text-muted small mb-0">General Management executive leadership and all 13 operational department heads.</p>
        </div>

        <!-- ── Level 1: General Manager Card (Executive Pinnacle) ── -->
        <div class="row justify-content-center mb-0">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow text-center position-relative" 
                     style="background: linear-gradient(135deg, #1c1917 0%, #292524 100%); color: #ffffff; border-radius: 16px; border-top: 4px solid #d97706 !important;">
                    <div class="card-body p-4">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3 rounded-circle"
                             style="width: 68px; height: 68px; background: rgba(217, 119, 6, 0.2); border: 2px solid #d97706; color: #fbbf24; font-size: 1.8rem;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="badge bg-warning text-dark fw-bold text-uppercase px-2.5 py-1 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            Top Executive Leadership
                        </div>
                        <h4 class="fw-bold text-white mb-1" style="letter-spacing: -0.01em;">
                            <?= e($gmUser['full_name'] ?? 'Tsedey Asrat') ?>
                        </h4>
                        <div class="text-warning fw-semibold small mb-2" style="letter-spacing: 0.03em;">
                            General Manager (GM) &bull; Kaldis Coffee PLC
                        </div>
                        <p class="text-white-50 small mb-0" style="font-size: 0.82rem; line-height: 1.4;">
                            Executive corporate governance, strategic direction, and operational oversight across all 13 departments.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Hierarchy Connector Stems ── -->
        <div class="text-center my-0">
            <div class="org-tree-stem"></div>
            <div class="org-tree-hub"></div>
            <div class="org-tree-stem"></div>
        </div>

        <!-- ── Level 2: 13 Operational Department Heads ── -->
        <div class="card border-0 shadow-sm p-4 p-md-4" style="background: rgba(255, 255, 255, 0.6); border-radius: 18px; backdrop-filter: blur(8px); border: 1px solid rgba(0,0,0,0.05) !important;">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fas fa-users-viewfinder text-primary me-2"></i> Operational Department Heads
                    </h5>
                    <small class="text-muted">13 operational business units reporting directly under General Management</small>
                </div>
                <span class="badge bg-light text-dark border px-3 py-1.5 fw-semibold font-monospace" style="font-size: 0.8rem;">
                    13 Departments Active
                </span>
            </div>

            <?php
                $deptIcons = [
                    'IT'   => ['icon' => 'fa-laptop-code', 'color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.1)'],
                    'OPS'  => ['icon' => 'fa-store', 'color' => '#d97706', 'bg' => 'rgba(217,119,6,0.1)'],
                    'MKT'  => ['icon' => 'fa-bullhorn', 'color' => '#ec4899', 'bg' => 'rgba(236,72,153,0.1)'],
                    'SCM'  => ['icon' => 'fa-truck-fast', 'color' => '#0284c7', 'bg' => 'rgba(2,132,199,0.1)'],
                    'HR'   => ['icon' => 'fa-users-gear', 'color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.1)'],
                    'FIN'  => ['icon' => 'fa-calculator', 'color' => '#059669', 'bg' => 'rgba(5,150,105,0.1)'],
                    'QAR'  => ['icon' => 'fa-mug-hot', 'color' => '#b45309', 'bg' => 'rgba(180,83,9,0.1)'],
                    'PM'   => ['icon' => 'fa-diagram-project', 'color' => '#4f46e5', 'bg' => 'rgba(79,70,229,0.1)'],
                    'TND'  => ['icon' => 'fa-chalkboard-user', 'color' => '#0891b2', 'bg' => 'rgba(8,145,178,0.1)'],
                    'RND'  => ['icon' => 'fa-flask', 'color' => '#9333ea', 'bg' => 'rgba(147,51,234,0.1)'],
                    'SAFE' => ['icon' => 'fa-shield-halved', 'color' => '#16a34a', 'bg' => 'rgba(22,163,74,0.1)'],
                    'LOG'  => ['icon' => 'fa-warehouse', 'color' => '#ea580c', 'bg' => 'rgba(234,88,12,0.1)'],
                    'AUD'  => ['icon' => 'fa-clipboard-check', 'color' => '#475569', 'bg' => 'rgba(71,85,105,0.1)'],
                ];
            ?>

            <div class="row g-3">
                <?php foreach ($deptRows as $dept): ?>
                    <?php
                        $code = strtoupper($dept['department_code']);
                        $meta = $deptIcons[$code] ?? ['icon' => 'fa-building', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)'];
                        $headName = !empty($dept['head_name']) ? $dept['head_name'] : 'Appointed Head';
                    ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card h-100 dept-card shadow-sm p-3.5 position-relative">
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                     style="width: 44px; height: 44px; background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 1.15rem;">
                                    <i class="fas <?= $meta['icon'] ?>"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                        <span class="badge font-monospace text-uppercase" 
                                              style="background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 0.68rem; font-weight: 700; border: 1px solid <?= $meta['color'] ?>20;">
                                            <?= e($dept['department_code']) ?>
                                        </span>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Dept #<?= (int)$dept['id'] ?></span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?= e($dept['department_name']) ?>" style="font-size: 0.92rem;">
                                        <?= e($dept['department_name']) ?>
                                    </h6>
                                </div>
                            </div>

                            <!-- Department Head Detail Capsule -->
                            <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary flex-shrink-0"
                                     style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <i class="fas fa-user-check text-success"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark small text-truncate" title="<?= e($headName) ?>" style="font-size: 0.84rem;">
                                        <?= e($headName) ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem; line-height: 1.2;">
                                        Department Head
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
