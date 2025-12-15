<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\FootballMatch;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\Village;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function index()
    {
        $campaigns = Campaign::with('messages')
            ->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $villages = Village::where('is_active', true)->get();
        $templates = MessageTemplate::where('is_active', true)->get();
        $matches = FootballMatch::where('status', '!=', 'finished')
            ->orderBy('match_date')
            ->get();

        return view('admin.campaigns.create', compact('villages', 'templates', 'matches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'audience_type' => 'required|in:all,village,status',
            'village_id' => 'nullable|exists:villages,id',
            'match_id' => 'nullable|exists:matches,id',
            'audience_status' => 'nullable|string',
            'message' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';
        $validated['total_recipients'] = $this->countRecipients($validated);

        $campaign = Campaign::create($validated);

        if ($request->input('send_now')) {
            return redirect()->route('admin.campaigns.send', $campaign)
                ->with('info', 'Campagne créée. Confirme l\'envoi.');
        }

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campagne créée avec succès !');
    }

    public function show(Campaign $campaign)
    {
        // Rafraîchir la campagne depuis la base de données
        $campaign->refresh();

        // Stats de la campagne (directement depuis la base de données)
        $stats = [
            'total' => $campaign->messages()->count(),
            'sent' => $campaign->messages()->where('status', 'sent')->count(),
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'pending' => $campaign->messages()->where('status', 'pending')->count(),
        ];

        // Charger les messages en échec avec les détails utilisateur
        $failedMessages = $campaign->messages()
            ->where('status', 'failed')
            ->with('user')
            ->latest()
            ->get()
            ->map(function($message) {
                $message->readable_error = $this->formatTwilioError($message->error_message);
                return $message;
            });

        // Charger les derniers messages (pour debug/affichage)
        $recentMessages = $campaign->messages()
            ->with('user')
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.campaigns.show', compact('campaign', 'stats', 'failedMessages', 'recentMessages'));
    }

    public function edit(Campaign $campaign)
    {
        if (in_array($campaign->status, ['processing', 'sent'])) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('error', 'Impossible de modifier une campagne en cours ou terminée.');
        }

        $villages = Village::where('is_active', true)->get();
        $templates = MessageTemplate::where('is_active', true)->get();

        return view('admin.campaigns.edit', compact('campaign', 'villages', 'templates'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        if (in_array($campaign->status, ['processing', 'sent'])) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('error', 'Impossible de modifier une campagne en cours ou terminée.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'audience_type' => 'required|in:all,village,status',
            'village_id' => 'nullable|exists:villages,id',
            'match_id' => 'nullable|exists:matches,id',
            'audience_status' => 'nullable|string',
            'message' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';
        $validated['total_recipients'] = $this->countRecipients($validated);

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campagne mise à jour avec succès !');
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->status === 'processing') {
            return redirect()->route('admin.campaigns.index')
                ->with('error', 'Impossible de supprimer une campagne en cours d\'envoi.');
        }

        $campaign->delete();

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campagne supprimée avec succès !');
    }

    /**
     * Afficher la page de confirmation d'envoi
     */
    public function confirmSend(Campaign $campaign)
    {
        // Vérifier que le message existe
        if (empty($campaign->message)) {
            return redirect()->route('admin.campaigns.edit', $campaign)
                ->with('error', 'Le message de la campagne est vide. Veuillez le remplir avant d\'envoyer.');
        }

        $recipients = $this->getRecipients($campaign);

        if ($recipients->isEmpty()) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('error', 'Aucun destinataire trouvé pour cette campagne.');
        }

        $previewMessage = $this->previewMessage($campaign->message, $recipients->first());

        return view('admin.campaigns.confirm-send', compact('campaign', 'recipients', 'previewMessage'));
    }

    /**
     * Envoyer la campagne
     */
    public function send(Campaign $campaign)
    {
        if (in_array($campaign->status, ['processing', 'sent'])) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('error', 'Cette campagne a déjà été envoyée.');
        }

        // Marquer la campagne comme "en cours d'envoi"
        $campaign->update(['status' => 'processing', 'sent_at' => now()]);

        // Récupérer les destinataires
        $recipients = $this->getRecipients($campaign);

        // Créer les messages à envoyer
        foreach ($recipients as $user) {
            CampaignMessage::create([
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'message' => $this->personalizeMessage($campaign->message, $user, $campaign),
                'status' => 'pending',
            ]);
        }

        // Lancer l'envoi en arrière-plan (via job)
        dispatch(function() use ($campaign) {
            $this->processCampaignSending($campaign);
        })->afterResponse();

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Envoi de la campagne en cours ! Les messages sont en train d\'être envoyés.');
    }

    /**
     * Traiter l'envoi des messages
     */
    protected function processCampaignSending(Campaign $campaign)
    {
        $messages = $campaign->messages()->where('status', 'pending')->get();

        $sent = 0;
        $failed = 0;

        // URL pour les status callbacks Twilio
        $statusCallbackUrl = route('api.twilio.status-callback');

        foreach ($messages as $message) {
            try {
                $result = $this->whatsapp->sendMessage(
                    $message->user->phone,
                    $message->message,
                    $statusCallbackUrl
                );

                if ($result && $result['success']) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'twilio_sid' => $result['sid']
                    ]);

                    // Logger dans MessageLog pour les stats globales
                    MessageLog::create([
                        'user_id' => $message->user_id,
                        'campaign_id' => $campaign->id,
                        'twilio_sid' => $result['sid'],
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    $sent++;
                } else {
                    $errorMsg = $result['error'] ?? 'Failed to send';
                    $message->update([
                        'status' => 'failed',
                        'error_message' => $errorMsg
                    ]);

                    // Logger dans MessageLog pour les stats globales
                    MessageLog::create([
                        'user_id' => $message->user_id,
                        'campaign_id' => $campaign->id,
                        'status' => 'failed',
                        'error_message' => $errorMsg,
                    ]);

                    $failed++;
                }

                // Petit délai pour éviter le rate limiting Twilio
                usleep(100000); // 0.1 seconde

            } catch (\Exception $e) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

                // Logger dans MessageLog pour les stats globales
                MessageLog::create([
                    'user_id' => $message->user_id,
                    'campaign_id' => $campaign->id,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $failed++;
                Log::error('Campaign message sending failed', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Mettre à jour le statut de la campagne
        $campaign->update([
            'status' => 'sent',
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);

        Log::info('Campaign sending completed', [
            'campaign_id' => $campaign->id,
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Compter les destinataires selon les critères
     */
    protected function countRecipients(array $criteria): int
    {
        return $this->buildRecipientsQuery($criteria)->count();
    }

    /**
     * Récupérer les destinataires selon les critères
     */
    protected function getRecipients(Campaign $campaign)
    {
        return $this->buildRecipientsQuery([
            'audience_type' => $campaign->audience_type,
            'village_id' => $campaign->village_id,
            'audience_status' => $campaign->audience_status,
        ])->get();
    }

    /**
     * Construire la requête pour récupérer les destinataires
     */
    protected function buildRecipientsQuery(array $criteria)
    {
        $query = User::where('is_active', true);

        // Filtrer par village
        if ($criteria['audience_type'] === 'village' && !empty($criteria['village_id'])) {
            $query->where('village_id', $criteria['village_id']);
        }

        // Filtrer par statut
        if ($criteria['audience_type'] === 'status' && !empty($criteria['audience_status'])) {
            switch ($criteria['audience_status']) {
                case 'inscribed':
                    $query->whereNotNull('opted_in_at');
                    break;
                case 'has_pronostic':
                    $query->has('pronostics');
                    break;
                case 'is_winner':
                    $query->whereHas('pronostics', function($q) {
                        $q->where('is_winner', true);
                    });
                    break;
                case 'no_pronostic':
                    $query->whereNotNull('opted_in_at')
                         ->doesntHave('pronostics');
                    break;
            }
        }

        return $query;
    }

    /**
     * Personnaliser le message avec les variables utilisateur et match
     */
    protected function personalizeMessage(?string $message, User $user, Campaign $campaign = null): string
    {
        if (empty($message)) {
            return '';
        }

        $replacements = [
            '{nom}' => $user->name,
            '{prenom}' => $user->name,
            '{village}' => $user->village->name ?? 'ton village',
            '{phone}' => $user->phone,
        ];

        // Ajouter les variables du match si disponible
        if ($campaign && $campaign->match) {
            $replacements['{match_equipe_a}'] = $campaign->match->team_a;
            $replacements['{match_equipe_b}'] = $campaign->match->team_b;
            $replacements['{match_date}'] = $campaign->match->match_date->format('d/m/Y à H:i');
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Prévisualiser le message
     */
    protected function previewMessage(?string $message, ?User $user): string
    {
        if (empty($message)) {
            return '[Message vide]';
        }

        if (!$user) {
            return $message;
        }

        return $this->personalizeMessage($message, $user);
    }

    /**
     * Formater les codes d'erreur Twilio en messages lisibles
     */
    protected function formatTwilioError(?string $errorMessage): string
    {
        if (empty($errorMessage)) {
            return 'Erreur inconnue';
        }

        // Mapping des codes d'erreur Twilio courants
        $errorMappings = [
            '63016' => 'Numéro WhatsApp invalide - Le numéro n\'est pas enregistré sur WhatsApp',
            '63015' => 'Numéro de téléphone invalide',
            '63003' => 'Pas de canal WhatsApp disponible',
            '21211' => 'Numéro de téléphone invalide',
            '21614' => 'Numéro \'To\' WhatsApp invalide',
            '21408' => 'Permission refusée pour envoyer au numéro de destination',
            '30007' => 'Message filtré - Spam détecté',
            '30008' => 'Numéro inconnu - Pas d\'abonnement WhatsApp',
            '30009' => 'Compte absent du réseau',
        ];

        // Extraire le code d'erreur (format: "Error code: XXXXX" ou juste "XXXXX")
        preg_match('/(?:Error code:\s*)?(\d{5})/', $errorMessage, $matches);

        if (!empty($matches[1])) {
            $errorCode = $matches[1];
            if (isset($errorMappings[$errorCode])) {
                return $errorMappings[$errorCode] . " (Code: {$errorCode})";
            }
            return "Erreur Twilio (Code: {$errorCode})";
        }

        return $errorMessage;
    }

    /**
     * Test d'envoi à un numéro spécifique
     */
    public function test(Request $request, Campaign $campaign)
    {
        $request->validate([
            'test_phone' => 'required|string',
        ]);

        $testMessage = $this->personalizeMessage(
            $campaign->message,
            User::where('phone', $request->test_phone)->first() ?? new User(['name' => 'Test', 'phone' => $request->test_phone])
        );

        try {
            $success = $this->whatsapp->sendMessage($request->test_phone, $testMessage);

            if ($success) {
                return back()->with('success', 'Message de test envoyé avec succès !');
            } else {
                return back()->with('error', 'Échec de l\'envoi du message de test.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
