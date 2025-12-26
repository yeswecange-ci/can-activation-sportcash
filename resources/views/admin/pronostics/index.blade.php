@extends('admin.layouts.app')

@section('title', 'Pronostics')
@section('page-title', 'Gestion des Pronostics')

@section('content')
<div class="space-y-6">
    <!-- Header et filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Tous les Pronostics</h3>
                <p class="text-sm text-gray-500">{{ $pronostics->total() }} pronostic(s) au total</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.pronostics.export.winners') }}"
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exporter tous les gagnants
                </a>
                <a href="{{ route('admin.pronostics.stats') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    📊 Statistiques
                </a>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Match</label>
                <select name="match_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tous les matchs</option>
                    @foreach($matches as $match)
                        <option value="{{ $match->id }}" {{ request('match_id') == $match->id ? 'selected' : '' }}>
                            {{ $match->team_a }} vs {{ $match->team_b }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="is_winner" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    <option value="1" {{ request('is_winner') === '1' ? 'selected' : '' }}>Gagnants</option>
                    <option value="0" {{ request('is_winner') === '0' ? 'selected' : '' }}>Perdants</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des pronostics -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pronostic</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Résultat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pronostics as $prono)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $prono->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $prono->user->phone }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $prono->match->team_a }} vs {{ $prono->match->team_b }}</div>
                                <div class="text-sm text-gray-500">{{ $prono->match->match_date->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">
                                    {{ $prono->prediction_text }}
                                </div>
                                @if($prono->prediction_type)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if($prono->prediction_type === 'team_a_win')
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">Victoire {{ $prono->match->team_a }}</span>
                                        @elseif($prono->prediction_type === 'team_b_win')
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded">Victoire {{ $prono->match->team_b }}</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded">Match nul</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($prono->match->status === 'finished')
                                    <span class="text-sm font-bold text-blue-600">
                                        {{ $prono->match->score_a }} - {{ $prono->match->score_b }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($prono->match->status === 'finished')
                                    @if($prono->is_winner)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            ✅ Gagné
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            ❌ Perdu
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        ⏳ En attente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $prono->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('admin.pronostics.show', $prono) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Voir les détails">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.pronostics.destroy', $prono) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce pronostic ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Aucun pronostic trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50">
            {{ $pronostics->links() }}
        </div>
    </div>
</div>
@endsection
