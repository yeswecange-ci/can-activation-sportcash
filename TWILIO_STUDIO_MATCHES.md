# Comment afficher la liste des matchs dans un Flow Twilio Studio

Ce document explique comment intégrer l'affichage de la liste des matchs dans votre Flow Twilio Studio pour WhatsApp.

## 📋 Endpoints disponibles

### 1. **Matchs formatés pour WhatsApp** (Recommandé)
**URL:** `GET https://votre-domaine.com/api/can/matches/formatted`

Retourne un message texte formaté prêt à être envoyé sur WhatsApp.

**Paramètres optionnels:**
- `limit` : Nombre de matchs à afficher (défaut: 5)
- `days` : Nombre de jours à venir (défaut: 7)

**Exemple de réponse:**
```json
{
  "success": true,
  "has_matches": true,
  "count": 3,
  "message": "⚽ *PROCHAINS MATCHS CAN 2025*\n\n1. Cameroun 🆚 Sénégal\n   📅 15/01/2025 à 18:00\n   ✅ Pronostics ouverts\n\n2. Nigeria 🆚 Égypte\n   📅 16/01/2025 à 21:00\n   ✅ Pronostics ouverts\n\n💡 Envoie PRONO pour faire ton pronostic !",
  "matches": [...]
}
```

---

### 2. **Matchs à venir (JSON structuré)**
**URL:** `GET https://votre-domaine.com/api/can/matches/upcoming`

Retourne les données structurées en JSON.

**Paramètres optionnels:**
- `limit` : Nombre de matchs (défaut: 10)
- `days` : Nombre de jours (défaut: 7)

**Exemple de réponse:**
```json
{
  "success": true,
  "has_matches": true,
  "count": 3,
  "matches": [
    {
      "id": 1,
      "number": 1,
      "team_a": "Cameroun",
      "team_b": "Sénégal",
      "match_date": "15/01/2025",
      "match_time": "18:00",
      "status": "scheduled",
      "pronostic_enabled": true
    }
  ]
}
```

---

### 3. **Matchs du jour uniquement**
**URL:** `GET https://votre-domaine.com/api/can/matches/today`

Retourne uniquement les matchs d'aujourd'hui.

---

## 🔧 Intégration dans Twilio Studio

### Méthode 1 : Utiliser un widget "HTTP Request" (Simple)

1. **Ajouter un widget "Make HTTP Request"** dans votre Flow
2. **Configurer le widget:**
   - **REQUEST METHOD:** `GET`
   - **REQUEST URL:** `https://votre-domaine.com/api/can/matches/formatted?limit=5`
   - **CONTENT TYPE:** `application/x-www-form-urlencoded`

3. **Ajouter un widget "Send Message"** après le HTTP Request
4. **Configurer le message:**
   ```
   {{widgets.http_request_1.parsed.message}}
   ```

5. **Ajouter une condition** pour gérer l'absence de matchs:
   - **Condition:** `{{widgets.http_request_1.parsed.has_matches}}` égale à `false`
   - **Message alternatif:** "⚽ Aucun match programmé pour le moment."

---

### Méthode 2 : Utiliser une Twilio Function (Avancé)

Si vous préférez plus de contrôle, créez une Twilio Function :

```javascript
exports.handler = function(context, event, callback) {
    const axios = require('axios');

    const apiUrl = 'https://votre-domaine.com/api/can/matches/formatted';
    const params = {
        limit: event.limit || 5,
        days: event.days || 7
    };

    axios.get(apiUrl, { params })
        .then(response => {
            callback(null, {
                message: response.data.message,
                has_matches: response.data.has_matches,
                count: response.data.count
            });
        })
        .catch(error => {
            console.error('Error fetching matches:', error);
            callback(null, {
                message: "⚽ Erreur lors de la récupération des matchs. Réessayez plus tard.",
                has_matches: false,
                count: 0
            });
        });
};
```

---

## 📱 Exemple de Flow Twilio Studio

Voici un exemple de flow complet pour afficher les matchs :

```
Trigger: Incoming Message
    ↓
[Split Based On...] - Vérifier si le message contient "MATCHS" ou "MATCHES"
    ↓ (Si oui)
[Make HTTP Request]
    - Method: GET
    - URL: https://votre-domaine.com/api/can/matches/formatted?limit=5
    ↓
[Split Based On...] - Vérifier si has_matches = true
    ↓ (Si oui)
    [Send Message]
        - Message Body: {{widgets.http_matches.parsed.message}}
    ↓ (Si non)
    [Send Message]
        - Message Body: ⚽ Aucun match programmé pour le moment.
```

---

## 🎯 Variables disponibles dans Twilio Studio

Après avoir appelé l'endpoint `/api/can/matches/formatted`, vous aurez accès aux variables suivantes :

- `{{widgets.nom_du_widget.parsed.success}}` - Boolean
- `{{widgets.nom_du_widget.parsed.has_matches}}` - Boolean
- `{{widgets.nom_du_widget.parsed.count}}` - Nombre de matchs
- `{{widgets.nom_du_widget.parsed.message}}` - Message formaté complet
- `{{widgets.nom_du_widget.parsed.matches}}` - Array des matchs (optionnel)

---

## 🔍 Personnalisation

Vous pouvez personnaliser l'affichage en modifiant les paramètres URL :

**Afficher 10 matchs sur 14 jours:**
```
https://votre-domaine.com/api/can/matches/formatted?limit=10&days=14
```

**Afficher seulement 3 matchs:**
```
https://votre-domaine.com/api/can/matches/formatted?limit=3
```

---

## 📝 Notes importantes

1. **Remplacez** `https://votre-domaine.com` par l'URL réelle de votre application Laravel
2. Assurez-vous que votre serveur Laravel est accessible depuis Twilio (pas de localhost)
3. Les endpoints sont publics et ne nécessitent pas d'authentification
4. Le message est formaté automatiquement avec des emojis pour WhatsApp

---

## 🐛 Dépannage

### L'appel API échoue
- Vérifiez que l'URL est correcte et accessible publiquement
- Testez l'URL directement dans votre navigateur
- Consultez les logs Twilio pour voir l'erreur exacte

### Le message ne s'affiche pas
- Vérifiez que vous utilisez bien `{{widgets.nom_du_widget.parsed.message}}`
- Assurez-vous que le nom du widget HTTP Request est correct
- Vérifiez que `has_matches` est bien géré dans votre flow

### Les matchs affichés sont incorrects
- Vérifiez les données dans la base de données Laravel
- Assurez-vous que les matchs ont le bon statut ('scheduled' ou 'live')
- Vérifiez les dates des matchs

---

## 📞 Support

Pour toute question ou problème, consultez les logs de l'application :
```bash
php artisan log:clear
# Puis testez votre flow
tail -f storage/logs/laravel.log
```
