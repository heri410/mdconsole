<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse show">
    <div class="position-sticky pt-3">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 px-3 text-white text-decoration-none">
            <span class="fs-4">{{ config('app.name', 'Rechnungsportal') }}</span>
        </a>
        <ul class="nav nav-pills flex-column mb-auto px-2">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i>{{ __('ui.dashboard', 'Dashboard') }}
                </a>
            </li>
            
            <!-- Domains for customers -->
            @if(Auth::user()->role === 'customer')
            <li class="nav-item">
                <a href="{{ route('domains.index') }}" class="nav-link text-white {{ request()->routeIs('domains.*') ? 'active' : '' }}">
                    <i class="bi bi-globe me-2"></i>{{ __('ui.my_domains', 'My Domains') }}
                </a>
            </li>
            @endif
            
            <!-- Admin sections -->
            @can('manage-positions')
            <li>
                <a href="{{ route('positions.index') }}" class="nav-link text-white {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                    <i class="bi bi-list-ul me-2"></i>{{ __('ui.manage_positions', 'Manage Positions') }}
                </a>
            </li>
            @endcan
        </ul>
        
        <hr class="text-secondary">
        
        <!-- Mobile logout for collapsed sidebar -->
        <div class="px-2 d-md-none">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100">
                    <i class="bi bi-box-arrow-right me-2"></i>{{ __('ui.logout', 'Logout') }}
                </button>
            </form>
        </div>
        
        <!-- Desktop user dropdown -->
        <div class="px-2 d-none d-md-block">
            <div class="dropdown dropup">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i><strong>{{ Auth::user()->name }}</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="userDropdown">
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
    </div>
</nav>
