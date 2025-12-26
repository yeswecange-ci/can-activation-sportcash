<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\Pronostic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'exact_scores' => $pronostics->filter(function ($p) use ($match) {
                return $p->is_winner && 
                       $p->predicted_score_a == $match->score_a && 
                       $p->predicted_score_b == $match->score_b;
            })->count(),
            'by_prediction' => $pronostics->groupBy(function ($p) {
                return $p->prediction_text;
            })->map->count()->sortDesc(),
        ];

        return view('admin.pronostics.by-match', compact('match', 'pronostics', 'stats'));
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Évaluer tous les pronostics d'un match terminé
     */
    public function evaluateMatch(FootballMatch $match)
    {
        // Vérifier que le match est terminé
        if ($match->status !== 'finished') {
            return redirect()->back()
                ->with('error', 'Le match n\'est pas encore terminé.');
        }

        // Vérifier que les scores sont renseignés
        if ($match->score_a === null || $match->score_b === null) {
            return redirect()->back()
                ->with('error', 'Les scores du match ne sont pas renseignés.');
        }

        DB::beginTransaction();
        try {
            // Récupérer tous les pronostics de ce match
            $pronostics = $match->pronostics;
            
            $evaluated = 0;
            $winners = 0;
            $exactScores = 0;

            foreach ($pronostics as $pronostic) {
                // Évaluer le pronostic
                $pronostic->evaluateResult($match->score_a, $match->score_b);
                
                $evaluated++;
                if ($pronostic->is_winner) {
                    $winners++;
                    if ($pronostic->isExactScore()) {
                        $exactScores++;
                    }
                }
            }

            DB::commit();

            Log::info('Match pronostics evaluated', [
                'match_id' => $match->id,
                'match' => "{$match->team_a} vs {$match->team_b}",
                'score' => "{$match->score_a} - {$match->score_b}",
                'total_evaluated' => $evaluated,
                'winners' => $winners,
                'exact_scores' => $exactScores,
            ]);

            return redirect()->back()
                ->with('success', "✅ {$evaluated} pronostics évalués : {$winners} gagnants ({$exactScores} scores exacts)");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error evaluating match pronostics', [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'évaluation des pronostics.');
        }
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Réévaluer tous les pronostics de tous les matchs terminés
     */
    public function reevaluateAll()
    {
        $matches = FootballMatch::where('status', 'finished')
            ->whereNotNull('score_a')
            ->whereNotNull('score_b')
            ->get();

        DB::beginTransaction();
        try {
            $totalEvaluated = 0;
            $totalWinners = 0;
            $totalExactScores = 0;

            foreach ($matches as $match) {
                $pronostics = $match->pronostics;
                
                foreach ($pronostics as $pronostic) {
                    $pronostic->evaluateResult($match->score_a, $match->score_b);
                    
                    $totalEvaluated++;
                    if ($pronostic->is_winner) {
                        $totalWinners++;
                        if ($pronostic->isExactScore()) {
                            $totalExactScores++;
                        }
                    }
                }
            }

            DB::commit();

            Log::info('All pronostics reevaluated', [
                'matches_count' => $matches->count(),
                'total_evaluated' => $totalEvaluated,
                'total_winners' => $totalWinners,
                'total_exact_scores' => $totalExactScores,
            ]);

            return redirect()->back()
                ->with('success', "✅ {$totalEvaluated} pronostics réévalués sur {$matches->count()} matchs : {$totalWinners} gagnants ({$totalExactScores} scores exacts)");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reevaluating all pronostics', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la réévaluation des pronostics.');
        }
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
            'total_exact_scores' => Pronostic::where('is_winner', true)
                ->where('points_won', Pronostic::POINTS_EXACT_SCORE)
                ->count(),
            'total_points_distributed' => Pronostic::sum('points_won'),
            'by_match' => FootballMatch::select('matches.*')
                ->selectRaw('COUNT(pronostics.id) as total_pronostics')
                ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_winners')
                ->selectRaw('SUM(CASE WHEN pronostics.points_won = ? THEN 1 ELSE 0 END) as exact_scores', [Pronostic::POINTS_EXACT_SCORE])
                ->leftJoin('pronostics', 'matches.id', '=', 'pronostics.match_id')
                ->groupBy('matches.id')
                ->having('total_pronostics', '>', 0)
                ->orderBy('match_date', 'desc')
                ->get(),
            'top_users' => User::select('users.*')
                ->selectRaw('SUM(pronostics.points_won) as total_points')
                ->selectRaw('COUNT(pronostics.id) as total_pronostics')
                ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_wins')
                ->selectRaw('SUM(CASE WHEN pronostics.points_won = ? THEN 1 ELSE 0 END) as exact_scores', [Pronostic::POINTS_EXACT_SCORE])
                ->leftJoin('pronostics', 'users.id', '=', 'pronostics.user_id')
                ->groupBy('users.id')
                ->having('total_points', '>', 0)
                ->orderBy('total_points', 'desc')
                ->take(10)
                ->get(),
            'unevaluated_count' => Pronostic::finishedMatches()
                ->unevaluated()
                ->count(),
        ];

        return view('admin.pronostics.stats', compact('stats'));
    }

    /**
     * Export CSV des gagnants d'un match spécifique
     */
    public function exportWinners(FootballMatch $match)
    {
        // Récupérer uniquement les gagnants de ce match
        $winners = $match->pronostics()
            ->with(['user.village'])
            ->where('is_winner', true)
            ->orderByDesc('points_won')
            ->orderBy('created_at', 'asc')
            ->get();

        $filename = 'gagnants_' . str_replace(' ', '_', $match->team_a) . '_vs_' . str_replace(' ', '_', $match->team_b) . '_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($winners, $match) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes
            fputcsv($file, [
                'Nom',
                'Téléphone',
                'Village',
                'Pronostic',
                'Score réel',
                'Points gagnés',
                'Type de gain',
                'Date du pronostic'
            ]);

            // Données des gagnants
            foreach ($winners as $winner) {
                $gainType = $winner->points_won == Pronostic::POINTS_EXACT_SCORE
                    ? 'Score exact'
                    : 'Bon résultat';

                fputcsv($file, [
                    $winner->user->name,
                    $winner->user->phone,
                    $winner->user->village->name ?? 'N/A',
                    $winner->prediction_text,
                    ($match->score_a ?? '-') . ' - ' . ($match->score_b ?? '-'),
                    $winner->points_won,
                    $gainType,
                    $winner->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export CSV de tous les gagnants (tous matchs confondus)
     */
    public function exportAllWinners()
    {
        $winners = Pronostic::with(['user.village', 'match'])
            ->where('is_winner', true)
            ->orderByDesc('points_won')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'tous_gagnants_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($winners) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Nom',
                'Téléphone',
                'Village',
                'Match',
                'Pronostic',
                'Score réel',
                'Points gagnés',
                'Type de gain',
                'Date du pronostic'
            ]);

            foreach ($winners as $winner) {
                $gainType = $winner->points_won == Pronostic::POINTS_EXACT_SCORE
                    ? 'Score exact'
                    : 'Bon résultat';

                fputcsv($file, [
                    $winner->user->name,
                    $winner->user->phone,
                    $winner->user->village->name ?? 'N/A',
                    $winner->match->team_a . ' vs ' . $winner->match->team_b,
                    $winner->prediction_text,
                    ($winner->match->score_a ?? '-') . ' - ' . ($winner->match->score_b ?? '-'),
                    $winner->points_won,
                    $gainType,
                    $winner->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}