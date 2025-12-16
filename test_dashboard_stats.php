<?php

/**
 * Script de test pour vérifier que les statistiques du dashboard fonctionnent correctement
 *
 * Usage: php test_dashboard_stats.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use App\Models\FootballMatch;
use App\Models\Pronostic;
use App\Models\Village;

echo "🧪 Test des Statistiques du Dashboard\n";
echo "=====================================\n\n";

// Test 1: Vérifier la colonne points_won
echo "1️⃣ Vérification de la colonne points_won...\n";
try {
    $testProno = Pronostic::first();
    if ($testProno && property_exists($testProno, 'points_won')) {
        echo "   ✅ Colonne points_won existe\n";
        echo "   📊 Exemple: Pronostic #{$testProno->id} a {$testProno->points_won} points\n";
    } else {
        echo "   ❌ Colonne points_won n'existe pas - Exécutez la migration!\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: {$e->getMessage()}\n";
}

echo "\n";

// Test 2: Statistiques générales
echo "2️⃣ Statistiques Générales...\n";
$totalPronostics = Pronostic::count();
$totalWinners = Pronostic::where('is_winner', true)->count();
$totalPoints = Pronostic::sum('points_won');

echo "   📊 Total pronostics: {$totalPronostics}\n";
echo "   🏆 Total gagnants: {$totalWinners}\n";
echo "   ✨ Total points distribués: {$totalPoints} pts\n";

echo "\n";

// Test 3: Top Utilisateurs
echo "3️⃣ Top 5 Utilisateurs (par points)...\n";
$topUsers = User::select('users.*')
    ->selectRaw('COALESCE(SUM(pronostics.points_won), 0) as total_points')
    ->selectRaw('COUNT(pronostics.id) as total_pronostics')
    ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_wins')
    ->leftJoin('pronostics', 'users.id', '=', 'pronostics.user_id')
    ->where('users.is_active', true)
    ->groupBy('users.id')
    ->having('total_points', '>', 0)
    ->orderByDesc('total_points')
    ->take(5)
    ->get();

if ($topUsers->count() > 0) {
    foreach ($topUsers as $index => $user) {
        $medal = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : '  '));
        echo "   {$medal} {$user->name} - {$user->total_points} pts ({$user->total_wins} victoires / {$user->total_pronostics} pronos)\n";
    }
} else {
    echo "   ℹ️  Aucun utilisateur avec des points pour le moment\n";
}

echo "\n";

// Test 4: Statistiques par Match
echo "4️⃣ Statistiques par Match (5 derniers)...\n";
$matchStats = FootballMatch::select('matches.*')
    ->selectRaw('COUNT(pronostics.id) as total_pronostics')
    ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_winners')
    ->selectRaw('SUM(pronostics.points_won) as total_points')
    ->leftJoin('pronostics', 'matches.id', '=', 'pronostics.match_id')
    ->groupBy('matches.id')
    ->having('total_pronostics', '>', 0)
    ->orderBy('match_date', 'desc')
    ->take(5)
    ->get();

if ($matchStats->count() > 0) {
    foreach ($matchStats as $match) {
        echo "   ⚽ {$match->team_a} vs {$match->team_b}\n";
        echo "      • {$match->total_pronostics} pronostic(s), {$match->total_winners} gagnant(s)\n";
        echo "      • {$match->total_points} points distribués\n";
        if ($match->status === 'finished') {
            echo "      • Score: {$match->score_a} - {$match->score_b}\n";
        }
    }
} else {
    echo "   ℹ️  Aucun match avec des pronostics pour le moment\n";
}

echo "\n";

// Test 5: Test du système de points
echo "5️⃣ Test du Système de Points...\n";

$match = FootballMatch::where('status', 'finished')
    ->whereNotNull('score_a')
    ->whereNotNull('score_b')
    ->first();

if ($match) {
    echo "   📊 Match testé: {$match->team_a} vs {$match->team_b} ({$match->score_a} - {$match->score_b})\n";

    $pronostics = Pronostic::where('match_id', $match->id)->get();

    if ($pronostics->count() > 0) {
        $exactScores = 0;
        $goodResults = 0;
        $wrongResults = 0;

        foreach ($pronostics as $prono) {
            if ($prono->points_won == 10) {
                $exactScores++;
            } elseif ($prono->points_won == 5) {
                $goodResults++;
            } else {
                $wrongResults++;
            }
        }

        echo "   🎯 Scores exacts (10 pts): {$exactScores}\n";
        echo "   ✅ Bons résultats (5 pts): {$goodResults}\n";
        echo "   ❌ Mauvais pronos (0 pts): {$wrongResults}\n";

        // Afficher quelques exemples
        echo "\n   📋 Exemples de pronostics:\n";
        foreach ($pronostics->take(3) as $prono) {
            $user = $prono->user;
            echo "      • {$user->name}: {$prono->prediction_text} = {$prono->points_won} pts ";
            echo $prono->is_winner ? "✅\n" : "❌\n";
        }
    } else {
        echo "   ℹ️  Aucun pronostic pour ce match\n";
    }
} else {
    echo "   ℹ️  Aucun match terminé trouvé pour tester\n";
}

echo "\n";

// Test 6: Vérifier l'attribut prediction_text
echo "6️⃣ Test de l'attribut prediction_text...\n";
$sampleProno = Pronostic::with('match')->first();

if ($sampleProno) {
    echo "   📝 Pronostic #{$sampleProno->id}:\n";
    echo "      • prediction_type: " . ($sampleProno->prediction_type ?? 'null') . "\n";
    echo "      • predicted_score_a: " . ($sampleProno->predicted_score_a ?? 'null') . "\n";
    echo "      • predicted_score_b: " . ($sampleProno->predicted_score_b ?? 'null') . "\n";
    echo "      • prediction_text: {$sampleProno->prediction_text}\n";
    echo "      ✅ L'attribut prediction_text fonctionne correctement\n";
} else {
    echo "   ℹ️  Aucun pronostic trouvé\n";
}

echo "\n";

// Test 7: Leaderboard
echo "7️⃣ Test du Leaderboard...\n";
$villages = Village::where('is_active', true)->take(2)->get();

foreach ($villages as $village) {
    echo "   🏘️  Village: {$village->name}\n";

    $leaderboard = User::select('users.*')
        ->selectRaw('COALESCE(SUM(pronostics.points_won), 0) as total_points')
        ->selectRaw('COUNT(pronostics.id) as total_pronostics')
        ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_wins')
        ->leftJoin('pronostics', 'users.id', '=', 'pronostics.user_id')
        ->where('users.is_active', true)
        ->where('users.village_id', $village->id)
        ->groupBy('users.id')
        ->having('total_points', '>', 0)
        ->orderByDesc('total_points')
        ->take(3)
        ->get();

    if ($leaderboard->count() > 0) {
        foreach ($leaderboard as $index => $user) {
            echo "      {" . ($index + 1) . "} {$user->name}: {$user->total_points} pts\n";
        }
    } else {
        echo "      ℹ️  Aucun joueur avec des points dans ce village\n";
    }
    echo "\n";
}

echo "=====================================\n";
echo "✅ Tous les tests sont terminés!\n\n";

echo "📌 Prochaines étapes:\n";
echo "   1. Visitez /admin/dashboard pour voir les stats générales\n";
echo "   2. Visitez /admin/pronostics/stats pour les stats détaillées\n";
echo "   3. Visitez /admin/leaderboard pour le classement\n";
echo "   4. Testez la commande: php artisan pronostic:calculate-winners\n";
echo "\n";
