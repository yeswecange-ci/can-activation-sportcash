@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Inscrits -->
        <div class="bg-white border-l-4 border-can-gold-500 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-can-gold-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-can-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 mb-1">Total Inscrits</p>
            <h3 class="text-4xl font-bold text-gray-900 mb-2">{{ number_format($totalUsers) }}</h3>
            <p class="text-sm text-gray-500 flex items-center">
                @if($userGrowthPercent >= 0)
                    <svg class="w-4 h-4 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-green-600 font-medium">+{{ $userGrowthPercent }}%</span> cette semaine
                @else
                    <svg class="w-4 h-4 mr-1 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span class="text-red-600 font-medium">{{ $userGrowthPercent }}%</span> cette semaine
                @endif
            </p>
        </div>

        <!-- Villages CAN -->
        <div class="bg-white border-l-4 border-can-green-500 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-can-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-can-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 mb-1">Villages CAN</p>
            <h3 class="text-4xl font-bold text-gray-900 mb-2">{{ $totalVillages }}</h3>
            <p class="text-sm text-gray-500">Villages actifs</p>
        </div>

        <!-- Pronostics Actifs -->
        <div class="bg-white border-l-4 border-blue-500 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 mb-1">Pronostics</p>
            <h3 class="text-4xl font-bold text-gray-900 mb-2">{{ number_format($pronosticsThisWeek) }}</h3>
            <p class="text-sm text-gray-500">Cette semaine ({{ $participationRate }}% participation)</p>
        </div>

        <!-- Messages Envoyés -->
        <div class="bg-white border-l-4 border-purple-500 rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-gray-600 mb-1">Messages Envoyés</p>
            <h3 class="text-4xl font-bold text-gray-900 mb-2">{{ number_format($totalMessages) }}</h3>
            <p class="text-sm text-gray-500">Via WhatsApp ({{ $deliveryRate }}% délivrés)</p>
        </div>
    </div>

    <!-- Graphiques - Ligne 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique Inscriptions (7 derniers jours) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Évolution des Inscriptions</h2>
                <p class="text-sm text-gray-500">7 derniers jours</p>
            </div>
            <div style="height: 280px;">
                <canvas id="registrationChart"></canvas>
            </div>
        </div>

        <!-- Graphique Sources -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Répartition par Source</h2>
                <p class="text-sm text-gray-500">Canaux d'acquisition</p>
            </div>
            <div style="height: 280px;">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphiques - Ligne 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique Top Villages -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Top 5 Villages</h2>
                <p class="text-sm text-gray-500">Par nombre d'inscrits</p>
            </div>
            <div style="height: 280px;">
                <canvas id="villageChart"></canvas>
            </div>
        </div>

        <!-- Taux de Livraison Messages -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Taux de Livraison Messages</h2>
                <p class="text-sm text-gray-500">WhatsApp Business API</p>
            </div>
            <div style="height: 280px;">
                <canvas id="deliveryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Autres sections simplifiées -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Prochains Matchs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Prochains Matchs</h2>
                <p class="text-sm text-gray-500">À venir</p>
            </div>
            <div style="height: 280px;">
                <canvas id="deliveryChart"></canvas>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Taux de Livraison Messages</h2>
            </div>
            <div class="mt-6">
                <div class="flex mb-3 items-center justify-between">
                    <div>
                        <span class="inline-flex items-center px-3 py-1 text-sm font-bold rounded-full text-white bg-gradient-to-r from-green-500 to-green-600">
                            {{ $messagesDelivered }} / {{ $totalMessages }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-green-600">{{ $deliveryRate }}%</span>
                    </div>
                </div>
                <div class="overflow-hidden h-4 mb-6 text-xs flex rounded-full bg-gradient-to-r from-gray-100 to-gray-200">
                    <div style="width: {{ $deliveryRate }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-green-500 to-green-600 transition-all duration-500"></div>
                </div>
            </div>

            <!-- Stats supplémentaires -->
            <div class="grid grid-cols-2 gap-4 mt-8">
                <div class="relative bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                    <p class="text-sm text-gray-600 font-medium mb-1">Total Envoyés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalMessages) }}</p>
                </div>
                <div class="relative bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                    <p class="text-sm text-green-600 font-medium mb-1">Délivrés</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($messagesDelivered) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Prochains Matchs -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-orange-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Prochains Matchs</h3>
                </div>
            </div>
            <div class="p-6">
                @if($upcomingMatches->isEmpty())
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="font-medium mb-4">Aucun match programmé</p>
                        <a href="{{ route('admin.matches.create') }}" class="inline-flex items-center bg-gradient-to-r from-orange-600 to-orange-700 text-white px-4 py-2 rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Ajouter un match
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($upcomingMatches as $match)
                            <div class="flex items-center justify-between p-4 border-2 border-gray-100 rounded-xl hover:border-orange-200 hover:bg-orange-50 transition-all">
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900">{{ $match->team_a }} vs {{ $match->team_b }}</p>
                                    <p class="text-sm text-gray-500 mt-1 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $match->match_date->format('d/m/Y à H:i') }}
                                    </p>
                                </div>
                                @if($match->pronostic_enabled)
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-green-100 text-green-800 rounded-full">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        Pronostics ouverts
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gray-100 text-gray-600 rounded-full">
                                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                        Pronostics fermés
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.matches.index') }}" class="text-orange-600 hover:text-orange-800 text-sm font-bold">
                            Voir tous les matchs →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Campagnes Planifiées -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Campagnes Planifiées</h3>
                </div>
            </div>
            <div class="p-6">
                @if($plannedCampaigns->isEmpty())
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        <p class="font-medium mb-4">Aucune campagne planifiée</p>
                        <a href="{{ route('admin.campaigns.create') }}" class="inline-flex items-center bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Créer une campagne
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($plannedCampaigns as $campaign)
                            <div class="flex items-center justify-between p-4 border-2 border-gray-100 rounded-xl hover:border-red-200 hover:bg-red-50 transition-all">
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900">{{ $campaign->name }}</p>
                                    <p class="text-sm text-gray-500 mt-1 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d/m/Y à H:i') : 'Non planifiée' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full
                                    @if($campaign->status === 'scheduled') bg-blue-100 text-blue-800
                                    @elseif($campaign->status === 'processing') bg-yellow-100 text-yellow-800
                                    @elseif($campaign->status === 'sent') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.campaigns.index') }}" class="text-red-600 hover:text-red-800 text-sm font-bold">
                            Voir toutes les campagnes →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Actions Rapides</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.villages.create') }}" class="group flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-green-500 hover:bg-gradient-to-br hover:from-green-50 hover:to-emerald-50 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 group-hover:from-green-500 group-hover:to-green-600 rounded-lg flex items-center justify-center mb-3 transition-all">
                    <svg class="w-6 h-6 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <span class="text-sm font-bold text-gray-700 group-hover:text-green-700 text-center">Nouveau Village</span>
            </a>

            <a href="{{ route('admin.matches.create') }}" class="group flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-orange-500 hover:bg-gradient-to-br hover:from-orange-50 hover:to-amber-50 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-orange-200 group-hover:from-orange-500 group-hover:to-orange-600 rounded-lg flex items-center justify-center mb-3 transition-all">
                    <svg class="w-6 h-6 text-orange-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <span class="text-sm font-bold text-gray-700 group-hover:text-orange-700 text-center">Nouveau Match</span>
            </a>

            <a href="{{ route('admin.users.index') }}" class="group flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 hover:bg-gradient-to-br hover:from-blue-50 hover:to-indigo-50 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 group-hover:from-blue-500 group-hover:to-blue-600 rounded-lg flex items-center justify-center mb-3 transition-all">
                    <svg class="w-6 h-6 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-bold text-gray-700 group-hover:text-blue-700 text-center">Utilisateurs</span>
            </a>

            <a href="{{ route('admin.qrcodes.create') }}" class="group flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-purple-500 hover:bg-gradient-to-br hover:from-purple-50 hover:to-pink-50 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 group-hover:from-purple-500 group-hover:to-purple-600 rounded-lg flex items-center justify-center mb-3 transition-all">
                    <svg class="w-6 h-6 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <span class="text-sm font-bold text-gray-700 group-hover:text-purple-700 text-center">Générer QR</span>
            </a>
        </div>
    </div>
</div>

<script>
// 1. Graphique des Inscriptions (7 derniers jours)
const regData = @json($registrationChart);
const regLabels = regData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
});
const regCounts = regData.map(d => d.count);

new Chart(document.getElementById('registrationChart'), {
    type: 'line',
    data: {
        labels: regLabels,
        datasets: [{
            label: 'Nouvelles Inscriptions',
            data: regCounts,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 12 }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: { size: 11 }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    font: { size: 11 }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// 2. Graphique des Sources d'inscription
const sourceData = @json($sourceStats);
const sourceLabels = sourceData.map(d => d.source_type || 'Autre');
const sourceCounts = sourceData.map(d => d.count);

new Chart(document.getElementById('sourceChart'), {
    type: 'doughnut',
    data: {
        labels: sourceLabels,
        datasets: [{
            data: sourceCounts,
            backgroundColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(251, 191, 36)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)',
                'rgb(236, 72, 153)'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 12 }
            }
        }
    }
});

// 3. Graphique des Top Villages
const villageData = @json($topVillages);
const villageLabels = villageData.map(v => v.name);
const villageCounts = villageData.map(v => v.users_count);

new Chart(document.getElementById('villageChart'), {
    type: 'bar',
    data: {
        labels: villageLabels,
        datasets: [{
            label: 'Nombre d\'inscrits',
            data: villageCounts,
            backgroundColor: 'rgb(34, 197, 94)',
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 12 }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: { size: 11 }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            y: {
                ticks: {
                    font: { size: 11 }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>
@endsection
