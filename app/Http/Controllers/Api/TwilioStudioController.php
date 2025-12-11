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
    public function checkUser(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $this->formatPhone($validated['phone']);

        // Chercher l'utilisateur (actif ou non)
        $user = User::where('phone', $phone)->first();

        // Pas trouvé → nouveau utilisateur
        if (! $user) {
            return response()->json([
                'status' => 'NOT_FOUND',
            ]);
        }

        // Utilisateur STOP ou inactif → proposer réactivation
        if (! $user->is_active || $user->registration_status === 'STOP') {
            return response()->json([
                'status' => 'STOP',
                'name'   => $user->name,
                'phone'  => $user->phone,
            ]);
        }

        // Utilisateur déjà inscrit et actif
        return response()->json([
            'status' => 'INSCRIT',
            'name'   => $user->name,
            'phone'  => $user->phone,
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
     * Endpoint: POST /api/can/pronostic
     * Enregistrer un pronostic
     */
    public function savePronostic(Request $request)
    {
        $validated = $request->validate([
            'phone'    => 'required|string',
            'match_id' => 'required|integer|exists:matches,id',
            'score_a'  => 'required|integer|min:0|max:20',
            'score_b'  => 'required|integer|min:0|max:20',
        ]);

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

        // Créer ou mettre à jour le pronostic
        $pronostic = Pronostic::createOrUpdate(
            $user,
            $match,
            $validated['score_a'],
            $validated['score_b']
        );

        Log::info('Twilio Studio - Pronostic saved', [
            'user_id'    => $user->id,
            'match_id'   => $match->id,
            'prediction' => "{$validated['score_a']} - {$validated['score_b']}",
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Pronostic enregistré avec succès !',
            'pronostic' => [
                'id'    => $pronostic->id,
                'match' => "{$match->team_a} vs {$match->team_b}",
                'prediction' => "{$validated['score_a']} - {$validated['score_b']}",
            ],
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
