@extends('admin.layouts.app')

@section('title', 'Joueurs')
@section('page-title', 'Joueurs')

@section('content')
<div class="space-y-6">
    <!-- Header et Filtres -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Liste des Joueurs Inscrits
                </h3>
                <p class="text-sm text-gray-500 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        {{ $users->total() }} joueur(s) au total
                    </span>
                </p>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Recherche -->
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Recherche</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        placeholder="Nom ou téléphone..."
                    >
                </div>
            </div>

            <!-- Village -->
            <div>
                <label for="village_id" class="block text-sm font-semibold text-gray-700 mb-2">Village</label>
                <select
                    name="village_id"
                    id="village_id"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                >
                    <option value="">Tous les villages</option>
                    @foreach($villages as $village)
                        <option value="{{ $village->id }}" {{ request('village_id') == $village->id ? 'selected' : '' }}>
                            {{ $village->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Boutons -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-5 py-2.5 rounded-lg hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-md hover:shadow-lg font-medium">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filtrer
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Messages de succès -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-900 px-6 py-4 rounded-lg shadow-sm flex items-center" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Table des joueurs -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Joueur</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Téléphone</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Village</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Inscrit le</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-indigo-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-900">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    {{ $user->phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->village)
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-bold rounded-full bg-blue-100 text-blue-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        </svg>
                                        {{ $user->village->name }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400 italic">Non assigné</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $user->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                        Actif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                        Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.users.show', $user) }}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Voir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-gray-500 font-medium">Aucun joueur trouvé</p>
                                <p class="text-sm text-gray-400 mt-2">Essayez de modifier les filtres de recherche</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
