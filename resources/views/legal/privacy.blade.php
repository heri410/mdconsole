@extends('layouts.app')
@section('title', 'Datenschutzerklärung')
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Datenschutzerklärung</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h3>1. Datenschutz auf einen Blick</h3>
            
            <h4>Allgemeine Hinweise</h4>
            <p>
                Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten 
                passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie 
                persönlich identifiziert werden können.
            </p>
            
            <h4>Datenerfassung auf dieser Website</h4>
            <p>
                <strong>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</strong><br>
                Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten 
                können Sie dem Impressum dieser Website entnehmen.
            </p>
            
            <hr>
            
            <h3>2. Hosting und Content Delivery Networks (CDN)</h3>
            
            <h4>Externes Hosting</h4>
            <p>
                Diese Website wird bei einem externen Dienstleister gehostet (Hoster). Die personenbezogenen Daten, 
                die auf dieser Website erfasst werden, werden auf den Servern des Hosters gespeichert. Hierbei kann 
                es sich v. a. um IP-Adressen, Kontaktanfragen, Meta- und Kommunikationsdaten, Vertragsdaten, 
                Kontaktdaten, Namen, Websitezugriffe und sonstige Daten, die über eine Website generiert werden, handeln.
            </p>
            
            <hr>
            
            <h3>3. Allgemeine Hinweise und Pflichtinformationen</h3>
            
            <h4>Datenschutz</h4>
            <p>
                Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre 
                personenbezogenen Daten vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften sowie 
                dieser Datenschutzerklärung.
            </p>
            
            <h4>Hinweis zur verantwortlichen Stelle</h4>
            <p>
                Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:
            </p>
            <p>
                <strong>{{ config('app.name', 'Ihr Unternehmen') }}</strong><br>
                Musterstraße 123<br>
                12345 Musterstadt<br>
                Deutschland<br><br>
                Telefon: +49 (0) 123 456789<br>
                E-Mail: datenschutz@example.com
            </p>
            
            <hr>
            
            <h3>4. Datenerfassung auf dieser Website</h3>
            
            <h4>Cookies</h4>
            <p>
                Unsere Internetseiten verwenden so genannte „Cookies". Cookies sind kleine Textdateien und richten auf 
                Ihrem Endgerät keinen Schaden an. Sie werden entweder vorübergehend für die Dauer einer Sitzung 
                (Session-Cookies) oder dauerhaft (permanente Cookies) auf Ihrem Endgerät gespeichert.
            </p>
            
            <h4>Server-Log-Dateien</h4>
            <p>
                Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, 
                die Ihr Browser automatisch an uns übermittelt. Dies sind:
            </p>
            <ul>
                <li>Browsertyp und Browserversion</li>
                <li>verwendetes Betriebssystem</li>
                <li>Referrer URL</li>
                <li>Hostname des zugreifenden Rechners</li>
                <li>Uhrzeit der Serveranfrage</li>
                <li>IP-Adresse</li>
            </ul>
            
            <h4>Kontaktformular</h4>
            <p>
                Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular 
                inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall 
                von Anschlussfragen bei uns gespeichert.
            </p>
            
            <hr>
            
            <h3>5. Ihre Rechte</h3>
            
            <p>Sie haben jederzeit das Recht:</p>
            <ul>
                <li>Auskunft über Ihre bei uns gespeicherten personenbezogenen Daten zu erhalten</li>
                <li>die Berichtigung unrichtiger personenbezogener Daten zu verlangen</li>
                <li>die Löschung Ihrer bei uns gespeicherten personenbezogenen Daten zu verlangen</li>
                <li>die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen</li>
                <li>der Verarbeitung Ihrer personenbezogenen Daten zu widersprechen</li>
                <li>Ihre personenbezogenen Daten in einem strukturierten, gängigen und maschinenlesbaren Format zu erhalten</li>
            </ul>
            
            <p>
                Wenn Sie von einem dieser Rechte Gebrauch machen möchten, wenden Sie sich bitte an unsere oben genannte 
                Kontaktadresse.
            </p>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="bi bi-shield-check me-1"></i>
                    Letzte Aktualisierung: {{ date('d.m.Y') }}
                </small>
            </div>
        </div>
    </div>
@endsection