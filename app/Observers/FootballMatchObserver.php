<?php

namespace App\Observers;

use App\Models\FootballMatch;

class FootballMatchObserver
{
    /**
     * Handle the FootballMatch "created" event.
     */
    public function created(FootballMatch $footballMatch): void
    {
        //
    }

    /**
     * Handle the FootballMatch "updated" event.
     */
    public function updated(FootballMatch $footballMatch): void
    {
        // Vérifier si le match vient d'être marqué comme terminé
        if ($footballMatch->status === 'finished' &&
            $footballMatch->wasChanged('status') &&
            $footballMatch->score_a !== null &&
            $footballMatch->score_b !== null) {

            // Évaluer tous les pronostics de ce match
            foreach ($footballMatch->pronostics as $pronostic) {
                $pronostic->evaluateResult($footballMatch->score_a, $footballMatch->score_b);
            }
        }

        // Ou si les scores ont changé et que le match est déjà terminé
        if ($footballMatch->status === 'finished' &&
            ($footballMatch->wasChanged('score_a') || $footballMatch->wasChanged('score_b')) &&
            $footballMatch->score_a !== null &&
            $footballMatch->score_b !== null) {

            // Réévaluer tous les pronostics avec les nouveaux scores
            foreach ($footballMatch->pronostics as $pronostic) {
                $pronostic->evaluateResult($footballMatch->score_a, $footballMatch->score_b);
            }
        }
    }

    /**
     * Handle the FootballMatch "deleted" event.
     */
    public function deleted(FootballMatch $footballMatch): void
    {
        //
    }

    /**
     * Handle the FootballMatch "restored" event.
     */
    public function restored(FootballMatch $footballMatch): void
    {
        //
    }

    /**
     * Handle the FootballMatch "force deleted" event.
     */
    public function forceDeleted(FootballMatch $footballMatch): void
    {
        //
    }
}
