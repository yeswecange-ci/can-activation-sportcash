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
     * Vérifier si le pronostic est correct (score exact)
     */
    public function isCorrect(): bool
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
     * Créer ou mettre à jour un pronostic avec un type simple
     */
    public static function createOrUpdateSimple(User $user, FootballMatch $match, string $predictionType): self
    {
        // Valider le type de prédiction
        if (!in_array($predictionType, [self::PREDICTION_TEAM_A_WIN, self::PREDICTION_TEAM_B_WIN, self::PREDICTION_DRAW])) {
            throw new \InvalidArgumentException("Type de prédiction invalide: {$predictionType}");
        }

        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'match_id' => $match->id,
            ],
            [
                'prediction_type' => $predictionType,
                'predicted_score_a' => null, // Reset les scores si on utilise le type
                'predicted_score_b' => null,
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
}
