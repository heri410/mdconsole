

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.dashboard') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
        <nav class="navbar navbar-dark bg-dark border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name', 'Rechnungsportal') }}</a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">
                        Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a class="dropdown-item" href="{{ route('positions.index') }}">Positionen verwalten</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">Abmelden</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <h4 class="alert-heading">{{ __('ui.welcome_back') }}</h4>
                    <p>{{ __('ui.invoice_overview') }}</p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">{{ __('ui.your_invoices') }}</h3>
                            <div class="d-flex align-items-center gap-3">
                                @php
                                    $openInvoices = 0;
                                    foreach($invoices as $invoice) {
                                        if($invoice->status === 'open') {
                                            $openInvoices++;
                                        }
                                    }
                                @endphp
                                @if($openInvoices > 0)
                                    <form action="{{ route('paypal.bulk.pay') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-paypal"></i> Alle offenen Rechnungen zahlen ({{ $openInvoices }})
                                        </button>
                                    </form>
                                @endif
                                <span class="text-muted">{{ trans_choice('ui.invoice_count', $invoices->total(), ['count' => $invoices->total()]) }}</span>
                            </div>
                        </div>
                        @php
                            $filterActive = count(request()->query()) > 0;
                        @endphp
                        <button class="btn btn-outline-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="{{ $filterActive ? 'true' : 'false' }}" aria-controls="filterPanel">
                            <i class="bi bi-funnel"></i> {{ __('ui.filter_search') }}
                        </button>
                        <div class="collapse mb-3{{ $filterActive ? ' show' : '' }}" id="filterPanel">
                            <form method="GET" action="{{ route('dashboard') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('ui.status') }}</label>
                                    <select name="status" class="form-select">
                                        <option value="">{{ __('ui.all_status') }}</option>
                                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ __('ui.open') }}</option>
                                        <option value="paidoff" {{ request('status') === 'paidoff' ? 'selected' : '' }}>{{ __('ui.paid') }}</option>
                                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('ui.overdue') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('ui.from_date') }}</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('ui.to_date') }}</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('ui.invoice_number') }}</label>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search_placeholder') }}" class="form-control">
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> {{ __('ui.apply_filter') }}</button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">{{ __('ui.reset_filter') }}</a>
                                </div>
                            </form>
                        </div>
                        @if($invoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('ui.invoice_number') }}</th>
                                            <th>{{ __('ui.date') }}</th>
                                            <th>{{ __('ui.due_date') }}</th>
                                            <th>{{ __('ui.amount') }}</th>
                                            <th>{{ __('ui.status') }}</th>
                                            <th>{{ __('ui.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->number ?: 'N/A' }}</td>
                                                <td>{{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') : 'N/A' }}</td>
                                                <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') : 'N/A' }}</td>
                                                <td>{{ number_format($invoice->total_amount, 2, ',', '.') }} €</td>
                                                <td>
                                                    @php
                                                        $statusClass = match($invoice->status) {
                                                            'open' => 'badge bg-warning text-dark',
                                                            'paidoff' => 'badge bg-success',
                                                            'overdue' => 'badge bg-danger',
                                                            default => 'badge bg-secondary'
                                                        };
                                                        $statusText = match($invoice->status) {
                                                            'open' => __('ui.open'),
                                                            'paidoff' => __('ui.paid'),
                                                            'overdue' => __('ui.overdue'),
                                                            default => ucfirst($invoice->status ?: 'Unbekannt')
                                                        };
                                                    @endphp
                                                    <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('invoice.download', $invoice->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-download"></i> {{ __('ui.download_pdf') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $invoices->appends(request()->query())->links('custom.pagination') }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <svg class="mx-auto" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="mb-2">{{ __('ui.no_invoices') }}</h3>
                                <p class="text-muted">{{ __('ui.no_invoices_text') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

