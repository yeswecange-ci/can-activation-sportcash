@extends('admin.layouts.app')

@section('title', 'Statistiques Pronostics')
@section('page-title', 'Statistiques des Pronostics')

@section('content')
<div class="space-y-6">
    <!-- Stats globales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Pronostics</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $stats['total_pronostics'] }}</h3>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Pronostics Gagnants</p>
                    <h3 class="text-3xl font-bold text-green-600 mt-2">{{ $stats['total_winners'] }}</h3>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Points Distribués</p>
                    <h3 class="text-3xl font-bold text-purple-600 mt-2">
                        {{ $stats['total_points_distributed'] ?? 0 }} pts
                    </h3>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Joueurs -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">🏆 Top 10 Joueurs</h3>
                <p class="text-sm text-gray-500">Classés par nombre de points</p>
            </div>
            <div class="p-6">
                @if($stats['top_users']->count() > 0)
                    <div class="space-y-3">
                        @foreach($stats['top_users'] as $index => $user)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full font-bold
                                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-200 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700')) }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->total_wins ?? 0 }} victoire(s) / {{ $user->total_pronostics ?? 0 }} prono(s)</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-600">{{ $user->total_points ?? 0 }} pts</div>
                                    <div class="text-xs text-gray-500">points</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">Aucun gagnant pour le moment</p>
                @endif
            </div>
        </div>

        <!-- Pronostics par Match -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">📊 Pronostics par Match</h3>
            </div>
            <div class="p-6">
                @if($stats['by_match']->count() > 0)
                    <div class="space-y-3">
                        @foreach($stats['by_match'] as $match)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium text-gray-900">
                                        {{ $match->team_a }} vs {{ $match->team_b }}
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $match->total_pronostics ?? 0 }} prono(s)
                                        </span>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ $match->total_winners ?? 0 }} gagnant(s)
                                        </span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $match->match_date->format('d/m/Y à H:i') }}
                                    @if($match->status === 'finished')
                                        • Score: {{ $match->score_a ?? '-' }} - {{ $match->score_b ?? '-' }}
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.matches.show', $match) }}" class="text-sm text-blue-600 hover:underline">
                                        → Voir les détails
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">Aucun pronostic enregistré</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Retour -->
    <div class="flex justify-center">
        <a href="{{ route('admin.pronostics.index') }}" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            ← Retour à la liste des pronostics
        </a>
    </div>
</div>
@endsection
