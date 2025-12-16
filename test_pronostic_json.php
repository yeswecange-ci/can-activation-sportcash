<?php

/**
 * Script de test pour vérifier que l'API pronostic accepte du JSON
 *
 * Usage: php test_pronostic_json.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test de l'API Pronostic avec JSON\n";
echo "====================================\n\n";

// 1. Récupérer un utilisateur actif
$user = \App\Models\User::where('is_active', true)->first();

if (!$user) {
    echo "❌ Erreur: Aucun utilisateur actif trouvé\n";
    echo "💡 Créez d'abord un utilisateur via le flow d'inscription\n";
    exit(1);
}

echo "✅ Utilisateur trouvé:\n";
echo "   - ID: {$user->id}\n";
echo "   - Nom: {$user->name}\n";
echo "   - Téléphone: {$user->phone}\n\n";

// 2. Récupérer un match disponible
$match = \App\Models\FootballMatch::where('pronostic_enabled', true)
    ->where('status', 'scheduled')
    ->first();

if (!$match) {
    echo "❌ Erreur: Aucun match disponible\n";
    echo "💡 Créez d'abord un match avec pronostic_enabled=true et status=scheduled\n";
    exit(1);
}

echo "✅ Match trouvé:\n";
echo "   - ID: {$match->id}\n";
echo "   - Équipes: {$match->team_a} vs {$match->team_b}\n";
echo "   - Date: {$match->match_date}\n\n";

// 3. Préparer la requête JSON
$jsonPayload = json_encode([
    'phone' => $user->phone,
    'match_id' => $match->id,
    'prediction_type' => 'team_a_win'
]);

echo "📤 Payload JSON:\n";
echo $jsonPayload . "\n\n";

// 4. Simuler une requête HTTP avec JSON
$url = 'http://localhost/api/can/pronostic';

// Option 1: Utiliser cURL
echo "🔄 Envoi de la requête à l'API...\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📥 Réponse de l'API:\n";
echo "   - Code HTTP: {$httpCode}\n";
echo "   - Réponse: {$response}\n\n";

// 5. Vérifier le résultat
$responseData = json_decode($response, true);

if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
    echo "✅ TEST RÉUSSI!\n";
    echo "   L'API accepte bien le JSON et a enregistré le pronostic.\n\n";

    if (isset($responseData['pronostic'])) {
        echo "📊 Détails du pronostic:\n";
        echo "   - ID: {$responseData['pronostic']['id']}\n";
        echo "   - Match: {$responseData['pronostic']['match']}\n";
        echo "   - Type: {$responseData['pronostic']['prediction_type']}\n";
        echo "   - Texte: {$responseData['pronostic']['prediction_text']}\n";
    }
} else {
    echo "❌ TEST ÉCHOUÉ!\n";
    echo "   L'API n'a pas réussi à traiter la requête JSON.\n";
    if (isset($responseData['message'])) {
        echo "   Message: {$responseData['message']}\n";
    }
}

echo "\n====================================\n";
echo "📝 Pour intégrer avec Twilio Studio:\n";
echo "   1. Utilisez un bloc 'Make HTTP Request'\n";
echo "   2. Configurez:\n";
echo "      - URL: {$url}\n";
echo "      - Method: POST\n";
echo "      - Content-Type: application/json\n";
echo "   3. Body (JSON):\n";
echo "      {\n";
echo "          \"phone\": \"{{trigger.message.From}}\",\n";
echo "          \"match_id\": \"{{widgets.match_choice.parsed.match_id}}\",\n";
echo "          \"prediction_type\": \"{{widgets.prediction_choice.parsed.prediction}}\"\n";
echo "      }\n";
echo "====================================\n";
