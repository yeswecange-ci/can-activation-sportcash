@extends('admin.layouts.app')

@section('title', 'Pronostics par Match')
@section('page-title', 'Pronostics - ' . $match->team_a . ' vs ' . $match->team_b)

@section('content')
<div class="space-y-6">
    <!-- Header du match -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ $match->team_a }} vs {{ $match->team_b }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $match->match_date->format('d/m/Y à H:i') }}</p>
                <div class="mt-2">
                    @if($match->status === 'finished')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            Terminé
                        </span>
                        <span class="ml-2 text-lg font-bold text-blue-600">
                            {{ $match->score_a }} - {{ $match->score_b }}
                        </span>
                    @elseif($match->status === 'live')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                            En cours
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            À venir
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                @if($match->status === 'finished' && $stats['winners'] > 0)
                    <a href="{{ route('admin.pronostics.export.match-winners', $match) }}"
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Exporter les gagnants (CSV)
                    </a>
                @endif

                @if($match->status === 'finished')
                    <form action="{{ route('admin.matches.evaluate', $match) }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                                onclick="return confirm('Réévaluer tous les pronostics de ce match ?');">
                            Réévaluer les pronostics
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.pronostics.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    Retour à la liste
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-blue-600 font-medium">Total Pronostics</div>
                <div class="text-2xl font-bold text-blue-900">{{ $stats['total'] }}</div>
            </div>

            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-green-600 font-medium">Gagnants</div>
                <div class="text-2xl font-bold text-green-900">{{ $stats['winners'] }}</div>
                @if($stats['total'] > 0)
                    <div class="text-xs text-green-600 mt-1">
                        {{ round(($stats['winners'] / $stats['total']) * 100, 1) }}%
                    </div>
                @endif
            </div>

            <div class="bg-yellow-50 rounded-lg p-4">
                <div class="text-sm text-yellow-600 font-medium">Scores Exacts</div>
                <div class="text-2xl font-bold text-yellow-900">{{ $stats['exact_scores'] }}</div>
                @if($stats['total'] > 0)
                    <div class="text-xs text-yellow-600 mt-1">
                        {{ round(($stats['exact_scores'] / $stats['total']) * 100, 1) }}%
                    </div>
                @endif
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm text-purple-600 font-medium">Pronostics Populaires</div>
                <div class="text-sm font-bold text-purple-900 mt-1">
                    @if($stats['by_prediction']->isNotEmpty())
                        {{ $stats['by_prediction']->first() }} × {{ $stats['by_prediction']->keys()->first() }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des pronostics -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Liste des pronostics ({{ $pronostics->count() }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Village</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pronostic</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pronostics as $index => $prono)
                        <tr class="{{ $prono->is_winner ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $prono->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $prono->user->phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $prono->user->village->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">
                                    {{ $prono->prediction_text }}
                                </div>
                                @if($prono->prediction_type)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if($prono->prediction_type === 'team_a_win')
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">Victoire {{ $match->team_a }}</span>
                                        @elseif($prono->prediction_type === 'team_b_win')
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded">Victoire {{ $match->team_b }}</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded">Match nul</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($match->status === 'finished')
                                    @if($prono->is_winner)
                                        @if($prono->points_won == 10)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                🎯 Score exact
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                ✅ Bon résultat
                                            </span>
                                        @endif
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold {{ $prono->points_won > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $prono->points_won ?? 0 }} pts
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $prono->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Aucun pronostic pour ce match.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Répartition des pronostics -->
    @if($stats['by_prediction']->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Répartition des pronostics</h3>
            <div class="space-y-3">
                @foreach($stats['by_prediction'] as $prediction => $count)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $prediction }}</span>
                            <span class="text-gray-500">{{ $count }} ({{ round(($count / $stats['total']) * 100, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($count / $stats['total']) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
