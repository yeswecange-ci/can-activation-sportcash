<?php

/**
 * Test Direct - Sans HTTP
 * Test la logique de getMatchesFormatted directement
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FootballMatch;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TwilioStudioController;

echo "🧪 TEST DIRECT : Single Match Detection\n";
echo str_repeat("=", 60) . "\n\n";

$controller = new TwilioStudioController();

// ========================================
// Test 1 : Aucun match disponible
// ========================================
echo "📋 TEST 1 : Aucun match disponible\n";
echo str_repeat("-", 60) . "\n";

FootballMatch::query()->update(['pronostic_enabled' => false]);

$request = new Request(['limit' => 5]);
$response = $controller->getMatchesFormatted($request);
$data = $response->getData(true);

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "📝 Message extrait: " . substr($data['message'], 0, 50) . "...\n\n";

if ($data['success'] === true && $data['has_matches'] === false && $data['single_match'] === false) {
    echo "✅ TEST 1 PASSED\n\n";
} else {
    echo "❌ TEST 1 FAILED\n\n";
    exit(1);
}

// ========================================
// Test 2 : Un seul match disponible
// ========================================
echo "📋 TEST 2 : Un seul match disponible\n";
echo str_repeat("-", 60) . "\n";

// Créer ou activer 1 match
$match = FootballMatch::where('match_date', '>', now())->first();

if (!$match) {
    echo "⚠️  Création d'un match de test...\n";
    $match = FootballMatch::create([
        'team_a' => 'Maroc',
        'team_b' => 'Sénégal',
        'match_date' => now()->addDays(2)->setTime(20, 0),
        'location' => 'Stade Mohammed V',
        'status' => 'scheduled',
        'pronostic_enabled' => true,
    ]);
} else {
    FootballMatch::query()->update(['pronostic_enabled' => false]);
    $match->update(['pronostic_enabled' => true]);
}

$request = new Request(['limit' => 5]);
$response = $controller->getMatchesFormatted($request);
$data = $response->getData(true);

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "✅ Count: " . $data['count'] . "\n";

if (isset($data['match'])) {
    echo "✅ Match ID: " . $data['match']['id'] . "\n";
    echo "✅ Team A: " . $data['match']['team_a'] . "\n";
    echo "✅ Team B: " . $data['match']['team_b'] . "\n";
} else {
    echo "❌ Champ 'match' manquant!\n";
}

echo "\n📝 Message complet :\n";
echo str_repeat("-", 60) . "\n";
echo $data['message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

// Vérifications
$checks = [
    'success === true' => $data['success'] === true,
    'has_matches === true' => $data['has_matches'] === true,
    'single_match === true' => $data['single_match'] === true,
    'count === 1' => $data['count'] === 1,
    'match field exists' => isset($data['match']),
    'match.id exists' => isset($data['match']['id']),
    'Message contains 1️⃣' => str_contains($data['message'], '1️⃣'),
    'Message contains 2️⃣' => str_contains($data['message'], '2️⃣'),
    'Message contains 3️⃣' => str_contains($data['message'], '3️⃣'),
    'Message contains "Match nul"' => str_contains($data['message'], 'Match nul') || str_contains($data['message'], 'nul'),
];

$allPassed = true;
foreach ($checks as $label => $result) {
    echo ($result ? "✅" : "❌") . " $label\n";
    if (!$result) $allPassed = false;
}

if ($allPassed) {
    echo "\n✅ TEST 2 PASSED\n\n";
} else {
    echo "\n❌ TEST 2 FAILED\n\n";
    exit(1);
}

// ========================================
// Test 3 : Plusieurs matchs (3)
// ========================================
echo "📋 TEST 3 : Plusieurs matchs disponibles (3 matchs)\n";
echo str_repeat("-", 60) . "\n";

// Créer 3 matchs
FootballMatch::query()->update(['pronostic_enabled' => false]);

$teams = [
    ['Maroc', 'Sénégal'],
    ['Côte d\'Ivoire', 'Nigeria'],
    ['Cameroun', 'Ghana'],
];

foreach ($teams as $index => $team) {
    $existingMatch = FootballMatch::where('team_a', $team[0])
        ->where('team_b', $team[1])
        ->where('match_date', '>', now())
        ->first();

    if ($existingMatch) {
        $existingMatch->update(['pronostic_enabled' => true]);
    } else {
        FootballMatch::create([
            'team_a' => $team[0],
            'team_b' => $team[1],
            'match_date' => now()->addDays($index + 1)->setTime(20, 0),
            'location' => 'Stade',
            'status' => 'scheduled',
            'pronostic_enabled' => true,
        ]);
    }
}

$request = new Request(['limit' => 5]);
$response = $controller->getMatchesFormatted($request);
$data = $response->getData(true);

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "✅ Count: " . $data['count'] . "\n";
echo "✅ Matches array count: " . count($data['matches']) . "\n\n";

echo "📝 Message complet :\n";
echo str_repeat("-", 60) . "\n";
echo $data['message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

// Vérifications
$checks = [
    'success === true' => $data['success'] === true,
    'has_matches === true' => $data['has_matches'] === true,
    'single_match === false' => $data['single_match'] === false,
    'count >= 3' => $data['count'] >= 3,
    'matches count >= 3' => count($data['matches']) >= 3,
    'Message contains "1."' => str_contains($data['message'], '1.'),
    'Message contains "2."' => str_contains($data['message'], '2.'),
    'Message contains 🆚' => str_contains($data['message'], '🆚'),
    'Message contains 📅' => str_contains($data['message'], '📅'),
    'Message contains instruction' => str_contains($data['message'], 'numéro') || str_contains($data['message'], 'Envoie'),
];

$allPassed = true;
foreach ($checks as $label => $result) {
    echo ($result ? "✅" : "❌") . " $label\n";
    if (!$result) $allPassed = false;
}

if ($allPassed) {
    echo "\n✅ TEST 3 PASSED\n\n";
} else {
    echo "\n❌ TEST 3 FAILED\n\n";
    exit(1);
}

// ========================================
// Résumé Final
// ========================================
echo str_repeat("=", 60) . "\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé :\n";
echo "  ✅ Test 1 : Aucun match (has_matches=false, single_match=false)\n";
echo "  ✅ Test 2 : Un seul match (single_match=true, message avec 1/2/3)\n";
echo "  ✅ Test 3 : Plusieurs matchs (single_match=false, liste numérotée)\n\n";

echo "🚀 Prochaines étapes :\n";
echo "  1. Importer twilio_flow_optimized.json dans Twilio Studio\n";
echo "  2. Tester le flow complet via WhatsApp\n";
echo "  3. Vérifier les 3 scénarios (new, existing, reactivated)\n";
echo "  4. Publier le flow en production\n\n";

echo "📄 Documentation : FLOW_OPTIMIZED_README.md\n";
echo "📄 Flow JSON : twilio_flow_optimized.json\n\n";
