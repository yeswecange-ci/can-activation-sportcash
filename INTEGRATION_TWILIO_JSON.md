# Intégration Twilio Studio avec l'API Pronostic (JSON)

## 🎯 Résumé

L'API `/api/can/pronostic` accepte maintenant du **JSON** pour faciliter l'intégration avec Twilio Studio. Vous pouvez envoyer des requêtes avec `Content-Type: application/json`.

## ✅ Configuration effectuée

1. **Middleware ForceJsonResponse** : Garantit que l'API accepte le JSON
2. **Route configurée** : Le middleware `force.json` est appliqué à la route `/api/can/pronostic`
3. **Tests réussis** : L'API accepte et traite correctement les requêtes JSON

## 📡 Endpoint

```
POST /api/can/pronostic
Content-Type: application/json
```

## 📝 Format de la requête

### Option 1 : Pronostic simple (recommandé pour Twilio)

```json
{
  "phone": "+243828500007",
  "match_id": 1,
  "prediction_type": "team_a_win"
}
```

**Valeurs possibles pour `prediction_type`:**
- `team_a_win` : Victoire de l'équipe A
- `team_b_win` : Victoire de l'équipe B
- `draw` : Match nul

### Option 2 : Pronostic avec scores

```json
{
  "phone": "+243828500007",
  "match_id": 1,
  "score_a": 2,
  "score_b": 1
}
```

## 📤 Réponse de l'API

### Succès (200 OK)

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

### Erreur - Utilisateur non trouvé (404)

```json
{
  "success": false,
  "message": "Utilisateur non trouvé. Veuillez vous inscrire d'abord."
}
```

### Erreur - Match fermé (400)

```json
{
  "success": false,
  "message": "Ce match n'accepte plus de pronostics."
}
```

### Erreur - Validation (422)

```json
{
  "message": "The phone field is required.",
  "errors": {
    "phone": ["The phone field is required."]
  }
}
```

## 🔧 Configuration Twilio Studio

### Étape 1 : Ajouter un bloc "Make HTTP Request"

Dans votre Twilio Studio Flow :

1. Ajoutez un widget **"Make HTTP Request"**
2. Donnez-lui un nom, par exemple : `save_pronostic`

### Étape 2 : Configurer la requête

**URL de l'API :**
```
https://votre-domaine.com/api/can/pronostic
```

**Method :** `POST`

**Content-Type :** `application/json`

**Body (JSON) :**
```json
{
  "phone": "{{trigger.message.From}}",
  "match_id": {{widgets.match_choice.parsed.match_id}},
  "prediction_type": "{{widgets.prediction_choice.parsed.prediction}}"
}
```

### Étape 3 : Gérer la réponse

Après le bloc HTTP Request, utilisez un **Split** pour vérifier le résultat :

**Variable à vérifier :** `{{widgets.save_pronostic.parsed.success}}`

**Branche TRUE (succès) :**
```
Send Message: {{widgets.save_pronostic.parsed.message}}
```

**Branche FALSE (erreur) :**
```
Send Message: Désolé, une erreur s'est produite : {{widgets.save_pronostic.parsed.message}}
```

## 📋 Exemple complet de flow Twilio

```
1. [Trigger: Incoming Message]
   ↓
2. [Split: Check if user exists]
   ↓
3. [Get Matches List] → Make HTTP Request
   GET /api/can/matches/formatted
   ↓
4. [Send Message] → Display matches
   ↓
5. [Gather Input] → User selects match number
   ↓
6. [Split] → User enters "1" for match 1
   ↓
7. [Send Message] → "Choisis ton pronostic..."
   ↓
8. [Gather Input] → User selects prediction
   ↓
9. [Make HTTP Request] → POST /api/can/pronostic
   {
     "phone": "{{trigger.message.From}}",
     "match_id": 1,
     "prediction_type": "team_a_win"
   }
   ↓
10. [Split: Check success]
    ↓
    TRUE: Send success message
    FALSE: Send error message
```

## 🧪 Tester l'intégration

### Méthode 1 : Avec cURL

```bash
curl -X POST https://votre-domaine.com/api/can/pronostic \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "+243828500007",
    "match_id": 1,
    "prediction_type": "team_a_win"
  }'
```

### Méthode 2 : Avec le script de test PHP

```bash
php test_json_direct.php
```

## 🔍 Variables Twilio utiles

Dans Twilio Studio, vous pouvez utiliser ces variables :

| Variable | Description | Exemple |
|----------|-------------|---------|
| `{{trigger.message.From}}` | Numéro du sender | `whatsapp:+243828500007` |
| `{{trigger.message.Body}}` | Message reçu | `1` |
| `{{widgets.nom_widget.parsed.cle}}` | Données parsées du widget | `team_a_win` |
| `{{widgets.nom_widget.body}}` | Réponse brute HTTP | JSON complet |
| `{{widgets.nom_widget.parsed.success}}` | Champ success du JSON | `true` ou `false` |

## 💡 Conseils

1. **Numéro de téléphone** : Twilio envoie le numéro au format `whatsapp:+243...`. L'API gère automatiquement ce format.

2. **Match ID** : Vous pouvez récupérer dynamiquement les matchs avec l'endpoint :
   ```
   GET /api/can/matches/formatted
   ```

3. **Validation** : L'API valide automatiquement :
   - Si l'utilisateur existe et est actif
   - Si le match existe et accepte encore des pronostics
   - Si les données sont valides

4. **Logs** : Tous les pronostics sont loggés dans Laravel. Vérifiez les logs avec :
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 🔐 Sécurité

L'API est protégée contre :
- ✅ Injection SQL (via Eloquent ORM)
- ✅ XSS (via validation Laravel)
- ✅ Données invalides (via validation stricte)
- ✅ Pronostics en double (mise à jour automatique)

## 📊 Monitoring

Pour monitorer les pronostics :

1. **Dashboard Admin** : `/admin/pronostics`
2. **API de test** : `/api/can/pronostic/test`
3. **Logs Laravel** : `storage/logs/laravel.log`

## ❓ FAQ

**Q: Puis-je toujours utiliser form-urlencoded ?**
R: Oui, l'API accepte les deux formats (JSON et form-urlencoded).

**Q: Comment gérer les erreurs ?**
R: Vérifiez toujours le champ `success` dans la réponse JSON.

**Q: Puis-je envoyer plusieurs pronostics pour le même match ?**
R: Oui, le dernier pronostic remplace les précédents pour le même match.

**Q: Comment récupérer la liste des matchs ?**
R: Utilisez l'endpoint `GET /api/can/matches/formatted` qui retourne un message formaté pour WhatsApp.

## 🚀 Prochaines étapes

1. Déployez votre application sur un serveur accessible (Coolify, etc.)
2. Configurez votre flow Twilio Studio avec l'URL de production
3. Testez avec un vrai numéro WhatsApp
4. Activez le monitoring et les logs

## 📞 Support

Pour toute question ou problème :
1. Vérifiez les logs Laravel : `storage/logs/laravel.log`
2. Testez avec le script : `php test_json_direct.php`
3. Consultez la documentation Twilio : https://www.twilio.com/docs/studio

---

✅ **L'API est maintenant prête pour l'intégration Twilio avec JSON !**
