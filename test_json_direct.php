<?php

/**
 * Script de test direct pour vérifier que l'API pronostic accepte du JSON
 * Ce test appelle directement le controller sans passer par HTTP
 *
 * Usage: php test_json_direct.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🧪 Test Direct de l'API Pronostic avec JSON\n";
echo "==========================================\n\n";

// 1. Récupérer un utilisateur actif
$user = \App\Models\User::where('is_active', true)->first();

if (!$user) {
    echo "❌ Erreur: Aucun utilisateur actif trouvé\n";
    exit(1);
}

echo "✅ Utilisateur: {$user->name} ({$user->phone})\n";

// 2. Récupérer un match disponible
$match = \App\Models\FootballMatch::where('pronostic_enabled', true)
    ->where('status', 'scheduled')
    ->first();

if (!$match) {
    echo "❌ Erreur: Aucun match disponible\n";
    exit(1);
}

echo "✅ Match: {$match->team_a} vs {$match->team_b}\n\n";

// 3. Créer une requête avec du JSON
$jsonData = [
    'phone' => $user->phone,
    'match_id' => $match->id,
    'prediction_type' => 'team_a_win'
];

echo "📤 Données JSON:\n";
echo json_encode($jsonData, JSON_PRETTY_PRINT) . "\n\n";

// 4. Simuler une requête HTTP avec JSON
$request = \Illuminate\Http\Request::create(
    '/api/can/pronostic',
    'POST',
    [],  // query params
    [],  // cookies
    [],  // files
    [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json'
    ],
    json_encode($jsonData)  // content
);

// 5. Appeler le controller
$controller = new \App\Http\Controllers\Api\TwilioStudioController();

try {
    $response = $controller->savePronostic($request);
    $responseData = json_decode($response->getContent(), true);
    $statusCode = $response->getStatusCode();

    echo "📥 Réponse de l'API:\n";
    echo "   - Code HTTP: {$statusCode}\n";
    echo "   - Données: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";

    if ($statusCode === 200 && isset($responseData['success']) && $responseData['success']) {
        echo "✅ TEST RÉUSSI!\n";
        echo "   L'API accepte bien le JSON et a enregistré le pronostic.\n\n";

        if (isset($responseData['pronostic'])) {
            echo "📊 Détails du pronostic:\n";
            echo "   - ID: {$responseData['pronostic']['id']}\n";
            echo "   - Match: {$responseData['pronostic']['match']}\n";
            echo "   - Type: {$responseData['pronostic']['prediction_type']}\n";
            echo "   - Texte: {$responseData['pronostic']['prediction_text']}\n\n";
        }

        echo "✅ L'intégration avec Twilio Studio est prête!\n";
    } else {
        echo "❌ TEST ÉCHOUÉ!\n";
        echo "   Message: " . ($responseData['message'] ?? 'Erreur inconnue') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERREUR:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n==========================================\n";
echo "📝 Configuration Twilio Studio:\n";
echo "==========================================\n";
echo "Bloc: Make HTTP Request\n";
echo "URL: https://votre-domaine.com/api/can/pronostic\n";
echo "Method: POST\n";
echo "Content-Type: application/json\n\n";
echo "Body:\n";
echo "{\n";
echo "  \"phone\": \"{{trigger.message.From}}\",\n";
echo "  \"match_id\": 1,\n";
echo "  \"prediction_type\": \"team_a_win\"\n";
echo "}\n\n";
echo "Les valeurs possibles pour prediction_type sont:\n";
echo "  - team_a_win (victoire équipe A)\n";
echo "  - team_b_win (victoire équipe B)\n";
echo "  - draw (match nul)\n";
echo "==========================================\n";
