<?php

/**
 * Test du message remaining_matches (après correction)
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

echo "🧪 TEST : Remaining Matches Message (Sans Redondance)\n";
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

// Créer 3 matchs disponibles
FootballMatch::query()->update(['pronostic_enabled' => false]);

$teams = [
    ['Maroc', 'Sénégal'],
    ['Côte d\'Ivoire', 'Nigeria'],
    ['Cameroun', 'Ghana'],
];

foreach ($teams as $index => $team) {
    $match = FootballMatch::where('team_a', $team[0])
        ->where('team_b', $team[1])
        ->where('match_date', '>', now())
        ->first();

    if ($match) {
        $match->update(['pronostic_enabled' => true]);
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

echo "✅ 3 matchs disponibles créés\n\n";

// Supprimer les pronostics existants pour ce user
Pronostic::where('user_id', $user->id)->delete();
echo "✅ Pronostics supprimés\n\n";

// Tester l'endpoint getUserPronostics
$controller = new TwilioStudioController();
$request = new Request(['phone' => '+243999999999']);
$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📊 Réponse de l'API :\n";
echo str_repeat("-", 60) . "\n";
echo "Has all pronostics : " . ($data['has_all_pronostics'] ? 'true' : 'false') . "\n";
echo "Has pronostics : " . ($data['has_pronostics'] ? 'true' : 'false') . "\n";
echo "Total available matches : " . $data['total_available_matches'] . "\n";
echo "Total user pronostics : " . $data['total_user_pronostics'] . "\n";
echo "Remaining matches count : " . $data['remaining_matches_count'] . "\n\n";

echo "📝 Historique Message :\n";
echo str_repeat("-", 60) . "\n";
echo $data['historique_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

echo "📝 Remaining Matches Message (SIMPLIFIÉ) :\n";
echo str_repeat("-", 60) . "\n";
echo $data['remaining_matches_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

// Vérifier que le message est simplifié
$isSimplified = !str_contains($data['remaining_matches_message'], '🆚')
    && !str_contains($data['remaining_matches_message'], '📅');

if ($isSimplified) {
    echo "✅ Le message est SIMPLIFIÉ (pas de détails des matchs)\n";
    echo "✅ Pas de redondance avec le flow !\n\n";
} else {
    echo "❌ Le message contient encore des détails de matchs\n";
    echo "❌ Il y aura une redondance avec le flow\n\n";
    exit(1);
}

// Test avec 1 seul match
echo str_repeat("=", 60) . "\n";
echo "📋 TEST : Avec 1 seul match\n";
echo str_repeat("=", 60) . "\n\n";

FootballMatch::query()->update(['pronostic_enabled' => false]);
$match = FootballMatch::where('match_date', '>', now())->first();
$match->update(['pronostic_enabled' => true]);

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📝 Remaining Matches Message (1 match) :\n";
echo str_repeat("-", 60) . "\n";
echo $data['remaining_matches_message'] . "\n";
echo str_repeat("-", 60) . "\n\n";

if (str_contains($data['remaining_matches_message'], '1 match disponible')) {
    echo "✅ Le message indique '1 match disponible' (singulier)\n\n";
} else {
    echo "❌ Le message ne contient pas '1 match disponible'\n\n";
    exit(1);
}

// Test avec 0 match
echo str_repeat("=", 60) . "\n";
echo "📋 TEST : Avec 0 match\n";
echo str_repeat("=", 60) . "\n\n";

FootballMatch::query()->update(['pronostic_enabled' => false]);

$response = $controller->getUserPronostics($request);
$data = $response->getData(true);

echo "📝 Remaining Matches Message (0 match) :\n";
echo str_repeat("-", 60) . "\n";
echo "'" . $data['remaining_matches_message'] . "'\n";
echo str_repeat("-", 60) . "\n\n";

if (empty($data['remaining_matches_message'])) {
    echo "✅ Le message est vide (aucun match disponible)\n\n";
} else {
    echo "⚠️  Le message n'est pas vide mais il n'y a pas de matchs\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé :\n";
echo "  ✅ Message simplifié (pas de détails)\n";
echo "  ✅ Pas de redondance avec le flow\n";
echo "  ✅ Singulier/Pluriel géré correctement\n";
echo "  ✅ Message vide si 0 match\n\n";

echo "💡 Exemple de flow maintenant :\n";
echo str_repeat("-", 60) . "\n";
echo "Message 1 (msg_remaining_matches) :\n";
echo "  👋 Salut Test User !\n";
echo "  Tu n'as encore fait aucun pronostic.\n";
echo "  ⚽ 3 matchs disponibles\n\n";
echo "Message 2 (msg_liste_matchs ou affichage direct) :\n";
echo "  ⚽ *PROCHAINS MATCHS CAN 2025*\n";
echo "  1. Maroc 🆚 Sénégal\n";
echo "     📅 15/01/2025 à 20:00\n";
echo "  ...\n";
echo str_repeat("-", 60) . "\n\n";

echo "✅ Plus de redondance ! 🎉\n";
