# Corrections du Flow Twilio pour l'Enregistrement des Pronostics

## 🔍 Problèmes Identifiés

### 1. **Body JSON mal formaté dans `http_save_prono`**

**Problème :**
```json
{
    "phone": "{{trigger.message.From}}",
    "match_id": {{flow.variables.selected_match_id}},
    "prediction_type": {{flow.variables.prediction_type}}
}
```

La variable `prediction_type` n'était **pas entre guillemets**, ce qui créait un JSON invalide quand Twilio essayait d'envoyer la requête.

**Solution :**
```json
{
    "phone": "{{flow.variables.phone_number}}",
    "match_id": {{flow.variables.selected_match_id}},
    "prediction_type": "{{flow.variables.prediction_type}}"
}
```

### 2. **Manque de validation de la réponse API**

**Problème :**
L'ancien flow passait directement du widget `http_save_prono` au message de confirmation sans vérifier si l'API avait réellement réussi à enregistrer le pronostic.

**Solution :**
Ajout d'un widget `check_prono_success` qui vérifie le champ `success` de la réponse API avant d'afficher le message de confirmation.

### 3. **Message de confirmation statique**

**Problème :**
Le message de confirmation était codé en dur et ne reflétait pas la réponse réelle de l'API.

**Solution :**
Utilisation du message dynamique de l'API : `{{widgets.http_save_prono.parsed.message}}`

### 4. **Utilisation incorrecte de la variable phone**

**Problème :**
Le body utilisait `{{trigger.message.From}}` qui peut contenir le préfixe `whatsapp:`.

**Solution :**
Utilisation de `{{flow.variables.phone_number}}` qui est déjà nettoyé et stocké au début du flow.

---

## ✅ Corrections Effectuées

### Widget `http_save_prono` (ligne ~5400)

**AVANT :**
```json
{
  "name": "http_save_prono",
  "type": "make-http-request",
  "transitions": [
    {
      "next": "msg_confirmation_prono",
      "event": "success"
    },
    {
      "next": "msg_erreur_prono",
      "event": "failed"
    }
  ],
  "properties": {
    "method": "POST",
    "content_type": "application/json;charset=utf-8",
    "body": "{\n    \"phone\": \"{{trigger.message.From}}\",\n    \"match_id\": {{flow.variables.selected_match_id}},\n    \"prediction_type\": {{flow.variables.prediction_type}}\n  }",
    "url": "https://can-wabracongo.ywcdigital.com/api/can/pronostic"
  }
}
```

**APRÈS :**
```json
{
  "name": "http_save_prono",
  "type": "make-http-request",
  "transitions": [
    {
      "next": "check_prono_success",
      "event": "success"
    },
    {
      "next": "msg_erreur_prono",
      "event": "failed"
    }
  ],
  "properties": {
    "method": "POST",
    "content_type": "application/json",
    "body": "{\"phone\":\"{{flow.variables.phone_number}}\",\"match_id\":{{flow.variables.selected_match_id}},\"prediction_type\":\"{{flow.variables.prediction_type}}\"}",
    "url": "https://can-wabracongo.ywcdigital.com/api/can/pronostic"
  }
}
```

### Nouveau Widget `check_prono_success`

**AJOUTÉ :**
```json
{
  "name": "check_prono_success",
  "type": "split-based-on",
  "transitions": [
    {
      "next": "msg_erreur_prono",
      "event": "noMatch"
    },
    {
      "next": "msg_confirmation_prono",
      "event": "match",
      "conditions": [
        {
          "friendly_name": "API Success",
          "arguments": [
            "{{widgets.http_save_prono.parsed.success}}"
          ],
          "type": "equal_to",
          "value": "true"
        }
      ]
    }
  ],
  "properties": {
    "input": "{{widgets.http_save_prono.parsed.success}}",
    "offset": {
      "x": -400,
      "y": 5650
    }
  }
}
```

### Widget `msg_confirmation_prono` (mis à jour)

**AVANT :**
```json
{
  "name": "msg_confirmation_prono",
  "type": "send-message",
  "properties": {
    "body": "✅ Pronostic enregistré !\n\n  Merci pour ta participation 🙌\n\n  📢 Nous te tiendrons informé(e) du résultat du match très bientôt."
  }
}
```

**APRÈS :**
```json
{
  "name": "msg_confirmation_prono",
  "type": "send-message",
  "properties": {
    "body": "{{widgets.http_save_prono.parsed.message}}"
  }
}
```

### Widget `msg_erreur_prono` (amélioré)

**AVANT :**
```json
{
  "name": "msg_erreur_prono",
  "type": "send-message",
  "properties": {
    "body": "Une erreur s'est produite. Réessaye plus tard !"
  }
}
```

**APRÈS :**
```json
{
  "name": "msg_erreur_prono",
  "type": "send-message",
  "properties": {
    "body": "❌ Erreur lors de l'enregistrement. {{widgets.http_save_prono.parsed.message}}"
  }
}
```

---

## 📋 Flux Complet de Pronostic (Corrigé)

```
1. [msg_liste_matchs] - Affiche la liste des matchs
   ↓
2. [check_choix_match] - Utilisateur choisit 1, 2, 3, 4 ou 5
   ↓
3. [set_match_X] - Sauvegarde match_id, team_a, team_b
   ↓
4. [msg_options_prono] - Affiche les options de pronostic
   ↓
5. [check_choix_prono] - Utilisateur choisit 1 (A), 2 (B) ou 3 (Nul)
   ↓
6. [set_prono_team_X] - Définit prediction_type
   ↓
7. [http_save_prono] - Envoie POST à l'API avec JSON correct
   ↓
8. [check_prono_success] - Vérifie success=true dans la réponse
   ↓
   ├─ TRUE → [msg_confirmation_prono] - Message de l'API
   └─ FALSE → [msg_erreur_prono] - Message d'erreur de l'API
```

---

## 🧪 Comment Tester

### Test 1 : Format JSON

Vérifiez que le JSON envoyé est valide :

```json
{
  "phone": "+243828500007",
  "match_id": 1,
  "prediction_type": "team_a_win"
}
```

✅ `prediction_type` doit être entre guillemets (chaîne de caractères)
✅ `match_id` peut être sans guillemets (nombre)

### Test 2 : Réponse API

L'API doit retourner :

**Succès :**
```json
{
  "success": true,
  "message": "Pronostic enregistre ! RDC vs Maroc - Ton pronostic : Victoire RDC",
  "pronostic": {
    "id": 3,
    "match": "RDC vs Maroc",
    "prediction_type": "team_a_win",
    "prediction_text": "Victoire RDC"
  }
}
```

**Erreur :**
```json
{
  "success": false,
  "message": "Ce match n'accepte plus de pronostics."
}
```

### Test 3 : Flow Twilio

1. Lancez le flow avec un numéro de test
2. Suivez le processus d'inscription
3. Choisissez un match (ex: 1)
4. Choisissez un pronostic (ex: 1 pour équipe A)
5. Vérifiez que vous recevez le message de confirmation

---

## 📊 Variables Utilisées

| Variable | Source | Utilisation |
|----------|--------|-------------|
| `phone_number` | `trigger.message.From` | Numéro de téléphone nettoyé |
| `selected_match_id` | `http_get_matchs.parsed.matches[X].id` | ID du match sélectionné |
| `selected_team_a` | `http_get_matchs.parsed.matches[X].team_a` | Nom équipe A |
| `selected_team_b` | `http_get_matchs.parsed.matches[X].team_b` | Nom équipe B |
| `prediction_type` | `set_prono_team_X` | "team_a_win", "team_b_win" ou "draw" |

---

## 🚀 Déploiement

### Étape 1 : Importer le Flow dans Twilio Studio

1. Connectez-vous à Twilio Console
2. Allez dans **Studio** > **Flows**
3. Ouvrez votre flow "CAN 2025 Kinshasa"
4. Cliquez sur les **trois points** > **Import from JSON**
5. Collez le contenu de `twilio_flow_pronostic_CORRECTED.json`
6. Cliquez sur **Save** puis **Publish**

### Étape 2 : Tester

1. Envoyez un message WhatsApp au numéro Twilio
2. Suivez le processus complet
3. Vérifiez dans la base de données que le pronostic est bien enregistré :

```sql
SELECT * FROM pronostics ORDER BY created_at DESC LIMIT 5;
```

### Étape 3 : Monitorer

Surveillez les logs Laravel pour détecter les erreurs :

```bash
tail -f storage/logs/laravel.log
```

---

## ⚠️ Points d'Attention

1. **Content-Type** : L'API accepte maintenant `application/json` grâce au middleware `force.json`
2. **Validation** : L'API valide automatiquement si l'utilisateur existe et si le match est ouvert
3. **Mise à jour** : Si un utilisateur fait plusieurs pronostics sur le même match, seul le dernier est conservé
4. **Format du numéro** : L'API gère automatiquement le format `whatsapp:+243...`

---

## 📝 Changelog

### Version CORRECTED (2025-12-16)

- ✅ Correction du JSON body dans `http_save_prono`
- ✅ Ajout de la validation `check_prono_success`
- ✅ Messages dynamiques basés sur la réponse API
- ✅ Utilisation de `phone_number` au lieu de `trigger.message.From`
- ✅ Amélioration de la gestion des erreurs
- ✅ Simplification du Content-Type (`application/json` au lieu de `application/json;charset=utf-8`)

---

✅ **Le flow est maintenant prêt pour enregistrer les pronostics correctement dans la base de données !**
