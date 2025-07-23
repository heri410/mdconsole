<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position bearbeiten</title>
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
                    <div class="card-header">
                        <h4 class="mb-0">Position bearbeiten</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('positions.update', $position) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Kunde *</label>
                                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Kunde auswählen...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $position->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Positionsname *</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $position->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Beschreibung</label>
                                <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $position->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Menge *</label>
                                        <input type="number" name="quantity" id="quantity" step="0.01" min="0.01" 
                                               class="form-control @error('quantity') is-invalid @enderror" 
                                               value="{{ old('quantity', $position->quantity) }}" required>
                                        @error('quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="unit_name" class="form-label">Einheit *</label>
                                        <input type="text" name="unit_name" id="unit_name" 
                                               class="form-control @error('unit_name') is-invalid @enderror" 
                                               value="{{ old('unit_name', $position->unit_name) }}" required>
                                        @error('unit_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="unit_price" class="form-label">Einzelpreis (€) *</label>
                                        <input type="number" name="unit_price" id="unit_price" step="0.01" min="0.01" 
                                               class="form-control @error('unit_price') is-invalid @enderror" 
                                               value="{{ old('unit_price', $position->unit_price) }}" required>
                                        @error('unit_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="discount" class="form-label">Rabatt (%)</label>
                                        <input type="number" name="discount" id="discount" step="0.01" min="0" max="100" 
                                               class="form-control @error('discount') is-invalid @enderror" 
                                               value="{{ old('discount', $position->discount) }}">
                                        @error('discount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Berechnung:</h6>
                                        <div id="calculation">
                                            Berechnung wird geladen...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('positions.index') }}" class="btn btn-secondary">Zurück</a>
                                <button type="submit" class="btn btn-primary">Position aktualisieren</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Live-Berechnung
        function updateCalculation() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            
            if (quantity > 0 && unitPrice > 0) {
                const subtotal = quantity * unitPrice;
                const discountAmount = subtotal * (discount / 100);
                const total = subtotal - discountAmount;
                
                let calculation = `${quantity} × ${unitPrice.toFixed(2)}€ = ${subtotal.toFixed(2)}€`;
                if (discount > 0) {
                    calculation += `<br>Rabatt ${discount}%: -${discountAmount.toFixed(2)}€`;
                    calculation += `<br><strong>Gesamt: ${total.toFixed(2)}€</strong>`;
                } else {
                    calculation += `<br><strong>Gesamt: ${total.toFixed(2)}€</strong>`;
                }
                
                document.getElementById('calculation').innerHTML = calculation;
            } else {
                document.getElementById('calculation').innerHTML = 'Bitte füllen Sie Menge und Einzelpreis aus.';
            }
        }
        
        document.getElementById('quantity').addEventListener('input', updateCalculation);
        document.getElementById('unit_price').addEventListener('input', updateCalculation);
        document.getElementById('discount').addEventListener('input', updateCalculation);
        
        // Initial calculation
        updateCalculation();
    </script>
</body>
</html>
