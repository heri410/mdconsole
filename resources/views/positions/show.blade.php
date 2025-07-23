<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position anzeigen</title>
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
                <a class="nav-link" href="{{ route('positions.index') }}">Positionen</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Position Details</h4>
                        @if($position->billed)
                            <span class="badge bg-success">Abgerechnet</span>
                        @else
                            <span class="badge bg-warning text-dark">Offen</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Kunde:</strong></div>
                            <div class="col-sm-9">{{ $position->customer->company_name }}</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Position:</strong></div>
                            <div class="col-sm-9">{{ $position->name }}</div>
                        </div>
                        
                        @if($position->description)
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Beschreibung:</strong></div>
                            <div class="col-sm-9">{{ $position->description }}</div>
                        </div>
                        @endif
                        
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Menge:</strong></div>
                            <div class="col-sm-9">{{ $position->quantity }} {{ $position->unit_name }}</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Einzelpreis:</strong></div>
                            <div class="col-sm-9">{{ number_format($position->unit_price, 2, ',', '.') }} €</div>
                        </div>
                        
                        @if($position->discount > 0)
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Rabatt:</strong></div>
                            <div class="col-sm-9">{{ $position->discount }}% (-{{ number_format($position->discount_amount, 2, ',', '.') }} €)</div>
                        </div>
                        @endif
                        
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Gesamtbetrag:</strong></div>
                            <div class="col-sm-9"><strong>{{ number_format($position->total_amount, 2, ',', '.') }} €</strong></div>
                        </div>
                        
                        @if($position->billed)
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Abgerechnet am:</strong></div>
                            <div class="col-sm-9">{{ $position->billed_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        
                        @if($position->invoice)
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Rechnung:</strong></div>
                            <div class="col-sm-9">{{ $position->invoice->number }}</div>
                        </div>
                        @endif
                        @endif
                        
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Erstellt am:</strong></div>
                            <div class="col-sm-9">{{ $position->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('positions.index') }}" class="btn btn-secondary">Zurück zur Übersicht</a>
                            <div>
                                @if(!$position->billed)
                                    <a href="{{ route('positions.edit', $position) }}" class="btn btn-outline-primary me-2">
                                        <i class="bi bi-pencil"></i> Bearbeiten
                                    </a>
                                    <form action="{{ route('positions.destroy', $position) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Position wirklich löschen?')">
                                            <i class="bi bi-trash"></i> Löschen
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
