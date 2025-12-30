<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index()
    {
        $matches = FootballMatch::withCount('pronostics')->orderBy('match_date', 'desc')->paginate(10);
        return view('admin.matches.index', compact('matches'));
    }

    public function create()
    {
        return view('admin.matches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_a' => 'required|string|max:255',
            'team_b' => 'required|string|max:255',
            'match_date' => 'required|date',
            'status' => 'required|in:scheduled,live,finished',
        ]);

        $validated['pronostic_enabled'] = $request->has('pronostic_enabled');

        FootballMatch::create($validated);

        return redirect()->route('admin.matches.index')
            ->with('success', 'Match créé avec succès !');
    }

    public function show(FootballMatch $match)
    {
        $match->load(['pronostics.user', 'prizeWinners.user', 'prizeWinners.prize']);
        return view('admin.matches.show', compact('match'));
    }

    public function edit(FootballMatch $match)
    {
        return view('admin.matches.edit', compact('match'));
    }

    public function update(Request $request, FootballMatch $match)
    {
        $validated = $request->validate([
            'team_a' => 'required|string|max:255',
            'team_b' => 'required|string|max:255',
            'match_date' => 'required|date',
            'score_a' => 'nullable|integer|min:0',
            'score_b' => 'nullable|integer|min:0',
            'status' => 'required|in:scheduled,live,finished',
        ]);

        $validated['pronostic_enabled'] = $request->has('pronostic_enabled');

        // Vérifier si le match passe à "finished" avec des scores définis
        $isBecomingFinished = $validated['status'] === 'finished'
            && $match->status !== 'finished'
            && !is_null($validated['score_a'])
            && !is_null($validated['score_b']);

        $match->update($validated);

        // Calculer automatiquement les gagnants si le match vient de se terminer
        if ($isBecomingFinished && !$match->winners_calculated) {
            $this->calculateWinners($match);

            return redirect()->route('admin.matches.index')
                ->with('success', 'Match mis à jour et gagnants calculés automatiquement !');
        }

        return redirect()->route('admin.matches.index')
            ->with('success', 'Match mis à jour avec succès !');
    }

    /**
     * Calculer automatiquement les gagnants d'un match
     */
    private function calculateWinners(FootballMatch $match)
    {
        // Récupérer tous les pronostics pour ce match
        $pronostics = $match->pronostics()->get();

        $winnersCount = 0;
        $exactScoreCount = 0;

        foreach ($pronostics as $pronostic) {
            // Utiliser la méthode evaluateResult() du modèle Pronostic
            // qui gère correctement les matchs nuls et les types de résultats
            $pronostic->evaluateResult($match->score_a, $match->score_b);

            if ($pronostic->is_winner) {
                $winnersCount++;

                // Compter les scores exacts
                if ($pronostic->points_won === \App\Models\Pronostic::POINTS_EXACT_SCORE) {
                    $exactScoreCount++;
                }
            }
        }

        // Marquer que les gagnants ont été calculés
        $match->update(['winners_calculated' => true]);

        \Log::info("Match {$match->id} - Gagnants calculés automatiquement", [
            'match' => "{$match->team_a} vs {$match->team_b}",
            'score_final' => "{$match->score_a} - {$match->score_b}",
            'total_pronostics' => $pronostics->count(),
            'winners_count' => $winnersCount,
            'exact_score_count' => $exactScoreCount,
        ]);

        return $winnersCount;
    }

    public function destroy(FootballMatch $match)
    {
        $match->delete();

        return redirect()->route('admin.matches.index')
            ->with('success', 'Match supprimé avec succès !');
    }
}
