<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-8 py-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">@yield('page-title', 'Dashboard')</h2>
            <p class="text-sm text-gray-500 mt-0.5">Bienvenue sur votre tableau de bord</p>
        </div>

        <div class="flex items-center space-x-6">
            <!-- Profile Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center space-x-3 text-gray-700 hover:text-gray-900 hover:bg-gray-50 px-4 py-2 rounded-lg transition-all">
                    <div class="w-9 h-9 bg-can-gold-500 rounded-full flex items-center justify-center text-white font-semibold">
                        {{ substr(Auth::guard('admin')->user()->name, 0, 1) }}
                    </div>
                    <div class="text-left hidden md:block">
                        <p class="font-semibold text-sm">{{ Auth::guard('admin')->user()->name }}</p>
                        <p class="text-xs text-gray-500">Administrateur</p>
                    </div>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900">{{ Auth::guard('admin')->user()->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ Auth::guard('admin')->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
