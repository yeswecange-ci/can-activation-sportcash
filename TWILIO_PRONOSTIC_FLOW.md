# Flow Interactif Pronostic WhatsApp - Twilio Studio

Ce document explique comment créer un flow interactif complet pour que les utilisateurs puissent choisir un match et faire un pronostic simple via WhatsApp.

---

## 🎯 Vue d'ensemble du Flow

1. **Afficher la liste des matchs** → L'utilisateur voit les matchs disponibles
2. **Choix du match** → L'utilisateur envoie le numéro du match (ex: "1")
3. **Afficher les options de pronostic** → Victoire équipe 1, Victoire équipe 2, Match nul
4. **Enregistrer le pronostic** → Confirmation d'enregistrement

---

## 📋 Endpoints API Disponibles

### 1. Liste des matchs formatée
```
GET https://votre-domaine.com/api/can/matches/formatted?limit=5
```

**Réponse:**
```json
{
  "success": true,
  "has_matches": true,
  "count": 3,
  "message": "⚽ *PROCHAINS MATCHS CAN 2025*\n\n1. Cameroun 🆚 Sénégal\n   📅 15/01/2025 à 18:00\n   ✅ Pronostics ouverts\n\n2. Nigeria 🆚 Égypte\n   📅 16/01/2025 à 21:00\n   ✅ Pronostics ouverts\n\n💡 Envoie le numéro du match pour faire ton pronostic !",
  "matches": [
    {
      "id": 1,
      "number": 1,
      "team_a": "Cameroun",
      "team_b": "Sénégal",
      ...
    }
  ]
}
```

### 2. Détails d'un match spécifique
```
GET https://votre-domaine.com/api/can/matches/{id}?phone={{contact.channel.address}}
```

**Réponse:**
```json
{
  "success": true,
  "match": {
    "id": 1,
    "team_a": "Cameroun",
    "team_b": "Sénégal",
    "match_date": "15/01/2025",
    "match_time": "18:00",
    "can_bet": true
  },
  "user_pronostic": null
}
```

### 3. Enregistrer un pronostic simple
```
POST https://votre-domaine.com/api/can/pronostic
```

**Body (Form):**
```
phone={{contact.channel.address}}
match_id={{widgets.match_details.parsed.match.id}}
prediction_type=team_a_win
```

**Types de prédiction possibles:**
- `team_a_win` → Victoire équipe A
- `team_b_win` → Victoire équipe B
- `draw` → Match nul

**Réponse:**
```json
{
  "success": true,
  "message": "✅ Pronostic enregistré !\n\nCameroun vs Sénégal\n🎯 Ton pronostic : Victoire Cameroun",
  "pronostic": {
    "id": 123,
    "match": "Cameroun vs Sénégal",
    "prediction_type": "team_a_win",
    "prediction_text": "Victoire Cameroun"
  }
}
```

---

## 🔧 Configuration du Flow Twilio Studio

### Étape 1: Afficher la liste des matchs

**Trigger:** L'utilisateur envoie "MATCHS" ou "PRONO"

1. **Widget: Make HTTP Request** (`get_matches`)
   - Method: `GET`
   - URL: `https://votre-domaine.com/api/can/matches/formatted?limit=5`

2. **Widget: Split Based On...** (`check_matches`)
   - Condition: `{{widgets.get_matches.parsed.has_matches}}` égale à `true`

3. **Si matches trouvés → Widget: Send Message**
   - Message Body:
     ```
     {{widgets.get_matches.parsed.message}}
     ```

4. **Si aucun match → Widget: Send Message**
   - Message Body:
     ```
     ⚽ Aucun match disponible pour le moment.
     Reviens bientôt !
     ```

---

### Étape 2: Capturer le choix du match

1. **Widget: Gather Input** (`get_match_choice`)
   - Message: (vide, car déjà affiché à l'étape 1)
   - Number of Digits: 1
   - Timeout: 60 seconds
   - Variable to save: `match_choice`

2. **Widget: Run Function** (optionnel - pour valider le numéro)
   - Fonction JavaScript pour extraire l'ID du match depuis le numéro choisi:
   ```javascript
   exports.handler = function(context, event, callback) {
       const matches = JSON.parse(event.matches_json);
       const choice = parseInt(event.match_choice);

       if (choice >= 1 && choice <= matches.length) {
           const selectedMatch = matches[choice - 1];
           callback(null, {
               match_id: selectedMatch.id,
               team_a: selectedMatch.team_a,
               team_b: selectedMatch.team_b
           });
       } else {
           callback(null, { error: 'Choix invalide' });
       }
   };
   ```

**OU plus simple:** Stocker les matches en variable et utiliser directement l'index

---

### Étape 3: Afficher les options de pronostic

1. **Widget: Send & Wait for Reply** (`show_prediction_options`)
   - Message Body:
     ```
     🎯 *FAIRE TON PRONOSTIC*

     Match : {{widgets.get_match_details.parsed.match.team_a}} vs {{widgets.get_match_details.parsed.match.team_b}}
     📅 {{widgets.get_match_details.parsed.match.match_date}} à {{widgets.get_match_details.parsed.match.match_time}}

     Quel est ton pronostic ?

     1️⃣ Victoire {{widgets.get_match_details.parsed.match.team_a}}
     2️⃣ Victoire {{widgets.get_match_details.parsed.match.team_b}}
     3️⃣ Match nul

     Envoie 1, 2 ou 3
     ```
   - Variable to save: `prediction_choice`

---

### Étape 4: Enregistrer le pronostic

1. **Widget: Split Based On...** (`convert_choice`)
   - Variable: `{{widgets.show_prediction_options.inbound.Body}}`
   - Conditions:
     - Égale à "1" → `set_prediction_type` = "team_a_win"
     - Égale à "2" → `set_prediction_type` = "team_b_win"
     - Égale à "3" → `set_prediction_type` = "draw"
     - Autre → Message d'erreur

2. **Widget: Make HTTP Request** (`save_pronostic`)
   - Method: `POST`
   - URL: `https://votre-domaine.com/api/can/pronostic`
   - Content Type: `application/x-www-form-urlencoded`
   - Parameters:
     ```
     phone: {{contact.channel.address}}
     match_id: {{widgets.get_match_details.parsed.match.id}}
     prediction_type: {{flow.variables.prediction_type}}
     ```

3. **Widget: Send Message** (`confirmation`)
   - Message Body:
     ```
     {{widgets.save_pronostic.parsed.message}}
     ```

---

## 🎨 Flow Complet (Diagramme)

```
┌─────────────────┐
│ Trigger:        │
│ "MATCHS" /      │
│ "PRONO"         │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ HTTP Request:           │
│ GET /matches/formatted  │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Split: has_matches?     │
└────┬────────────────┬───┘
     │ true           │ false
     ▼                ▼
┌──────────────┐  ┌──────────────┐
│ Send Message │  │ Send Message │
│ (Liste)      │  │ (Aucun)      │
└──────┬───────┘  └──────────────┘
       │
       ▼
┌──────────────────┐
│ Gather Input:    │
│ Numéro du match  │
└──────┬───────────┘
       │
       ▼
┌──────────────────────┐
│ HTTP Request:        │
│ GET /matches/{id}    │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Send & Wait:         │
│ Options 1/2/3        │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Split: Choix?        │
│ 1→team_a_win         │
│ 2→team_b_win         │
│ 3→draw               │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ HTTP Request:        │
│ POST /pronostic      │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Send Message:        │
│ Confirmation         │
└──────────────────────┘
```

---

## 📝 Variables Twilio Studio à utiliser

### Variables de Flow
- `flow.variables.match_id` - ID du match sélectionné
- `flow.variables.prediction_type` - Type de prédiction (team_a_win, team_b_win, draw)

### Widgets utilisés
- `{{widgets.get_matches.parsed.message}}` - Liste des matchs formatée
- `{{widgets.get_matches.parsed.matches}}` - Array des matchs
- `{{widgets.get_match_details.parsed.match.team_a}}` - Équipe A
- `{{widgets.get_match_details.parsed.match.team_b}}` - Équipe B
- `{{widgets.save_pronostic.parsed.message}}` - Message de confirmation

---

## 🔍 Exemple de Flow simplifié (Sans Function)

Si vous voulez éviter les Twilio Functions, voici une approche plus simple :

### Utiliser les widgets natifs seulement

1. **Afficher les matchs** avec numéros (1, 2, 3...)
2. **L'utilisateur envoie le numéro** → Stocker dans une variable
3. **Utiliser un widget "Split"** pour mapper le numéro au match_id:
   - Si numéro = 1 → `set match_id = 1`
   - Si numéro = 2 → `set match_id = 2`
   - etc.

**Limitation:** Fonctionne bien pour un nombre limité de matchs (max 5-6)

---

## 🐛 Dépannage

### Le pronostic n'est pas enregistré
- Vérifiez que la migration a été exécutée: `php artisan migrate`
- Vérifiez les logs Laravel: `tail -f storage/logs/laravel.log`
- Testez l'endpoint directement avec Postman

### L'utilisateur voit "Choix invalide"
- Vérifiez que le numéro envoyé correspond bien à un match
- Assurez-vous que les matchs ont le statut "scheduled"
- Vérifiez que `pronostic_enabled` est à `true`

### Le message n'est pas formaté correctement
- Les variables Twilio doivent être entre `{{...}}`
- Vérifiez le nom exact du widget dans votre flow

---

## ✅ Checklist avant de lancer

- [ ] Migration exécutée: `php artisan migrate`
- [ ] Routes API testées dans le navigateur
- [ ] Au moins un match avec `pronostic_enabled=true` et `status=scheduled`
- [ ] L'utilisateur est inscrit et actif dans la base
- [ ] L'URL de l'API est accessible depuis Twilio (pas localhost)
- [ ] Le Flow Twilio est publié

---

## 🎉 Résultat final

L'utilisateur reçoit :

```
⚽ *PROCHAINS MATCHS CAN 2025*

1. Cameroun 🆚 Sénégal
   📅 15/01/2025 à 18:00
   ✅ Pronostics ouverts

2. Nigeria 🆚 Égypte
   📅 16/01/2025 à 21:00
   ✅ Pronostics ouverts

💡 Envoie le numéro du match pour faire ton pronostic !
```

Puis envoie "1"

```
🎯 *FAIRE TON PRONOSTIC*

Match : Cameroun vs Sénégal
📅 15/01/2025 à 18:00

Quel est ton pronostic ?

1️⃣ Victoire Cameroun
2️⃣ Victoire Sénégal
3️⃣ Match nul

Envoie 1, 2 ou 3
```

Puis envoie "1"

```
✅ Pronostic enregistré !

Cameroun vs Sénégal
🎯 Ton pronostic : Victoire Cameroun
```

---

## 📞 Support

Pour toute question :
- Consultez les logs: `php artisan log:tail`
- Testez les endpoints directement
- Vérifiez la console Twilio pour les erreurs de flow
