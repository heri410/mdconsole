
@extends('layouts.app')
@section('title', __('ui.dashboard'))
@section('content')
    <h2 class="mb-4">{{ __('ui.dashboard') }}</h2>
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @php $openInvoices = $invoices->where('status', 'open')->count(); @endphp
    @if($openInvoices > 0)
        <form action="{{ route('paypal.bulk.pay') }}" method="POST" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-warning w-100">
                {{ __('ui.pay_all_open') }} ({{ $openInvoices }})
            </button>
        </form>
    @endif
    @if($invoices->count() > 0)
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
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
                        @php
                            $badgeClass = match($invoice->status) {
                                'open' => 'badge bg-warning text-dark',
                                'paidoff' => 'badge bg-success',
                                'overdue' => 'badge bg-danger',
                                default => 'badge bg-secondary'
                            };
                            $statusText = __('ui.' . $invoice->status) ?: ucfirst($invoice->status);
                        @endphp
                        <tr>
                            <td>{{ $invoice->number ?? 'N/A' }}</td>
                            <td>{{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') : 'N/A' }}</td>
                            <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') : 'N/A' }}</td>
                            <td>{{ number_format($invoice->total_amount,2,',','.') }} €</td>
                            <td><span class="{{ $badgeClass }}">{{ $statusText }}</span></td>
                            <td>
                                <a href="{{ route('invoice.download',$invoice->id) }}" class="btn btn-sm btn-outline-light">
                                    {{ __('ui.download_pdf') }}
                                </a>
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
            <h4>{{ __('ui.no_invoices') }}</h4>
            <p class="text-muted">{{ __('ui.no_invoices_text') }}</p>
        </div>
    @endif
@endsection

