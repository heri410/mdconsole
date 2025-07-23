<nav class="navbar navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
    <button class="navbar-toggler d-md-none collapsed ms-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand px-3" href="#">
        <img src="{{ asset('resources/images/logo-breit.png') }}" alt="Logo" height="30">
    </a>
    <button class="btn btn-dark d-md-none ms-auto me-3" id="themeToggle" title="Dark/Light Mode">
        <i class="bi bi-moon" id="themeIcon"></i>
    </button>
    <div class="dropdown d-none d-md-block ms-auto me-3">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>{{ Auth::user()->name }}
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="userMenu">
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('ui.profile') }}</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">{{ __('ui.logout') }}</button>
                </form>
            </li>
        </ul>
    </div>
</nav>
