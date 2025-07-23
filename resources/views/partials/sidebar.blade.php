<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse show">
    <div class="position-sticky pt-3">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 px-3 text-white text-decoration-none">
            <span class="fs-4">{{ config('app.name', 'Rechnungsportal') }}</span>
        </a>
        <ul class="nav nav-pills flex-column mb-auto px-2">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i>{{ __('ui.dashboard') }}
            </a>
        </li>
        @can('manage-positions')
        <li>
            <a href="{{ route('positions.index') }}" class="nav-link text-white {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                <i class="bi bi-list-ul me-2"></i>{{ __('ui.manage_positions') }}
            </a>
        </li>
        @endcan
        </ul>
        <hr class="text-secondary">
        <div class="px-2">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i><strong>{{ Auth::user()->name }}</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="userDropdown">
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
