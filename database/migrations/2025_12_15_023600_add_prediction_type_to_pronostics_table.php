<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pronostics', function (Blueprint $table) {
            // Ajouter le type de prédiction simple : team_a_win, team_b_win, draw
            $table->enum('prediction_type', ['team_a_win', 'team_b_win', 'draw'])->nullable()->after('match_id');

            // Rendre les scores optionnels pour supporter les deux modes
            $table->integer('predicted_score_a')->nullable()->change();
            $table->integer('predicted_score_b')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pronostics', function (Blueprint $table) {
            $table->dropColumn('prediction_type');

            // Restaurer les scores comme non-nullable
            $table->integer('predicted_score_a')->nullable(false)->change();
            $table->integer('predicted_score_b')->nullable(false)->change();
        });
    }
};
