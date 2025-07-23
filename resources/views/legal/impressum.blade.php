@extends('layouts.app')
@section('title', 'Impressum')
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Impressum</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h3>Angaben gemäß § 5 TMG</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <h4>Unternehmen</h4>
                    <p>
                        <strong>{{ config('app.name', 'Ihr Unternehmen') }}</strong><br>
                        Musterstraße 123<br>
                        12345 Musterstadt<br>
                        Deutschland
                    </p>
                    
                    <h4>Kontakt</h4>
                    <p>
                        <strong>Telefon:</strong> +49 (0) 123 456789<br>
                        <strong>E-Mail:</strong> info@example.com<br>
                        <strong>Website:</strong> {{ config('app.url') }}
                    </p>
                </div>
                
                <div class="col-md-6">
                    <h4>Vertreten durch</h4>
                    <p>
                        Max Mustermann (Geschäftsführer)
                    </p>
                    
                    <h4>Registereintrag</h4>
                    <p>
                        <strong>Registergericht:</strong> Amtsgericht Musterstadt<br>
                        <strong>Registernummer:</strong> HRB 12345
                    </p>
                    
                    <h4>Umsatzsteuer-ID</h4>
                    <p>
                        Umsatzsteuer-Identifikationsnummer gemäß §27 a Umsatzsteuergesetz:<br>
                        DE123456789
                    </p>
                </div>
            </div>
            
            <hr>
            
            <h3>Haftungsausschluss</h3>
            
            <h4>Haftung für Inhalte</h4>
            <p>
                Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den 
                allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht 
                unter der Verpflichtung, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach 
                Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.
            </p>
            
            <h4>Haftung für Links</h4>
            <p>
                Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. 
                Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der 
                verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.
            </p>
            
            <h4>Urheberrecht</h4>
            <p>
                Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen 
                Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der 
                Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.
            </p>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Letzte Aktualisierung: {{ date('d.m.Y') }}
                </small>
            </div>
        </div>
    </div>
@endsection