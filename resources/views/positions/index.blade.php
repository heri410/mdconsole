<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Positionsverwaltung</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <img src="{{ Vite::asset('resources/images/logo-breit.png') }}" alt="Logo" style="height:40px; margin-right:10px;">
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link active" href="{{ route('positions.index') }}">Positionen</a>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Positionsverwaltung</h3>
                            <a href="{{ route('positions.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Neue Position
                            </a>
                        </div>
                        
                        <!-- Filter -->
                        <div class="collapse mb-3" id="filterPanel">
                            <form method="GET" action="{{ route('positions.index') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Kunde</label>
                                    <select name="customer_id" class="form-select">
                                        <option value="">Alle Kunden</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->company_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="billed" class="form-select">
                                        <option value="">Alle</option>
                                        <option value="0" {{ request('billed') === '0' ? 'selected' : '' }}>Nicht abgerechnet</option>
                                        <option value="1" {{ request('billed') === '1' ? 'selected' : '' }}>Abgerechnet</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Suche</label>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Position suchen..." class="form-control">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Filtern</button>
                                    <a href="{{ route('positions.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                        
                        <button class="btn btn-outline-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        
                        @if($positions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kunde</th>
                                            <th>Position</th>
                                            <th>Menge</th>
                                            <th>Einzelpreis</th>
                                            <th>Rabatt</th>
                                            <th>Gesamt</th>
                                            <th>Status</th>
                                            <th>Aktionen</th>
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
                                                <td>{{ number_format($position->total_amount, 2, ',', '.') }} €</td>
                                                <td>
                                                    @if($position->billed)
                                                        <span class="badge bg-success">Abgerechnet</span>
                                                        @if($position->billed_at)
                                                            <br><small class="text-muted">{{ $position->billed_at->format('d.m.Y') }}</small>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-warning text-dark">Offen</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('positions.show', $position) }}" class="btn btn-sm btn-outline-primary" title="Anzeigen">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        @if(!$position->billed)
                                                            <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-outline-secondary" title="Bearbeiten">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="if(confirm('Position wirklich löschen?')) { document.getElementById('delete-form-{{ $position->id }}').submit(); }" 
                                                                    title="Löschen">
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
                                {{ $positions->appends(request()->query())->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <h3 class="mb-2">Keine Positionen gefunden</h3>
                                <p class="text-muted">Erstellen Sie Ihre erste Position.</p>
                                <a href="{{ route('positions.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus"></i> Neue Position erstellen
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
