@extends('layouts.app')

@section('title', 'Telegram Bot Settings & Status')

@section('content')
<div class="container-fluid py-2" style="max-width: 1200px; margin: 0 auto;">

    <!-- ── Page Header ── -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fab fa-telegram text-info me-2"></i> Telegram Bot Settings &amp; Status
                </h4>
                @if(!empty($botInfo['ok']))
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-circle text-success me-1" style="font-size: 0.55rem;"></i> Connected
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        <i class="fas fa-circle text-danger me-1" style="font-size: 0.55rem;"></i> Offline / Unauthorized
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Manage bot connection token, register webhook or enable polling, test message delivery, trigger automated reminder schedules, and monitor user chat IDs.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="syncUpdates(this)" title="Fetch and process any pending messages from Telegram immediately">
                <i class="fas fa-arrows-rotate me-1"></i> Sync Updates
            </button>
            <a href="{{ route('admin.settings') }}#reminders" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-clock me-1"></i> Edit Schedules
            </a>
            @if(!empty($botInfo['result']['username']))
                <a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">
                    <i class="fab fa-telegram me-1"></i> Open @<span>{{ $botInfo['result']['username'] }}</span>
                </a>
            @endif
        </div>
    </div>

    @if(empty($botInfo['ok']))
        <div class="alert alert-warning border border-warning-subtle shadow-sm rounded-3 mb-4 p-3 d-flex align-items-start gap-3">
            <i class="fas fa-triangle-exclamation text-warning fs-4 mt-0.5"></i>
            <div class="small">
                <div class="fw-bold text-dark mb-1">Telegram Bot is Offline or Token is Unauthorized</div>
                <div class="text-secondary mb-2">
                    Automated reminders and notifications cannot be delivered to Telegram because the current API token is invalid, expired, or revoked by Telegram.
                </div>
                <div class="text-dark">
                    <strong>To fix:</strong> Open Telegram, search for <code>@BotFather</code>, copy the HTTP API token for your bot (or create a new bot with <code>/newbot</code>), paste it in the <strong>Bot Connection &amp; Token</strong> card below, and click <strong>Save</strong>.
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- ── Card 1: Bot Connection & Token ── -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="fas fa-robot text-primary me-2"></i> Bot Connection &amp; Token
                        </h6>
                        <p class="text-muted small mb-0">Telegram Bot authentication credentials.</p>
                    </div>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="fab fa-telegram-plane fs-4"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-dark fs-6">{{ $botInfo['result']['first_name'] ?? 'Kaldis Planning Bot' }}</div>
                            <div class="text-muted small font-monospace">
                                @if(!empty($botInfo['result']['username']))
                                    @<span>{{ $botInfo['result']['username'] }}</span>
                                @else
                                    Not connected
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnPingBot" onclick="pingConnection()">
                            <i class="fas fa-rotate me-1"></i> Test Ping
                        </button>
                    </div>

                    <form method="POST" action="{{ route('telegram.update_token') }}" id="updateTokenForm">
                        @csrf
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">BOT API TOKEN</label>
                        <div class="input-group">
                            <input type="password" name="token" id="botTokenInput" class="form-control font-monospace form-control-sm" 
                                   value="{{ $botToken ?? '' }}" placeholder="Paste BotFather token here..." required>
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="toggleTokenVisibility()">
                                <i class="fas fa-eye" id="tokenEyeIcon"></i>
                            </button>
                            <button class="btn btn-kaldis btn-sm fw-bold px-3" type="submit">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                        <div class="small text-muted mt-1">Get your token by messaging <code>@BotFather</code> on Telegram.</div>
                    </form>

                    <div id="pingStatusAlert" class="alert d-none py-2 px-3 small rounded-3 mb-0"></div>
                </div>
            </div>
        </div>

        <!-- ── Card 2: Webhook & Delivery Mode ── -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="fas fa-network-wired text-info me-2"></i> Telegram Webhook &amp; Delivery
                        </h6>
                        <p class="text-muted small mb-0">Configure real-time push deliveries from Telegram to your server.</p>
                    </div>
                    <div>
                        @if(!empty($webhookInfo['result']['url']))
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="webhookBadge">
                                <i class="fas fa-bolt text-success me-1" style="font-size: 0.55rem;"></i> Webhook Active
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" id="webhookBadge">
                                <i class="fas fa-satellite-dish text-warning me-1" style="font-size: 0.55rem;"></i> Polling Mode
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    
                    <!-- Webhook Live Status Panel -->
                    <div class="p-3 bg-light rounded-3 d-flex flex-column gap-1.5 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.72rem;">ACTIVE WEBHOOK ENDPOINT</span>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-2" id="btnRefreshWebhook" onclick="refreshWebhookDiagnostics()">
                                <i class="fas fa-rotate me-1"></i> Check Status
                            </button>
                        </div>
                        <div class="font-monospace small text-dark text-break fw-semibold py-1" id="webhookDisplayUrl">
                            {{ !empty($webhookInfo['result']['url']) ? $webhookInfo['result']['url'] : 'No active webhook registered (Operating in Long-Polling mode)' }}
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 small text-muted pt-1 border-top" id="webhookMetrics">
                            <span>Pending Updates: <strong class="text-dark" id="webhookPendingCount">{{ $webhookInfo['result']['pending_update_count'] ?? 0 }}</strong></span>
                            <span>&bull; Max Conns: <strong class="text-dark" id="webhookMaxConns">{{ $webhookInfo['result']['max_connections'] ?? 40 }}</strong></span>
                            <span>&bull; Custom Cert: <strong class="text-dark" id="webhookCustomCert">{{ !empty($webhookInfo['result']['has_custom_certificate']) ? 'Yes' : 'No' }}</strong></span>
                        </div>
                        @if(!empty($webhookInfo['result']['last_error_message']))
                            <div class="alert alert-danger py-1.5 px-2.5 small rounded-2 mb-0 mt-1" id="webhookLastErrorBox">
                                <i class="fas fa-triangle-exclamation me-1"></i> <strong>Telegram Error:</strong> {{ $webhookInfo['result']['last_error_message'] }}
                                @if(!empty($webhookInfo['result']['last_error_date']))
                                    <span class="text-muted small">({{ \Carbon\Carbon::createFromTimestamp($webhookInfo['result']['last_error_date'])->diffForHumans() }})</span>
                                @endif
                            </div>
                        @elseif(!empty($webhookError))
                            <div class="alert alert-warning py-1.5 px-2.5 small rounded-2 mb-0 mt-1" id="webhookLastErrorBox">
                                <i class="fas fa-triangle-exclamation me-1"></i> <strong>Telegram Notice:</strong> {{ $webhookError }}
                            </div>
                        @endif
                    </div>

                    <!-- Register Webhook Form -->
                    <form method="POST" action="{{ route('telegram.webhook.set') }}" id="setWebhookForm">
                        @csrf
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">REGISTER WEBHOOK URL (HTTPS REQUIRED)</label>
                        <div class="input-group">
                            <input type="url" name="url" id="webhookUrlInput" class="form-control font-monospace form-control-sm"
                                   value="{{ !empty($webhookInfo['result']['url']) ? $webhookInfo['result']['url'] : (request()->isSecure() || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? secure_url('/telegram/webhook') : url('/telegram/webhook')) }}" 
                                   placeholder="https://your-domain.com/telegram/webhook" required>
                            <button class="btn btn-info btn-sm fw-bold px-3 text-white" type="submit" id="btnSubmitWebhook">
                                <i class="fas fa-link me-1"></i> Set Webhook
                            </button>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                            <div class="d-flex gap-1.5">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2" onclick="fillDefaultServerUrl()">
                                    <i class="fas fa-server me-1"></i> Use Default Server URL
                                </button>
                            </div>
                            <div class="form-check form-check-inline m-0 small">
                                <input class="form-check-input" type="checkbox" name="drop_pending_updates" id="dropPendingUpdates" value="1">
                                <label class="form-check-label text-muted small" for="dropPendingUpdates">Drop pending backlog</label>
                            </div>
                        </div>
                    </form>

                    <!-- Remove Webhook Button -->
                    @if(!empty($webhookInfo['result']['url']))
                        <form method="POST" action="{{ route('telegram.webhook.delete') }}" id="deleteWebhookForm">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-1.5" 
                                    onclick="return confirm('Remove active webhook and switch back to long-polling mode? Telegram will stop sending real-time HTTP push deliveries.');">
                                <i class="fas fa-trash-can me-1"></i> Remove Webhook (Switch to Polling)
                            </button>
                        </form>
                    @endif

                    <div id="webhookResultAlert" class="alert d-none py-2 px-3 small rounded-3 mb-0"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Card 3: Send Test Message ── -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h6 class="fw-bold text-dark mb-1">
                <i class="fas fa-paper-plane text-success me-2"></i> Send Test Message
            </h6>
            <p class="text-muted small mb-0">Verify instant message delivery to any Telegram chat ID.</p>
        </div>
        <div class="card-body p-4 pt-2">
            <form method="POST" action="{{ route('telegram.send_test') }}" id="sendTestForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Recipient</label>
                        <select name="chat_id" id="testChatIdSelect" class="form-select form-select-sm mb-2" required>
                            <option value="">-- Choose User or Enter Custom ID --</option>
                            @foreach($users as $u)
                                @if(!empty($u->telegram_chat_id))
                                    <option value="{{ $u->telegram_chat_id }}">
                                        {{ $u->full_name ?? $u->username }} ({{ $u->department->department_name ?? 'Admin' }}) - ID: {{ $u->telegram_chat_id }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Or Enter Custom Chat ID</label>
                        <input type="text" id="customChatIdInput" class="form-control form-control-sm font-monospace" placeholder="e.g. 123456789">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Test Message</label>
                        <textarea name="message" id="testMessageText" class="form-control form-control-sm" rows="3" required>☕ Kaldis Planning Portal: Telegram test connection verified successfully!</textarea>
                    </div>

                    <div class="col-md-2 d-flex flex-column justify-content-end">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" id="btnSendTest">
                            <i class="fas fa-paper-plane me-1"></i> Send Test
                        </button>
                    </div>
                </div>
            </form>

            <div id="testMessageResult" class="alert d-none py-2 px-3 small rounded-3 mt-3 mb-0"></div>
        </div>
    </div>

    <!-- ── Card 4: Automated Reminders Schedule & Execution Hub ── -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    <i class="fas fa-calendar-check text-primary me-2"></i> Automated Reminders Schedule &amp; Execution Hub
                </h6>
                <p class="text-muted small mb-0">
                    Catch-up resilient scheduler evaluates weekly &amp; monthly cycles and dispatches alerts strictly according to configured times.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="executeAutomation('dispatch', this)">
                    <i class="fas fa-bolt me-1"></i> Run Due Reminders Now
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="executeAutomation('dispatch_force', this)" title="Bypasses time and cache debounce checks to force a test run">
                    <i class="fas fa-rotate me-1"></i> Force Check
                </button>
            </div>
        </div>
        <div class="card-body p-4 pt-1">
            @php
                $weeklyActive = kaldis_setting('enable_weekly_reminders', 'true') === 'true';
                $monthlyActive = kaldis_setting('enable_monthly_reminders', 'true') === 'true';
                $ethPeriod = get_ethiopian_period();
            @endphp
            <div class="row g-3 mb-3">
                <!-- Weekly Schedule Summary -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="fas fa-clock text-primary me-1"></i> Weekly Deliverables Schedule
                            </span>
                            <span class="badge {{ $weeklyActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary text-white' }} px-2 py-0.5">
                                {{ $weeklyActive ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                        <ul class="list-unstyled small mb-2 text-secondary" style="line-height: 1.7;">
                            <li>• <strong>1st Weekend Reminder:</strong> Saturday at <code>{{ kaldis_setting('weekly_reminder_saturday_time', '10:00') }}</code></li>
                            <li>• <strong>2nd Advance Reminder:</strong> Sunday at <code>{{ kaldis_setting('weekly_reminder_sunday_time', '16:30') }}</code></li>
                            <li>• <strong>3rd Final Morning Reminder:</strong> Monday at <code>{{ kaldis_setting('weekly_reminder_monday_time', '08:30') }}</code></li>
                            <li>• <strong>Weekly Submission Cutoff:</strong> {{ kaldis_setting('weekly_deadline_day', 'Monday') }} at <code>{{ kaldis_setting('weekly_deadline_time', '12:30') }}</code></li>
                            <li>• <strong>Cutoff Delinquent Escalations:</strong> {{ kaldis_setting('weekly_deadline_day', 'Monday') }} at <code>12:35</code></li>
                        </ul>
                        <div class="d-flex flex-wrap gap-1.5 pt-2 border-top">
                            <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-2" onclick="executeAutomation('weekly_reminder_saturday', this)">
                                Test Saturday Alert
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-2" onclick="executeAutomation('weekly_reminder_sunday', this)">
                                Test Sunday Alert
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-2" onclick="executeAutomation('weekly_reminder_monday', this)">
                                Test Monday Alert
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-2" onclick="executeAutomation('deadline_check_heads', this)">
                                Test Cutoff Check
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Monthly Schedule Summary -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="far fa-calendar-check text-warning me-1"></i> Monthly Plan Deliverables
                            </span>
                            <span class="badge {{ $monthlyActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary text-white' }} px-2 py-0.5">
                                {{ $monthlyActive ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                        <ul class="list-unstyled small mb-2 text-secondary" style="line-height: 1.7;">
                            <li>• <strong>Start Day:</strong> Day <code>{{ kaldis_setting('monthly_reminder_start_day', 25) }}</code> of Month</li>
                            <li>• <strong>Day 25 Morning (1st):</strong> <code>{{ kaldis_setting('monthly_reminder_day25_morning_time', '09:30') }}</code></li>
                            <li>• <strong>Day 25 Afternoon (2nd):</strong> <code>{{ kaldis_setting('monthly_reminder_day25_afternoon_time', '16:00') }}</code></li>
                            <li>• <strong>Day 26 Morning (3rd):</strong> <code>{{ kaldis_setting('monthly_reminder_day26_morning_time', '09:00') }}</code></li>
                            <li>• <strong>Day 26 Afternoon (3-Hr):</strong> <code>{{ kaldis_setting('monthly_reminder_day26_afternoon_time', '14:00') }}</code></li>
                            <li>• <strong>Official Cutoff:</strong> Day {{ kaldis_setting('monthly_plan_deadline_day', 26) }} at <code>{{ kaldis_setting('monthly_plan_deadline_time', '17:00') }}</code></li>
                        </ul>
                        <div class="d-flex flex-wrap gap-1.5 pt-2 border-top">
                            <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0.5 px-2" onclick="executeAutomation('monthly_reminder_day25_morning', this)">
                                Test Day 25 Alert
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0.5 px-2" onclick="executeAutomation('monthly_reminder_day26_morning', this)">
                                Test Day 26 Alert
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-2" onclick="executeAutomation('monthly_cutoff', this)">
                                Test Monthly Cutoff
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Automation Execution Live Output -->
            <div id="automationExecutionAlert" class="alert d-none py-2 px-3 small rounded-3 mb-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong id="automationResultTitle"><i class="fas fa-terminal me-1"></i> Execution Output</strong>
                    <span class="font-monospace text-muted small" id="automationResultTimestamp"></span>
                </div>
                <pre class="mb-0 bg-dark text-light p-2.5 rounded font-monospace small" style="max-height: 180px; overflow-y: auto;" id="automationResultPre"></pre>
            </div>
        </div>
    </div>

    <!-- ── Card 5: Department Heads & Linked Telegram Accounts ── -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    <i class="fas fa-users text-secondary me-2"></i> Department Heads &amp; Telegram Linkages
                </h6>
                <p class="text-muted small mb-0">Chat IDs used for automated reminder alerts.</p>
            </div>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-user-gear me-1"></i> Manage User Accounts
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-uppercase fw-bold" style="font-size: 0.72rem;">
                        <tr>
                            <th style="padding-left: 1.5rem;">User / Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Telegram Chat ID</th>
                            <th class="text-end" style="padding-right: 1.5rem;">Status / Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td style="padding-left: 1.5rem;">
                                    <div class="fw-bold text-dark">{{ $user->full_name ?? $user->username }}</div>
                                    <div class="text-muted small font-monospace">{{ $user->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $user->role->display_name ?? 'User' }}</span>
                                </td>
                                <td>
                                    {{ $user->department->department_name ?? '—' }}
                                </td>
                                <td>
                                    @if(!empty($user->telegram_chat_id))
                                        <code class="text-dark">{{ $user->telegram_chat_id }}</code>
                                    @else
                                        <span class="text-muted small">Not Linked</span>
                                    @endif
                                </td>
                                <td class="text-end" style="padding-right: 1.5rem;">
                                    @if(!empty($user->telegram_chat_id))
                                        <button type="button" class="btn btn-xs btn-outline-success py-0.5 px-2" 
                                                onclick="quickSendPing('{{ $user->telegram_chat_id }}', '{{ addslashes($user->full_name ?? $user->username) }}')">
                                            <i class="fas fa-paper-plane me-1"></i> Ping
                                        </button>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Missing ID</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ── Telegram Status JS Helpers ── -->
<script>
    function toggleTokenVisibility() {
        const inp = document.getElementById('botTokenInput');
        const icon = document.getElementById('tokenEyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            inp.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    async function pingConnection() {
        const alertBox = document.getElementById('pingStatusAlert');
        alertBox.className = 'alert alert-info py-2 px-3 small rounded-3';
        alertBox.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing connection to Telegram API...';
        alertBox.classList.remove('d-none');

        try {
            const res = await fetch('{{ route("telegram.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.ok) {
                alertBox.className = 'alert alert-success py-2 px-3 small rounded-3';
                alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> Connected to @' + (data.result?.username || 'Bot') + ' successfully!';
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3';
                alertBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Connection failed: ' + (data.description || 'Check Bot Token');
            }
        } catch(err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3';
            alertBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Network error testing bot.';
        }
    }

    function fillDefaultServerUrl() {
        const input = document.getElementById('webhookUrlInput');
        const isHttps = window.location.protocol === 'https:';
        let defaultUrl = '{{ url('/telegram/webhook') }}';
        if (isHttps && defaultUrl.startsWith('http://')) {
            defaultUrl = defaultUrl.replace('http://', 'https://');
        }
        input.value = defaultUrl;
    }

    async function refreshWebhookDiagnostics() {
        const btn = document.getElementById('btnRefreshWebhook');
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Checking...'; }
        const alertBox = document.getElementById('webhookResultAlert');

        try {
            const res = await fetch('{{ route("telegram.webhook.info") }}', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.ok && data.result) {
                const r = data.result;
                const urlEl = document.getElementById('webhookDisplayUrl');
                const metricsEl = document.getElementById('webhookMetrics');
                const badge = document.getElementById('webhookBadge');

                if (urlEl) {
                    urlEl.textContent = r.url || 'No active webhook registered (Operating in Long-Polling mode)';
                }
                if (badge) {
                    if (r.url) {
                        badge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
                        badge.innerHTML = '<i class="fas fa-bolt text-success me-1" style="font-size: 0.55rem;"></i> Webhook Active';
                    } else {
                        badge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1';
                        badge.innerHTML = '<i class="fas fa-satellite-dish text-warning me-1" style="font-size: 0.55rem;"></i> Polling Mode';
                    }
                }
                if (metricsEl) {
                    metricsEl.innerHTML = `
                        <span>Pending Updates: <strong class="text-dark">${r.pending_update_count || 0}</strong></span>
                        <span>&bull; Max Conns: <strong class="text-dark">${r.max_connections || 40}</strong></span>
                        <span>&bull; Custom Cert: <strong class="text-dark">${r.has_custom_certificate ? 'Yes' : 'No'}</strong></span>
                    `;
                }
                if (alertBox) {
                    alertBox.className = 'alert alert-success py-2 px-3 small rounded-3 mt-2';
                    alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> Webhook status: ' + (r.url ? 'Live & registered (' + r.url + ')' : 'Operating in Long Polling mode.');
                    alertBox.classList.remove('d-none');
                }
            } else {
                if (alertBox) {
                    alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
                    alertBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Webhook check failed: ' + (data.description || 'Unknown error');
                    alertBox.classList.remove('d-none');
                }
            }
        } catch (e) {
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
                alertBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Network error checking webhook status.';
                alertBox.classList.remove('d-none');
            }
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    }

    async function syncUpdates(btn) {
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Syncing...'; }
        const alertBox = document.getElementById('webhookResultAlert');

        try {
            const res = await fetch('{{ route("telegram.poll") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (alertBox) {
                alertBox.className = data.ok ? 'alert alert-success py-2 px-3 small rounded-3 mt-2' : 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
                alertBox.innerHTML = data.ok 
                    ? '<i class="fas fa-check-circle me-1"></i> ' + (data.message || 'Synced updates successfully.') 
                    : '<i class="fas fa-times-circle me-1"></i> ' + (data.description || 'Failed to sync updates.');
                alertBox.classList.remove('d-none');
            }
        } catch(err) {
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
                alertBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Error syncing updates: ' + err.message;
                alertBox.classList.remove('d-none');
            }
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    }

    document.getElementById('setWebhookForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitWebhook');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Setting...';

        const alertBox = document.getElementById('webhookResultAlert');
        alertBox.classList.add('d-none');

        const url = document.getElementById('webhookUrlInput').value.trim();
        const dropPending = document.getElementById('dropPendingUpdates').checked;

        try {
            const res = await fetch('{{ route("telegram.webhook.set") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ url: url, drop_pending_updates: dropPending })
            });
            const data = await res.json();
            if (data.ok) {
                alertBox.className = 'alert alert-success py-2 px-3 small rounded-3 mt-2';
                alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> Webhook registered successfully! Telegram will push real-time events to: ' + url;
                alertBox.classList.remove('d-none');
                refreshWebhookDiagnostics();
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
                alertBox.innerHTML = '<i class="fas fa-circle-exclamation me-1"></i> Webhook registration failed: ' + (data.description || 'Telegram rejected the URL');
                alertBox.classList.remove('d-none');
            }
        } catch(err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-2';
            alertBox.innerHTML = '<i class="fas fa-triangle-exclamation me-1"></i> Server error registering webhook: ' + err.message;
            alertBox.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });

    document.getElementById('customChatIdInput').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('testChatIdSelect').value = '';
            document.getElementById('testChatIdSelect').removeAttribute('required');
        } else {
            document.getElementById('testChatIdSelect').setAttribute('required', 'required');
        }
    });

    document.getElementById('testChatIdSelect').addEventListener('change', function() {
        if (this.value !== '') {
            document.getElementById('customChatIdInput').value = '';
        }
    });

    document.getElementById('sendTestForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const customId = document.getElementById('customChatIdInput').value.trim();
        const selectId = document.getElementById('testChatIdSelect').value;
        const targetChatId = customId || selectId;
        const message = document.getElementById('testMessageText').value;
        const resultBox = document.getElementById('testMessageResult');

        if (!targetChatId) {
            alert('Please select a user or enter a Chat ID.');
            return;
        }

        resultBox.className = 'alert alert-info py-2 px-3 small rounded-3';
        resultBox.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending test message...';
        resultBox.classList.remove('d-none');

        try {
            const res = await fetch('{{ route("telegram.send_test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ chat_id: targetChatId, message: message })
            });
            const data = await res.json();
            if (data.ok) {
                resultBox.className = 'alert alert-success py-2 px-3 small rounded-3';
                resultBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> Message delivered successfully!';
            } else {
                resultBox.className = 'alert alert-danger py-2 px-3 small rounded-3';
                resultBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Delivery failed: ' + (data.description || 'Unknown error');
            }
        } catch(err) {
            resultBox.className = 'alert alert-danger py-2 px-3 small rounded-3';
            resultBox.innerHTML = '<i class="fas fa-times-circle me-1"></i> Server error sending message.';
        }
    });

    function quickSendPing(chatId, userName) {
        document.getElementById('customChatIdInput').value = chatId;
        document.getElementById('testChatIdSelect').value = '';
        document.getElementById('testMessageText').value = '☕ Greetings ' + userName + '! This is an automated connectivity test from the Kaldis Planning Portal.';
        document.getElementById('sendTestForm').scrollIntoView({ behavior: 'smooth' });
    }

    async function executeAutomation(task, btn) {
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Running...';
        }

        const alertBox = document.getElementById('automationExecutionAlert');
        const pre = document.getElementById('automationResultPre');
        const ts = document.getElementById('automationResultTimestamp');
        const title = document.getElementById('automationResultTitle');

        alertBox.className = 'alert alert-info py-2 px-3 small rounded-3 mt-3';
        title.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Executing automation: ' + task + '...';
        ts.textContent = new Date().toLocaleTimeString();
        pre.textContent = 'Please wait, evaluating reminder cycle and dispatching...';
        alertBox.classList.remove('d-none');

        try {
            const res = await fetch('{{ route("telegram.run_automation") }}?task=' + encodeURIComponent(task));
            const data = await res.json();
            if (data.ok) {
                alertBox.className = 'alert alert-success py-2 px-3 small rounded-3 mt-3';
                title.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Automation Completed Successfully [' + data.task + ']';
                ts.textContent = data.timestamp || new Date().toLocaleTimeString();
                pre.textContent = typeof data.output === 'object' ? JSON.stringify(data.output, null, 2) : String(data.output || 'Execution complete.');
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-3';
                title.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Automation Error';
                pre.textContent = data.error || 'Unknown error occurred during automation execution.';
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 mt-3';
            title.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Request Error';
            pre.textContent = 'Network or server error while running automation task: ' + err.message;
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }
</script>
@endsection
