<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    /**
     * Gérer les status callbacks de Twilio
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function statusCallback(Request $request)
    {
        // Log tous les paramètres reçus pour debug
        Log::info('Twilio Status Callback received', [
            'all_params' => $request->all()
        ]);

        // Récupérer les informations du callback
        $messageSid = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus'); // queued, sent, delivered, failed, undelivered
        $to = $request->input('To');
        $errorCode = $request->input('ErrorCode');
        $errorMessage = $request->input('ErrorMessage');

        // Retirer le préfixe whatsapp: du numéro
        $phoneNumber = str_replace('whatsapp:', '', $to);

        // Trouver le message de campagne correspondant
        $campaignMessage = CampaignMessage::whereHas('user', function($query) use ($phoneNumber) {
            $query->where('phone', $phoneNumber);
        })->where('twilio_sid', $messageSid)->first();

        if (!$campaignMessage) {
            Log::warning('Campaign message not found for Twilio callback', [
                'message_sid' => $messageSid,
                'phone' => $phoneNumber
            ]);
            return response()->json(['status' => 'ok'], 200);
        }

        // Mapper les statuts Twilio vers nos statuts
        $statusMap = [
            'queued' => 'pending',
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'undelivered' => 'failed'
        ];

        $newStatus = $statusMap[$messageStatus] ?? 'pending';

        // Mettre à jour le statut du message
        $updateData = [
            'status' => $newStatus
        ];

        // Si le message est envoyé, enregistrer la date
        if ($newStatus === 'sent' && !$campaignMessage->sent_at) {
            $updateData['sent_at'] = now();
        }

        // Si échec, enregistrer l'erreur
        if ($newStatus === 'failed') {
            $updateData['error_message'] = $errorMessage ?: "Error code: {$errorCode}";
        }

        $campaignMessage->update($updateData);

        Log::info('Campaign message status updated', [
            'campaign_message_id' => $campaignMessage->id,
            'old_status' => $campaignMessage->status,
            'new_status' => $newStatus,
            'twilio_sid' => $messageSid
        ]);

        // Mettre à jour les compteurs de la campagne
        $this->updateCampaignCounters($campaignMessage->campaign_id);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Gérer les webhooks entrants (messages reçus)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function incomingMessage(Request $request)
    {
        Log::info('Twilio Incoming Message received', [
            'all_params' => $request->all()
        ]);

        $from = str_replace('whatsapp:', '', $request->input('From'));
        $body = $request->input('Body');
        $messageSid = $request->input('MessageSid');

        // Ici, vous pouvez traiter les messages entrants
        // Par exemple, pour gérer les réponses des utilisateurs
        // Cela sera utile pour le flow interactif des pronostics

        Log::info('Incoming WhatsApp message', [
            'from' => $from,
            'body' => $body,
            'sid' => $messageSid
        ]);

        // Retourner une réponse vide (pas de réponse automatique pour l'instant)
        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Mettre à jour les compteurs de la campagne
     *
     * @param int $campaignId
     * @return void
     */
    protected function updateCampaignCounters($campaignId)
    {
        $campaign = \App\Models\Campaign::find($campaignId);

        if (!$campaign) {
            return;
        }

        $sent = $campaign->messages()->where('status', 'sent')->count();
        $delivered = $campaign->messages()->where('status', 'delivered')->count();
        $failed = $campaign->messages()->where('status', 'failed')->count();

        $campaign->update([
            'sent_count' => $sent + $delivered, // sent + delivered = envoyés avec succès
            'failed_count' => $failed
        ]);
    }
}
