<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Pronostic;
use App\Models\PrizeWinner;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculatePronosticWinners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pronostic:calculate-winners {--match= : ID du match spécifique à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculer les gagnants pour les matchs terminés et envoyer les notifications';

    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏆 Calcul des gagnants en cours...');

        // Si un match spécifique est fourni
        if ($matchId = $this->option('match')) {
            $matches = FootballMatch::where('id', $matchId)
                ->where('status', 'finished')
                ->get();

            if ($matches->isEmpty()) {
                $this->error("❌ Match #{$matchId} introuvable ou non terminé");
                return Command::FAILURE;
            }
        } else {
            // Récupérer tous les matchs terminés qui n'ont pas encore été traités
            $matches = FootballMatch::where('status', 'finished')
                ->whereNotNull('score_a')
                ->whereNotNull('score_b')
                ->where('winners_calculated', false)
                ->get();
        }

        if ($matches->isEmpty()) {
            $this->info('✅ Aucun match à traiter');
            return Command::SUCCESS;
        }

        $this->info("📊 {$matches->count()} match(s) à traiter");

        $totalWinners = 0;
        $totalPrizesAwarded = 0;

        foreach ($matches as $match) {
            $this->line("\n⚽ Traitement: {$match->team_a} vs {$match->team_b}");
            $this->line("   Score: {$match->score_a} - {$match->score_b}");

            // Trouver tous les pronostics pour ce match
            $allPronostics = Pronostic::where('match_id', $match->id)->get();

            if ($allPronostics->isEmpty()) {
                $this->warn("   ⚠️ Aucun pronostic pour ce match");
                $match->update(['winners_calculated' => true]);
                continue;
            }

            // Déterminer le résultat du match
            $matchResult = $this->getMatchResult($match);

            // Trouver les pronostics gagnants
            $exactWinners = collect();
            $goodResultWinners = collect();

            foreach ($allPronostics as $prono) {
                $result = $this->checkPronostic($prono, $match, $matchResult);

                if ($result === 'exact') {
                    $exactWinners->push($prono);
                    $prono->update(['is_winner' => true, 'points_won' => 10]);
                } elseif ($result === 'good_result') {
                    $goodResultWinners->push($prono);
                    $prono->update(['is_winner' => true, 'points_won' => 5]);
                } else {
                    $prono->update(['is_winner' => false, 'points_won' => 0]);
                }
            }

            $exactCount = $exactWinners->count();
            $goodResultCount = $goodResultWinners->count();
            $winnersCount = $exactCount + $goodResultCount;
            $participantsCount = $allPronostics->count();

            $this->info("   📈 {$participantsCount} participants");
            $this->info("   🎯 {$exactCount} score(s) exact(s) (10 pts)");
            $this->info("   ✅ {$goodResultCount} bon(s) résultat(s) (5 pts)");
            $this->info("   🏆 Total gagnants: {$winnersCount}");

            if ($winnersCount > 0) {
                // Fusionner tous les gagnants
                $allWinners = $exactWinners->merge($goodResultWinners);

                // Attribuer les prix si définis (seulement aux scores exacts)
                if ($match->prize_id && $exactCount > 0) {
                    foreach ($exactWinners as $prono) {
                        $prizeWinner = PrizeWinner::create([
                            'user_id' => $prono->user_id,
                            'prize_id' => $match->prize_id,
                            'match_id' => $match->id,
                        ]);

                        $totalPrizesAwarded++;

                        $this->line("   🎁 Prix attribué à {$prono->user->name}");
                    }
                }

                // Envoyer notifications WhatsApp aux gagnants
                foreach ($allWinners as $prono) {
                    try {
                        $this->sendWinnerNotification($prono->user, $match, $prono->points_won ?? 5);
                        $this->line("   ✅ Notification envoyée à {$prono->user->name}");
                    } catch (\Exception $e) {
                        $this->error("   ❌ Erreur notification pour {$prono->user->name}: {$e->getMessage()}");
                        Log::error("Winner notification error", [
                            'user_id' => $prono->user_id,
                            'match_id' => $match->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $totalWinners += $winnersCount;
            }

            // Marquer le match comme traité
            $match->update(['winners_calculated' => true]);

            Log::info('Pronostic winners calculated', [
                'match_id' => $match->id,
                'participants' => $participantsCount,
                'winners' => $winnersCount,
            ]);
        }

        $this->newLine();
        $this->info("✅ Traitement terminé !");
        $this->info("🏆 Total gagnants: {$totalWinners}");
        $this->info("🎁 Total prix attribués: {$totalPrizesAwarded}");

        return Command::SUCCESS;
    }

    /**
     * Déterminer le résultat du match
     */
    protected function getMatchResult($match)
    {
        if ($match->score_a > $match->score_b) {
            return 'team_a_win';
        } elseif ($match->score_b > $match->score_a) {
            return 'team_b_win';
        } else {
            return 'draw';
        }
    }

    /**
     * Vérifier un pronostic
     * Retourne: 'exact', 'good_result', ou 'wrong'
     */
    protected function checkPronostic($prono, $match, $matchResult)
    {
        // Mode 1: Pronostic avec scores
        if ($prono->predicted_score_a !== null && $prono->predicted_score_b !== null) {
            // Score exact ?
            if ($prono->predicted_score_a == $match->score_a && $prono->predicted_score_b == $match->score_b) {
                return 'exact';
            }

            // Bon résultat (victoire/nul) ?
            $pronoResult = $this->getResultFromScores($prono->predicted_score_a, $prono->predicted_score_b);
            if ($pronoResult === $matchResult) {
                return 'good_result';
            }

            return 'wrong';
        }

        // Mode 2: Pronostic simple (prediction_type)
        if ($prono->prediction_type) {
            if ($prono->prediction_type === $matchResult) {
                return 'good_result';
            }

            return 'wrong';
        }

        return 'wrong';
    }

    /**
     * Déterminer le résultat à partir de scores
     */
    protected function getResultFromScores($scoreA, $scoreB)
    {
        if ($scoreA > $scoreB) {
            return 'team_a_win';
        } elseif ($scoreB > $scoreA) {
            return 'team_b_win';
        } else {
            return 'draw';
        }
    }

    /**
     * Envoyer notification WhatsApp au gagnant
     */
    protected function sendWinnerNotification($user, $match, $points = 5)
    {
        $message = "🎉 *FÉLICITATIONS !* 🎉\n\n";
        $message .= "Tu as GAGNÉ ton pronostic !\n\n";
        $message .= "⚽ *Match:* {$match->team_a} vs {$match->team_b}\n";
        $message .= "📊 *Score final:* {$match->score_a} - {$match->score_b}\n";
        $message .= "✨ *Points gagnés:* {$points} pts\n\n";

        if ($points == 10) {
            $message .= "🎯 *SCORE EXACT !* Tu es un champion !\n\n";
        }

        if ($match->prize_id && $points == 10) {
            $prize = $match->prize;
            $message .= "🎁 *Tu as gagné:* {$prize->name} !\n";
            $message .= "💰 Valeur: {$prize->value} {$prize->currency}\n\n";
            $message .= "📍 Pour récupérer ton prix, contacte-nous ou consulte les instructions dans l'app.\n\n";
        } else {
            $message .= "🏆 Continue comme ça pour gagner encore plus de prix !\n\n";
        }

        $message .= "💡 Envoie MENU pour faire d'autres pronostics !";

        $this->whatsapp->sendMessage($user->phone, $message);
    }
}
