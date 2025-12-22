<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationSession;
use App\Models\User;
use App\Models\Village;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Webhook principal pour recevoir les messages WhatsApp de Twilio
     */
    public function receiveMessage(Request $request)
    {
        // Extraire les données Twilio
        $from = $request->input('From'); // Format: whatsapp:+243XXXXXXXXX
        $body = trim($request->input('Body', ''));
        $messageId = $request->input('MessageSid');

        // Log pour debug
        Log::info('WhatsApp message received', [
            'from' => $from,
            'body' => $body,
            'message_id' => $messageId
        ]);

        // Extraire le numéro de téléphone
        $phone = str_replace('whatsapp:', '', $from);

        // Récupérer ou créer la session de conversation
        $session = ConversationSession::getOrCreate($phone);

        // Réinitialiser si session expirée
        if ($session->isExpired()) {
            $session->reset();
        }

        // Vérifier si l'utilisateur existe déjà
        $user = User::where('phone', $phone)->first();

        if ($user) {
            // Utilisateur déjà inscrit - traiter comme commande
            $this->handleRegisteredUser($user, $body, $session);
        } else {
            // Nouveau utilisateur - flow d'inscription
            $this->handleRegistrationFlow($session, $body);
        }

        // Réponse vide pour Twilio (200 OK)
        return response('', 200);
    }

    /**
     * Gérer les messages d'un utilisateur déjà inscrit
     */
    protected function handleRegisteredUser(User $user, string $message, ConversationSession $session)
    {
        $messageUpper = strtoupper($message);

        // Permettre d'annuler à tout moment
        if ($messageUpper === 'ANNULER' || $messageUpper === 'CANCEL') {
            $session->setState(ConversationSession::STATE_REGISTERED);
            $this->whatsapp->sendMessage($session->phone, "❌ Opération annulée.");
            sleep(1);
            $this->whatsapp->sendMenu($session->phone);
            return;
        }

        // Gérer les états du flow de pronostic
        switch ($session->state) {
            case ConversationSession::STATE_AWAITING_MATCH_CHOICE:
                $this->handleMatchChoice($user, $session, $message);
                return;

            case ConversationSession::STATE_AWAITING_SCORE_A:
                $this->handleScoreA($session, $message);
                return;

            case ConversationSession::STATE_AWAITING_SCORE_B:
                $this->handleScoreB($user, $session, $message);
                return;
        }

        // Gérer les commandes normales
        switch ($messageUpper) {
            case 'MENU':
            case 'AIDE':
            case 'HELP':
                $this->whatsapp->sendMenu($session->phone);
                break;

            case '1':
            case 'MATCHS':
                $this->sendUpcomingMatches($session->phone);
                break;

            case '2':
            case 'PRONOSTIC':
                $this->startPronosticFlow($session);
                break;

            case '3':
            case 'MES PRONOS':
                $this->sendUserPronostics($user, $session->phone);
                break;

            case '4':
            case 'CLASSEMENT':
                $this->sendLeaderboard($session->phone);
                break;

            default:
                $this->whatsapp->sendError($session->phone, "Commande non reconnue.");
                $this->whatsapp->sendMenu($session->phone);
                break;
        }
    }

    /**
     * Gérer le flow d'inscription pour un nouveau utilisateur
     */
    protected function handleRegistrationFlow(ConversationSession $session, string $message)
    {
        switch ($session->state) {
            case ConversationSession::STATE_IDLE:
                // Première interaction - demander le nom
                $session->setState(ConversationSession::STATE_AWAITING_NAME);
                $this->whatsapp->askName($session->phone);
                break;

            case ConversationSession::STATE_AWAITING_NAME:
                // L'utilisateur a envoyé son nom
                $name = ucwords(strtolower($message));

                // Valider le nom
                if (strlen($name) < 2) {
                    $this->whatsapp->sendMessage($session->phone, "❌ Le nom doit contenir au moins 2 caractères. Réessaie.");
                    return;
                }

                // Sauvegarder le nom dans la session
                $session->setState(ConversationSession::STATE_AWAITING_VILLAGE, ['name' => $name]);

                // Charger les villages actifs
                $villages = Village::where('is_active', true)->get();

                if ($villages->isEmpty()) {
                    $this->whatsapp->sendError($session->phone, "Aucun village disponible pour le moment.");
                    $session->reset();
                    return;
                }

                // Envoyer la liste des villages
                $this->whatsapp->askVillageChoice($session->phone, $villages->toArray());
                break;

            case ConversationSession::STATE_AWAITING_VILLAGE:
                // L'utilisateur a choisi un village
                $this->processVillageChoice($session, $message);
                break;

            default:
                $session->reset();
                $this->whatsapp->askName($session->phone);
                break;
        }
    }

    /**
     * Traiter le choix du village
     */
    protected function processVillageChoice(ConversationSession $session, string $choice)
    {
        $villages = Village::where('is_active', true)->get();

        // Vérifier si c'est un numéro
        if (is_numeric($choice)) {
            $index = (int)$choice - 1;

            if ($index >= 0 && $index < $villages->count()) {
                $village = $villages[$index];
            } else {
                $this->whatsapp->sendError($session->phone, "Numéro invalide. Choisis un numéro entre 1 et {$villages->count()}.");
                return;
            }
        } else {
            // Recherche par nom
            $village = $villages->firstWhere('name', 'like', "%{$choice}%");

            if (!$village) {
                $this->whatsapp->sendError($session->phone, "Village non trouvé. Envoie le numéro correspondant.");
                return;
            }
        }

        // Créer l'utilisateur
        $name = $session->getData('name');
        $phone = $session->phone;

        try {
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'village_id' => $village->id,
                'is_active' => true,
                'opted_in_at' => now(),
            ]);

            // Associer la session à l'utilisateur
            $session->update([
                'user_id' => $user->id,
                'state' => ConversationSession::STATE_REGISTERED,
            ]);

            // Envoyer message de bienvenue
            $this->whatsapp->sendWelcomeMessage($phone, $name, $village->name);

            // Envoyer le menu
            sleep(1); // Petit délai pour ne pas spammer
            $this->whatsapp->sendMenu($phone);

            Log::info('User registered via WhatsApp', [
                'user_id' => $user->id,
                'phone' => $phone,
                'village' => $village->name,
            ]);

        } catch (\Exception $e) {
            Log::error('User registration error: ' . $e->getMessage());
            $this->whatsapp->sendError($session->phone, "Erreur lors de l'inscription. Réessaie plus tard.");
            $session->reset();
        }
    }

    /**
     * Gérer le choix du match pour le pronostic
     */
    protected function handleMatchChoice(User $user, ConversationSession $session, string $choice)
    {
        $availableMatches = $session->getData('available_matches', []);

        if (empty($availableMatches)) {
            $this->whatsapp->sendError($session->phone, "Session expirée. Envoie PRONOSTIC pour recommencer.");
            $session->setState(ConversationSession::STATE_REGISTERED);
            return;
        }

        // Vérifier si c'est un numéro valide
        if (!is_numeric($choice)) {
            $this->whatsapp->sendError($session->phone, "❌ Envoie le numéro du match (exemple: 1)");
            return;
        }

        $index = (int)$choice - 1;

        if ($index < 0 || $index >= count($availableMatches)) {
            $this->whatsapp->sendError($session->phone, "❌ Numéro invalide. Choisis entre 1 et " . count($availableMatches));
            return;
        }

        $matchId = $availableMatches[$index];
        $match = \App\Models\FootballMatch::find($matchId);

        if (!$match) {
            $this->whatsapp->sendError($session->phone, "❌ Match introuvable. Envoie PRONOSTIC pour recommencer.");
            $session->setState(ConversationSession::STATE_REGISTERED);
            return;
        }

        // Vérifier si le match est toujours disponible pour pronostic
        if (!$match->pronostic_enabled || $match->match_date->diffInMinutes(now(), false) < 5) {
            $this->whatsapp->sendMessage($session->phone, "❌ Les pronostics sont fermés pour ce match.");
            $session->setState(ConversationSession::STATE_REGISTERED);
            return;
        }

        // Vérifier si l'utilisateur a déjà un pronostic pour ce match
        $existingProno = \App\Models\Pronostic::where('user_id', $user->id)
            ->where('match_id', $match->id)
            ->first();

        if ($existingProno) {
            // BLOQUER l'utilisateur - impossible de modifier
            $message = "🚫 *PRONOSTIC DÉJÀ ENREGISTRÉ*\n\n";
            $message .= "⚽ {$match->team_a} vs {$match->team_b}\n\n";
            $message .= "📊 Ton pronostic actuel :\n";
            $message .= "   *{$existingProno->predicted_score_a} - {$existingProno->predicted_score_b}*\n\n";
            $message .= "📅 Placé le : " . $existingProno->created_at->format('d/m/Y à H:i') . "\n\n";
            $message .= "❌ *Impossible de modifier ton pronostic.*\n\n";
            $message .= "💡 Envoie MENU pour voir d'autres options.";

            $this->whatsapp->sendMessage($session->phone, $message);
            
            // Réinitialiser la session
            $session->setState(ConversationSession::STATE_REGISTERED);
            
            Log::info('User tried to modify existing pronostic', [
                'user_id' => $user->id,
                'match_id' => $match->id,
                'existing_pronostic_id' => $existingProno->id,
            ]);

            return;
        }

        // Pas de pronostic existant - continuer le flow
        $message = "🎯 *PRONOSTIC*\n\n";
        $message .= "⚽ {$match->team_a} vs {$match->team_b}\n";
        $message .= "📅 " . $match->match_date->format('d/m à H:i') . "\n\n";
        $message .= "Quel sera le score de *{$match->team_a}* ?\n";
        $message .= "Envoie un chiffre (0-9)";

        $session->setState(ConversationSession::STATE_AWAITING_SCORE_A, [
            'match_id' => $match->id,
            'team_a' => $match->team_a,
            'team_b' => $match->team_b,
        ]);

        $this->whatsapp->sendMessage($session->phone, $message);
    }

    /**
     * Gérer la saisie du score de l'équipe A
     */
    protected function handleScoreA(ConversationSession $session, string $score)
    {
        // Valider que c'est un chiffre
        if (!is_numeric($score) || $score < 0 || $score > 9) {
            $this->whatsapp->sendError($session->phone, "❌ Envoie un chiffre entre 0 et 9");
            return;
        }

        $scoreA = (int)$score;
        $teamA = $session->getData('team_a');
        $teamB = $session->getData('team_b');

        // Sauvegarder le score A et demander le score B
        $session->setState(ConversationSession::STATE_AWAITING_SCORE_B, [
            'match_id' => $session->getData('match_id'),
            'team_a' => $teamA,
            'team_b' => $teamB,
            'score_a' => $scoreA,
        ]);

        $message = "✅ Score {$teamA}: *{$scoreA}*\n\n";
        $message .= "Quel sera le score de *{$teamB}* ?\n";
        $message .= "Envoie un chiffre (0-9)";

        $this->whatsapp->sendMessage($session->phone, $message);
    }

    /**
     * Gérer la saisie du score de l'équipe B et créer le pronostic
     */
    protected function handleScoreB(User $user, ConversationSession $session, string $score)
    {
        // Valider que c'est un chiffre
        if (!is_numeric($score) || $score < 0 || $score > 9) {
            $this->whatsapp->sendError($session->phone, "❌ Envoie un chiffre entre 0 et 9");
            return;
        }

        $scoreB = (int)$score;
        $scoreA = $session->getData('score_a');
        $matchId = $session->getData('match_id');
        $teamA = $session->getData('team_a');
        $teamB = $session->getData('team_b');

        $match = \App\Models\FootballMatch::find($matchId);

        if (!$match) {
            $this->whatsapp->sendError($session->phone, "❌ Match introuvable.");
            $session->setState(ConversationSession::STATE_REGISTERED);
            return;
        }

        try {
            // Créer le pronostic (on utilise create au lieu de updateOrCreate car on a déjà vérifié l'existence)
            $pronostic = \App\Models\Pronostic::create([
                'user_id' => $user->id,
                'match_id' => $match->id,
                'predicted_score_a' => $scoreA,
                'predicted_score_b' => $scoreB,
            ]);

            // Message de confirmation
            $message = "✅ *PRONOSTIC ENREGISTRÉ !*\n\n";
            $message .= "⚽ {$teamA} vs {$teamB}\n";
            $message .= "📊 Ton pronostic: *{$scoreA} - {$scoreB}*\n";
            $message .= "📅 Match: " . $match->match_date->format('d/m à H:i') . "\n\n";
            $message .= "🍀 Bonne chance !\n\n";
            $message .= "💡 Envoie MENU pour d'autres options";

            $this->whatsapp->sendMessage($session->phone, $message);

            // Réinitialiser la session
            $session->setState(ConversationSession::STATE_REGISTERED);

            Log::info('Pronostic created via WhatsApp', [
                'user_id' => $user->id,
                'match_id' => $match->id,
                'score' => "{$scoreA}-{$scoreB}",
            ]);

        } catch (\Exception $e) {
            Log::error('Pronostic creation error: ' . $e->getMessage());
            $this->whatsapp->sendError($session->phone, "❌ Erreur lors de l'enregistrement. Réessaie.");
            $session->setState(ConversationSession::STATE_REGISTERED);
        }
    }

    /**
     * Envoyer les prochains matchs
     */
    protected function sendUpcomingMatches(string $phone)
    {
        $matches = \App\Models\FootballMatch::where('status', 'scheduled')
            ->where('match_date', '>=', now())
            ->orderBy('match_date')
            ->take(5)
            ->get();

        if ($matches->isEmpty()) {
            $this->whatsapp->sendMessage($phone, "📅 Aucun match programmé pour le moment.");
            return;
        }

        $message = "⚽ *PROCHAINS MATCHS*\n\n";

        foreach ($matches as $index => $match) {
            $number = $index + 1;
            $date = $match->match_date->format('d/m à H:i');
            $message .= "{$number}. {$match->team_a} 🆚 {$match->team_b}\n";
            $message .= "   📅 {$date}\n";

            if ($match->pronostic_enabled) {
                $message .= "   ✅ Pronostics ouverts\n";
            }
            $message .= "\n";
        }

        $message .= "💡 Envoie PRONOSTIC pour faire un pronostic !";

        $this->whatsapp->sendMessage($phone, $message);
    }

    /**
     * Démarrer le flow de pronostic
     */
    protected function startPronosticFlow(ConversationSession $session)
    {
        $matches = \App\Models\FootballMatch::where('status', 'scheduled')
            ->where('pronostic_enabled', true)
            ->where('match_date', '>', now()->addMinutes(5))
            ->orderBy('match_date')
            ->get();

        if ($matches->isEmpty()) {
            $message = "❌ *AUCUN MATCH DISPONIBLE*\n\n";
            $message .= "Il n'y a aucun match ouvert pour les pronostics en ce moment.\n\n";
            $message .= "📅 Les pronostics seront disponibles dès qu'un nouveau match sera programmé.\n\n";
            $message .= "💡 Envoie MENU pour voir les autres options.";
            
            $this->whatsapp->sendMessage($session->phone, $message);
            return;
        }

        $message = "🎯 *FAIRE UN PRONOSTIC*\n\n";
        $message .= "Choisis le numéro du match :\n\n";

        foreach ($matches as $index => $match) {
            $number = $index + 1;
            $date = $match->match_date->format('d/m à H:i');
            $message .= "{$number}. {$match->team_a} vs {$match->team_b}\n";
            $message .= "   📅 {$date}\n\n";
        }

        $message .= "💡 Envoie ANNULER pour abandonner";

        // Sauvegarder les matchs dans la session
        $session->setState(ConversationSession::STATE_AWAITING_MATCH_CHOICE, [
            'available_matches' => $matches->pluck('id')->toArray()
        ]);

        $this->whatsapp->sendMessage($session->phone, $message);
    }

    /**
     * Envoyer les pronostics de l'utilisateur
     */
    protected function sendUserPronostics(User $user, string $phone)
    {
        $pronostics = $user->pronostics()
            ->with('match')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        if ($pronostics->isEmpty()) {
            $this->whatsapp->sendMessage($phone, "📋 Tu n'as pas encore fait de pronostics.\n\nEnvoie PRONOSTIC pour commencer !");
            return;
        }

        $message = "📊 *MES PRONOSTICS*\n\n";

        foreach ($pronostics as $prono) {
            $match = $prono->match;
            $message .= "⚽ {$match->team_a} vs {$match->team_b}\n";
            $message .= "   Mon prono: {$prono->predicted_score_a} - {$prono->predicted_score_b}\n";

            if ($match->status === 'finished') {
                $message .= "   Résultat: {$match->score_a} - {$match->score_b}\n";
                $message .= $prono->is_winner ? "   ✅ GAGNÉ !\n" : "   ❌ Perdu\n";
            } else {
                $message .= "   ⏳ En attente\n";
            }
            $message .= "\n";
        }

        $this->whatsapp->sendMessage($phone, $message);
    }

    /**
     * Envoyer le classement
     */
    protected function sendLeaderboard(string $phone)
    {
        $message = "🏆 *CLASSEMENT*\n\n";
        $message .= "📊 Cette fonctionnalité arrive bientôt !\n\n";
        $message .= "Tu pourras voir :\n";
        $message .= "• Le top 10 général\n";
        $message .= "• Le classement de ton village\n";
        $message .= "• Ta position\n\n";
        $message .= "En attendant, envoie MENU pour les autres options.";

        $this->whatsapp->sendMessage($phone, $message);
    }

    /**
     * Webhook de statut (optionnel - pour tracker la livraison des messages)
     */
    public function statusCallback(Request $request)
    {
        $messageSid = $request->input('MessageSid');
        $status = $request->input('MessageStatus');

        Log::info('WhatsApp message status', [
            'sid' => $messageSid,
            'status' => $status
        ]);

        return response('', 200);
    }
}