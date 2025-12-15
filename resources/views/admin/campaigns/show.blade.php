@extends('admin.layouts.app')

@section('title', 'Détails Campagne')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="{{ route('admin.campaigns.index') }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour
                </a>
                <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $campaign->name }}</h1>
            </div>
            @if($campaign->status === 'draft')
                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Modifier
                </a>
            @endif
        </div>

        <!-- Statut & Stats -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Statistiques d'envoi</h2>
                <div>
                    @if($campaign->status === 'draft')
                        <span class="px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-200 rounded-full">Brouillon</span>
                    @elseif($campaign->status === 'scheduled')
                        <span class="px-3 py-1 text-sm font-semibold text-yellow-700 bg-yellow-200 rounded-full">Programmé</span>
                    @elseif($campaign->status === 'processing')
                        <span class="px-3 py-1 text-sm font-semibold text-blue-700 bg-blue-200 rounded-full">En cours</span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold text-green-700 bg-green-200 rounded-full">Envoyé</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="text-xs font-medium text-gray-500 uppercase mb-1">Total</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                </div>

                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="text-xs font-medium text-blue-600 uppercase mb-1">Envoyés</div>
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['sent']) }}</div>
                    @if($stats['total'] > 0)
                        <div class="text-xs text-blue-500 mt-1">{{ round(($stats['sent'] / $stats['total']) * 100, 1) }}%</div>
                    @endif
                </div>

                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="text-xs font-medium text-green-600 uppercase mb-1">Délivrés</div>
                    <div class="text-2xl font-bold text-green-600">{{ number_format($stats['delivered']) }}</div>
                    @if($stats['total'] > 0)
                        <div class="text-xs text-green-500 mt-1">{{ round(($stats['delivered'] / $stats['total']) * 100, 1) }}%</div>
                    @endif
                </div>

                <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                    <div class="text-xs font-medium text-red-600 uppercase mb-1">Échecs</div>
                    <div class="text-2xl font-bold text-red-600">{{ number_format($stats['failed']) }}</div>
                    @if($stats['total'] > 0)
                        <div class="text-xs text-red-500 mt-1">{{ round(($stats['failed'] / $stats['total']) * 100, 1) }}%</div>
                    @endif
                </div>

                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <div class="text-xs font-medium text-yellow-600 uppercase mb-1">En attente</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending']) }}</div>
                    @if($stats['total'] > 0)
                        <div class="text-xs text-yellow-500 mt-1">{{ round(($stats['pending'] / $stats['total']) * 100, 1) }}%</div>
                    @endif
                </div>
            </div>

            @if($campaign->status === 'sent' && ($stats['sent'] + $stats['delivered'] + $stats['failed'] + $stats['pending']) > 0)
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Progression</span>
                        <span>{{ number_format($stats['sent'] + $stats['delivered'] + $stats['failed']) }} / {{ number_format($stats['total']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full flex">
                            @if($stats['total'] > 0)
                                <div class="bg-green-600 h-2.5" style="width: {{ ($stats['delivered'] / $stats['total']) * 100 }}%"></div>
                                <div class="bg-blue-600 h-2.5" style="width: {{ ($stats['sent'] / $stats['total']) * 100 }}%"></div>
                                <div class="bg-red-600 h-2.5 rounded-r-full" style="width: {{ ($stats['failed'] / $stats['total']) * 100 }}%"></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Détails -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Détails de la Campagne</h2>

            <div class="space-y-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Audience cible</span>
                    <p class="mt-1 text-sm text-gray-900">
                        @if($campaign->audience_type === 'all')
                            Tous les utilisateurs
                        @elseif($campaign->audience_type === 'village')
                            Village: {{ $campaign->village->name ?? 'N/A' }}
                        @else
                            Statut: {{ $campaign->audience_status }}
                        @endif
                    </p>
                </div>

                @if($campaign->scheduled_at)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Date programmée</span>
                        <p class="mt-1 text-sm text-gray-900">{{ $campaign->scheduled_at->format('d/m/Y à H:i') }}</p>
                    </div>
                @endif

                <div>
                    <span class="text-sm font-medium text-gray-500">Créé le</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $campaign->created_at->format('d/m/Y à H:i') }}</p>
                </div>

                @if($campaign->sent_at)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Envoyé le</span>
                        <p class="mt-1 text-sm text-gray-900">{{ $campaign->sent_at->format('d/m/Y à H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Message -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Message</h2>

            <div class="bg-gray-100 rounded-lg p-4 max-w-md">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-gray-800 whitespace-pre-wrap">{{ $campaign->message }}</div>
                </div>
            </div>

            <div class="mt-4 text-sm text-gray-500">
                <p><strong>Variables détectées:</strong>
                    @php
                        preg_match_all('/\{([a-z_]+)\}/', $campaign->message, $matches);
                        $vars = array_unique($matches[1]);
                    @endphp
                    @if(count($vars) > 0)
                        @foreach($vars as $var)
                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 rounded mr-1">{{"{".$var."}"}}</span>
                        @endforeach
                    @else
                        Aucune
                    @endif
                </p>
            </div>
        </div>

        <!-- Liste des messages en échec -->
        @if($campaign->status === 'sent' && $failedMessages->count() > 0)
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Messages en Échec ({{ $failedMessages->count() }})</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nom
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Numéro
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Raison de l'échec
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($failedMessages as $failedMsg)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $failedMsg->user->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $failedMsg->user->phone ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="text-red-600 font-medium">{{ $failedMsg->readable_error }}</span>
                                        @if($failedMsg->error_message && $failedMsg->error_message !== $failedMsg->readable_error)
                                            <div class="text-xs text-gray-400 mt-1">{{ $failedMsg->error_message }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $failedMsg->updated_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($campaign->status === 'draft')
            <div class="mt-6 flex justify-end">
                <a href="{{ route('admin.campaigns.confirm-send', $campaign) }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                    Envoyer la Campagne
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
