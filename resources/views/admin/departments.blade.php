@extends('layouts.app')

@section('title', 'Departments Management')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Company Departments</h4>
        <p class="text-muted small mb-0">Organizational units, department codes, and appointed heads</p>
    </div>
</div>

<!-- Departments Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-building me-1 text-secondary"></i> Departments ({{ $departments->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">Code</th>
                    <th>Department Name</th>
                    <th>Appointed Department Head</th>
                    <th style="width: 100px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $dept)
                    <tr>
                        <td>
                            <span class="badge bg-dark font-monospace">{{ $dept->department_code }}</span>
                        </td>
                        <td><strong class="text-dark">{{ $dept->department_name }}</strong></td>
                        <td>
                            @if($dept->head)
                                <span class="fw-semibold text-primary"><i class="fas fa-user-tie me-1"></i> {{ $dept->head->full_name }}</span>
                            @else
                                <span class="text-muted small">Not Appointed</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $dept->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $dept->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
