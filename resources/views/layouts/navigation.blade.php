<nav x-data="{ open: false }" class="bg-[#e4cea5]/80 backdrop-blur border-b-2 border-[#5a3a22]/50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center py-2">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-12 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Übersicht') }}
                    </x-nav-link>
                    <x-nav-link :href="route('players.index')" :active="request()->routeIs('players.*')">
                        {{ __('Spieler') }}
                    </x-nav-link>
                    @can('heldenregister.view')
                        <x-nav-link :href="route('heroes.index')" :active="request()->routeIs('heroes.*')">
                            {{ __('Heldenregister') }}
                        </x-nav-link>
                        <x-nav-link :href="route('skills.catalog')" :active="request()->routeIs('skills.catalog')">
                            {{ __('Fertigkeiten') }}
                        </x-nav-link>
                    @endcan
                    @can('heldenregister.edit')
                        <x-nav-link :href="route('ep.create')" :active="request()->routeIs('ep.*')">
                            {{ __('EP buchen') }}
                        </x-nav-link>
                    @endcan
                    @can('adventure.access')
                        <x-nav-link :href="route('adventures.index')" :active="request()->routeIs('adventures.*')">
                            {{ __('Abenteuer') }}
                        </x-nav-link>
                    @endcan
                    @can('portal.manage')
                        <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                            {{ __('Verwaltung') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                {{-- In-App-Benachrichtigungen (NOTI-07) --}}
                @php($unreadNotes = Auth::user()->unreadNotifications)
                <div class="me-3">
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 005.454-1.31A8.97 8.97 0 0118 9.75V9A6 6 0 006 9v.75a8.97 8.97 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.26 24.26 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                @if ($unreadNotes->count())
                                    <span class="absolute -top-0.5 -end-0.5 inline-flex items-center justify-center px-1.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">{{ $unreadNotes->count() }}</span>
                                @endif
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Benachrichtigungen</div>
                            @forelse ($unreadNotes->take(10) as $note)
                                <x-dropdown-link :href="route('notifications.read', $note->id)">
                                    {{ $note->data['message'] ?? 'Benachrichtigung' }}
                                </x-dropdown-link>
                            @empty
                                <div class="px-4 py-2 text-sm text-gray-500">Keine neuen Benachrichtigungen.</div>
                            @endforelse
                            @if ($unreadNotes->count())
                                <div class="border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-start px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Alle als gelesen markieren</button>
                                </form>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                            {{ __('Rollen') }}: {{ Auth::user()->roles->pluck('label')->implode(', ') ?: __('keine') }}
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Abmelden
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('players.index')" :active="request()->routeIs('players.*')">
                {{ __('Spieler') }}
            </x-responsive-nav-link>
            @can('heldenregister.view')
                <x-responsive-nav-link :href="route('heroes.index')" :active="request()->routeIs('heroes.*')">
                    {{ __('Heldenregister') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('skills.catalog')" :active="request()->routeIs('skills.catalog')">
                    {{ __('Fertigkeiten') }}
                </x-responsive-nav-link>
            @endcan
            @can('heldenregister.edit')
                <x-responsive-nav-link :href="route('ep.create')" :active="request()->routeIs('ep.*')">
                    {{ __('EP buchen') }}
                </x-responsive-nav-link>
            @endcan
            @can('adventure.access')
                <x-responsive-nav-link :href="route('adventures.index')" :active="request()->routeIs('adventures.*')">
                    {{ __('Abenteuer') }}
                </x-responsive-nav-link>
            @endcan
            @can('portal.manage')
                <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                    {{ __('Verwaltung') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Abmelden
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
