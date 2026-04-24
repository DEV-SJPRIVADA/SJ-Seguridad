<nav x-data="{ open: false }" class="top-nav">
    <div class="app-container">
        <div class="top-nav__inner">
            <div class="top-nav__brand">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="brand-logo" />
                </a>
            </div>

            <div class="top-nav__account">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="dropdown__trigger">
                            <div>{{ Auth::user()->name }}</div>

                            <div>
                                <svg class="icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesion') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div>
                <button @click="open = ! open" class="nav-toggle" type="button">
                    <svg class="icon-md" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" class="nav-mobile" style="display: none;">
        <div class="app-container">
            <div class="nav-mobile__links">
                @foreach ($appNavigation as $module)
                    <a href="{{ $module['url'] }}" class="nav-mobile-link {{ $module['active'] ? 'nav-mobile-link--active' : '' }}">
                        {{ $module['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="nav-mobile__meta">
                <strong>{{ Auth::user()->name }}</strong>
                <span>{{ Auth::user()->email }}</span>
            </div>

            @if ($currentModule)
                <div class="nav-mobile__actions panel-divider-top">
                    @foreach ($currentModuleTabs as $tab)
                        <a href="{{ $tab['url'] }}" class="nav-mobile-link {{ $tab['active'] ? 'nav-mobile-link--active' : '' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="nav-mobile__actions">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesion') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
