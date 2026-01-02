# 🔧 Modification Manuelle du Flow (Alternative à l'Import)

## Si l'import JSON ne fonctionne pas, suivez ces étapes pour modifier votre flow existant

---

## Étape 1 : Ajouter `check_single_match_existing`

**Position :** Après `check_has_matchs_existing`

1. Ajouter un widget **Split Based On**
2. Nom : `check_single_match_existing`
3. Input : `{{widgets.http_get_matchs_existing.parsed.single_match}}`
4. Conditions :
   - **Match 1** : Friendly name = "Un seul match"
     - Type : `equal_to`
     - Value : `true`
     - Transition : → `set_match_auto_existing`

   - **Match 2** : Friendly name = "Plusieurs matchs"
     - Type : `equal_to`
     - Value : `false`
     - Transition : → `msg_liste_matchs_existing`

   - **No Match** : → `msg_liste_matchs_existing`

---

## Étape 2 : Ajouter `set_match_auto_existing`

**Position :** Après `check_single_match_existing`

1. Ajouter un widget **Set Variables**
2. Nom : `set_match_auto_existing`
3. Variables :
   ```
   selected_match_id = {{widgets.http_get_matchs_existing.parsed.match.id}}
   selected_team_a = {{widgets.http_get_matchs_existing.parsed.match.team_a}}
   selected_team_b = {{widgets.http_get_matchs_existing.parsed.match.team_b}}
   ```
4. Transition : → `http_check_existing_prono`

---

## Étape 3 : Ajouter `check_single_match_new`

**Position :** Après `check_has_matchs_new`

1. Ajouter un widget **Split Based On**
2. Nom : `check_single_match_new`
3. Input : `{{widgets.http_get_matchs_new.parsed.single_match}}`
4. Conditions :
   - **Match 1** : Value = `true` → `set_match_auto_new`
   - **Match 2** : Value = `false` → `msg_liste_matchs_new`
   - **No Match** : → `msg_liste_matchs_new`

---

## Étape 4 : Ajouter `set_match_auto_new`

**Position :** Après `check_single_match_new`

1. Ajouter un widget **Set Variables**
2. Nom : `set_match_auto_new`
3. Variables :
   ```
   selected_match_id = {{widgets.http_get_matchs_new.parsed.match.id}}
   selected_team_a = {{widgets.http_get_matchs_new.parsed.match.team_a}}
   selected_team_b = {{widgets.http_get_matchs_new.parsed.match.team_b}}
   ```
4. Transition : → `send_single_match_message`

---

## Étape 5 : Ajouter `send_single_match_message`

**Position :** Après `set_match_auto_new`

1. Ajouter un widget **Send & Wait for Reply**
2. Nom : `send_single_match_message`
3. From : `{{flow.channel.address}}`
4. Body : `{{widgets.http_get_matchs_new.parsed.message}}`
5. Timeout : `3600` secondes
6. Transitions :
   - **Incoming Message** : → `check_choix_prono`
   - **Timeout** : → `msg_timeout_prono`
   - **Delivery Failure** : → `http_log_timeout`

---

## Étape 6 : Modifier `check_choix_prono`

**IMPORTANT :** Ajouter 3 nouvelles conditions pour gérer `send_single_match_message`

Dans le widget `check_choix_prono`, ajouter :

**Nouvelles Conditions (en plus des existantes) :**

1. **Condition "Victoire équipe A (single)"**
   - Friendly name : "Victoire équipe A (single)"
   - Arguments : `{{widgets.send_single_match_message.inbound.Body}}`
   - Type : `equal_to`
   - Value : `1`
   - Transition : → `set_prono_team_a`

2. **Condition "Victoire équipe B (single)"**
   - Friendly name : "Victoire équipe B (single)"
   - Arguments : `{{widgets.send_single_match_message.inbound.Body}}`
   - Type : `equal_to`
   - Value : `2`
   - Transition : → `set_prono_team_b`

3. **Condition "Match nul (single)"**
   - Friendly name : "Match nul (single)"
   - Arguments : `{{widgets.send_single_match_message.inbound.Body}}`
   - Type : `equal_to`
   - Value : `3`
   - Transition : → `set_prono_draw`

---

## Étape 7 : Répéter pour les utilisateurs réactivés

**Ajouter les mêmes widgets pour le scénario "reactivated" :**

1. `check_single_match_reactivated` (après `check_has_matchs_reactivated`)
2. `set_match_auto_reactivated` (→ `http_check_existing_prono`)

Utilisez la même logique que pour "existing".

---

## Étape 8 : Améliorer les messages d'erreur (Optionnel mais recommandé)

### Ajouter `msg_error_api`

1. Widget **Send Message**
2. Nom : `msg_error_api`
3. Body :
   ```
   ⚠️ Erreur technique temporaire.

   Réessaye dans quelques instants.

   📞 Support : contact@sportcash.ci
   ```
4. Transitions :
   - **Sent** : → `end_error`
   - **Failed** : → `end_error`

### Modifier `http_check_user`

Changer la transition **Failed** : → `msg_error_api` (au lieu de `http_log_scan`)

### Ajouter `msg_error_matchs`

1. Widget **Send Message**
2. Body :
   ```
   ⚠️ Impossible de charger les matchs.

   Réessaye plus tard.

   📞 Support : contact@sportcash.ci
   ```
3. Transitions : → `end_error`

### Modifier tous les `http_get_matchs_*`

Changer les transitions **Failed** : → `msg_error_matchs`

---

## Résumé des Widgets à Ajouter

### Pour utilisateurs existants (déjà inscrits) :
- [x] `check_single_match_existing`
- [x] `set_match_auto_existing`

### Pour nouveaux utilisateurs :
- [x] `check_single_match_new`
- [x] `set_match_auto_new`
- [x] `send_single_match_message`

### Pour utilisateurs réactivés :
- [x] `check_single_match_reactivated`
- [x] `set_match_auto_reactivated`

### Gestion d'erreurs (optionnel) :
- [x] `msg_error_api`
- [x] `msg_error_matchs`
- [x] `msg_error_inscription`

### Modification existante :
- [x] `check_choix_prono` (ajouter 3 conditions)

---

## Schéma Visuel Simplifié

### Avant (Flow Actuel)
```
check_has_matchs_existing
  ├─ has_matches = true → msg_liste_matchs_existing
  └─ has_matches = false → end
```

### Après (Flow Optimisé)
```
check_has_matchs_existing
  └─ has_matches = true → check_single_match_existing ✨ NOUVEAU
                           ├─ single_match = true → set_match_auto_existing ✨
                           │                        └─ http_check_existing_prono
                           └─ single_match = false → msg_liste_matchs_existing
```

---

## Test Après Modification

1. **Activer 1 seul match** dans la base de données
2. Envoyer un message WhatsApp (utilisateur déjà inscrit)
3. **Résultat attendu** : Affichage direct des options 1/2/3

---

## Temps Estimé

- **Ajout des widgets principaux** : 15-20 minutes
- **Amélioration erreurs** : 5 minutes
- **Tests** : 10 minutes

**Total : ~30-35 minutes**

---

C'est plus long que l'import JSON, mais plus sûr et vous gardez le contrôle total !
