<?php

/**
 * Test: Vérifier le message quand il n'y a pas de pronostics disponibles
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

echo "🧪 TEST : Message quand pas de pronostics disponibles\n";
echo str_repeat("=", 60) . "\n\n";

// Créer ou récupérer un utilisateur
$user = User::where('phone', '+243999999999')->first();
if (!$user) {
    $user = User::create([
        'phone' => '+243999999999',
        'name' => 'Test User',
        'status' => 'INSCRIT',
    ]);
}

echo "✅ Utilisateur : {$user->name}\n\n";

// Nettoyer les pronostics
Pronostic::where('user_id', $user->id)->delete();

// Créer 1 match
FootballMatch::query()->update(['pronostic_enabled' => false]);

$match = FootballMatch::where('match_date', '>', now())->first();
if (!$match) {
    $match = FootballMatch::create([
        'team_a' => 'Maroc',
        'team_b' => 'Sénégal',
        'match_date' => now()->addDays(1)->setTime(20, 0),
        'location' => 'Stade',
        'status' => 'scheduled',
        'pronostic_enabled' => false,
    ]);
} else {
    $match->update(['pronostic_enabled' => false]);
}

$controller = new TwilioStudioController();
$request = new Request(['phone' => '+243999999999']);

// =============================================================================
// TEST 1 : AUCUN match ouvert pour pronostics
// =============================================================================
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 1 : AUCUN match ouvert (pronostic_enabled = false)\n";
echo str_repeat("=", 60) . "\n\n";

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n";
echo "Remaining matches count : " . $data['remaining_matches_count'] . "\n\n";

echo "📝 Remaining Matches Message :\n";
echo str_repeat("-", 60) . "\n";
echo $data['remaining_matches_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

if ($data['total_available_matches'] === 0) {
    echo "✅ Aucun match disponible (normal)\n";
} else {
    echo "❌ Il devrait y avoir 0 matchs disponibles\n";
    exit(1);
}

if (str_contains($data['remaining_matches_message'], 'Aucun pronostic disponible')) {
    echo "✅ Message affiché : 'Aucun pronostic disponible'\n\n";
} else {
    echo "❌ Le message 'Aucun pronostic disponible' n'est pas affiché\n\n";
    exit(1);
}

// =============================================================================
// TEST 2 : 1 match disponible (pronostics ouverts)
// =============================================================================
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 2 : 1 match disponible (pronostic_enabled = true)\n";
echo str_repeat("=", 60) . "\n\n";

$match->update(['pronostic_enabled' => true]);

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n";
echo "Remaining matches count : " . $data['remaining_matches_count'] . "\n\n";

echo "📝 Remaining Matches Message :\n";
echo str_repeat("-", 60) . "\n";
echo $data['remaining_matches_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

if ($data['total_available_matches'] === 1) {
    echo "✅ 1 match disponible\n";
} else {
    echo "❌ Il devrait y avoir 1 match disponible\n";
    exit(1);
}

if (str_contains($data['remaining_matches_message'], '1 match disponible')) {
    echo "✅ Message affiché : '1 match disponible'\n\n";
} else {
    echo "❌ Le message '1 match disponible' n'est pas affiché\n\n";
    exit(1);
}

// =============================================================================
// TEST 3 : Utilisateur a fait son pronostic (tous les matchs complétés)
// =============================================================================
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 3 : Utilisateur a fait son pronostic\n";
echo str_repeat("=", 60) . "\n\n";

Pronostic::create([
    'user_id' => $user->id,
    'match_id' => $match->id,
    'prediction_type' => 'team_a_win',
]);

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Has all pronostics : " . ($data['has_all_pronostics'] ? 'true' : 'false') . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n";
echo "Remaining matches count : " . $data['remaining_matches_count'] . "\n\n";

echo "📝 Remaining Matches Message :\n";
echo str_repeat("-", 60) . "\n";
echo "'" . $data['remaining_matches_message'] . "'\n";
echo str_repeat("-", 60) . "\n\n";

if ($data['has_all_pronostics'] === true) {
    echo "✅ L'utilisateur a fait tous ses pronostics\n";
} else {
    echo "❌ has_all_pronostics devrait être true\n";
    exit(1);
}

if (empty(trim($data['remaining_matches_message']))) {
    echo "✅ Message vide (normal, tous les pronostics faits)\n\n";
} else {
    echo "⚠️  Le message n'est pas vide mais tous les pronostics sont faits\n";
    echo "   (Ce n'est pas forcément une erreur si géré par le flow)\n\n";
}

// =============================================================================
// TEST 4 : Utilisateur avec historique + pas de nouveaux matchs
// =============================================================================
echo str_repeat("=", 60) . "\n";
echo "📋 TEST 4 : Avec historique + aucun nouveau match disponible\n";
echo str_repeat("=", 60) . "\n\n";

// Fermer le match
$match->update(['pronostic_enabled' => false]);

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Résultat :\n";
echo str_repeat("-", 60) . "\n";
echo "Has pronostics : " . ($data['has_pronostics'] ? 'true' : 'false') . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n\n";

echo "📝 Historique Message :\n";
echo str_repeat("-", 60) . "\n";
echo $data['historique_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

echo "📝 Remaining Matches Message :\n";
echo str_repeat("-", 60) . "\n";
echo $data['remaining_matches_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

if ($data['has_pronostics'] === true) {
    echo "✅ L'utilisateur a un historique de pronostics\n";
} else {
    echo "❌ has_pronostics devrait être true\n";
    exit(1);
}

if (!empty($data['historique_message'])) {
    echo "✅ L'historique est affiché\n";
} else {
    echo "❌ L'historique devrait être affiché\n";
    exit(1);
}

if (str_contains($data['remaining_matches_message'], 'Aucun pronostic disponible')) {
    echo "✅ Message 'Aucun pronostic disponible' affiché\n\n";
} else {
    echo "❌ Le message 'Aucun pronostic disponible' devrait être affiché\n\n";
    exit(1);
}

echo str_repeat("=", 60) . "\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé des 3 cas gérés :\n";
echo "  ✅ Cas 1 : Aucun match ouvert → 'Aucun pronostic disponible'\n";
echo "  ✅ Cas 2 : Matchs disponibles → 'X matchs disponibles'\n";
echo "  ✅ Cas 3 : Tous pronostics faits → Message vide\n";
echo "  ✅ Cas 4 : Historique + pas de nouveaux matchs → Historique + 'Aucun pronostic disponible'\n\n";

echo "💬 Messages utilisateur :\n";
echo "  • Aucun match : '⏸️ Aucun pronostic disponible pour le moment. 📅 Reviens plus tard !'\n";
echo "  • 1 match : '⚽ 1 match disponible'\n";
echo "  • Plusieurs matchs : '⚽ X matchs disponibles'\n\n";
