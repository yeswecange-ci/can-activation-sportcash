<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\Pronostic;
use App\Models\User;
use Illuminate\Http\Request;

class PronosticController extends Controller
{
    /**
     * Afficher tous les pronostics
     */
    public function index(Request $request)
    {
        $query = Pronostic::with(['user', 'match'])
            ->orderBy('created_at', 'desc');

        // Filtre par match
        if ($request->filled('match_id')) {
            $query->where('match_id', $request->match_id);
        }

        // Filtre par statut (gagnant/perdant)
        if ($request->filled('is_winner')) {
            $query->where('is_winner', $request->is_winner === '1');
        }

        // Filtre par utilisateur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $pronostics = $query->paginate(20);

        // Pour les filtres
        $matches = FootballMatch::orderBy('match_date', 'desc')->get();

        return view('admin.pronostics.index', compact('pronostics', 'matches'));
    }

    /**
     * Afficher les détails d'un pronostic
     */
    public function show(Pronostic $pronostic)
    {
        $pronostic->load(['user.village', 'match']);
        return view('admin.pronostics.show', compact('pronostic'));
    }

    /**
     * Afficher les pronostics pour un match spécifique
     */
    public function byMatch(FootballMatch $match)
    {
        $pronostics = $match->pronostics()
            ->with(['user.village'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Statistiques
        $stats = [
            'total' => $pronostics->count(),
            'winners' => $pronostics->where('is_winner', true)->count(),
            'by_prediction' => $pronostics->groupBy(function ($p) {
                // Utiliser prediction_text qui gère les deux modes
                return $p->prediction_text;
            })->map->count()->sortDesc(),
        ];

        return view('admin.pronostics.by-match', compact('match', 'pronostics', 'stats'));
    }

    /**
     * Supprimer un pronostic (admin seulement)
     */
    public function destroy(Pronostic $pronostic)
    {
        $pronostic->delete();

        return redirect()->back()
            ->with('success', 'Pronostic supprimé avec succès.');
    }

    /**
     * Statistiques globales des pronostics
     */
    public function stats()
    {
        $stats = [
            'total_pronostics' => Pronostic::count(),
            'total_winners' => Pronostic::where('is_winner', true)->count(),
            'total_points_distributed' => Pronostic::sum('points_won'),
            'by_match' => FootballMatch::select('matches.*')
                ->selectRaw('COUNT(pronostics.id) as total_pronostics')
                ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_winners')
                ->leftJoin('pronostics', 'matches.id', '=', 'pronostics.match_id')
                ->groupBy('matches.id')
                ->having('total_pronostics', '>', 0)
                ->orderBy('match_date', 'desc')
                ->get(),
            'top_users' => User::select('users.*')
                ->selectRaw('SUM(pronostics.points_won) as total_points')
                ->selectRaw('COUNT(pronostics.id) as total_pronostics')
                ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_wins')
                ->leftJoin('pronostics', 'users.id', '=', 'pronostics.user_id')
                ->groupBy('users.id')
                ->having('total_points', '>', 0)
                ->orderBy('total_points', 'desc')
                ->take(10)
                ->get(),
        ];

        return view('admin.pronostics.stats', compact('stats'));
    }
}
