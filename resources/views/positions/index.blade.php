@extends('layouts.app')
@section('title', __('ui.manage_positions', 'Position Management'))
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __('ui.manage_positions', 'Position Management') }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('positions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i> {{ __('ui.new_position', 'New Position') }}
            </a>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="card mb-4">
        <div class="card-body">
            <button class="btn btn-outline-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="false" aria-controls="filterPanel">
                <i class="bi bi-funnel me-2"></i>{{ __('ui.filter', 'Filter') }}
            </button>
            
            <div class="collapse" id="filterPanel">
                <form method="GET" action="{{ route('positions.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.customer', 'Customer') }}</label>
                        <select name="customer_id" class="form-select">
                            <option value="">{{ __('ui.all_customers', 'All Customers') }}</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.status', 'Status') }}</label>
                        <select name="billed" class="form-select">
                            <option value="">{{ __('ui.all', 'All') }}</option>
                            <option value="0" {{ request('billed') === '0' ? 'selected' : '' }}>{{ __('ui.not_billed', 'Not Billed') }}</option>
                            <option value="1" {{ request('billed') === '1' ? 'selected' : '' }}>{{ __('ui.billed', 'Billed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.search', 'Search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search_position', 'Search position...') }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">{{ __('ui.filter', 'Filter') }}</button>
                        <a href="{{ route('positions.index') }}" class="btn btn-secondary">{{ __('ui.reset', 'Reset') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Positions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('ui.positions', 'Positions') }}</h6>
        </div>
        <div class="card-body">
            @if($positions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('ui.customer', 'Customer') }}</th>
                                <th>{{ __('ui.position', 'Position') }}</th>
                                <th>{{ __('ui.quantity', 'Quantity') }}</th>
                                <th>{{ __('ui.unit_price', 'Unit Price') }}</th>
                                <th>{{ __('ui.discount', 'Discount') }}</th>
                                <th>{{ __('ui.total', 'Total') }}</th>
                                <th>{{ __('ui.status', 'Status') }}</th>
                                <th>{{ __('ui.actions', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($positions as $position)
                                <tr>
                                    <td>{{ $position->customer->company_name }}</td>
                                    <td>
                                        <strong>{{ $position->name }}</strong>
                                        @if($position->description)
                                            <br><small class="text-muted">{{ $position->description }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $position->quantity }} {{ $position->unit_name }}</td>
                                    <td>{{ number_format($position->unit_price, 2, ',', '.') }} €</td>
                                    <td>{{ $position->discount }}%</td>
                                    <td><strong>{{ number_format($position->total_amount, 2, ',', '.') }} €</strong></td>
                                    <td>
                                        @if($position->billed)
                                            <span class="badge bg-success">{{ __('ui.billed', 'Billed') }}</span>
                                            @if($position->billed_at)
                                                <br><small class="text-muted">{{ $position->billed_at->format('d.m.Y') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark">{{ __('ui.open', 'Open') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('positions.show', $position) }}" class="btn btn-sm btn-outline-primary" title="{{ __('ui.view', 'View') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(!$position->billed)
                                                <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('ui.edit', 'Edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="if(confirm('{{ __('ui.confirm_delete_position', 'Really delete position?') }}')) { document.getElementById('delete-form-{{ $position->id }}').submit(); }" 
                                                        title="{{ __('ui.delete', 'Delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $position->id }}" action="{{ route('positions.destroy', $position) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $positions->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-list-ul text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">{{ __('ui.no_positions_found', 'No Positions Found') }}</h4>
                    <p class="text-muted">{{ __('ui.create_first_position', 'Create your first position.') }}</p>
                    <a href="{{ route('positions.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus me-1"></i> {{ __('ui.create_new_position', 'Create New Position') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
