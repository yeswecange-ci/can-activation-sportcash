<?php

/**
 * Script de Test - Single Match Detection
 * Test l'endpoint /api/can/matches/formatted avec différents scénarios
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use Illuminate\Support\Facades\Http;

echo "🧪 TEST : Single Match Detection\n";
echo str_repeat("=", 60) . "\n\n";

// URL de base
$baseUrl = env('APP_URL', 'http://localhost:8000');
$endpoint = "$baseUrl/api/can/matches/formatted?limit=5";

echo "📡 Endpoint testé : $endpoint\n\n";

// ========================================
// Test 1 : Aucun match disponible
// ========================================
echo "📋 TEST 1 : Aucun match disponible\n";
echo str_repeat("-", 60) . "\n";

// Désactiver tous les matchs
FootballMatch::query()->update(['pronostic_enabled' => false]);

$response = Http::get($endpoint);
$data = $response->json();

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "📝 Message: \n" . $data['message'] . "\n\n";

assert($data['success'] === true, "❌ FAIL: success devrait être true");
assert($data['has_matches'] === false, "❌ FAIL: has_matches devrait être false");
assert($data['single_match'] === false, "❌ FAIL: single_match devrait être false");

echo "✅ TEST 1 PASSED\n\n";

// ========================================
// Test 2 : Un seul match disponible
// ========================================
echo "📋 TEST 2 : Un seul match disponible\n";
echo str_repeat("-", 60) . "\n";

// Activer 1 seul match (le plus récent)
$match = FootballMatch::where('match_date', '>', now())
    ->orderBy('match_date', 'asc')
    ->first();

if (!$match) {
    echo "⚠️  Aucun match futur trouvé. Création d'un match de test...\n";
    $match = FootballMatch::create([
        'team_a' => 'Maroc',
        'team_b' => 'Sénégal',
        'match_date' => now()->addDays(2),
        'status' => 'scheduled',
        'pronostic_enabled' => true,
    ]);
} else {
    $match->update(['pronostic_enabled' => true]);
}

$response = Http::get($endpoint);
$data = $response->json();

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "✅ Count: " . $data['count'] . "\n";
echo "✅ Match ID: " . ($data['match']['id'] ?? 'N/A') . "\n";
echo "✅ Team A: " . ($data['match']['team_a'] ?? 'N/A') . "\n";
echo "✅ Team B: " . ($data['match']['team_b'] ?? 'N/A') . "\n";
echo "📝 Message: \n" . $data['message'] . "\n\n";

assert($data['success'] === true, "❌ FAIL: success devrait être true");
assert($data['has_matches'] === true, "❌ FAIL: has_matches devrait être true");
assert($data['single_match'] === true, "❌ FAIL: single_match devrait être true");
assert($data['count'] === 1, "❌ FAIL: count devrait être 1");
assert(isset($data['match']), "❌ FAIL: le champ 'match' devrait exister");
assert(isset($data['match']['id']), "❌ FAIL: match.id devrait exister");
assert(str_contains($data['message'], '1️⃣'), "❌ FAIL: le message devrait contenir les options 1/2/3");
assert(str_contains($data['message'], '2️⃣'), "❌ FAIL: le message devrait contenir les options 1/2/3");
assert(str_contains($data['message'], '3️⃣'), "❌ FAIL: le message devrait contenir les options 1/2/3");

echo "✅ TEST 2 PASSED\n\n";

// ========================================
// Test 3 : Plusieurs matchs disponibles
// ========================================
echo "📋 TEST 3 : Plusieurs matchs disponibles (3 matchs)\n";
echo str_repeat("-", 60) . "\n";

// Créer ou activer 3 matchs
$matches = FootballMatch::where('match_date', '>', now())
    ->orderBy('match_date', 'asc')
    ->take(3)
    ->get();

if ($matches->count() < 3) {
    echo "⚠️  Moins de 3 matchs futurs. Création de matchs de test...\n";

    $teams = [
        ['Maroc', 'Sénégal'],
        ['Côte d\'Ivoire', 'Nigeria'],
        ['Cameroun', 'Ghana'],
    ];

    foreach ($teams as $index => $team) {
        FootballMatch::create([
            'team_a' => $team[0],
            'team_b' => $team[1],
            'match_date' => now()->addDays($index + 1),
            'status' => 'scheduled',
            'pronostic_enabled' => true,
        ]);
    }
} else {
    $matches->each(fn($m) => $m->update(['pronostic_enabled' => true]));
}

$response = Http::get($endpoint);
$data = $response->json();

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "✅ Has matches: " . ($data['has_matches'] ? 'true' : 'false') . "\n";
echo "✅ Single match: " . ($data['single_match'] ? 'true' : 'false') . "\n";
echo "✅ Count: " . $data['count'] . "\n";
echo "✅ Matches count: " . count($data['matches']) . "\n";
echo "📝 Message: \n" . $data['message'] . "\n\n";

assert($data['success'] === true, "❌ FAIL: success devrait être true");
assert($data['has_matches'] === true, "❌ FAIL: has_matches devrait être true");
assert($data['single_match'] === false, "❌ FAIL: single_match devrait être false");
assert($data['count'] >= 3, "❌ FAIL: count devrait être >= 3");
assert(count($data['matches']) >= 3, "❌ FAIL: matches devrait contenir >= 3 items");
assert(str_contains($data['message'], '1.'), "❌ FAIL: le message devrait contenir une liste numérotée");
assert(str_contains($data['message'], '🆚'), "❌ FAIL: le message devrait contenir 🆚");
assert(str_contains($data['message'], '💡'), "❌ FAIL: le message devrait contenir l'instruction");

echo "✅ TEST 3 PASSED\n\n";

// ========================================
// Test 4 : Vérifier format du message (1 match)
// ========================================
echo "📋 TEST 4 : Vérifier format message (1 match)\n";
echo str_repeat("-", 60) . "\n";

// Garder seulement 1 match
FootballMatch::query()->update(['pronostic_enabled' => false]);
$match = FootballMatch::where('match_date', '>', now())->first();
$match->update(['pronostic_enabled' => true]);

$response = Http::get($endpoint);
$data = $response->json();

$message = $data['message'];

echo "Vérifications du format :\n";

$checks = [
    '⚽ *MATCH DISPONIBLE*' => str_contains($message, '⚽'),
    'Noms des équipes' => str_contains($message, 'vs'),
    'Date et heure' => str_contains($message, '📅'),
    'Option 1️⃣' => str_contains($message, '1️⃣'),
    'Option 2️⃣' => str_contains($message, '2️⃣'),
    'Option 3️⃣ (nul)' => str_contains($message, '3️⃣'),
    'Match nul' => str_contains($message, 'Match nul') || str_contains($message, 'nul'),
    'Instruction' => str_contains($message, 'Réponds'),
];

foreach ($checks as $label => $result) {
    echo ($result ? "✅" : "❌") . " $label\n";
    assert($result, "❌ FAIL: $label");
}

echo "\n✅ TEST 4 PASSED\n\n";

// ========================================
// Test 5 : Vérifier format du message (plusieurs matchs)
// ========================================
echo "📋 TEST 5 : Vérifier format message (plusieurs matchs)\n";
echo str_repeat("-", 60) . "\n";

// Activer 3 matchs
FootballMatch::where('match_date', '>', now())
    ->orderBy('match_date', 'asc')
    ->take(3)
    ->get()
    ->each(fn($m) => $m->update(['pronostic_enabled' => true]));

$response = Http::get($endpoint);
$data = $response->json();

$message = $data['message'];

echo "Vérifications du format :\n";

$checks = [
    'Titre PROCHAINS MATCHS' => str_contains($message, 'PROCHAINS MATCHS'),
    'Liste numérotée (1.)' => str_contains($message, '1.'),
    'Liste numérotée (2.)' => str_contains($message, '2.'),
    'Symbole 🆚' => str_contains($message, '🆚'),
    'Date 📅' => str_contains($message, '📅'),
    'Status pronostic' => str_contains($message, '✅') || str_contains($message, '🔒'),
    'Instruction numéro' => str_contains($message, 'numéro') || str_contains($message, 'Envoie'),
];

foreach ($checks as $label => $result) {
    echo ($result ? "✅" : "❌") . " $label\n";
    assert($result, "❌ FAIL: $label");
}

echo "\n✅ TEST 5 PASSED\n\n";

// ========================================
// Résumé
// ========================================
echo str_repeat("=", 60) . "\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé des tests :\n";
echo "  ✅ Test 1 : Aucun match\n";
echo "  ✅ Test 2 : Un seul match (single_match = true)\n";
echo "  ✅ Test 3 : Plusieurs matchs (single_match = false)\n";
echo "  ✅ Test 4 : Format message 1 match\n";
echo "  ✅ Test 5 : Format message plusieurs matchs\n\n";

echo "✅ L'endpoint est prêt pour le flow Twilio optimisé !\n\n";

echo "🔗 Prochaines étapes :\n";
echo "  1. Importer twilio_flow_optimized.json dans Twilio Studio\n";
echo "  2. Tester le flow complet via WhatsApp\n";
echo "  3. Publier le flow en production\n\n";
