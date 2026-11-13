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
                        <i class="fas fa-circle text-danger me-1" style="font-size: 0.55rem;"></i> Offline
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Manage bot connection token, test message delivery, and monitor user chat IDs.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.settings') }}#reminders" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-clock me-1"></i> Reminder Schedules
            </a>
            @if(!empty($botInfo['result']['username']))
                <a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">
                    <i class="fab fa-telegram me-1"></i> Open @<span>{{ $botInfo['result']['username'] }}</span>
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- ── Card 1: Bot Connection & Token ── -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-robot text-primary me-2"></i> Bot Connection &amp; Token
                    </h6>
                    <p class="text-muted small mb-0">Telegram Bot authentication credentials.</p>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="fab fa-telegram-plane fs-4"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-dark fs-6">{{ $botInfo['result']['first_name'] ?? 'Kaldis Planning Bot' }}</div>
                            <div class="text-muted small">
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

        <!-- ── Card 2: Send Test Message ── -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-paper-plane text-success me-2"></i> Send Test Message
                    </h6>
                    <p class="text-muted small mb-0">Verify instant message delivery to any Telegram chat ID.</p>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <form method="POST" action="{{ route('telegram.send_test') }}" id="sendTestForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Recipient</label>
                            <select name="chat_id" id="testChatIdSelect" class="form-select form-select-sm" required>
                                <option value="">-- Choose User or Enter Custom ID --</option>
                                @foreach($users as $u)
                                    @if(!empty($u->telegram_chat_id))
                                        <option value="{{ $u->telegram_chat_id }}">
                                            {{ $u->full_name ?? $u->username }} ({{ $u->department->department_name ?? 'Admin' }}) - ID: {{ $u->telegram_chat_id }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Or Enter Custom Chat ID</label>
                            <input type="text" id="customChatIdInput" class="form-control form-control-sm font-monospace" placeholder="e.g. 123456789">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Test Message</label>
                            <textarea name="message" id="testMessageText" class="form-control form-control-sm" rows="2" required>☕ Kaldis Planning Portal: Telegram test connection verified successfully!</textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" id="btnSendTest">
                            <i class="fas fa-paper-plane me-1"></i> Send Test Message
                        </button>
                    </form>

                    <div id="testMessageResult" class="alert d-none py-2 px-3 small rounded-3 mb-0"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Card 3: Department Heads & Linked Telegram Accounts ── -->
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
</script>
@endsection
