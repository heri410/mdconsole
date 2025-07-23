<nav class="navbar navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
    <button class="navbar-toggler d-md-none collapsed ms-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand px-3" href="{{ route('dashboard') }}">
        <img src="{{ Vite::asset('resources/images/logo-breit.png') }}" alt="{{ config('app.name') }} Logo" height="30">
    </a>
    
    <!-- Mobile Dark Mode Toggle and User Menu -->
    <div class="d-md-none d-flex align-items-center ms-auto me-3">
        <button class="btn btn-outline-light btn-sm me-2" id="themeToggleMobile" title="Dark/Light Mode">
            <i class="bi bi-moon" id="themeIconMobile"></i>
        </button>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userMenuMobile" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end text-small shadow" aria-labelledby="userMenuMobile">
                <li><h6 class="dropdown-header">{{ Auth::user()->name }}</h6></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('ui.profile', 'Profile') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>{{ __('ui.logout', 'Logout') }}</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Desktop Dark Mode Toggle and User Menu -->
    <div class="d-none d-md-flex align-items-center ms-auto me-3">
        <button class="btn btn-outline-light btn-sm me-3" id="themeToggle" title="Toggle Dark/Light Mode">
            <i class="bi bi-moon" id="themeIcon"></i>
        </button>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>{{ Auth::user()->name }}
            </a>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end text-small shadow" aria-labelledby="userMenu">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('ui.profile', 'Profile') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>{{ __('ui.logout', 'Logout') }}</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
