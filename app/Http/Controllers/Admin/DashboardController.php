<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\FootballMatch;
use App\Models\MessageLog;
use App\Models\Pronostic;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total Inscrits avec variation hebdomadaire
        $totalUsers = User::where('is_active', true)->count();
        $usersThisWeek = User::where('is_active', true)
            ->where('created_at', '>=', now()->subWeek())
            ->count();
        $usersLastWeek = User::where('is_active', true)
            ->whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])
            ->count();

        $userGrowthPercent = $usersLastWeek > 0
            ? round((($usersThisWeek - $usersLastWeek) / $usersLastWeek) * 100, 1)
            : 0;

        // 2. Villages actifs
        $totalVillages = Village::where('is_active', true)->count();

        // Top 5 villages par nombre d'inscrits
        $topVillages = Village::withCount(['users' => function($query) {
            $query->where('is_active', true);
        }])
        ->having('users_count', '>', 0)
        ->orderByDesc('users_count')
        ->take(5)
        ->get();

        // 3. Pronostics cette semaine
        $pronosticsThisWeek = Pronostic::whereBetween('created_at', [now()->startOfWeek(), now()])
            ->count();

        $totalPronostics = Pronostic::count();

        // Taux de participation (utilisateurs avec au moins 1 pronostic)
        $usersWithPronostics = User::has('pronostics')->where('is_active', true)->count();
        $participationRate = $totalUsers > 0
            ? round(($usersWithPronostics / $totalUsers) * 100, 1)
            : 0;

        // 4. Messages envoyés
        $totalMessages = MessageLog::count();
        $messagesDelivered = MessageLog::where('status', 'delivered')->count();
        $deliveryRate = $totalMessages > 0
            ? round(($messagesDelivered / $totalMessages) * 100, 1)
            : 0;

        // 5. Prochains matchs (5 prochains)
        $upcomingMatches = FootballMatch::where('status', 'scheduled')
            ->where('match_date', '>=', now())
            ->orderBy('match_date')
            ->take(5)
            ->get();

        // 6. Campagnes planifiées
        $plannedCampaigns = Campaign::whereIn('status', ['draft', 'scheduled'])
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        // 7. Évolution des inscriptions (7 derniers jours)
        $registrationChart = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 8. Statistiques par source (Twilio Studio tracking)
        $sourceStats = User::select('source_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('source_type')
            ->groupBy('source_type')
            ->orderByDesc('count')
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'userGrowthPercent',
            'totalVillages',
            'topVillages',
            'pronosticsThisWeek',
            'totalPronostics',
            'participationRate',
            'totalMessages',
            'messagesDelivered',
            'deliveryRate',
            'upcomingMatches',
            'plannedCampaigns',
            'registrationChart',
            'sourceStats'
        ));
    }
}
