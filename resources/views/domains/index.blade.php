@extends('layouts.app')
@section('title', __('ui.my_domains', 'My Domains'))
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __('ui.my_domains', 'My Domains') }}</h1>
    </div>

    <!-- Filter Options -->
    <div class="card mb-4">
        <div class="card-body">
            <button class="btn btn-outline-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="false" aria-controls="filterPanel">
                <i class="bi bi-funnel me-2"></i>{{ __('ui.filter', 'Filter') }}
            </button>
            
            <div class="collapse" id="filterPanel">
                <form method="GET" action="{{ route('domains.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.status', 'Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('ui.all_statuses', 'All Statuses') }}</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('ui.active', 'Active') }}</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('ui.inactive', 'Inactive') }}</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('ui.expired', 'Expired') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.search', 'Search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Domain name..." class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.expiring_soon', 'Expiring Soon') }}</label>
                        <select name="expiring_soon" class="form-select">
                            <option value="">{{ __('ui.all', 'All') }}</option>
                            <option value="1" {{ request('expiring_soon') ? 'selected' : '' }}>{{ __('ui.expiring_soon_only', 'Expiring Soon Only') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">{{ __('ui.filter', 'Filter') }}</button>
                        <a href="{{ route('domains.index') }}" class="btn btn-secondary">{{ __('ui.reset', 'Reset') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('ui.total_domains', 'Total Domains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $domains->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-globe text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('ui.active_domains', 'Active Domains') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $domains->where('status', 'active')->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('ui.expiring_soon', 'Expiring Soon') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $domains->filter(fn($d) => $d->is_expiring_soon)->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                {{ __('ui.expired', 'Expired') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $domains->filter(fn($d) => $d->is_expired)->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Domains Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.domains', 'Domains') }}</h6>
        </div>
        <div class="card-body">
            @if($domains->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('ui.domain', 'Domain') }}</th>
                                <th>{{ __('ui.tld', 'TLD') }}</th>
                                <th>{{ __('ui.register_date', 'Register Date') }}</th>
                                <th>{{ __('ui.due_date', 'Due Date') }}</th>
                                <th>{{ __('ui.provider', 'Provider') }}</th>
                                <th>{{ __('ui.status', 'Status') }}</th>
                                <th>{{ __('ui.actions', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($domains as $domain)
                                @php
                                    $statusClass = match($domain->status) {
                                        'active' => 'badge bg-success',
                                        'inactive' => 'badge bg-secondary',
                                        'expired' => 'badge bg-danger',
                                        default => 'badge bg-secondary'
                                    };
                                    
                                    $daysUntilExpiry = $domain->days_until_expiry;
                                    $expiryClass = '';
                                    if ($daysUntilExpiry !== null) {
                                        if ($daysUntilExpiry < 0) {
                                            $expiryClass = 'text-danger fw-bold';
                                        } elseif ($daysUntilExpiry <= 30) {
                                            $expiryClass = 'text-warning fw-bold';
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $domain->fqdn }}</strong>
                                        @if($domain->is_expiring_soon)
                                            <i class="bi bi-exclamation-triangle text-warning ms-1" title="{{ __('ui.expiring_soon', 'Expiring Soon') }}"></i>
                                        @elseif($domain->is_expired)
                                            <i class="bi bi-x-circle text-danger ms-1" title="{{ __('ui.expired', 'Expired') }}"></i>
                                        @endif
                                    </td>
                                    <td>{{ $domain->tld }}</td>
                                    <td>{{ $domain->register_date ? $domain->register_date->format('d.m.Y') : 'N/A' }}</td>
                                    <td class="{{ $expiryClass }}">
                                        {{ $domain->due_date ? $domain->due_date->format('d.m.Y') : 'N/A' }}
                                        @if($daysUntilExpiry !== null)
                                            <br><small class="text-muted">
                                                @if($daysUntilExpiry < 0)
                                                    {{ abs($daysUntilExpiry) }} {{ __('ui.days_overdue', 'days overdue') }}
                                                @elseif($daysUntilExpiry === 0)
                                                    {{ __('ui.expires_today', 'Expires today') }}
                                                @else
                                                    {{ $daysUntilExpiry }} {{ __('ui.days_left', 'days left') }}
                                                @endif
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $domain->provider->name ?? 'N/A' }}</td>
                                    <td><span class="{{ $statusClass }}">{{ __('' . $domain->status, ucfirst($domain->status)) }}</span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('domains.show', $domain) }}" class="btn btn-sm btn-outline-primary" title="{{ __('ui.view_details', 'View Details') }}">
                                                <i class="bi bi-eye"></i>
                                                <span class="d-none d-lg-inline ms-1">{{ __('ui.details', 'Details') }}</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $domains->appends(request()->query())->links('custom.pagination') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-globe text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">{{ __('ui.no_domains', 'No Domains Found') }}</h4>
                    <p class="text-muted">{{ __('ui.no_domains_text', 'You currently have no domains registered.') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection