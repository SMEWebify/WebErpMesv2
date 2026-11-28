# Configurateurs externes : contrat ERP ↔ configurateur

> Statut : **lot 1 développé** (novembre 2026). Lots 2 et 3 à faire (§ 9).
> Date : 19 novembre 2026, branche `WEM-2.0`.
> Lecteurs : l'équipe ERP, et l'éditeur d'un configurateur qui veut se brancher sur l'ERP.

## Sommaire

0. Synthèse
1. Principes
2. Réglage côté ERP
3. Parcours utilisateur
4. Contrat API v1
5. Écriture dans l'ERP
6. Reconfigurer une ligne
7. Sécurité
8. Migrations
9. Découpage en lots
10. Questions ouvertes
11. Annexe : exemple complet

---

## 0. Synthèse

- Les demandes de configurateur sont trop différentes d'un métier à l'autre pour un moteur
  unique dans l'ERP. Exemples : poinçons à options et suppléments par plage de cote, gamme
  paramétrée (diamètre, pression, matière), pliage.
- L'ERP ne configure donc rien lui-même. Il **ouvre une session** chez un configurateur tiers,
  puis **relit le résultat** et l'écrit en lignes, détails de ligne et documents de la GED.
- Le schéma est celui de NEST2BEND, déjà en production :
  - session ouverte par le serveur ;
  - URL courte à usage unique ;
  - fin signalée par le navigateur ;
  - résultat relu par le serveur.

  Le navigateur n'apporte jamais de données métier.
- Un bouton **« Configurer »** dans la barre des lignes des devis et des commandes. Il
  n'apparaît que si au moins un configurateur est actif.
- Les configurateurs se règlent en base (écran Intégrations), comme NEST2BEND, DocuSign ou
  SharePoint. Le `.env` ne porte qu'un interrupteur général.
- **Sans configurateur actif, rien ne change** : ni bouton, ni requête, ni colonne lue.

---

## 1. Principes

1. **Le configurateur ne connaît pas la base de l'ERP.**
   - Il reçoit le strict nécessaire : document, client, article présélectionné, quantité, langue.
   - Il renvoie un JSON au format de ce contrat.
   - Il n'appelle jamais l'ERP, sauf par le webhook facultatif (§ 4.6).
2. **Le serveur de l'ERP fait foi.**
   - Un `resultId` n'est accepté que s'il ferme une session ouverte par l'ERP : même session,
     même document, même utilisateur.
   - Rejouer un `resultId` déjà traité est sans effet.
3. **L'écriture passe par les services existants** : création de ligne, détail de ligne, GED.
   Une ligne configurée est une ligne ordinaire. Elle se convertit en commande, se livre et se
   facture comme les autres.
4. **Le contrat est versionné** (`/api/v1`).
   - Seuls des ajouts sont possibles dans une version : l'ERP ignore les champs inconnus et
     tolère les champs absents.
   - Un changement incompatible passe par `/api/v2`.

---

## 2. Réglage côté ERP

### 2.1 Interrupteur général

`CONFIGURATORS_ENABLED`, vrai par défaut, porté par `config('configurators.enabled')`. Faux :
aucun bouton, et les routes `configurators.*` répondent 404.

### 2.2 Écran Intégrations → Configurateurs

Liste de configurateurs (table `external_configurators`). Chacun porte :

| Champ | Rôle |
|---|---|
| `code`, `label` | Identifiant interne, libellé du bouton (« Poinçons », « Raccords HP »…). |
| `base_url` | Racine HTTPS de l'API, ex. `https://configurateur.exemple.com`. |
| `api_key` | Clé fournie par l'éditeur, chiffrée (`encrypted`, `$hidden`). Champ vide à l'enregistrement = conserver. |
| `display` | `iframe` (fenêtre dans l'ERP) ou `tab` (nouvel onglet). |
| `document_types` | `quote`, `order`, ou les deux. |
| `families` | Familles d'articles (`methods_families`) pour lesquelles le proposer. Vide = toujours. |
| `price_mode` | `net` (le prix du configurateur est le prix de vente) ou `gross` (l'ERP applique ensuite la remise du client). Défaut : `gross`. |
| `active` | Case « actif ». |
| `last_check_at`, `last_error` | Résultat du bouton « Tester » (`GET /api/v1/ping`). |

- Aucune permission dédiée : le bouton suit le droit d'écrire les lignes du document.
- L'écran affiche l'**origine de l'ERP** (`APP_URL`), que l'éditeur doit autoriser pour
  l'affichage en iframe.

---

## 3. Parcours utilisateur

1. Sur un devis au statut 1, ou une commande au statut 1, l'utilisateur clique **« Configurer »**
   dans la barre des lignes ; s'il y a plusieurs configurateurs, un menu s'affiche.
   Lot 1 : aucun article présélectionné depuis l'écran (l'API l'accepte déjà, `product_id`) ;
   le filtre par famille servira quand le bouton sera aussi dans le tiroir de ligne.
2. L'ERP ouvre la session (§ 4.3) et affiche le configurateur, en iframe ou dans un onglet.
3. L'utilisateur configure, puis valide.
4. L'ERP relit le résultat (§ 4.5) et montre un **aperçu** : lignes, prix, délais, fichiers.
   L'utilisateur confirme. Rien n'est écrit avant cette confirmation.
5. Les lignes s'ajoutent en fin de document, avec une pastille « Configuré » et le nom du
   configurateur. Le bouton **« Reconfigurer »** de la ligne rouvrira la même configuration (§ 6, lot 2 : pas encore fait).

Abandon (fenêtre fermée, onglet fermé) : rien n'est écrit, la session expire.

---

## 4. Contrat API v1

### 4.1 Généralités

- HTTPS obligatoire en production.
- JSON UTF-8.
- Dates ISO 8601.
- Montants HT en nombres décimaux, dans la devise de l'instance ERP, indiquée dans la session.
- Authentification : `Authorization: Bearer {api_key}` sur chaque appel de l'ERP.
- Délais d'attente côté ERP : 10 s, et 30 s pour un téléchargement de fichier.

### 4.2 `GET /api/v1/ping`

Utilisé par le bouton « Tester ». Réponse 200 :

```json
{ "name": "Configurateur Exemple", "version": "1.4.0", "contract": "v1" }
```

401 = clé refusée.

### 4.3 `POST /api/v1/sessions` (ERP → configurateur)

```json
{
  "reference_externe": "QT-0012/L30",
  "document": { "type": "quote", "code": "QT-0012", "line_order": 30 },
  "customer": { "code": "CLI-0042", "label": "Tôlerie Exemple", "country": "FR" },
  "product":  { "code": "PC-RECT-241", "label": "Poinçon rectangle", "family": "POINCONNAGE" },
  "quantity": 2,
  "locale": "fr",
  "currency": "EUR",
  "user": { "name": "Jean Commercial", "email": "jean@erp.exemple" },
  "configuration_ref": null,
  "return": {
    "origin": "https://erp.exemple.fr",
    "url": "https://erp.exemple.fr/fr/configurators/return/9f1c…"
  }
}
```

- `product` est absent si rien n'est présélectionné.
- `configuration_ref` est renseigné pour « Reconfigurer » (§ 6).
- `customer` ne porte ni adresse ni contact.
- `return.url` sert à l'affichage en onglet (§ 4.4).

Réponse 201 :

```json
{
  "session_id": "0192f1c4-…",
  "url": "https://configurateur.exemple.com/s/AbC123…",
  "expires_at": "2026-11-19T21:10:00Z"
}
```

- `url` est à usage unique, valable quelques minutes.
- L'ERP ne la stocke pas et refuse une `url` d'une autre origine que `base_url`.

### 4.4 Fin de configuration (configurateur → navigateur)

**Iframe** : le configurateur poste un message à la fenêtre parente, à destination de
`return.origin` :

```js
window.parent.postMessage({ type: 'wem-configurator:done', sessionId, resultId }, returnOrigin);
// abandon :
window.parent.postMessage({ type: 'wem-configurator:cancel', sessionId }, returnOrigin);
```

L'ERP ignore tout message dont l'origine n'est pas celle de `base_url`, dont la source n'est
pas l'iframe ouverte, ou qui a une autre forme.

**Onglet** : le configurateur redirige vers `return.url?result_id={resultId}`. Cette page de
l'ERP referme l'onglet et prévient l'écran d'origine.

Dans les deux cas, **le message ne porte aucune donnée métier** : seulement des identifiants.

### 4.5 `GET /api/v1/results/{resultId}` (ERP → configurateur)

Le résultat complet. Seuls les champs marqués « requis » sont obligatoires.

```json
{
  "result_id": "0192f1c9-…",
  "session_id": "0192f1c4-…",
  "reference_externe": "QT-0012/L30",
  "configuration_ref": "CFG-2026-000871",
  "configuration_url": "https://configurateur.exemple.com/c/CFG-2026-000871",
  "lines": [
    {
      "code": "PC-RECT-241-LP",
      "label": "Poinçon rectangle long plat 30 × 5",
      "product_code": "PC-RECT-241",
      "quantity": 2,
      "unit": "U",
      "unit_price": 182.40,
      "purchase_price": 121.00,
      "lead_time_days": 8,
      "options": [
        { "code": "COAT", "label": "Revêtement", "value": "Optima", "unit_price": 48.10 },
        { "code": "SHEAR", "label": "Coupe", "value": "Silence", "unit_price": 10.20 }
      ],
      "dimensions": [
        { "code": "A", "label": "Cote A", "value": 30, "unit": "mm" },
        { "code": "B", "label": "Cote B", "value": 5, "unit": "mm" }
      ],
      "detail": {
        "material": "S355", "thickness": 3, "x_size": 30, "y_size": 5, "z_size": null,
        "diameter": null, "finishing": null, "weight": null
      },
      "requirements": [
        { "label": "Machine", "value": "Presse 20 t, système Trumpf" }
      ],
      "comment_internal": "Prix catalogue 2026, supplément Optima plage 30,01-40",
      "comment_external": "Poinçon long plat, revêtement Optima, coupe silence",
      "supplier": { "code": "FOURN-01", "reference": "25825", "label": "PUNCH RECT LP 30x5" },
      "files": [
        { "id": "f1", "name": "PC-RECT-241-LP.pdf", "role": "plan", "mime": "application/pdf", "size": 182344 },
        { "id": "f2", "name": "PC-RECT-241-LP.step", "role": "modele_3d", "mime": "model/step", "size": 912004 }
      ],
      "routing": null
    }
  ],
  "files": [
    { "id": "f9", "name": "configuration-CFG-2026-000871.pdf", "role": "autre", "mime": "application/pdf", "size": 40211 }
  ]
}
```

| Champ | Requis | Règle |
|---|---|---|
| `lines[]` | oui | Au moins une ligne. Une configuration peut produire **plusieurs lignes**, par exemple poinçon + matrice + dévêtisseur. Au plus 50. |
| `lines[].label` | oui | Au plus 255 caractères ; au-delà, le texte complet va dans le commentaire externe. |
| `lines[].quantity` | oui | Supérieure à 0. |
| `lines[].unit_price` | oui | Prix unitaire HT, **options comprises**. Selon `price_mode` (§ 2.2), l'ERP applique ou non la remise du client. |
| `lines[].options[].unit_price` | non | Supplément déjà compris dans `unit_price`. Il sert à l'affichage, au détail imprimable et à la commande fournisseur. |
| `lines[].product_code` | non | Code d'un article existant de l'ERP ; inconnu = ligne sans article. **Le configurateur ne crée jamais d'article.** |
| `lines[].unit` | non | Code d'unité de l'ERP, sinon l'unité par défaut de l'ERP. |
| `lines[].supplier` | non | Code et désignation chez le fabricant, repris sur la commande fournisseur. |
| `lines[].routing` | non | Réservé au lot 3 (§ 9) : opérations et composants pour une gamme paramétrée. Ignoré tant que le lot n'est pas fait. |
| `files[].role` | non | Rôle de la GED : `plan`, `modele_3d`, `vectoriel`, `photo`, `cam`, `certificat`, `controle`, `autre`. Inconnu = `autre`. |

### 4.6 Fichiers : `GET /api/v1/results/{resultId}/files/{fileId}`

- Même authentification. Réponse : le contenu brut, avec un `Content-Type` qui correspond.
- Limite : 50 Mo par fichier, 200 Mo par résultat ; au-delà, le fichier est ignoré et signalé.
- L'ERP ne télécharge **que** depuis `base_url`, jamais depuis une URL arbitraire.

**Webhook facultatif.** Pour un configurateur qui termine hors navigateur (validation
différée), l'appel `POST {erp}/api/configurators/{code}/results` porte `{ "session_id",
"result_id" }`, signé en HMAC-SHA256 avec la clé dans l'en-tête `X-Configurator-Signature`.
L'ERP relit alors le résultat par § 4.5, exactement comme après un `postMessage`.

### 4.7 Erreurs

| Code | Côté ERP |
|---|---|
| 401 | Clé refusée : carte Intégrations en erreur, bouton masqué jusqu'au prochain test réussi. |
| 404 sur un résultat | « Configuration introuvable ou expirée » ; rien n'est écrit. |
| 409 | Résultat pas encore prêt : l'ERP réessaie 3 fois en 3 s, puis affiche un message. |
| 422 | Message du configurateur affiché tel quel (champ `message`). |
| 429 | « Trop de demandes, réessayez dans un instant » (en-tête `Retry-After` respecté). |
| 5xx, délai dépassé | « Configurateur indisponible » ; la ligne peut être saisie à la main. |

---

## 5. Écriture dans l'ERP

`App\Services\Configurators\ConfiguredLineWriter`, dans une transaction. Les lignes vont en
fin de document. La correspondance avec les tables :

| JSON | ERP |
|---|---|
| `code`, `label`, `quantity`, `unit` | `quote_lines` / `order_lines` : `code`, `label`, `qty`, `methods_units_id` |
| `unit_price` | `selling_price`. Remise : celle du client si `price_mode = gross`, sinon 0. TVA par défaut. |
| `product_code` | `product_id` si l'article existe |
| `lead_time_days` | `delivery_date` = aujourd'hui + délai, seulement si plus tardive que la date par défaut du document |
| `detail.*` | `quote_line_details` / `order_line_details` : `material`, `thickness`, `x_size`, `y_size`, `z_size`, `diameter`, `finishing`, `weight` |
| `options[]`, `dimensions[]`, `requirements[]` | **Lignes structurées** dans `line_configuration_items` (§ 8), et non du texte libre |
| `comment_internal`, `comment_external` | `internal_comment`, `external_comment` du détail |
| `supplier` | Copie sur la ligne de configuration, reprise par la future commande fournisseur |
| `files[]` | GED de la ligne de devis ou de commande (alias `quote-line` / `order-line`), avec le rôle donné ; le premier plan est principal |
| Fichiers du résultat (`files` de premier niveau) | GED du document (`quote` / `order`) |
| `configuration_ref`, `configuration_url` | Marque de la ligne (§ 8) |

- **Prix figé.** Pas de prix calculé (`use_calculated_price` faux).
- **Conversion devis → commande** (lot 2, pas encore fait) : `QuoteLineToOrderLineConverter`
  recopie déjà ligne, détail et fichiers ; il devra recopier en plus les
  `line_configuration_items` et la marque de configuration.
- **Copie de ligne, duplication de devis, trame** (lot 2, pas encore fait) : la marque et les
  éléments de configuration seront recopiés. C'est la même configuration, que l'on pourra rouvrir.
- **PDF** (lot 2, pas encore fait) : options et cotes imprimées sous la désignation, seulement
  si la ligne en a. Un document sans ligne configurée devra s'imprimer à l'identique
  (`SalesPdfLegacyRenderingTest`).

---

## 6. Reconfigurer une ligne

- La session est ouverte avec `configuration_ref` (§ 4.3) : le configurateur rouvre la
  configuration existante.
- **Devis au statut 1, ligne non commandée** : la ligne est **remplacée**. Sont remplacés le
  libellé, le prix, le détail, les éléments de configuration et les fichiers issus du
  configurateur. Les fichiers déposés à la main restent. Si le résultat compte plusieurs
  lignes, les lignes en plus s'ajoutent juste après.
- **Ligne déjà commandée, livrée ou facturée** : « Reconfigurer » crée une **nouvelle ligne**.
  L'ancienne n'est jamais touchée.
- Historique par activitylog (`line_reconfigured`, ancienne et nouvelle référence).

---

## 7. Sécurité

- Clé chiffrée en base, jamais renvoyée au navigateur ni écrite dans les journaux.
- Session (`configurator_sessions`) liée à l'utilisateur, au document et à la ligne :
  - un `resultId` qui ne ferme pas une session ouverte par l'ERP est refusé ;
  - une session ne se ferme qu'une fois.
- `postMessage` filtré par origine **et** par fenêtre (comme `lib/nest2bendMessage.js`).
- Fichiers : téléchargés seulement depuis `base_url`, taille bornée, type déduit de
  l'extension par `FileKindResolver`. Un SVG est servi avec `nosniff` + CSP `sandbox`, comme
  tout fichier de la GED.
- Données envoyées au configurateur : codes et libellés du client et de l'article, quantité,
  nom et e-mail de l'utilisateur. Ni adresse, ni prix d'achat, ni historique.
- Route de retour (onglet) et webhook limités en débit, webhook signé.

---

## 8. Migrations (toutes additives)

| Migration | Contenu |
|---|---|
| `create_external_configurators_table` | Champs du § 2.2. |
| `create_configurator_sessions_table` | `configurator_id`, `session_id` (unique), `user_id`, document (`quote` / `order` + id), `line_id` (nullable, pour « Reconfigurer »), `reference_externe`, `status` (`pending`, `done`, `cancelled`, `expired`), `result_id` (unique, nullable), `closed_at`. Purge après 30 jours. |
| `add_configuration_to_quote_and_order_lines` | `configurator_id`, `configuration_ref`, `configured_at`, nullables sur `quote_lines` et `order_lines`, hors `$fillable`. |
| `create_line_configuration_items_table` | Polymorphe (`configurable_type` / `configurable_id` : ligne de devis, de commande, et plus tard d'achat), `kind` (`option`, `dimension`, `requirement`, `supplier`), `code`, `label`, `value`, `unit`, `unit_price`, `sort`. |

Cette dernière table est aussi celle du besoin B4 (options structurées sur la ligne) : la
commande fournisseur en contremarque (B5) la lira de la même façon, que la ligne vienne d'un
configurateur ou non.

---

## 9. Découpage en lots

| Lot | Contenu | Jours |
|---|---|---|
| 1 | Réglage (§ 2), test de liaison, bouton, session, iframe et onglet, relecture du résultat, aperçu, écriture des lignes, détails, options, fichiers ; textes fr / en ; tests (configurateur simulé par `Http::fake`) | 5 à 6 |
| 2 | « Reconfigurer », conversion et copies, impression des options et cotes dans les PDF, webhook | 3 |
| 3 | `routing` : opérations et composants issus du configurateur, écrits en gamme de la ligne | 3 à 4, à préciser |
| 4 | Commande fournisseur en contremarque qui reprend options et référence fournisseur (B5) | selon le chantier B5 |

Un **configurateur de démonstration** (petite application Laravel ou simple page statique avec
un résultat fixe) servira aux tests manuels et à montrer le contrat aux éditeurs : 1 jour, en
dehors de ce dépôt.

---

## 10. Questions ouvertes

1. **Prix** : `price_mode` par configurateur (proposé : `gross` par défaut, l'ERP applique la
   remise du client). À confirmer avec les premiers prospects.
2. **Marge** : `purchase_price` doit-il alimenter le coût de la ligne pour la marge du devis
   (`QuoteMargin`), en l'absence de gamme ? Proposé : oui, en coût unitaire sans opération.
3. **Création d'article** : refusée dans le lot 1 (le configurateur renvoie au plus un code
   existant). À rouvrir si un client veut un article par configuration.
4. **Hors ligne** : faut-il un import de fichier JSON (même format que § 4.5) pour un
   configurateur de bureau sans API ? Peu coûteux, à garder en réserve.
5. **Langue du contrat** : document en français. Une version anglaise sera à faire pour un
   éditeur étranger.

---

## 11. Annexe : exemple complet

Commercial sur le devis `QT-0012`, client `CLI-0042`, article présélectionné `PC-RECT-241`,
quantité 2.

1. Clic sur « Configurer » → « Poinçons ».
2. `POST /api/v1/sessions` (§ 4.3) → `session_id`, `url`.
3. Iframe ouverte sur `url`. Le commercial choisit long plat, A = 30, B = 5, revêtement
   Optima, coupe silence, puis valide.
4. `postMessage({ type: 'wem-configurator:done', sessionId, resultId })`.
5. L'ERP vérifie que la session est ouverte, à cet utilisateur, sur ce devis, puis appelle
   `GET /api/v1/results/{resultId}` (§ 4.5) et télécharge `f1`, `f2` et `f9`.
6. Aperçu : 1 ligne, 182,40 € HT × 2, remise client 15 % → 310,08 € HT, délai 8 jours, 3 fichiers.
7. Confirmation : une ligne de devis avec son détail, 4 éléments de configuration (2 options,
   2 cotes), 2 fichiers sur la ligne et 1 sur le devis. Pastille « Configuré ». Session `done`.

Toutes les valeurs de cet exemple sont fictives.
