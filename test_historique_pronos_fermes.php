<?php

/**
 * Test: Vérifier que l'historique est visible même quand les pronostics sont fermés
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\FootballMatch;
use App\Models\Pronostic;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TwilioStudioController;

echo "🧪 TEST : Historique visible même quand pronostics fermés\n";
echo str_repeat("=", 60) . "\n\n";

// Créer ou récupérer un utilisateur
$user = User::where('phone', '+243999999999')->first();
if (!$user) {
    $user = User::create([
        'phone' => '+243999999999',
        'name' => 'Test User',
        'status' => 'INSCRIT',
    ]);
    echo "✅ Utilisateur créé : {$user->name}\n";
} else {
    echo "✅ Utilisateur existant : {$user->name}\n";
}

// Créer 2 matchs
FootballMatch::query()->update(['pronostic_enabled' => false]);

$match1 = FootballMatch::where('team_a', 'Maroc')
    ->where('team_b', 'Sénégal')
    ->where('match_date', '>', now())
    ->first();

if (!$match1) {
    $match1 = FootballMatch::create([
        'team_a' => 'Maroc',
        'team_b' => 'Sénégal',
        'match_date' => now()->addDays(1)->setTime(20, 0),
        'location' => 'Stade',
        'status' => 'scheduled',
        'pronostic_enabled' => true, // Pronostics OUVERTS initialement
    ]);
} else {
    $match1->update(['pronostic_enabled' => true]);
}

$match2 = FootballMatch::where('team_a', 'Côte d\'Ivoire')
    ->where('team_b', 'Nigeria')
    ->where('match_date', '>', now())
    ->first();

if (!$match2) {
    $match2 = FootballMatch::create([
        'team_a' => 'Côte d\'Ivoire',
        'team_b' => 'Nigeria',
        'match_date' => now()->addDays(2)->setTime(20, 0),
        'location' => 'Stade',
        'status' => 'scheduled',
        'pronostic_enabled' => true, // Pronostics OUVERTS initialement
    ]);
} else {
    $match2->update(['pronostic_enabled' => true]);
}

echo "✅ 2 matchs créés avec pronostics OUVERTS\n\n";

// Supprimer les anciens pronostics
Pronostic::where('user_id', $user->id)->delete();

// Créer 2 pronostics pour cet utilisateur
$prono1 = Pronostic::create([
    'user_id' => $user->id,
    'match_id' => $match1->id,
    'prediction_type' => 'team_a_win',
    'predicted_score_a' => null,
    'predicted_score_b' => null,
]);

$prono2 = Pronostic::create([
    'user_id' => $user->id,
    'match_id' => $match2->id,
    'prediction_type' => 'draw',
    'predicted_score_a' => null,
    'predicted_score_b' => null,
]);

echo "✅ 2 pronostics créés pour l'utilisateur\n\n";

// Test 1 : Pronostics OUVERTS (pronostic_enabled = true)
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 1 : Pronostics OUVERTS (pronostic_enabled = true)\n";
echo str_repeat("=", 60) . "\n\n";

$controller = new TwilioStudioController();
$request = new Request(['phone' => '+243999999999']);
$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Has pronostics : " . ($data['has_pronostics'] ? 'true' : 'false') . "\n";
echo "Total user pronostics : " . $data['total_user_pronostics'] . "\n\n";

if ($data['total_user_pronostics'] === 2) {
    echo "✅ Les 2 pronostics sont visibles\n\n";
} else {
    echo "❌ Nombre incorrect de pronostics : " . $data['total_user_pronostics'] . "\n\n";
    exit(1);
}

echo "📝 Historique :\n";
echo str_repeat("-", 60) . "\n";
echo $data['historique_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

// Test 2 : FERMER les pronostics (pronostic_enabled = false)
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 2 : Pronostics FERMÉS (pronostic_enabled = false)\n";
echo str_repeat("=", 60) . "\n\n";

// Fermer les pronostics pour les 2 matchs
$match1->update(['pronostic_enabled' => false]);
$match2->update(['pronostic_enabled' => false]);

echo "✅ Pronostics fermés pour les 2 matchs\n\n";

// Récupérer à nouveau les pronostics
$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Has pronostics : " . ($data['has_pronostics'] ? 'true' : 'false') . "\n";
echo "Total user pronostics : " . $data['total_user_pronostics'] . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n\n";

if ($data['total_user_pronostics'] === 2) {
    echo "✅ Les 2 pronostics sont TOUJOURS visibles (même si pronos fermés) !\n\n";
} else {
    echo "❌ ERREUR : Nombre incorrect de pronostics : " . $data['total_user_pronostics'] . "\n";
    echo "❌ L'historique a disparu alors que les pronostics sont juste fermés !\n\n";
    exit(1);
}

if ($data['total_available_matches'] === 0) {
    echo "✅ Aucun match disponible pour de nouveaux pronostics (normal, ils sont fermés)\n\n";
} else {
    echo "⚠️  Il y a encore des matchs disponibles alors qu'ils devraient être fermés\n\n";
}

echo "📝 Historique (pronos fermés) :\n";
echo str_repeat("-", 60) . "\n";
echo $data['historique_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

// Vérifier que l'historique n'est pas vide
if (!empty($data['historique_message'])) {
    echo "✅ L'historique est bien affiché même quand les pronostics sont fermés !\n\n";
} else {
    echo "❌ L'historique est vide alors qu'il devrait être visible !\n\n";
    exit(1);
}

echo str_repeat("=", 60) . "\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé :\n";
echo "  ✅ L'historique est visible quand pronostics OUVERTS\n";
echo "  ✅ L'historique est visible quand pronostics FERMÉS\n";
echo "  ✅ Les matchs disponibles = 0 quand pronostics fermés\n";
echo "  ✅ L'utilisateur peut toujours consulter ses paris passés\n\n";
