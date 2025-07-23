
@extends('layouts.app')
@section('title', __('ui.dashboard', 'Dashboard'))
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __('ui.dashboard', 'Dashboard') }}</h1>
    </div>
    
    @php $openInvoices = $invoices->where('status', 'open')->count(); @endphp
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('ui.total_invoices', 'Total Invoices') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $invoices->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-receipt text-gray-300" style="font-size: 2rem;"></i>
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
                                {{ __('ui.open_invoices', 'Open Invoices') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $openInvoices }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle text-gray-300" style="font-size: 2rem;"></i>
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
                                {{ __('ui.paid_invoices', 'Paid Invoices') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $invoices->where('status', 'paidoff')->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ __('ui.total_amount', 'Total Amount') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($invoices->sum('total_amount'), 2, ',', '.') }} €
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-euro text-gray-300" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pay All Open Invoices Button -->
    @if($openInvoices > 0)
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ __('ui.open_invoices', 'Open Invoices') }}</h5>
                        <p class="card-text text-muted">{{ __('ui.pay_all_open_text', 'You have :count open invoices that can be paid together.', ['count' => $openInvoices]) }}</p>
                    </div>
                    <form action="{{ route('paypal.bulk.pay') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-credit-card me-2"></i>
                            {{ __('ui.pay_all_open', 'Pay All Open') }} ({{ $openInvoices }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Invoices Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.invoices', 'Invoices') }}</h6>
        </div>
        <div class="card-body">
            @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('ui.invoice_number', 'Invoice Number') }}</th>
                                <th>{{ __('ui.date', 'Date') }}</th>
                                <th>{{ __('ui.due_date', 'Due Date') }}</th>
                                <th>{{ __('ui.amount', 'Amount') }}</th>
                                <th>{{ __('ui.status', 'Status') }}</th>
                                <th>{{ __('ui.actions', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                @php
                                    $badgeClass = match($invoice->status) {
                                        'open' => 'badge bg-warning text-dark',
                                        'paidoff' => 'badge bg-success',
                                        'overdue' => 'badge bg-danger',
                                        default => 'badge bg-secondary'
                                    };
                                    $statusText = __('ui.' . $invoice->status, ucfirst($invoice->status));
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $invoice->number ?? 'N/A' }}</strong>
                                    </td>
                                    <td>{{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') : 'N/A' }}</td>
                                    <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') : 'N/A' }}</td>
                                    <td>
                                        <strong>{{ number_format($invoice->total_amount, 2, ',', '.') }} €</strong>
                                    </td>
                                    <td><span class="{{ $badgeClass }}">{{ $statusText }}</span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('invoice.download', $invoice->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('ui.download_pdf', 'Download PDF') }}">
                                                <i class="bi bi-download"></i>
                                                <span class="d-none d-lg-inline ms-1">{{ __('ui.download_pdf', 'Download PDF') }}</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $invoices->links('custom.pagination') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">{{ __('ui.no_invoices', 'No Invoices') }}</h4>
                    <p class="text-muted">{{ __('ui.no_invoices_text', 'You have no invoices at the moment.') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection

