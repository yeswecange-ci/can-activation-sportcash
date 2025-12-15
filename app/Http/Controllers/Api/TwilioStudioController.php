<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationSession;
use App\Models\FootballMatch;
use App\Models\Partner;
use App\Models\Prize;
use App\Models\Pronostic;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioStudioController extends Controller
{
    /**
     * Endpoint: POST /api/can/scan
     * Log initial du scan QR code ou contact direct
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'phone'         => 'required|string',
            'source_type'   => 'required|string',
            'source_detail' => 'required|string',
            'timestamp'     => 'nullable|string',
            'status'        => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        // Créer ou mettre à jour la session de conversation
        $session = ConversationSession::updateOrCreate(
            ['phone' => $phone],
            [
                'state'         => ConversationSession::STATE_SCAN,
                'data'          => [
                    'source_type'    => $validated['source_type'],
                    'source_detail'  => $validated['source_detail'],
                    'scan_timestamp' => $validated['timestamp'] ?? now()->toDateTimeString(),
                ],
                'last_activity' => now(),
            ]
        );

        Log::info('Twilio Studio - Scan logged', [
            'phone'  => $phone,
            'source' => $validated['source_type'] . ' / ' . $validated['source_detail'],
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Scan logged successfully',
            'session_id' => $session->id,
        ]);
    }

    /**
     * Endpoint: POST /api/can/optin
     * Log de l'opt-in (réponse OUI)
     */
    public function optin(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        $session = ConversationSession::where('phone', $phone)->first();

        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_OPT_IN,
                'last_activity' => now(),
            ]);
        }

        Log::info('Twilio Studio - Opt-in confirmed', ['phone' => $phone]);

        return response()->json([
            'success' => true,
            'message' => 'Opt-in logged successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/inscription
     * Inscription finale avec nom et création de l'utilisateur
     */
    public function inscription(Request $request)
    {
        $validated = $request->validate([
            'phone'         => 'required|string',
            'name'          => 'required|string|min:2',
            'source_type'   => 'required|string',
            'source_detail' => 'required|string',
            'status'        => 'nullable|string',
            'timestamp'     => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        // Vérifier si l'utilisateur existe déjà
        $user = User::where('phone', $phone)->first();

        if ($user) {
            // Utilisateur déjà inscrit - mise à jour
            $user->update([
                'name'                => ucwords(strtolower($validated['name'])),
                'source_type'         => $validated['source_type'],
                'source_detail'       => $validated['source_detail'],
                'registration_status' => 'INSCRIT',
                'opted_in_at'         => now(),
                'is_active'           => true,
            ]);

            Log::info('Twilio Studio - User updated', [
                'user_id' => $user->id,
                'phone'   => $phone,
            ]);
        } else {
            // Nouvel utilisateur - extraire le village depuis la source
            $villageId = $this->extractVillageFromSource($validated['source_type'], $validated['source_detail']);

            if (! $villageId) {
                // Si pas de village trouvé, utiliser le premier village actif
                $defaultVillage = Village::where('is_active', true)->first();
                $villageId      = $defaultVillage ? $defaultVillage->id : null;
            }

            if (! $villageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active village available',
                ], 400);
            }

            $user = User::create([
                'name'                => ucwords(strtolower($validated['name'])),
                'phone'               => $phone,
                'village_id'          => $villageId,
                'source_type'         => $validated['source_type'],
                'source_detail'       => $validated['source_detail'],
                'scan_timestamp'      => $validated['timestamp'] ?? now(),
                'registration_status' => 'INSCRIT',
                'opted_in_at'         => now(),
                'is_active'           => true,
            ]);

            Log::info('Twilio Studio - New user registered', [
                'user_id'    => $user->id,
                'phone'      => $phone,
                'village_id' => $villageId,
                'source'     => $validated['source_type'] . ' / ' . $validated['source_detail'],
            ]);
        }

        // Mettre à jour la session
        $session = ConversationSession::where('phone', $phone)->first();
        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_REGISTERED,
                'user_id'       => $user->id,
                'last_activity' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user_id' => $user->id,
            'name'    => $user->name,
        ]);
    }

    /**
     * Endpoint: POST /api/can/refus
     * Log du refus d'opt-in
     */
    public function refus(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        $session = ConversationSession::where('phone', $phone)->first();
        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_REFUS,
                'last_activity' => now(),
            ]);
        }

        Log::info('Twilio Studio - Opt-in refused', ['phone' => $phone]);

        return response()->json([
            'success' => true,
            'message' => 'Refusal logged successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/stop
     * Désinscription (STOP)
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        // Désactiver l'utilisateur s'il existe
        $user = User::where('phone', $phone)->first();
        if ($user) {
            $user->update([
                'is_active'           => false,
                'registration_status' => 'STOP',
            ]);
        }

        $session = ConversationSession::where('phone', $phone)->first();
        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_STOP,
                'last_activity' => now(),
            ]);
        }

        Log::info('Twilio Studio - User stopped', ['phone' => $phone]);

        return response()->json([
            'success' => true,
            'message' => 'User unsubscribed successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/abandon
     * Abandon du processus d'inscription
     */
    public function abandon(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        $session = ConversationSession::where('phone', $phone)->first();
        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_ABANDON,
                'last_activity' => now(),
            ]);
        }

        Log::info('Twilio Studio - Registration abandoned', ['phone' => $phone]);

        return response()->json([
            'success' => true,
            'message' => 'Abandonment logged successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/timeout
     * Timeout pendant le processus
     */
    public function timeout(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        $session = ConversationSession::where('phone', $phone)->first();
        if ($session) {
            $session->update([
                'state'         => ConversationSession::STATE_TIMEOUT,
                'last_activity' => now(),
            ]);
        }

        Log::info('Twilio Studio - Timeout', [
            'phone'  => $phone,
            'status' => $validated['status'] ?? 'UNKNOWN',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timeout logged successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/error
     * Erreur de livraison ou autre
     */
    public function error(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        Log::error('Twilio Studio - Delivery error', [
            'phone'  => $phone,
            'status' => $validated['status'] ?? 'DELIVERY_FAILED',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Error logged successfully',
        ]);
    }

    /**
     * Endpoint: POST /api/can/check-user
     * Vérifier si l'utilisateur existe déjà
     */
    // Dans TwilioStudioController.php - checkUser()

    public function checkUser(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);
        $user  = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'status'  => 'NOT_FOUND',
                'message' => 'User not found',
            ]);
        }

        if (! $user->is_active || $user->registration_status === 'STOP') {
            return response()->json([
                'status'  => 'STOP',
                'name'    => $user->name,
                'phone'   => $user->phone,
                'message' => 'User was stopped',
            ]);
        }

        // ✅ IMPORTANT : Retourner exactement "INSCRIT"
        return response()->json([
            'status'  => 'INSCRIT', // Doit être exactement ce mot
            'name'    => $user->name,
            'phone'   => $user->phone,
            'user_id' => $user->id, // Ajouter l'ID pour debug
        ]);
    }

    /**
     * Endpoint: POST /api/can/reactivate
     * Réactiver un utilisateur STOP
     */
    public function reactivate(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);
        $user  = User::where('phone', $phone)->first();

        if ($user) {
            $user->update([
                'is_active'           => true,
                'registration_status' => 'REACTIVATED',
                'opted_in_at'         => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User reactivated successfully',
            'name'    => $user?->name,
        ]);
    }

    /**
     * Endpoint: POST /api/can/log
     * Log générique
     */
    public function log(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string',
            'status'    => 'required|string',
            'timestamp' => 'nullable|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        Log::info('Twilio Studio - Event logged', [
            'phone'  => $phone,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event logged successfully',
        ]);
    }

    /**
     * Endpoint: GET /api/can/villages
     * Récupérer la liste des villages actifs
     */
    public function getVillages(Request $request)
    {
        $villages = Village::where('is_active', true)
            ->withCount('users')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'address', 'capacity']);

        if ($villages->isEmpty()) {
            return response()->json([
                'success'      => true,
                'has_villages' => false,
                'message'      => 'Aucun village disponible pour le moment.',
                'villages'     => [],
            ]);
        }

        $formattedVillages = $villages->map(function ($village, $index) {
            return [
                'id'            => $village->id,
                'number'        => $index + 1,
                'name'          => $village->name,
                'address'       => $village->address,
                'capacity'      => $village->capacity,
                'members_count' => $village->users_count,
            ];
        });

        return response()->json([
            'success'      => true,
            'has_villages' => true,
            'count'        => $villages->count(),
            'villages'     => $formattedVillages,
        ]);
    }

    /**
     * Endpoint: GET /api/can/matches/today
     * Récupérer les matchs du jour
     */
    public function getMatchesToday(Request $request)
    {
        $today    = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $matches = FootballMatch::whereBetween('match_date', [$today, $endOfDay])
            ->where('pronostic_enabled', true)
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('match_date', 'asc')
            ->get(['id', 'team_a', 'team_b', 'match_date', 'status']);

        if ($matches->isEmpty()) {
            return response()->json([
                'success'     => true,
                'has_matches' => false,
                'message'     => 'Aucun match disponible aujourd\'hui.',
                'matches'     => [],
            ]);
        }

        $formattedMatches = $matches->map(function ($match, $index) {
            return [
                'id'         => $match->id,
                'number'     => $index + 1,
                'team_a'     => $match->team_a,
                'team_b'     => $match->team_b,
                'match_time' => $match->match_date->format('H:i'),
                'status'     => $match->status,
            ];
        });

        return response()->json([
            'success'     => true,
            'has_matches' => true,
            'count'       => $matches->count(),
            'matches'     => $formattedMatches,
        ]);
    }

    /**
     * Endpoint: GET /api/can/matches/upcoming
     * Récupérer tous les matchs à venir (prochains 7 jours)
     */
    public function getUpcomingMatches(Request $request)
    {
        $limit = $request->input('limit', 10); // Par défaut 10 matchs
        $days = $request->input('days', 7); // Par défaut 7 jours

        $now = now();
        $endDate = now()->addDays($days);

        $matches = FootballMatch::where('match_date', '>=', $now)
            ->where('match_date', '<=', $endDate)
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('match_date', 'asc')
            ->limit($limit)
            ->get(['id', 'team_a', 'team_b', 'match_date', 'status', 'pronostic_enabled']);

        if ($matches->isEmpty()) {
            return response()->json([
                'success'     => true,
                'has_matches' => false,
                'message'     => 'Aucun match à venir.',
                'matches'     => [],
            ]);
        }

        $formattedMatches = $matches->map(function ($match, $index) {
            return [
                'id'                => $match->id,
                'number'            => $index + 1,
                'team_a'            => $match->team_a,
                'team_b'            => $match->team_b,
                'match_date'        => $match->match_date->format('d/m/Y'),
                'match_time'        => $match->match_date->format('H:i'),
                'status'            => $match->status,
                'pronostic_enabled' => $match->pronostic_enabled,
            ];
        });

        return response()->json([
            'success'     => true,
            'has_matches' => true,
            'count'       => $matches->count(),
            'matches'     => $formattedMatches,
        ]);
    }

    /**
     * Endpoint: GET /api/can/matches/formatted
     * Récupérer la liste des matchs formatée pour WhatsApp (message texte)
     */
    public function getMatchesFormatted(Request $request)
    {
        $limit = $request->input('limit', 5); // Par défaut 5 matchs
        $days = $request->input('days', 30); // Par défaut 30 jours (augmenté de 7 à 30)

        $now = now();
        $endDate = now()->addDays($days);

        $matches = FootballMatch::where('match_date', '>=', $now)
            ->where('match_date', '<=', $endDate)
            ->where('pronostic_enabled', true) // Seulement les matchs avec pronostics activés
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('match_date', 'asc')
            ->limit($limit)
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'success'     => true,
                'has_matches' => false,
                'message'     => "⚽ Aucun match programmé pour le moment.\n\nRevenez bientôt pour découvrir les prochaines rencontres !",
            ]);
        }

        // Construire le message formaté
        $message = "⚽ *PROCHAINS MATCHS CAN 2025*\n\n";

        foreach ($matches as $index => $match) {
            $number = $index + 1;
            $date = $match->match_date->format('d/m/Y');
            $time = $match->match_date->format('H:i');
            $pronoStatus = $match->pronostic_enabled ? '✅' : '🔒';

            $message .= "{$number}. {$match->team_a} 🆚 {$match->team_b}\n";
            $message .= "   📅 {$date} à {$time}\n";
            $message .= "   {$pronoStatus} Pronostics " . ($match->pronostic_enabled ? 'ouverts' : 'fermés') . "\n\n";
        }

        $message .= "💡 Envoie PRONO pour faire ton pronostic !";

        return response()->json([
            'success'     => true,
            'has_matches' => true,
            'count'       => $matches->count(),
            'message'     => $message,
            'matches'     => $matches->map(function ($match, $index) {
                return [
                    'id'                => $match->id,
                    'number'            => $index + 1,
                    'team_a'            => $match->team_a,
                    'team_b'            => $match->team_b,
                    'match_date'        => $match->match_date->format('d/m/Y'),
                    'match_time'        => $match->match_date->format('H:i'),
                    'pronostic_enabled' => $match->pronostic_enabled,
                ];
            }),
        ]);
    }

    /**
     * Endpoint: GET /api/can/matches/{id}
     * Récupérer les détails d'un match spécifique
     */
    public function getMatch(Request $request, $id)
    {
        $match = FootballMatch::find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match non trouvé.',
            ], 404);
        }

        // Vérifier si l'utilisateur a déjà fait un pronostic sur ce match
        $userPronostic = null;
        if ($request->has('phone')) {
            $phone = $this->formatPhone($request->input('phone'));
            $user = User::where('phone', $phone)->first();

            if ($user) {
                $userPronostic = Pronostic::where('user_id', $user->id)
                    ->where('match_id', $match->id)
                    ->first();
            }
        }

        return response()->json([
            'success' => true,
            'match' => [
                'id' => $match->id,
                'team_a' => $match->team_a,
                'team_b' => $match->team_b,
                'match_date' => $match->match_date->format('d/m/Y'),
                'match_time' => $match->match_date->format('H:i'),
                'status' => $match->status,
                'pronostic_enabled' => $match->pronostic_enabled,
                'can_bet' => Pronostic::canBet($match),
            ],
            'user_pronostic' => $userPronostic ? [
                'prediction_type' => $userPronostic->prediction_type,
                'prediction_text' => $userPronostic->prediction_text,
                'created_at' => $userPronostic->created_at->format('d/m/Y H:i'),
            ] : null,
        ]);
    }

    /**
     * Endpoint: POST /api/can/pronostic
     * Enregistrer un pronostic (accepte les scores OU le type simple)
     */
    public function savePronostic(Request $request)
    {
        // LOG: Début de la requête
        Log::info('=== DÉBUT savePronostic ===', [
            'all_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
        ]);

        // Validation avec support des deux modes : scores OU type simple
        $validated = $request->validate([
            'phone'           => 'required|string',
            'match_id'        => 'required|integer|exists:matches,id',
            'prediction_type' => 'nullable|in:team_a_win,team_b_win,draw',
            'score_a'         => 'nullable|integer|min:0|max:20',
            'score_b'         => 'nullable|integer|min:0|max:20',
        ]);

        Log::info('Validation passed', ['validated' => $validated]);

        $phone = $this->formatPhone($validated['phone']);
        $user  = User::where('phone', $phone)->where('is_active', true)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé. Veuillez vous inscrire d\'abord.',
            ], 404);
        }

        $match = FootballMatch::find($validated['match_id']);

        // Vérifier si le match accepte encore les pronostics
        if (! Pronostic::canBet($match)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce match n\'accepte plus de pronostics.',
            ], 400);
        }

        // Mode 1 : Type de prédiction simple (recommandé pour WhatsApp)
        if (isset($validated['prediction_type'])) {
            $pronostic = Pronostic::createOrUpdateSimple(
                $user,
                $match,
                $validated['prediction_type']
            );

            $predictionText = match($validated['prediction_type']) {
                'team_a_win' => "Victoire {$match->team_a}",
                'team_b_win' => "Victoire {$match->team_b}",
                'draw' => "Match nul",
            };

            Log::info('Twilio Studio - Pronostic saved (simple)', [
                'user_id'    => $user->id,
                'match_id'   => $match->id,
                'prediction' => $validated['prediction_type'],
            ]);

            return response()->json([
                'success'   => true,
                'message'   => "✅ Pronostic enregistré !\n\n{$match->team_a} vs {$match->team_b}\n🎯 Ton pronostic : {$predictionText}",
                'pronostic' => [
                    'id'              => $pronostic->id,
                    'match'           => "{$match->team_a} vs {$match->team_b}",
                    'prediction_type' => $validated['prediction_type'],
                    'prediction_text' => $predictionText,
                ],
            ]);
        }

        // Mode 2 : Scores (mode classique)
        if (isset($validated['score_a']) && isset($validated['score_b'])) {
            $pronostic = Pronostic::createOrUpdate(
                $user,
                $match,
                $validated['score_a'],
                $validated['score_b']
            );

            Log::info('Twilio Studio - Pronostic saved (scores)', [
                'user_id'    => $user->id,
                'match_id'   => $match->id,
                'prediction' => "{$validated['score_a']} - {$validated['score_b']}",
            ]);

            return response()->json([
                'success'   => true,
                'message'   => "✅ Pronostic enregistré !\n\n{$match->team_a} vs {$match->team_b}\n🎯 Ton pronostic : {$validated['score_a']} - {$validated['score_b']}",
                'pronostic' => [
                    'id'         => $pronostic->id,
                    'match'      => "{$match->team_a} vs {$match->team_b}",
                    'prediction' => "{$validated['score_a']} - {$validated['score_b']}",
                ],
            ]);
        }

        // Si ni prediction_type ni scores fournis
        return response()->json([
            'success' => false,
            'message' => 'Vous devez fournir soit prediction_type, soit score_a et score_b.',
        ], 400);
    }

    /**
     * Endpoint: GET /api/can/pronostic/test
     * Test de l'endpoint pronostic (pour debug)
     */
    public function testPronostic(Request $request)
    {
        // Récupérer un utilisateur actif
        $user = User::where('is_active', true)->first();

        if (!$user) {
            return response()->json([
                'error' => 'Aucun utilisateur actif trouvé',
                'solution' => 'Créer un utilisateur via le flow d\'inscription'
            ]);
        }

        // Récupérer un match disponible
        $match = FootballMatch::where('pronostic_enabled', true)
            ->where('status', 'scheduled')
            ->first();

        if (!$match) {
            return response()->json([
                'error' => 'Aucun match disponible',
                'solution' => 'Créer un match avec pronostic_enabled=true et status=scheduled'
            ]);
        }

        // Simuler la requête
        $testRequest = new Request([
            'phone' => $user->phone,
            'match_id' => $match->id,
            'prediction_type' => 'team_a_win'
        ]);

        // Appeler l'endpoint réel
        $response = $this->savePronostic($testRequest);
        $data = json_decode($response->getContent(), true);

        return response()->json([
            'test_success' => true,
            'user_tested' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone
            ],
            'match_tested' => [
                'id' => $match->id,
                'teams' => "{$match->team_a} vs {$match->team_b}"
            ],
            'api_response' => $data,
            'instructions' => 'Si vous voyez ce message, l\'API fonctionne correctement depuis le navigateur'
        ]);
    }

    /**
     * Endpoint: POST /api/can/unsubscribe
     * Désinscrire un utilisateur
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);
        $user  = User::where('phone', $phone)->first();

        if ($user) {
            $user->update([
                'is_active'           => false,
                'registration_status' => 'UNSUBSCRIBED',
            ]);

            Log::info('Twilio Studio - User unsubscribed', [
                'user_id' => $user->id,
                'phone'   => $phone,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Désinscription effectuée avec succès.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Utilisateur non trouvé.',
        ], 404);
    }

    /**
     * Endpoint: GET /api/can/partners
     * Récupérer la liste des partenaires actifs
     */
    public function getPartners(Request $request)
    {
        $partners = Partner::where('is_active', true)
            ->with('village:id,name')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'village_id']);

        if ($partners->isEmpty()) {
            return response()->json([
                'success'      => true,
                'has_partners' => false,
                'message'      => 'Aucun partenaire disponible pour le moment.',
                'partners'     => [],
            ]);
        }

        $formattedPartners = $partners->map(function ($partner, $index) {
            return [
                'id'      => $partner->id,
                'number'  => $index + 1,
                'name'    => $partner->name,
                'village' => $partner->village?->name ?? 'N/A',
            ];
        });

        return response()->json([
            'success'      => true,
            'has_partners' => true,
            'count'        => $partners->count(),
            'partners'     => $formattedPartners,
        ]);
    }

    /**
     * Endpoint: GET /api/can/prizes
     * Récupérer la liste des lots disponibles
     */
    public function getPrizes(Request $request)
    {
        $prizes = Prize::where('is_active', true)
            ->whereRaw('quantity > distributed_count')
            ->with('partner:id,name')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'description', 'partner_id', 'quantity', 'distributed_count']);

        if ($prizes->isEmpty()) {
            return response()->json([
                'success'    => true,
                'has_prizes' => false,
                'message'    => 'Aucun lot disponible pour le moment.',
                'prizes'     => [],
            ]);
        }

        $formattedPrizes = $prizes->map(function ($prize, $index) {
            return [
                'id'          => $prize->id,
                'number'      => $index + 1,
                'name'        => $prize->name,
                'description' => $prize->description,
                'partner'     => $prize->partner?->name ?? 'N/A',
                'remaining'   => $prize->remaining,
            ];
        });

        return response()->json([
            'success'    => true,
            'has_prizes' => true,
            'count'      => $prizes->count(),
            'prizes'     => $formattedPrizes,
        ]);
    }

    /**
     * Extraire le village depuis la source
     */
    private function extractVillageFromSource(string $sourceType, string $sourceDetail): ?int
    {
        // Si la source est AFFICHE, le source_detail contient le nom du village
        if ($sourceType === 'AFFICHE') {
            $villageName = $sourceDetail;

            // Essayer de trouver le village correspondant
            $village = Village::where('is_active', true)
                ->where(function ($query) use ($villageName) {
                    $query->where('name', 'LIKE', "%{$villageName}%")
                        ->orWhereRaw('UPPER(name) = ?', [strtoupper($villageName)]);
                })
                ->first();

            if ($village) {
                return $village->id;
            }
        }

        // Pour les autres types de sources, retourner null (utiliser le village par défaut)
        return null;
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhone(string $phone): string
    {
        // Retirer "whatsapp:" si présent
        $phone = str_replace('whatsapp:', '', $phone);

        // Retirer tous les caractères non numériques sauf le +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Ajouter + si absent
        if (! str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
