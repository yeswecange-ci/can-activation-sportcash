<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pronostic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_id',
        'prediction_type',
        'predicted_score_a',
        'predicted_score_b',
        'is_winner',
        'points_won',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'predicted_score_a' => 'integer',
        'predicted_score_b' => 'integer',
        'points_won' => 'integer',
    ];

    // Types de prédiction possibles
    const PREDICTION_TEAM_A_WIN = 'team_a_win';
    const PREDICTION_TEAM_B_WIN = 'team_b_win';
    const PREDICTION_DRAW = 'draw';

    // Système de points
    const POINTS_EXACT_SCORE = 10;  // Score exact
    const POINTS_CORRECT_RESULT = 5; // Bon résultat (victoire/nul/défaite)
    const POINTS_WRONG = 0;          // Mauvais pronostic

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Déterminer le type de résultat d'un match
     */
    private function determineResultType(int $scoreA, int $scoreB): string
    {
        if ($scoreA > $scoreB) {
            return self::PREDICTION_TEAM_A_WIN;
        }
        
        if ($scoreA < $scoreB) {
            return self::PREDICTION_TEAM_B_WIN;
        }
        
        return self::PREDICTION_DRAW;
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Évaluer le pronostic après le match
     * C'est LA méthode principale qui corrige le problème des matchs nuls
     */
    public function evaluateResult(int $actualScoreA, int $actualScoreB): self
    {
        // Déterminer le type de résultat réel
        $actualResult = $this->determineResultType($actualScoreA, $actualScoreB);

        // Déterminer le type de résultat prédit
        // Si prediction_type est défini, l'utiliser directement
        // Sinon, calculer à partir des scores prédits
        if ($this->prediction_type) {
            $predictedResult = $this->prediction_type;
        } else {
            $predictedResult = $this->determineResultType(
                $this->predicted_score_a ?? 0,
                $this->predicted_score_b ?? 0
            );
        }

        // Le résultat est-il correct ?
        if ($actualResult === $predictedResult) {
            $this->is_winner = true;

            // Score exact ? Bonus !
            if ($this->predicted_score_a == $actualScoreA &&
                $this->predicted_score_b == $actualScoreB) {
                $this->points_won = self::POINTS_EXACT_SCORE;
            } else {
                // Bon résultat mais pas le score exact
                $this->points_won = self::POINTS_CORRECT_RESULT;
            }
        } else {
            // Mauvais pronostic
            $this->is_winner = false;
            $this->points_won = self::POINTS_WRONG;
        }

        $this->save();

        return $this;
    }

    /**
     * ✅ AMÉLIORÉE : Vérifier si le pronostic est correct
     * Cette méthode utilise maintenant la logique du type de résultat
     */
    public function isCorrect(): bool
    {
        if (!$this->match || $this->match->status !== 'finished') {
            return false;
        }

        $actualResult = $this->determineResultType(
            $this->match->score_a, 
            $this->match->score_b
        );
        
        $predictedResult = $this->determineResultType(
            $this->predicted_score_a, 
            $this->predicted_score_b
        );

        return $actualResult === $predictedResult;
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Vérifier si le score est exact
     */
    public function isExactScore(): bool
    {
        if (!$this->match || $this->match->status !== 'finished') {
            return false;
        }

        return $this->predicted_score_a === $this->match->score_a
            && $this->predicted_score_b === $this->match->score_b;
    }

    /**
     * Formater le pronostic (ex: "2 - 1")
     */
    public function getFormattedScoreAttribute(): string
    {
        return "{$this->predicted_score_a} - {$this->predicted_score_b}";
    }

    /**
     * Vérifier si le match peut encore recevoir des pronostics
     */
    public static function canBet(FootballMatch $match): bool
    {
        // Pas de pronostic si le match a commencé ou est terminé
        if (in_array($match->status, ['live', 'finished'])) {
            return false;
        }

        // Pas de pronostic si les pronostics sont désactivés
        if (!$match->pronostic_enabled) {
            return false;
        }

        // Pas de pronostic si le match est dans le passé ou dans moins de 5 minutes
        $minutesUntilMatch = now()->diffInMinutes($match->match_date, false);
        if ($minutesUntilMatch < 5) {
            return false;
        }

        return true;
    }

    /**
     * Créer ou mettre à jour un pronostic avec des scores
     */
    public static function createOrUpdate(User $user, FootballMatch $match, int $scoreA, int $scoreB): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'match_id' => $match->id,
            ],
            [
                'predicted_score_a' => $scoreA,
                'predicted_score_b' => $scoreB,
                'prediction_type' => null, // Reset le type si on utilise des scores
            ]
        );
    }

    /**
     * ✅ AMÉLIORÉE : Créer ou mettre à jour un pronostic avec un type simple
     * Maintenant stocke aussi les scores correspondants pour l'évaluation
     */
    public static function createOrUpdateSimple(User $user, FootballMatch $match, string $predictionType): self
    {
        // Valider le type de prédiction
        if (!in_array($predictionType, [self::PREDICTION_TEAM_A_WIN, self::PREDICTION_TEAM_B_WIN, self::PREDICTION_DRAW])) {
            throw new \InvalidArgumentException("Type de prédiction invalide: {$predictionType}");
        }

        // Convertir le type en scores pour faciliter l'évaluation
        [$scoreA, $scoreB] = match ($predictionType) {
            self::PREDICTION_TEAM_A_WIN => [1, 0],
            self::PREDICTION_TEAM_B_WIN => [0, 1],
            self::PREDICTION_DRAW => [0, 0],
        };

        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'match_id' => $match->id,
            ],
            [
                'prediction_type' => $predictionType,
                'predicted_score_a' => $scoreA,
                'predicted_score_b' => $scoreB,
            ]
        );
    }

    /**
     * Obtenir le pronostic en format lisible
     */
    public function getPredictionTextAttribute(): string
    {
        if ($this->prediction_type) {
            return match($this->prediction_type) {
                self::PREDICTION_TEAM_A_WIN => "Victoire {$this->match->team_a}",
                self::PREDICTION_TEAM_B_WIN => "Victoire {$this->match->team_b}",
                self::PREDICTION_DRAW => "Match nul",
                default => "Pronostic inconnu",
            };
        }

        if ($this->predicted_score_a !== null && $this->predicted_score_b !== null) {
            return "{$this->predicted_score_a} - {$this->predicted_score_b}";
        }

        return "Pas de pronostic";
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Obtenir le type de résultat prédit
     */
    public function getPredictedResultTypeAttribute(): string
    {
        if ($this->prediction_type) {
            return $this->prediction_type;
        }

        return $this->determineResultType(
            $this->predicted_score_a ?? 0, 
            $this->predicted_score_b ?? 0
        );
    }

    /**
     * Scope pour récupérer les pronostics gagnants
     */
    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }

    /**
     * Scope pour récupérer les pronostics d'un match
     */
    public function scopeForMatch($query, $matchId)
    {
        return $query->where('match_id', $matchId);
    }

    /**
     * Scope pour récupérer les pronostics d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Scope pour les pronostics non évalués
     */
    public function scopeUnevaluated($query)
    {
        return $query->whereNull('is_winner');
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Scope pour les pronostics de matchs terminés
     */
    public function scopeFinishedMatches($query)
    {
        return $query->whereHas('match', function ($q) {
            $q->where('status', 'finished');
        });
    }
}