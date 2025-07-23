<footer class="bg-dark text-center text-white py-3 mt-auto border-top">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <small>&copy; {{ date('Y') }} {{ config('app.name') }}. Alle Rechte vorbehalten.</small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <nav class="footer-nav">
                    <a href="{{ route('legal.impressum') }}" class="text-light text-decoration-none me-3">
                        <i class="bi bi-info-circle me-1"></i>Impressum
                    </a>
                    <a href="{{ route('legal.privacy') }}" class="text-light text-decoration-none">
                        <i class="bi bi-shield-check me-1"></i>Datenschutzerklärung
                    </a>
                </nav>
            </div>
        </div>
    </div>
</footer>
