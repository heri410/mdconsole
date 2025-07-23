@extends('layouts.app')
@section('title', $domain->fqdn . ' - ' . __('ui.domain_details', 'Domain Details'))
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ $domain->fqdn }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('domains.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                {{ __('ui.back_to_domains', 'Back to Domains') }}
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Domain Information -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.domain_information', 'Domain Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('ui.domain', 'Domain') }}:</dt>
                                <dd class="col-sm-8">
                                    <strong>{{ $domain->fqdn }}</strong>
                                    @if($domain->is_expiring_soon)
                                        <span class="badge bg-warning text-dark ms-2">{{ __('ui.expiring_soon', 'Expiring Soon') }}</span>
                                    @elseif($domain->is_expired)
                                        <span class="badge bg-danger ms-2">{{ __('ui.expired', 'Expired') }}</span>
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">{{ __('ui.tld', 'TLD') }}:</dt>
                                <dd class="col-sm-8">{{ $domain->tld }}</dd>
                                
                                <dt class="col-sm-4">{{ __('ui.status', 'Status') }}:</dt>
                                <dd class="col-sm-8">
                                    @php
                                        $statusClass = match($domain->status) {
                                            'active' => 'badge bg-success',
                                            'inactive' => 'badge bg-secondary',
                                            'expired' => 'badge bg-danger',
                                            default => 'badge bg-secondary'
                                        };
                                    @endphp
                                    <span class="{{ $statusClass }}">{{ __('' . $domain->status, ucfirst($domain->status)) }}</span>
                                </dd>
                                
                                <dt class="col-sm-4">{{ __('ui.provider', 'Provider') }}:</dt>
                                <dd class="col-sm-8">{{ $domain->provider->name ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">{{ __('ui.register_date', 'Register Date') }}:</dt>
                                <dd class="col-sm-7">{{ $domain->register_date ? $domain->register_date->format('d.m.Y') : 'N/A' }}</dd>
                                
                                <dt class="col-sm-5">{{ __('ui.due_date', 'Due Date') }}:</dt>
                                <dd class="col-sm-7">
                                    @if($domain->due_date)
                                        @php
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
                                        <span class="{{ $expiryClass }}">{{ $domain->due_date->format('d.m.Y') }}</span>
                                        @if($daysUntilExpiry !== null)
                                            <br><small class="text-muted">
                                                @if($daysUntilExpiry < 0)
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    {{ abs($daysUntilExpiry) }} {{ __('ui.days_overdue', 'days overdue') }}
                                                @elseif($daysUntilExpiry === 0)
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ __('ui.expires_today', 'Expires today') }}
                                                @else
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ $daysUntilExpiry }} {{ __('ui.days_left', 'days left') }}
                                                @endif
                                            </small>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-5">{{ __('ui.billing_interval', 'Billing Interval') }}:</dt>
                                <dd class="col-sm-7">
                                    @if($domain->billing_interval)
                                        {{ $domain->billing_interval }} {{ __('ui.months', 'months') }}
                                    @else
                                        N/A
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Information -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.status_information', 'Status Information') }}</h6>
                </div>
                <div class="card-body text-center">
                    @if($domain->is_expired)
                        <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                        <h5 class="mt-2 text-danger">{{ __('ui.domain_expired', 'Domain Expired') }}</h5>
                        <p class="text-muted">{{ __('ui.domain_expired_text', 'This domain has expired and needs renewal.') }}</p>
                    @elseif($domain->is_expiring_soon)
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-2 text-warning">{{ __('ui.expiring_soon', 'Expiring Soon') }}</h5>
                        <p class="text-muted">{{ __('ui.expiring_soon_text', 'This domain will expire soon. Consider renewing it.') }}</p>
                    @else
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-2 text-success">{{ __('ui.domain_active', 'Domain Active') }}</h5>
                        <p class="text-muted">{{ __('ui.domain_active_text', 'This domain is active and functioning normally.') }}</p>
                    @endif
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.quick_actions', 'Quick Actions') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($domain->is_expired || $domain->is_expiring_soon)
                            <button class="btn btn-warning" disabled>
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                {{ __('ui.renew_domain', 'Renew Domain') }}
                            </button>
                            <small class="text-muted">{{ __('ui.contact_support_renewal', 'Contact support for domain renewal.') }}</small>
                        @endif
                        
                        <button class="btn btn-outline-primary" disabled>
                            <i class="bi bi-gear me-2"></i>
                            {{ __('ui.manage_domain', 'Manage Domain') }}
                        </button>
                        <small class="text-muted">{{ __('ui.manage_domain_disabled', 'Domain management coming soon.') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection