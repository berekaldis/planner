@extends('layouts.app')

@section('title', 'Telegram Bot Status & Integration')

@section('content')
<div class="container-fluid px-0">

    <!-- React-Style Pill Navigation Switcher -->
    <div class="react-nav-pills mb-4">
        <a href="{{ route('admin.settings') }}" class="nav-link">
            <i class="fas fa-sliders-h text-warning"></i> System Parameters
        </a>
        <a href="{{ route('telegram.status') }}" class="nav-link active">
            <i class="fab fa-telegram text-info"></i> Telegram Bot &amp; Webhook
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0 text-dark">Telegram Bot Status &amp; Integration</h4>
                @if(!empty($botInfo['ok']))
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-circle text-success me-1" style="font-size: 0.55rem;"></i> Live &amp; Connected
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        <i class="fas fa-circle text-danger me-1" style="font-size: 0.55rem;"></i> Offline / Unreachable
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Monitor live bot connection status, test messaging, manage webhook/polling, and verify user chat ID linkages.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnRunAutomation" class="btn btn-sm btn-success d-inline-flex align-items-center gap-2" title="Trigger Automated Schedule & Reminders Immediately">
                <i class="fas fa-play"></i> Run Automation
            </button>
            <button type="button" id="btnPingBot" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
                <i class="fas fa-rotate"></i> Ping Connection
            </button>
            <button type="button" id="btnPollNow" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="fas fa-arrows-rotate"></i> Sync Updates
            </button>
            @if(!empty($botInfo['result']['username']))
                <a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" class="btn btn-sm btn-warning fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="fab fa-telegram"></i> Open @<span>{{ $botInfo['result']['username'] }}</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fab fa-telegram-plane fs-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Bot Account</div>
                        <div class="fw-bold text-dark text-truncate fs-6">
                            {{ $botInfo['result']['first_name'] ?? 'Kaldis Bot' }}
                        </div>
                        <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                            @if(!empty($botInfo['result']['username']))
                                @<span>{{ $botInfo['result']['username'] }}</span>
                            @else
                                Token Masked
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle {{ !empty($webhookInfo['result']['url']) ? 'bg-info bg-opacity-10 text-info' : 'bg-secondary bg-opacity-10 text-secondary' }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas {{ !empty($webhookInfo['result']['url']) ? 'fa-bolt' : 'fa-satellite-dish' }} fs-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Delivery Mode</div>
                        <div class="fw-bold text-dark fs-6">
                            {{ !empty($webhookInfo['result']['url']) ? 'Webhook Active' : 'Polling Active' }}
                        </div>
                        <div class="small text-muted text-truncate" style="font-size: 0.72rem;">
                            {{ !empty($webhookInfo['result']['url']) ? Str::limit($webhookInfo['result']['url'], 26) : 'Manual / Cron Poll' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-paper-plane fs-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Sent Messages</div>
                        <div class="fw-bold text-dark fs-5">
                            {{ number_format($sentCount) }}
                        </div>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Notifications &amp; Reminders
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-inbox fs-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Received Messages</div>
                        <div class="fw-bold text-dark fs-5">
                            {{ number_format($receivedCount) }}
                        </div>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Commands &amp; Submissions
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">

        <!-- Left Column: Bot Details, Quick Send & Webhook Settings -->
        <div class="col-lg-5">

            <!-- Bot Identity Card -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-robot text-warning"></i> Bot Profile &amp; Credentials
                    </h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted small fw-semibold" style="width: 35%;">Bot ID:</th>
                                <td class="text-dark small font-monospace">{{ $botInfo['result']['id'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted small fw-semibold">Bot Name:</th>
                                <td class="text-dark small fw-semibold">{{ $botInfo['result']['first_name'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted small fw-semibold">Username:</th>
                                <td class="small">
                                    @if(!empty($botInfo['result']['username']))
                                        <a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" class="fw-bold text-primary">
                                            @<span>{{ $botInfo['result']['username'] }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted small fw-semibold">Active Token:</th>
                                <td class="small font-monospace text-muted">{{ $maskedToken ?: 'Not set' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted small fw-semibold">Pending Updates:</th>
                                <td class="small">
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $webhookInfo['result']['pending_update_count'] ?? 0 }} pending
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @if(!empty($webhookInfo['result']['last_error_message']))
                        <div class="alert alert-warning py-2 px-3 small mt-3 mb-0 rounded-3">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            <strong>Webhook Error:</strong> {{ $webhookInfo['result']['last_error_message'] }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Test Send Message -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-paper-plane text-success"></i> Send Test Message
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form id="testMessageForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Select Recipient User</label>
                            <select name="user_id" id="recipientUserSelect" class="form-select form-select-sm">
                                <option value="">-- Or enter Custom Chat ID below --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ $u->telegram_chat_id ? '' : 'disabled' }}>
                                        {{ $u->full_name }} ({{ $u->username }}) — {{ $u->department->department_name ?? 'Kaldis' }}
                                        {{ $u->telegram_chat_id ? '[' . $u->telegram_chat_id . ']' : '[No Chat ID]' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Users with a linked Telegram Chat ID can receive direct messages.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Custom Telegram Chat ID (Optional)</label>
                            <input type="text" name="chat_id" id="customChatIdInput" class="form-control form-control-sm font-monospace" placeholder="e.g. 123456789">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Message Content</label>
                            <textarea name="message" id="testMessageText" class="form-control form-control-sm" rows="3" placeholder="Hello from Kaldis Planning &amp; Performance Portal!" required>🔔 [Kaldis Planning Portal Test] Hello! Telegram notification service is fully online.</textarea>
                        </div>

                        <button type="submit" id="btnSubmitTestMessage" class="btn btn-sm btn-primary w-100 fw-semibold">
                            <i class="fas fa-paper-plane me-1"></i> Send Test Notification
                        </button>
                    </form>

                    <div id="testMessageAlert" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- Webhook Configuration & Diagnostics Card -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-network-wired text-info"></i> Webhook Configuration
                    </h6>
                    <button type="button" id="btnRefreshWebhook" class="btn btn-sm btn-link text-info p-0 text-decoration-none" title="Refresh Live Webhook Diagnostics">
                        <i class="fas fa-rotate"></i> Check Status
                    </button>
                </div>
                <div class="card-body p-3">
                    <!-- Current Webhook Status Banner -->
                    <div id="webhookLiveStatus" class="p-3 rounded-3 mb-3 border {{ !empty($webhookInfo['result']['url']) ? 'bg-info bg-opacity-10 border-info-subtle' : 'bg-light border-light-subtle' }}">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.72rem;">Current Delivery Mode</span>
                            @if(!empty($webhookInfo['result']['url']))
                                <span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i> Webhook Active</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1"><i class="fas fa-satellite-dish me-1"></i> Long Polling Active</span>
                            @endif
                        </div>
                        <div class="font-monospace small text-break text-dark fw-bold mb-1" id="webhookDisplayUrl">
                            {{ ($webhookInfo['result']['url'] ?? '') ?: 'No active webhook registered (Polling mode)' }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 text-muted small mt-2" style="font-size: 0.75rem;" id="webhookMetrics">
                            <span>Pending Updates: <strong class="text-dark">{{ $webhookInfo['result']['pending_update_count'] ?? 0 }}</strong></span>
                            @if(isset($webhookInfo['result']['max_connections']))
                                <span>&bull; Max Conns: <strong class="text-dark">{{ $webhookInfo['result']['max_connections'] }}</strong></span>
                            @endif
                            @if(isset($webhookInfo['result']['has_custom_certificate']))
                                <span>&bull; Custom Cert: <strong class="text-dark">{{ $webhookInfo['result']['has_custom_certificate'] ? 'Yes' : 'No' }}</strong></span>
                            @endif
                        </div>
                        @if(!empty($webhookInfo['result']['last_error_message']))
                            <div class="alert alert-warning py-1 px-2 mb-0 mt-2 small" style="font-size: 0.74rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Last Error ({{ date('Y-m-d H:i:s', $webhookInfo['result']['last_error_date'] ?? time()) }}):
                                {{ $webhookInfo['result']['last_error_message'] }}
                            </div>
                        @endif
                    </div>

                    <!-- Webhook Setup Form -->
                    <form method="POST" action="{{ route('telegram.webhook.set') }}" id="webhookConfigForm" class="mb-3">
                        @csrf
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-muted mb-0">Webhook URL (HTTPS Required)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="btnFillLocaltunnelUrl" class="btn btn-sm btn-link text-warning p-0 text-decoration-none fw-semibold" style="font-size: 0.72rem;">
                                        <i class="fas fa-bolt"></i> Use Localtunnel URL
                                    </button>
                                    <span class="text-muted" style="font-size: 0.72rem;">|</span>
                                    <button type="button" id="btnFillAppUrl" class="btn btn-sm btn-link text-primary p-0 text-decoration-none" style="font-size: 0.72rem;">
                                        Use Server URL
                                    </button>
                                </div>
                            </div>
                            <input type="url" name="url" id="webhookUrlInput" class="form-control form-control-sm font-monospace" placeholder="{{ url('/telegram/webhook') }}" value="{{ ($webhookInfo['result']['url'] ?? '') ?: url('/telegram/webhook') }}" required>

                        <div class="row g-2 mb-2">
                            <div class="col-sm-7">
                                <label class="form-label small fw-semibold text-muted mb-1">Secret Token (Optional)</label>
                                <input type="text" name="secret_token" class="form-control form-control-sm font-monospace" placeholder="e.g. kaldis_secret_token" maxlength="256">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Max Connections</label>
                                <select name="max_connections" class="form-select form-select-sm">
                                    <option value="20">20</option>
                                    <option value="40" selected>40 (Default)</option>
                                    <option value="60">60</option>
                                    <option value="100">100 (Max)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="drop_pending_updates" value="1" id="dropPendingUpdates">
                            <label class="form-check-label small text-muted" for="dropPendingUpdates">
                                Drop pending updates upon setup
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-semibold">
                                <i class="fas fa-cloud-arrow-up me-1"></i> Deploy / Update Webhook
                            </button>
                        </div>
                    </form>

                    <!-- Remove Webhook / Switch to Polling Button -->
                    @if(!empty($webhookInfo['result']['url']))
                        <form method="POST" action="{{ route('telegram.webhook.delete') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Switch back to long-polling mode? Telegram will stop sending real-time webhook HTTP requests.');">
                                <i class="fas fa-trash me-1"></i> Remove Webhook (Switch to Polling)
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>

        <!-- Right Column: User Chat ID Linkages & Bot Commands & Message Log -->
        <div class="col-lg-7">

            <!-- Linked Users Directory -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-users text-primary"></i> Team Chat ID Directory
                    </h6>
                    <span class="badge bg-primary-subtle text-primary">
                        {{ $users->whereNotNull('telegram_chat_id')->count() }} / {{ $users->count() }} Linked
                    </span>
                </div>
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.84rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Telegram Chat ID</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $u->full_name }}</div>
                                        <div class="text-muted small font-monospace">{{ $u->username }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">
                                            {{ $u->role->display_name ?? $u->role->name }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $u->department->department_name ?? 'Company Wide' }}
                                    </td>
                                    <td>
                                        @if($u->telegram_chat_id)
                                            <span class="font-monospace text-dark fw-bold">{{ $u->telegram_chat_id }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Not linked</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($u->telegram_chat_id)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check me-1"></i> Active
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light py-2 text-muted small">
                    <i class="fas fa-circle-info text-info me-1"></i>
                    To link an account, send <code>/start</code> to <strong>@<span>{{ $botInfo['result']['username'] ?? 'KaldisPlannerbot' }}</span></strong> from your Telegram app.
                </div>
            </div>

            <!-- Bot Commands Guide -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-terminal text-secondary"></i> Supported Bot Commands
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/start</div>
                                <div class="text-muted small">Registers user and binds Telegram Chat ID to the system user account.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/status</div>
                                <div class="text-muted small">Returns the department's weekly task count, completion percentage, and submission state.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/report</div>
                                <div class="text-muted small">Returns a comprehensive breakdown of DONE vs NOT DONE tasks for the current week.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/achievement &lt;text&gt;</div>
                                <div class="text-muted small">Instantly logs an official weekly achievement directly into the department workspace.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/challenge &lt;text&gt;</div>
                                <div class="text-muted small">Instantly records a weekly operational challenge or bottleneck.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-2 bg-light-subtle h-100">
                                <div class="fw-bold font-monospace text-primary small">/help</div>
                                <div class="text-muted small">Displays the interactive guide and list of available portal commands.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Messages Audit Trail -->
            <div class="card border border-light-subtle shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-list-check text-muted"></i> Recent Telegram Message Trail
                    </h6>
                    <span class="badge bg-secondary-subtle text-secondary">{{ count($recentMessages) }} logged</span>
                </div>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Direction</th>
                                <th>Chat ID</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th class="text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMessages as $msg)
                                <tr>
                                    <td>
                                        @if(($msg->direction ?? '') === 'OUTGOING')
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="fas fa-arrow-up me-1"></i> OUT
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-arrow-down me-1"></i> IN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="font-monospace text-muted">{{ $msg->chat_id ?? 'N/A' }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 280px;" title="{{ $msg->message_text ?? '' }}">
                                            {{ $msg->message_text ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if(($msg->status ?? '') === 'SENT' || ($msg->status ?? '') === 'DELIVERED')
                                            <span class="badge bg-success-subtle text-success">Delivered</span>
                                        @elseif(($msg->status ?? '') === 'FAILED')
                                            <span class="badge bg-danger-subtle text-danger">Failed</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ $msg->status ?? 'OK' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted small font-monospace">
                                        {{ !empty($msg->created_at) ? \Carbon\Carbon::parse($msg->created_at)->diffForHumans() : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-envelope-open fs-4 d-block mb-2 text-secondary opacity-50"></i>
                                        No Telegram messages logged yet. Use the test sender or Telegram app to initiate messages.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Interactive Modals / Scripts -->
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pingBtn = document.getElementById('btnPingBot');
    const pollBtn = document.getElementById('btnPollNow');
    const testForm = document.getElementById('testMessageForm');
    const autoBtn = document.getElementById('btnRunAutomation');
    const testAlert = document.getElementById('testMessageAlert');
    const submitBtn = document.getElementById('btnSubmitTestMessage');

    // Run Automation Now
    if (autoBtn) {
        autoBtn.addEventListener('click', function () {
            autoBtn.disabled = true;
            autoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';

            fetch('{{ route('telegram.run_automation') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                autoBtn.disabled = false;
                autoBtn.innerHTML = '<i class="fas fa-play"></i> Run Automation';
                if (data.ok) {
                    alert('✅ Automation Triggered Successfully!\n\nExecuted at: ' + data.timestamp + '\nOutput: ' + JSON.stringify(data.output, null, 2));
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('❌ Automation Error: ' + (data.error || 'Execution failed'));
                }
            })
            .catch(err => {
                autoBtn.disabled = false;
                autoBtn.innerHTML = '<i class="fas fa-play"></i> Run Automation';
                alert('❌ Automation Error: ' + err.message);
            });
        });
    }

    // Ping Bot Connection
    if (pingBtn) {
        pingBtn.addEventListener('click', function () {
            pingBtn.disabled = true;
            pingBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';

            fetch('{{ route('telegram.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                pingBtn.disabled = false;
                pingBtn.innerHTML = '<i class="fas fa-rotate"></i> Ping Connection';
                if (data.ok) {
                    alert('✅ Telegram Bot Connection OK!\n\nBot: ' + data.result.first_name + ' (@' + data.result.username + ')\nID: ' + data.result.id);
                } else {
                    alert('❌ Connection Failed:\n' + (data.description || 'Unknown error'));
                }
            })
            .catch(err => {
                pingBtn.disabled = false;
                pingBtn.innerHTML = '<i class="fas fa-rotate"></i> Ping Connection';
                alert('❌ Network / Server Error: ' + err.message);
            });
        });
    }

    // Sync updates (Polling cycle)
    if (pollBtn) {
        pollBtn.addEventListener('click', function () {
            pollBtn.disabled = true;
            pollBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Polling...';

            fetch('{{ route('telegram.poll') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                pollBtn.disabled = false;
                pollBtn.innerHTML = '<i class="fas fa-arrows-rotate"></i> Sync Updates';
                alert(data.message || 'Synced updates successfully');
                if (data.processed_count > 0) {
                    location.reload();
                }
            })
            .catch(err => {
                pollBtn.disabled = false;
                pollBtn.innerHTML = '<i class="fas fa-arrows-rotate"></i> Sync Updates';
                alert('Polling error: ' + err.message);
            });
        });
    }

    // Quick Test Message Sender
    if (testForm) {
        testForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';
            testAlert.classList.add('d-none');

            const payload = {
                user_id: document.getElementById('recipientUserSelect').value || null,
                chat_id: document.getElementById('customChatIdInput').value || null,
                message: document.getElementById('testMessageText').value
            };

            fetch('{{ route('telegram.send_test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Test Notification';

                testAlert.classList.remove('d-none', 'alert-success', 'alert-danger');

                if (body.ok) {
                    testAlert.classList.add('alert', 'alert-success');
                    testAlert.innerHTML = '<i class="fas fa-check-circle me-1"></i> Test notification sent successfully to Telegram!';
                } else {
                    testAlert.classList.add('alert', 'alert-danger');
                    testAlert.innerHTML = '<i class="fas fa-circle-exclamation me-1"></i> ' + (body.description || 'Failed to send message.');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Test Notification';
                testAlert.classList.remove('d-none', 'alert-success');
                testAlert.classList.add('alert', 'alert-danger');
                testAlert.innerHTML = '<i class="fas fa-triangle-exclamation me-1"></i> ' + err.message;
            });
        });
    }

    // Webhook Diagnostics & Autofill
    const fillBtn = document.getElementById('btnFillAppUrl');
    const fillLocaltunnelBtn = document.getElementById('btnFillLocaltunnelUrl');
    const webhookUrlInput = document.getElementById('webhookUrlInput');
    const refreshWebhookBtn = document.getElementById('btnRefreshWebhook');

    if (fillLocaltunnelBtn && webhookUrlInput) {
        fillLocaltunnelBtn.addEventListener('click', function () {
            webhookUrlInput.value = "https://cold-ducks-grab.loca.lt/telegram/webhook";
            if (window.Toast) {
                window.Toast.info("Localtunnel webhook URL set: " + webhookUrlInput.value);
            }
        });
    }

    if (fillBtn && webhookUrlInput) {
        fillBtn.addEventListener('click', function () {
            webhookUrlInput.value = "{{ url('/telegram/webhook') }}";
            if (window.Toast) {
                window.Toast.info("Server webhook endpoint populated: " + webhookUrlInput.value);
            }
        });
    }

    if (refreshWebhookBtn) {
        refreshWebhookBtn.addEventListener('click', function () {
            refreshWebhookBtn.disabled = true;
            refreshWebhookBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';

            fetch('{{ route('telegram.webhook.info') }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                refreshWebhookBtn.disabled = false;
                refreshWebhookBtn.innerHTML = '<i class="fas fa-rotate"></i> Check Status';

                if (data.ok && data.result) {
                    const r = data.result;
                    const urlEl = document.getElementById('webhookDisplayUrl');
                    const metricsEl = document.getElementById('webhookMetrics');
                    if (urlEl) {
                        urlEl.textContent = r.url || 'No active webhook registered (Polling mode)';
                    }
                    if (metricsEl) {
                        metricsEl.innerHTML = `
                            <span>Pending Updates: <strong class="text-dark">${r.pending_update_count || 0}</strong></span>
                            ${r.max_connections ? `<span>&bull; Max Conns: <strong class="text-dark">${r.max_connections}</strong></span>` : ''}
                            ${r.has_custom_certificate !== undefined ? `<span>&bull; Custom Cert: <strong class="text-dark">${r.has_custom_certificate ? 'Yes' : 'No'}</strong></span>` : ''}
                        `;
                    }
                    if (window.Toast) {
                        window.Toast.success(r.url ? "Live webhook is reachable and registered." : "Currently operating in Long Polling mode.");
                    }
                } else {
                    if (window.Toast) {
                        window.Toast.error(data.description || "Could not fetch webhook info.");
                    }
                }
            })
            .catch(err => {
                refreshWebhookBtn.disabled = false;
                refreshWebhookBtn.innerHTML = '<i class="fas fa-rotate"></i> Check Status';
                if (window.Toast) {
                    window.Toast.error("Failed to check webhook: " + err.message);
                }
            });
        });
    }
});
</script>
@endsection
