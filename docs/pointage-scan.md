# Pointage par scan — méthode commune ERP / MES Nest2Prod

Contrat validé avec le MES Nest2Prod (spec v2, novembre 2026). Un client pointe avec
**un seul système** (ERP ou MES) ; les deux appliquent la même logique, les mêmes écrans,
la même fiche suiveuse et les mêmes codes, et échangent les mêmes événements `task.*`.

## Principes

- Le système qui pointe fait tout : badges, scans, temps. L'autre reçoit les `task.*`.
- **Propriété** : l'ERP possède le planning de la tâche (code opération, poste, temps,
  quantité et dates prévus) ; le système qui pointe possède l'exécution (statut, début,
  fin, temps réel, quantités).
- Le temps réel est **calculé par le serveur** depuis les horodatages ; le scan n'en saisit
  jamais. Une correction manuelle par un responsable n'émet aucun événement de temps.
- Quantités **entières**, envoyées en **écarts**, jamais en cumul.

## Codes imprimés

| Objet | Contenu | Symboles |
|---|---|---|
| Opération | `N2P:{of_code}*{line_ref}*{operation_code}*{seq}` — ex. `N2P:OF1234*1234*PLI*20` | QR + Code128 |
| Opération sans ligne | `N2P:OF1234**PLI*20` (OF importé hors ERP) | QR + Code128 |
| Badge | `N2P-B:{badge_code}` | QR + Code128 |

- `of_code` = `OF` + id de ligne de commande ; `line_ref` = id de ligne (vide possible) ;
  `seq` = `tasks.ordre` (10, 20…), envoyé dans `tasks[].seq` du payload des OF : entier ≥ 1,
  omis sinon (le MES refuse le lot en 422 sur un 0 ou un non-entier, et prend la position
  quand il est absent).
- Recherche : (`line_ref`, `seq`) si `line_ref` renseigné, sinon (`of_code`, `seq`), puis
  contrôle de `operation_code` (sinon refus). Sans `seq` : première opération de ce code.
- Séparateur `*` ; `|` accepté en lecture. `*` et `|` interdits dans les codes : refusés à
  la création d'un poste (`StoreServicesRequest`), codes existants signalés par
  `wem:diagnostics`. Une opération concernée n'a pas de code de scan sur la fiche.
- **Code128 obligatoire** quel que soit `factory.task_barre_code` (EAN ne code ni `:` ni `*`).
- Douchettes en mode clavier, **disposition française (AZERTY)**, Entrée après lecture.
  Test réel sur PC AZERTY (`:`, `*`, chiffres, majuscules) avant mise en service.

## Logique d'un scan

Mode par instance (ERP) / par tenant (MES) : `open` (ouvre la tâche), `toggle`
(1er scan = début, 2e = fin), `toggle_qty` (2e scan = saisie qté bonne / rebut puis fin).

| État de la tâche | Effet du scan |
|---|---|
| Non démarrée | Début |
| En cours | Fin (+ quantités en `toggle_qty`) |
| En pause | Reprise |
| Terminée / annulée | Message, aucune écriture |
| BPF manquant | Refus avec le motif |

Anti-double scan : même opération et même opérateur à moins de 5 s → ignoré. Badge
facultatif, actif jusqu'au badge suivant ou 15 min d'inactivité ; sans badge, l'opérateur
est l'utilisateur connecté.

## Écrans et fiche suiveuse

- Poste fixe : page plein écran, champ toujours actif ; opérateur actif en haut, retour du
  dernier scan au centre (vert démarrée, bleu terminée, orange reprise, rouge refus), 5 derniers
  scans en bas ; fenêtre de quantités en `toggle_qty` (bonne = reste à faire, rebut 0, Entrée).
- Téléphone : même page, lecture des QR par la caméra.
- Fiche suiveuse : QR (≈ 15 mm) dans la ligne de l'opération, Code128 pleine largeur sous la
  ligne ; QR commande inchangé ; **par option** (désactivée = fiche actuelle, références
  `ProductionSheetLegacyRenderingTest` intactes).

## Bon pour fabrication

Option ERP `production.require_release`. Active : l'ERP envoie `production_released` (booléen,
au niveau de l'OF) **à chaque envoi** de l'OF, et le renverra quand le bon est donné ou annulé
(étape 3) ; inactive : champ absent, le MES remet « pas de contrôle ». Côté ERP, un `task.started` reçu sans bon est mis en échec (rejouable).

## Événements (identiques dans les deux sens)

```json
{
  "event_id": "uuid v4",
  "event_type": "task.started",
  "occurred_at": "2026-11-13T08:02:11+01:00",
  "source": "wem | n2p",
  "data": {
    "of_code": "OF1234", "line_ref": "1234",
    "operation_code": "PLI", "seq": 20,
    "user_email": "operateur@client.fr",
    "capture_mode": "scan | manual"
  }
}
```

| Action | Événements |
|---|---|
| Début / reprise / pause | `task.started` / `task.resumed` / `task.paused` |
| Fin | `task.finished` (+ `started_at`, `actual_time_min` informatifs) |
| Quantités | `task.declared_good { good_qty }`, `task.declared_bad { bad_qty }` (après la fin, même `occurred_at`) |
| Annulation | `task.canceled` (sans quantités) |

Opérateur = `user_email` seulement (badge actif, sinon utilisateur connecté). Le code badge
ne voyage pas.

## Règles de réception

1. Dédoublonnage par `event_id` (`IntegrationDispatcher`).
2. Horodatage = `occurred_at`, borné à maintenant.
3. Temps recalculé depuis les débuts / fins triés ; `actual_time_min` comparé, écart > 1 min
   journalisé (canal `n2p`).
4. Un événement plus ancien qu'un début / une fin déjà enregistrés est historisé mais ne fait
   pas reculer le statut.
5. Opérateur inconnu : utilisateur technique `INTEGRATIONS_FALLBACK_USER_EMAIL` (sinon repli
   historique : responsable de la tâche), toujours journalisé.
6. Tâche introuvable ou `operation_code` différent au rang `seq` : échec, rejouable.
7. `task.canceled` : sessions ouvertes refermées à `occurred_at` (le temps passé reste compté),
   aucune quantité, trace dans l'historique. L'ERP n'a pas de statut « annulé » pour une tâche :
   statut inchangé ; l'ERP n'émet pas `task.canceled`.

Quantités comptées seulement sur `declared_good` / `declared_bad` : une quantité portée par
`task.finished` est stockée sur la ligne de fin mais n'entre dans aucun total.

## Renvoi d'un OF

Le MES rapproche les tâches par (`tasks[].seq`, `operation_code`) — deux opérations au même
rang gardent chacune leur rang et leur code de scan ; une tâche sans `seq` se place juste après
la précédente du payload. **Le code opération fait partie de l'identité** : changer le code
d'une opération à un rang donné = nouvelle opération au MES (l'ancienne supprimée si elle n'a
pas été démarrée, gardée sinon), et les fiches déjà imprimées ne la retrouvent plus. Le MES met à jour le planning,
conserve l'exécution (statut qui n'avance que, temps pris seulement s'il est supérieur), supprime
une opération retirée seulement si rien n'a été pointé, et n'émet aucun `task.*` pour ces mises
à jour. L'ERP peut donc renvoyer un OF avec ses tâches librement.

**Alignement des rangs, une fois** : avant le déploiement de cette règle, le MES numérotait
ses tâches 10, 20, 30… dans l'ordre du payload. Une fois le MES déployé, renvoyer les OF
ouverts **avant d'imprimer des fiches avec les codes `N2P:`** :

```bash
php artisan wem:n2p:push-order --open --dry-run   # liste les commandes
php artisan wem:n2p:push-order --open             # confirmation, puis mise en file
```

`--open` = commandes au statut qui déclenche l'envoi à N2P (« en cours » par défaut) ou
partiellement livrées ; une commande encore ouverte n'a jamais été envoyée et n'est pas poussée.
⚠️ Jamais avant que le MES conserve l'exécution au renvoi (sinon il recrée les tâches).

## État d'avancement

| Étape | ERP | MES |
|---|---|---|
| 1 | `seq` dans le payload et la recherche, réception (`task.canceled`, `line_ref` vide, statut sans recul, opérateur de repli, contrôle du temps), interdiction `*` / `|` + diagnostic | Lecture des codes, 3 modes, écran poste, codes sur la fiche |
| 2 | Écran de scan, 3 modes, badges (`users.badge_code`), codes sur la fiche suiveuse | Pause / reprise, segments de temps, badges |
| 3 | Émission des `task.*`, `production_released` + renvoi de l'OF | Réception des `task.*` de l'ERP |

Code : `App\Services\Integrations\Handlers\HandleTaskEvent`, `Task::resolveByExternalRef()`,
`N2PPayloadBuilder::mapTasks()`. Tests : `tests/Feature/IntegrationInboundTest.php`,
`tests/Unit/N2PPayloadBuilderTest.php`.
