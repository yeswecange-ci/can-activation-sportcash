# 🔍 Guide de Débogage - Flow Twilio Pronostic

## Problème Signalé
"Après avoir placé mon pronostic, le flow s'arrête, je n'ai aucun retour et rien ne va au niveau du dashboard"

---

## ✅ Tests Backend Effectués

### Test API - Résultat: **SUCCÈS** ✅

L'API Laravel fonctionne parfaitement:
- ✅ `GET /api/can/matches/formatted` retourne 2 matchs
- ✅ `POST /api/can/pronostic` retourne 200 avec message de confirmation
- ✅ Le pronostic est enregistré en base de données
- ✅ Le message de réponse est bien formaté

**Exemple de réponse API:**
```json
{
    "success": true,
    "message": "✅ Pronostic enregistré !\n\nCote d'ivoire vs Mali\n🎯 Ton pronostic : Victoire Cote d'ivoire",
    "pronostic": {
        "id": 2,
        "match": "Cote d'ivoire vs Mali",
        "prediction_type": "team_a_win",
        "prediction_text": "Victoire Cote d'ivoire"
    }
}
```

### Conclusion
Le problème n'est PAS dans l'API Laravel. Le problème est dans la communication entre Twilio Studio et l'API.

---

## 🔍 Étapes de Débogage

### Étape 1: Vérifier les Logs Laravel

Les logs ont été améliorés pour capturer toutes les requêtes entrantes.

**Commande:**
```bash
tail -f storage/logs/laravel.log
```

**Ce qu'on devrait voir quand Twilio appelle l'API:**
```
[2025-12-15 06:00:00] local.INFO: === DÉBUT savePronostic ===
[2025-12-15 06:00:00] local.INFO: Validation passed
[2025-12-15 06:00:00] local.INFO: Twilio Studio - Pronostic saved (simple)
```

**Si vous NE voyez PAS ces logs:**
- Twilio n'appelle PAS l'API
- Problème de configuration dans le flow Twilio

**Si vous voyez une erreur de validation:**
- Les paramètres envoyés par Twilio ne correspondent pas à ce qu'on attend
- Vérifier le format du `body` dans le widget `http_save_prono`

---

### Étape 2: Vérifier les Logs Twilio Studio

1. Aller dans **Twilio Console**
2. **Monitor** → **Logs** → **Debugger**
3. Filtrer par votre numéro WhatsApp ou par date/heure
4. Chercher les erreurs liées au flow

**Erreurs possibles:**

#### A) HTTP Request Failed (11200)
```
Error 11200: HTTP retrieval failure
```
**Cause:** Twilio ne peut pas atteindre votre API
**Solution:**
- Vérifier que l'URL est accessible depuis l'extérieur (pas localhost)
- Vérifier que le serveur répond bien sur HTTPS
- Tester l'URL manuellement: `curl https://can-wabracongo.ywcdigital.com/api/can/pronostic`

#### B) Invalid Response
```
Widget failed to parse response
```
**Cause:** La réponse JSON n'est pas valide
**Solution:**
- Vérifier que l'API retourne bien du JSON valide
- Vérifier le Content-Type: `application/json`

#### C) Variable Not Found
```
Liquid error: variable 'widgets.http_save_prono.parsed.message' not found
```
**Cause:** Le widget ne peut pas parser la réponse
**Solution:**
- Essayer `{{widgets.http_save_prono.body}}` au lieu de `{{widgets.http_save_prono.parsed.message}}`
- Vérifier que la réponse contient bien un champ `message`

---

### Étape 3: Tester le Widget HTTP Manuellement

Dans Twilio Studio, vous pouvez tester le widget `http_save_prono` en isolation:

1. Ouvrir le flow dans **Studio**
2. Cliquer sur le widget `http_save_prono`
3. Utiliser le **Test Runner** avec des variables de test:
   ```
   flow.variables.phone_number = "+243828500007"
   flow.variables.selected_match_id = "2"
   flow.variables.prediction_type = "team_a_win"
   ```

**Résultat attendu:**
- Status: `success`
- Response body visible
- Transition vers `msg_confirmation_prono`

**Si échec:**
- Status: `failed`
- Transition vers `msg_erreur_prono`
- Vérifier l'erreur dans les logs

---

### Étape 4: Vérifier les Variables du Flow

Le widget `http_save_prono` utilise ces variables:
```
phone={{flow.variables.phone_number}}
match_id={{flow.variables.selected_match_id}}
prediction_type={{flow.variables.prediction_type}}
```

**Vérifier que ces variables sont bien définies:**

1. `flow.variables.phone_number` → Définie dans `set_phone` au début du flow ✅
2. `flow.variables.selected_match_id` → Définie dans `set_match_1` à `set_match_5`
3. `flow.variables.prediction_type` → Définie dans `set_prono_team_a/b/draw`

**Problème possible:**
- Si l'utilisateur choisit le match 3 mais qu'il n'y a que 2 matchs, `widgets.http_get_matchs.parsed.matches[2]` n'existe pas
- Cela cause une erreur silencieuse

---

### Étape 5: Vérifier le Format de la Réponse API

Twilio Studio parse automatiquement le JSON si le Content-Type est `application/json`.

**Vérifier la réponse avec curl:**
```bash
curl -v -X POST "https://can-wabracongo.ywcdigital.com/api/can/pronostic" \
  -d "phone=243828500007" \
  -d "match_id=2" \
  -d "prediction_type=team_a_win"
```

**Ce qu'on devrait voir:**
```
< HTTP/1.1 200 OK
< Content-Type: application/json

{"success":true,"message":"✅ Pronostic enregistré !...","pronostic":{...}}
```

**Si Content-Type est différent** (text/html, etc.) → **PROBLÈME**

---

## 🔧 Solutions Possibles

### Solution 1: Problème de Parsing JSON

Si Twilio ne peut pas parser `{{widgets.http_save_prono.parsed.message}}`, modifier le widget `msg_confirmation_prono`:

**Au lieu de:**
```
{{widgets.http_save_prono.parsed.message}}
```

**Essayer:**
```
{{widgets.http_save_prono.body}}
```

Cela affichera le JSON brut, mais au moins vous verrez si la réponse arrive.

---

### Solution 2: Ajouter un Header Content-Type Explicite

Dans `TwilioStudioController.php`, changer:

```php
return response()->json([...]);
```

En:

```php
return response()->json([...])
    ->header('Content-Type', 'application/json; charset=utf-8');
```

---

### Solution 3: Simplifier le Message

Si les sauts de ligne `\n` posent problème, simplifier le message:

```php
'message' => "Pronostic enregistré ! " . $match->team_a . " vs " . $match->team_b . " - Ton pronostic : " . $predictionText
```

---

### Solution 4: Vérifier l'Index des Matchs

Si vous avez 2 matchs, les choix valides sont 1 et 2.
Mais `set_match_3`, `set_match_4`, `set_match_5` vont essayer d'accéder à des index qui n'existent pas.

**Ajouter une vérification** dans `check_choix_match`:
- Si choix > nombre de matchs → `msg_choix_invalide`

---

## 🎯 Checklist de Vérification

- [ ] **Le flow Twilio est publié** (pas en mode draft)
- [ ] **L'URL de l'API est accessible** depuis l'extérieur (tester avec curl)
- [ ] **Les logs Laravel montrent les requêtes** de Twilio (tail -f storage/logs/laravel.log)
- [ ] **Les logs Twilio Debugger** ne montrent pas d'erreur HTTP
- [ ] **Le widget http_save_prono** a le bon Content-Type: `application/x-www-form-urlencoded`
- [ ] **Les variables flow** sont bien définies (phone_number, selected_match_id, prediction_type)
- [ ] **Le pronostic est enregistré** en base de données (vérifier table `pronostics`)

---

## 📊 Test Manuel Complet

### 1. Tester l'API directement

```bash
# Test GET matches
curl "https://can-wabracongo.ywcdigital.com/api/can/matches/formatted?limit=5"

# Test POST pronostic
curl -X POST "https://can-wabracongo.ywcdigital.com/api/can/pronostic" \
  -d "phone=243828500007" \
  -d "match_id=2" \
  -d "prediction_type=team_a_win"
```

### 2. Vérifier en Base de Données

```sql
-- Voir les pronostics récents
SELECT p.id, u.name, u.phone, m.team_a, m.team_b, p.prediction_type, p.created_at
FROM pronostics p
JOIN users u ON p.user_id = u.id
JOIN matches m ON p.match_id = m.id
ORDER BY p.created_at DESC
LIMIT 10;
```

### 3. Tester via WhatsApp

1. Envoyer un message pour s'inscrire
2. Attendre la liste des matchs
3. Choisir un match (envoyer "1")
4. Choisir un pronostic (envoyer "1")
5. **Vérifier:**
   - Vous recevez un message de confirmation
   - Le pronostic apparaît en base de données
   - Les logs Laravel montrent la requête
   - Les logs Twilio ne montrent pas d'erreur

---

## 🐛 Problèmes Connus

### Problème 1: Timeout Twilio
Si le serveur Laravel met trop de temps à répondre (> 10 secondes), Twilio va timeout.

**Solution:**
- Optimiser les requêtes SQL
- Ajouter des index sur les tables
- Vérifier les performances du serveur

### Problème 2: Caractères Spéciaux
Les emojis et caractères spéciaux peuvent poser problème.

**Solution:**
- S'assurer que tout est en UTF-8
- Tester sans emojis d'abord

### Problème 3: CORS
Si Twilio ne peut pas faire la requête HTTP à cause de CORS.

**Solution:**
- Les endpoints API ne devraient PAS avoir de protection CORS pour Twilio
- Vérifier que les routes ne sont pas dans le groupe `middleware('web')`

---

## 📞 Prochaines Étapes

1. **Tester le flow** via WhatsApp maintenant
2. **Surveiller les logs** Laravel en temps réel pendant le test
3. **Vérifier les logs** Twilio Debugger après le test
4. **Rapporter le résultat:**
   - Que voyez-vous dans les logs Laravel ?
   - Que voyez-vous dans Twilio Debugger ?
   - Le pronostic est-il enregistré en base ?
   - Quel message recevez-vous (ou ne recevez-vous pas) ?

Avec ces informations, nous pourrons identifier précisément le problème.
