# Nest2Prod ERP — API pour les intégrateurs

Ce document décrit ce qu'un logiciel tiers (outil de chiffrage, CAO/FAO, automate, outil
d'automatisation de type n8n, MES) peut appeler sur une instance Nest2Prod ERP avec ce que
l'entreprise lui fournit : un **jeton d'API** ou un **secret de signature**.

> Dernière mise à jour : 2026-11-28.
> Les écrans de l'application utilisent d'autres routes, réservées au navigateur connecté :
> elles ne font pas partie de cette API et peuvent changer sans préavis.

## Sommaire

1. [Généralités](#1-généralités)
   - [Adresse de base](#adresse-de-base)
   - [Authentification par jeton](#authentification-par-jeton)
   - [Format des échanges](#format-des-échanges)
   - [Limites d'appels](#limites-dappels)
   - [Erreurs communes](#erreurs-communes)
   - [Versions et stabilité](#versions-et-stabilité)
2. [Clients](#2-clients)
3. [Devis](#3-devis)
4. [Articles](#4-articles)
5. [Documents attachés (GED)](#5-documents-attachés-ged)
6. [Demandes entrantes (pistes)](#6-demandes-entrantes-pistes)
7. [Prospects](#7-prospects)
8. [Stock d'un lot de tôle](#8-stock-dun-lot-de-tôle)
9. [Imbrications et chutes](#9-imbrications-et-chutes)
10. [Événements entrants signés (webhooks)](#10-événements-entrants-signés-webhooks)
11. [Ce que l'API ne permet pas encore](#11-ce-que-lapi-ne-permet-pas-encore)

---

## 1. Généralités

### Adresse de base

```
https://{adresse-de-l-instance}/api
```

Toutes les URL de ce document sont relatives à cette adresse. Elles ne portent **pas** de
préfixe de langue (contrairement aux pages de l'application). HTTPS est requis en production.

### Authentification par jeton

Les routes des sections 2 à 9 exigent un jeton d'API, envoyé à chaque appel :

```http
Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1
Accept: application/json
```

- **Création** : un administrateur de l'instance crée le jeton dans **Votre société → Tokens
  API** (nom, durée : une semaine, un mois ou sans expiration). Le jeton n'est affiché
  **qu'une fois**, à la création : le conserver aussitôt.
- **Droits** : le jeton agit **au nom de l'utilisateur qui l'a créé**, avec ses permissions.
  Il est conseillé de créer un utilisateur dédié à l'intégration, avec les seuls droits
  utiles (par exemple « lecture et écriture de la base article » pour déposer des fichiers sur
  un article), puis de créer le jeton depuis ce compte.
- **Révocation** : la suppression du jeton dans le même écran le rend inutilisable
  immédiatement. Un jeton expiré ou supprimé reçoit `401`.
- Les jetons créés depuis l'écran portent toutes les capacités. Les routes
  [Demandes entrantes](#6-demandes-entrantes-pistes) et [Prospects](#7-prospects) vérifient les
  capacités `leads:inbound` et `prospects:write`, et [Imbrications et chutes](#9-imbrications-et-chutes)
  les capacités `nests:write` et `remnants:read` : elles ne sont refusées (`403`) qu'à un jeton
  créé avec une liste de capacités restreinte.

> ⚠️ Envoyer **toujours** `Accept: application/json`. Sans cet en-tête, une erreur
> d'authentification ou de validation peut renvoyer une redirection HTML vers la page de
> connexion au lieu d'une réponse JSON.

### Format des échanges

- Corps en JSON (`Content-Type: application/json`), sauf les envois de fichiers en
  `multipart/form-data` (sections 5 et 6). Jamais de fichier en base64, sauf la vignette d'une
  ligne de devis (`picture_base64`).
- Dates au format `AAAA-MM-JJ` (ou ISO 8601 complet) ; nombres décimaux avec un point.
- Les réponses **Devis** et **Articles** sont enveloppées dans une clé `data`
  (`{"data": {...}}`). Les autres routes renvoient l'objet directement, comme montré dans
  chaque exemple.
- Les valeurs numériques stockées en décimal peuvent revenir sous forme de chaîne
  (`"85.000"`) : les lire comme des nombres.
- Ignorer les champs inconnus d'une réponse : des champs peuvent s'ajouter.

### Limites d'appels

| Portée | Limite |
|---|---|
| Toutes les routes `/api` | 60 requêtes par minute, par utilisateur du jeton (par adresse IP pour les appels sans jeton) |
| `POST /leads/inbound` | 60 requêtes par minute, en plus de la limite générale |
| `POST /prospects` | 60 requêtes par minute, en plus de la limite générale |
| `POST /nests` | 60 requêtes par minute, en plus de la limite générale |
| `POST /integrations/{système}/inbound` | 120 requêtes par minute ; la limite générale de 60 par minute s'applique aussi |

Chaque réponse porte `X-RateLimit-Limit` et `X-RateLimit-Remaining`. Au-delà : `429 Too Many
Requests` avec l'en-tête `Retry-After` (secondes à attendre).

### Erreurs communes

| Code | Cas | Corps |
|---|---|---|
| `400` | Paramètre de chemin mal formé (section 8) | `{"error": "code_erreur"}` |
| `401` | Jeton absent, inconnu, expiré ou révoqué ; signature invalide (section 10) | `{"message": "Unauthenticated."}` ou `{"error": "..."}` |
| `403` | L'utilisateur du jeton n'a pas le droit requis | `{"message": "..."}` |
| `404` | Ressource inconnue, ou route inexistante | `{"message": "..."}` |
| `409` | Opération refusée par une règle métier (ex. supprimer un fichier versionné) | `{"message": "..."}` |
| `422` | Données refusées par la validation | voir ci-dessous |
| `429` | Limite d'appels atteinte | `{"message": "Too Many Attempts."}` |
| `500` | Erreur du serveur | `{"message": "Server Error"}` |

Format d'une erreur de validation (`422`) :

```json
{
  "message": "Le champ label est obligatoire.",
  "errors": {
    "label": ["Le champ label est obligatoire."],
    "lines.0.qty": ["Le champ lines.0.qty est obligatoire quand lines est présent."]
  }
}
```

Les messages sont rédigés dans la langue par défaut de l'instance : se fier aux **clés** de
`errors`, pas au texte.

### Versions et stabilité

- L'API **n'a pas de préfixe de version** (`/api/v1` n'existe pas). Il n'y a qu'une version :
  celle de l'instance appelée.
- Les évolutions sont faites autant que possible par **ajout** (nouveaux champs, nouvelles
  routes). Un changement incompatible est signalé dans les notes de version du logiciel.
- La version installée sur une instance est indiquée sur l'écran de connexion.

---

## 2. Clients

### `GET /clients`

Liste **complète** (non paginée) des clients actifs, triée par raison sociale, avec leurs
contacts et adresses. Sert à alimenter une liste de choix (devis, commandes).

| Paramètre de requête | Type | Obligatoire | Description |
|---|---|---|---|
| `search` | texte | non | Filtre sur la raison sociale ou le code client (contient) |

Exemple :

```http
GET /api/clients?search=forge
Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1
Accept: application/json
```

Réponse `200` :

```json
[
  {
    "id": 12,
    "code": "CLI-0042",
    "label": "Forges de l'Exemple SAS",
    "contacts": [
      {
        "id": 3,
        "first_name": "Camille",
        "name": "Durand",
        "mail": "camille.durand@exemple.fr",
        "number": "0300000000",
        "mobile": "0600000000",
        "default": true
      }
    ],
    "addresses": [
      {
        "id": 5,
        "label": "Siège",
        "adress": "1 rue de l'Exemple",
        "zipcode": "75000",
        "city": "Paris",
        "country": "France",
        "default": true
      }
    ]
  }
]
```

Seuls les clients au statut « client actif » sont listés (pas les prospects ni les
fournisseurs).

---

## 3. Devis

Création et mise à jour d'un devis complet : en-tête, lignes, détail technique de chaque
ligne, opérations de gamme et liens entre opérations. Conçu pour un outil de chiffrage
externe.

### `POST /quote`

#### En-tête

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `code` | texte | non | 255 car. max. Vide : numéro attribué par la numérotation de l'instance. Doit être unique (voir [À savoir](#à-savoir-sur-les-devis)) |
| `label` | texte | **oui** | 255 car. max |
| `companies_id` | entier | **oui** | Identifiant d'un client (voir `GET /clients`) |
| `companies_contacts_id` | entier | non | Contact existant. Absent : contact par défaut du client pour les devis, sinon son contact par défaut, sinon le premier |
| `companies_addresses_id` | entier | non | Adresse existante. Absent : même règle que le contact |
| `accounting_payment_conditions_id` | entier | non | Absent : condition de règlement du client, sinon celle par défaut de l'instance |
| `accounting_payment_methods_id` | entier | non | Absent : mode de règlement du client, sinon celui par défaut de l'instance |
| `accounting_deliveries_id` | entier | non | Absent : mode de livraison par défaut de l'instance |
| `customer_reference` | texte | non | Référence du client, 255 car. max |
| `validity_date` | date | non | Date de validité |
| `statu` | entier | non | 1 ouvert, 2 envoyé, 3 gagné, 4 perdu, 5 fermé, 6 obsolète |
| `opportunities_id` | entier | non | Opportunité existante à laquelle rattacher le devis |
| `comment` | texte | non | Commentaire |
| `lines` | tableau | non | Lignes du devis, voir ci-dessous. Absent : devis sans ligne |

Un client sans aucun contact ou sans aucune adresse, quand le champ correspondant n'est pas
fourni, fait refuser l'appel (`422` sur `companies_contacts_id` ou `companies_addresses_id`).

#### Ligne (`lines[]`)

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `id` | entier | non | Identifiant d'une ligne **de ce devis** à mettre à jour ; absent = nouvelle ligne |
| `ordre` | entier | **oui** | Rang de la ligne |
| `label` | texte | **oui** | Désignation, 255 car. max |
| `qty` | nombre | **oui** | ≥ 0 |
| `code` | texte | non | Référence de la pièce, 255 car. max |
| `ind` | texte | non | Indice du plan, 50 car. max |
| `product_id` | entier | non | Article existant |
| `methods_units_id` | entier | non | Unité ; absent = unité par défaut |
| `selling_price` | nombre | non | Prix unitaire HT, ≥ 0 |
| `discount` | nombre | non | Remise en %, de 0 à 100 |
| `accounting_vats_id` | entier | non | Taux de TVA ; absent = taux par défaut |
| `delivery_date` | date | non | Délai demandé |
| `statu` | entier | non | Statut de la ligne |
| `use_calculated_price` | booléen | non | Prix relu sur la gamme |
| `detail` | objet | non | Détail technique, voir ci-dessous |
| `tasks` | tableau | non | Opérations de gamme, voir ci-dessous |

#### Détail technique (`lines[].detail`)

Tous facultatifs. Nombres : `x_size`, `y_size`, `z_size`, `x_oversize`, `y_oversize`,
`z_oversize`, `diameter`, `diameter_oversize`, `thickness`, `weight`, `material_loss_rate` ;
entier : `bend_count` ; textes : `material`, `finishing` (255 car.), `internal_comment`,
`external_comment` ; `custom_requirements` (objet libre) ; `cad_file`, `cam_file`,
`cad_file_path`, `cam_file_path` (textes, 255 car.).

`picture_base64` (texte) : vignette de la ligne, PNG, JPEG, GIF, BMP, WebP ou SVG, en base64
avec ou sans préfixe `data:image/...;base64,`, **10 Mo au plus**. Une image illisible, trop
lourde ou un SVG refusé ne fait pas échouer l'appel : la ligne est créée sans vignette.

#### Opération (`lines[].tasks[]`)

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `id` | entier | non | Identifiant d'une opération **de cette ligne** à mettre à jour ; absent = nouvelle |
| `label` | texte | **oui** | 255 car. max |
| `operation_code` | texte | non | Code d'un poste de charge de l'instance : renseigne le poste et le type de l'opération. Un code inconnu laisse le poste vide, sans erreur |
| `code` | texte | non | Code de l'opération ; sert aussi à trouver le poste si `operation_code` est absent |
| `ordre` | entier | non | Rang dans la gamme ; absent = rang précédent + 1 |
| `type` | entier | non | 1 productif, 2 matière, 3 tôle, 4 profilé, 5 bloc, 6 fournitures, 7 sous-traitance, 8 composant. Absent : type du poste trouvé |
| `methods_services_id` | entier | non | Poste de charge (prioritaire sur `operation_code`) |
| `methods_units_id`, `methods_tools_id`, `products_id` | entier | non | Unité, outil, article composant existants |
| `qty`, `qty_init` | nombre | non | ≥ 0 |
| `qty_aviable` | entier | non | ≥ 0 |
| `seting_time` | nombre | non | Temps de réglage, ≥ 0 |
| `unit_time` | nombre | non | Temps unitaire, ≥ 0 |
| `remaining_time` | nombre | non | ≥ 0 |
| `unit_cost`, `unit_price` | nombre | non | Coût et prix unitaires, ≥ 0 |
| `status_id` | entier | non | Statut existant |
| `priority` | entier | non | 1, 2 ou 3 |
| `delay`, `due_date` | date | non | |
| `material` | texte | non | 255 car. max |
| `thickness`, `weight`, `x_size` … `z_oversize`, `diameter`, `diameter_oversize` | nombre | non | |
| `to_schedule`, `not_recalculate` | booléen | non | |
| `planned_qty` | nombre | non | Quantité traitée par cette opération (lot partagé entre deux machines). Absent ou `null` = quantité de la ligne |
| `predecessors` | tableau | non | Liens vers les opérations amont, voir ci-dessous |

Les temps s'expriment dans l'unité de temps réglée sur l'instance : se la faire préciser par
l'entreprise.

#### Liens entre opérations (`lines[].tasks[].predecessors[]`)

Sans lien, une opération suit la précédente (fin-début). Les opérations amont sont désignées
par leur `ordre` dans la même ligne ; seules les opérations de type 1 ou 7 peuvent être liées.

| Champ | Type | Obligatoire | Notes |
|---|---|---|---|
| `ordre` | entier | **oui** | Rang de l'opération amont |
| `type` | texte | non | `FS` fin-début (défaut), `SS` début-début, `FF` fin-fin |
| `lag_value` | nombre | non | Décalage, ≥ 0, 0 par défaut |
| `lag_unit` | texte | non | `hour` (défaut) ou `piece` (seulement avec `SS` et `FF`) |

Un rang inconnu, une opération liée à elle-même, un décalage en pièces sur un `FS`, un lien
sur une opération qui n'est pas de type 1 ou 7, ou une boucle entre opérations renvoient `422`
(clé `predecessors`) et **rien n'est enregistré**.

#### Exemple

```http
POST /api/quote
Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1
Accept: application/json
Content-Type: application/json
```

```json
{
  "label": "Châssis tôlé A",
  "companies_id": 12,
  "customer_reference": "DEM-2026-118",
  "validity_date": "2026-12-31",
  "lines": [
    {
      "ordre": 1,
      "code": "P-0001",
      "label": "Flanc latéral",
      "qty": 4,
      "selling_price": 85.00,
      "detail": {
        "material": "S235",
        "thickness": 3,
        "x_size": 500,
        "y_size": 300,
        "finishing": "Galvanisé"
      },
      "tasks": [
        { "label": "Découpe laser", "operation_code": "LASER", "ordre": 10, "seting_time": 0.25, "unit_time": 0.1 },
        { "label": "Pliage", "operation_code": "PLIAGE", "ordre": 20, "seting_time": 0.5, "unit_time": 0.2,
          "predecessors": [ { "ordre": 10, "type": "SS", "lag_value": 2, "lag_unit": "piece" } ] }
      ]
    }
  ]
}
```

Réponse `201` (extrait) :

```json
{
  "data": {
    "id": 42,
    "uuid": "0b6f4a52-3c1e-4f7a-9d55-2a1c8e7b9f10",
    "code": "QT-0042",
    "label": "Châssis tôlé A",
    "customer_reference": "DEM-2026-118",
    "companies_id": { "id": 12, "code": "CLI-0042", "label": "Forges de l'Exemple SAS", "...": "..." },
    "companies_contacts_id": { "civility": null, "first_name": "Camille", "name": "Durand", "number": "0300000000", "mobile": "0600000000", "mail": "camille.durand@exemple.fr" },
    "companies_addresses_id": { "adress": "1 rue de l'Exemple", "zipcode": "75000", "city": "Paris", "province": null, "country": "France", "number": null, "mail": null },
    "accounting_payment_conditions_id": { "label": "30 jours net" },
    "accounting_payment_methods_id": { "label": "Virement" },
    "accounting_deliveries_id": { "label": "Départ usine" },
    "validity_date": "2026-12-31",
    "statu": 1,
    "comment": null,
    "created_at": "2026-11-27T09:00:00.000000Z",
    "updated_at": "2026-11-27T09:00:00.000000Z",
    "quote_lines": [
      {
        "id": 101,
        "quotes_id": 42,
        "ordre": 1,
        "code": "P-0001",
        "ind": null,
        "product_id": null,
        "label": "Flanc latéral",
        "equipment_tag": [],
        "qty": "4.000",
        "methods_units_id": 1,
        "selling_price": "85.000",
        "discount": "0.000",
        "accounting_vats_id": 1,
        "delivery_date": null,
        "statu": 1,
        "use_calculated_price": false,
        "line_type": "article",
        "hide_on_pdf": false,
        "pdf_package": 0,
        "detail": { "id": 88, "quote_lines_id": 101, "material": "S235", "thickness": "3.000", "x_size": "500.000", "...": "..." },
        "tasks": [
          {
            "id": 55,
            "label": "Découpe laser",
            "ordre": 10,
            "quote_lines_id": 101,
            "methods_services_id": 3,
            "type": 1,
            "setting_time": "0.250",
            "unit_time": "0.100",
            "planned_qty": null,
            "predecessors": [],
            "...": "..."
          },
          {
            "id": 56,
            "label": "Pliage",
            "ordre": 20,
            "predecessors": [
              { "ordre": 10, "task_id": 55, "type": "SS", "lag_value": "2.000", "lag_unit": "piece" }
            ],
            "...": "..."
          }
        ],
        "created_at": "2026-11-27T09:00:00.000000Z",
        "updated_at": "2026-11-27T09:00:00.000000Z"
      }
    ]
  }
}
```

Dans la réponse, `companies_id`, `companies_contacts_id`, `companies_addresses_id` et les trois
champs `accounting_*` sont des **objets** (pas des identifiants), et le temps de réglage
d'une opération s'appelle `setting_time` (il s'envoie `seting_time`). `line_type` vaut
`article`, `section`, `subtotal` ou `text` (les trois derniers sont saisis dans l'application).

### `PUT /quote/{id}`

Met à jour le devis `{id}`. **Mêmes champs et mêmes règles que `POST`** : `label` et
`companies_id` restent obligatoires. Seuls les champs d'en-tête envoyés sont modifiés.

Synchronisation des lignes et des opérations :

| Envoi | Effet |
|---|---|
| Clé `lines` absente | Lignes inchangées |
| Ligne avec `id` | Ligne mise à jour |
| Ligne sans `id` | Ligne créée |
| Ligne article existante absente de `lines` | Ligne **supprimée** |
| `"lines": []` | **Toutes** les lignes article sont supprimées |
| Sections, sous-totaux, textes saisis dans l'application | Jamais supprimés par l'API |
| `detail` fourni | Détail créé ou mis à jour ; `detail` absent ou `null` : inchangé |
| Clé `tasks` absente | Opérations inchangées |
| Opération avec / sans `id` | Mise à jour / création |
| Opération existante absente de `tasks` (ou `"tasks": []`) | Opération **supprimée** |
| Clé `predecessors` absente | Liens de l'opération inchangés |
| `"predecessors": []` | Liens supprimés : l'opération suit la précédente |

Réponse `200`, même structure que `POST`.

### Erreurs des devis

| Code | Cas |
|---|---|
| `404` | Devis inconnu ; `lines[].id` d'une ligne d'un autre devis ; `tasks[].id` d'une opération d'une autre ligne |
| `422` | Champ manquant ou invalide ; client sans contact ou sans adresse ; lien entre opérations refusé |
| `500` | `code` déjà utilisé par un autre devis |

### À savoir sur les devis

- L'appel est **tout ou rien** : en cas d'erreur, ni l'en-tête ni aucune ligne n'est
  enregistré.
- Le `code` n'est pas contrôlé avant l'enregistrement : un code déjà pris fait échouer l'appel
  en `500`. Laisser `code` vide pour utiliser la numérotation de l'instance, ou garantir
  l'unicité côté appelant.
- L'API ne contrôle pas le statut du devis : un devis envoyé, gagné ou déjà converti en
  commande peut être modifié. Ne pas renvoyer un devis que l'entreprise a déjà traité.
- `statu` est écrit tel quel : passer un devis en perdu (4) par l'API ne demande pas de motif
  de perte.
- `code` et `uuid` sont toujours présents dans la réponse.

---

## 4. Articles

Création et mise à jour d'articles de la base article, par exemple les pièces issues d'un
logiciel de CAO ou d'imbrication.

### `POST /product`

#### Champs

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `code` | texte | non | 255 car. max, **unique**. Vide : numéro attribué par la numérotation de l'instance |
| `label` | texte | **oui** | 255 car. max |
| `service_code` | texte | non | Code d'un poste de charge, converti en `methods_services_id` |
| `family_code` | texte | non | Code d'une famille, converti en `methods_families_id` |
| `unit_code` | texte | non | Code d'une unité, converti en `methods_units_id` |
| `methods_services_id` | entier | non | Poste existant (prioritaire sur `service_code`) |
| `methods_families_id` | entier | non | Famille existante (prioritaire sur `family_code`) |
| `methods_units_id` | entier | non | Unité existante (prioritaire sur `unit_code`) |
| `sold` | entier | non | 1 vendu, 2 non vendu ; défaut 1 |
| `purchased` | entier | non | 1 acheté, 2 non acheté ; défaut 1 |
| `tracability_type` | entier | non | 1 sans traçabilité, 2 par lot, 3 par numéro de série ; défaut 1 |
| `ind` | texte | non | Indice, 50 car. max |
| `material`, `finishing` | texte | non | 255 car. max |
| `thickness`, `weight` | nombre | non | ≥ 0 |
| `bend_count` | entier | non | Nombre de plis, ≥ 0 |
| `x_size`, `y_size`, `z_size`, `x_oversize`, `y_oversize`, `z_oversize`, `diameter`, `diameter_oversize`, `section_size` | nombre | non | ≥ 0 |
| `qty_eco_min`, `qty_eco_max` | nombre | non | Quantités économiques, ≥ 0 |
| `purchased_price`, `selling_price` | nombre | non | Prix d'achat et de vente HT, ≥ 0 |
| `comment` | texte | non | |
| `picture`, `drawing_file`, `stl_file`, `svg_file`, `cad_file_path`, `cam_file_path`, `csv_file_name` | texte | non | 255 car. max. Simples noms ou chemins : **aucun fichier n'est transmis** par ces champs (pour un fichier, voir [Documents attachés](#5-documents-attachés-ged)) |

Résolution du poste, de la famille et de l'unité, dans l'ordre :

| | 1. Identifiant | 2. Code | 3. À défaut |
|---|---|---|---|
| Poste | `methods_services_id` | `service_code` | premier poste de type « composant » |
| Famille | `methods_families_id` | `family_code` | première famille du poste retenu |
| Unité | `methods_units_id` | `unit_code` | unité par défaut de l'instance |

Un code inconnu n'est pas une erreur : la valeur par défaut est prise.

#### Exemple

```json
{
  "label": "Flanc latéral châssis A",
  "service_code": "COMPO",
  "family_code": "TOLERIE",
  "unit_code": "PCE",
  "material": "S235",
  "thickness": 3,
  "weight": 4.5,
  "bend_count": 4,
  "x_size": 500,
  "y_size": 300,
  "z_size": 60,
  "purchased": 2,
  "selling_price": 18.00
}
```

Réponse `201` (extrait) :

```json
{
  "data": {
    "id": 142,
    "code": "ART-0142",
    "label": "Flanc latéral châssis A",
    "ind": null,
    "methods_services_id": 7,
    "methods_families_id": 3,
    "methods_units_id": 1,
    "purchased": 2,
    "purchased_price": null,
    "sold": 1,
    "selling_price": "18.000",
    "material": "S235",
    "thickness": "3.000",
    "weight": "4.500",
    "bend_count": 4,
    "x_size": "500.000",
    "y_size": "300.000",
    "z_size": "60.000",
    "tracability_type": 1,
    "drawing_file": null,
    "created_at": "2026-11-27T09:00:00.000000Z",
    "updated_at": "2026-11-27T09:00:00.000000Z",
    "...": "..."
  }
}
```

### `PUT /product/{id}`

Met à jour l'article `{id}` ; `label` reste obligatoire, et `code` doit rester unique (l'article
lui-même excepté). Les champs absents sont conservés, **sauf** :

- `sold`, `purchased` et `tracability_type` absents **reviennent à 1** ;
- poste, famille et unité absents (ni identifiant ni code) reprennent les valeurs par défaut
  du tableau ci-dessus.

Renvoyer donc ces six informations à chaque mise à jour. Réponse `200`, même structure.

### Erreurs des articles

| Code | Cas |
|---|---|
| `404` | Article inconnu |
| `422` | `label` manquant, `code` déjà pris, valeur hors liste ou négative, identifiant inexistant |

---

## 5. Documents attachés (GED)

Dépôt de fichiers (plan PDF, DXF, STEP, photo…) sur une entité de l'ERP, liste, modification
et retrait. Les fichiers déposés par l'API sont rangés exactement comme ceux déposés dans
l'application.

### Entités et rôles

`fileable_type` (entité visée) :

| Valeur | Entité |
|---|---|
| `company` | Société |
| `lead` | Piste |
| `opportunity` | Opportunité |
| `quote` / `quote-line` | Devis / ligne de devis |
| `order` / `order-line` | Commande / ligne de commande |
| `delivery` | Bon de livraison |
| `invoice` / `credit-note` | Facture / avoir |
| `product` | Article (lecture : droit de lecture de la base article ; dépôt, modification, retrait : droit d'écriture) |
| `purchase` / `purchase-quotation` / `purchase-receipt` | Commande d'achat / demande de prix / réception |
| `stock-move` | Mouvement de stock |
| `batch` | Lot (droit de gestion des lots et numéros de série) |
| `non-conformity` / `derogation` | Non-conformité / dérogation |
| `inventory` | Inventaire |

Les dossiers du personnel (salarié, contrat de travail, note de frais) ne sont accessibles
qu'au salarié concerné et aux ressources humaines : un compte d'intégration reçoit `403`.

`role` (facultatif) : `plan`, `modele_3d`, `vectoriel`, `photo`, `cam`, `certificat`,
`controle`, `document_signe`, `accord_client`, `contractuel`, `ar_fournisseur`,
`offre_fournisseur`, `bl_fournisseur`, `justificatif`, `autre`. Absent : rôle déduit du type de
fichier.

Extensions acceptées : `stl obj 3mf ply glb gltf step stp iges igs brep dxf svg pdf jpg jpeg
png gif bmp webp csv xls xlsx ods eml msg nc tap gcode ngc cnc zip 7z rar gz doc docx odt rtf
txt json xml sym geo bvc`. Taille maximale par fichier : 50 Mo par défaut (réglable par
l'entreprise).

### `GET /files/json/list`

| Paramètre de requête | Obligatoire | Description |
|---|---|---|
| `fileable_type` | **oui** | Voir le tableau des entités |
| `fileable_id` | **oui** | Identifiant de l'entité |

Seule la dernière version de chaque fichier est listée, les principaux d'abord.

Réponse `200` :

```json
{
  "files": [
    {
      "id": 310,
      "name": "P-0001_indA.pdf",
      "stored_name": "a4c1e2f0d9b84f7c.pdf",
      "extension": "pdf",
      "kind": "doc",
      "mime": "application/pdf",
      "size": 284512,
      "formatted_size": "278 KB",
      "comment": "Plan client",
      "hashtags": ["plan", "client"],
      "role": "plan",
      "is_primary": true,
      "version": 1,
      "revision_label": "A",
      "is_superseded": false,
      "has_history": false,
      "uploaded_by": "Intégration CAO",
      "created_at": "2026-11-27T09:05:00+01:00",
      "view_url": "https://erp.exemple.fr/files/310/raw",
      "download_url": "https://erp.exemple.fr/files/310/download",
      "...": "..."
    }
  ]
}
```

Les liens (`view_url`, `download_url`, `thumbnail_url`…) ouvrent le fichier **dans
l'application**, pour un utilisateur connecté : ils ne se téléchargent pas avec le jeton. Avec le
jeton, lire le contenu par [`GET /files/json/{id}/download`](#get-filesjsoniddownload).

### `POST /files/json/store`

`multipart/form-data` :

| Champ | Type | Obligatoire | Règles |
|---|---|---|---|
| `fileable_type` | texte | **oui** | Voir le tableau des entités |
| `fileable_id` | entier | **oui** | ≥ 1 |
| `files[]` | fichier | **oui** | 1 à 20 fichiers, extensions et taille ci-dessus |
| `role` | texte | non | Voir la liste des rôles |
| `is_primary` | booléen | non | `true` / `false` / `1` / `0`. Seul le premier fichier de l'envoi devient principal |
| `comment` | texte | non | 65 535 car. max |
| `hashtags` | texte | non | Mots séparés par des espaces ou des virgules, `#` facultatif, 1 024 car. max |
| `revision_label` | texte | non | Indice du document, 20 car. max |

```bash
curl -X POST "https://erp.exemple.fr/api/files/json/store" \
  -H "Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1" \
  -H "Accept: application/json" \
  -F fileable_type=quote-line \
  -F fileable_id=101 \
  -F role=plan \
  -F is_primary=true \
  -F revision_label=A \
  -F "files[]=@P-0001_indA.pdf" \
  -F "files[]=@P-0001.dxf"
```

Réponse `201` : `{"files": [ ... ]}`, même format que la liste.

### `GET /files/json/{id}/download`

Contenu d'un fichier (plan, dessin de chute…), en pièce jointe, avec son type d'origine. Même
contrôle que dans l'application : l'utilisateur du jeton doit pouvoir voir l'entité à laquelle le
fichier est rattaché. Chaque lecture est inscrite au journal des consultations quand il est activé.

| Code | Cas |
|---|---|
| `403` | Fichier rattaché à une entité que l'utilisateur ne peut pas voir |
| `404` | Fichier inconnu |

### `PATCH /files/json/{id}`

Modifie les informations d'un fichier **déposé par l'utilisateur du jeton**. Corps JSON :

| Champ | Obligatoire | Notes |
|---|---|---|
| `fileable_type`, `fileable_id` | **oui** | Entité à laquelle le fichier est rattaché |
| `role` | non | Nouveau rôle |
| `is_primary` | non | `true` : devient le fichier principal de son rôle |
| `comment` | non | Vide = commentaire effacé |
| `hashtags` | non | Remplace les mots-clés |

Réponse `200` : `{"file": { ... }}`.

### `DELETE /files/json/{id}`

Détache un fichier **déposé par l'utilisateur du jeton** de l'entité désignée par
`fileable_type` et `fileable_id` (dans le corps JSON ou en paramètres de requête). Le fichier
est supprimé quand plus aucune entité ne le cite. Réponse `200` : `{"deleted": true}`.

### Erreurs des documents

| Code | Cas |
|---|---|
| `403` | Droit manquant sur l'entité (article, lot, dossier du personnel) ; modification ou retrait d'un fichier déposé par un autre utilisateur |
| `404` | `fileable_type` / `fileable_id` absents ou entité inconnue ; fichier non rattaché à cette entité |
| `409` | Retrait d'un fichier qui a plusieurs versions (l'historique d'un plan n'est jamais supprimé) |
| `422` | Extension refusée, fichier trop lourd, plus de 20 fichiers, rôle inconnu |

---

## 6. Demandes entrantes (pistes)

Pour un outil qui relève une boîte mail (n8n ou autre) : **un appel = un e-mail = une piste**
dans l'ERP. L'ERP retrouve l'expéditeur parmi les contacts (par l'adresse e-mail, sinon par le
domaine de l'adresse), sinon crée un prospect à compléter, range l'e-mail et ses pièces
jointes sur la piste et prévient le responsable.

### `POST /leads/inbound`

`multipart/form-data` (ou JSON s'il n'y a aucun fichier) :

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `from_email` | texte | **oui** | Adresse de l'expéditeur, 255 car. max |
| `from_name` | texte | non | Nom affiché de l'expéditeur, 255 car. max |
| `subject` | texte | non | Objet, 1 000 car. max |
| `received_at` | date | non | Date de réception |
| `body` | texte | non | Texte du message |
| `message_id` | texte | non | En-tête `Message-ID`, 1 000 car. max. Sert à ne pas créer deux fois la même piste |
| `owner_email` | texte | non | E-mail d'un utilisateur de l'ERP à qui attribuer la piste ; absent : responsable par défaut de l'instance, sinon aucun |
| `original` | fichier | non | L'e-mail d'origine, `.eml` ou `.msg`, 20 Mo max |
| `attachments[]` | fichier | non | Pièces jointes, 20 au plus, 20 Mo max chacune |

Sans `message_id`, l'ERP reconnaît un renvoi par l'expéditeur, la date et l'objet.

```bash
curl -X POST "https://erp.exemple.fr/api/leads/inbound" \
  -H "Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1" \
  -H "Accept: application/json" \
  -F from_email=achats@client-exemple.fr \
  -F "from_name=Service achats" \
  -F "subject=Demande de prix — 40 supports pliés" \
  -F received_at=2026-11-27T08:42:00+01:00 \
  -F "message_id=<4f2a9c@client-exemple.fr>" \
  -F "body=Bonjour, merci de nous chiffrer..." \
  -F "original=@demande.eml" \
  -F "attachments[]=@plan-support.pdf"
```

Réponse `201` (piste créée) ou `200` (même e-mail déjà reçu : la piste existante est
renvoyée, rien n'est recréé) :

```json
{
  "id": 87,
  "created": true,
  "url": "https://erp.exemple.fr/leads/87"
}
```

| Code | Cas |
|---|---|
| `403` | Jeton créé sans la capacité `leads:inbound` |
| `422` | `from_email` manquant ou invalide, fichier trop lourd, extension de `original` autre que `eml` / `msg`, plus de 20 pièces jointes |
| `429` | Plus de 60 appels par minute |

---

## 7. Prospects

Pour un logiciel de chiffrage, un formulaire de contact du site, un scan de badges de salon ou
un CRM tiers : **un appel = une société et une personne à recontacter**. L'ERP retrouve la
société, sinon la crée (prospect, ou client) avec son contact et son adresse. Si l'appel porte
une demande (`lead`), il ouvre aussi une piste et prévient le responsable.

La même route sert à **créer et à mettre à jour** : un logiciel qui envoie à nouveau son client
(nouvelle information, client gagné) retrouve la même fiche. Une fiche déjà connue est seulement
**complétée** :

- un champ vide dans l'ERP est rempli (SIREN, NAF, TVA, site ; prénom, téléphone, mobile,
  fonction, civilité du contact) ; **une valeur déjà saisie n'est jamais remplacée**, la raison
  sociale non plus ;
- l'adresse n'est posée que si la société n'en a aucune, ou n'a que l'adresse « à compléter » ;
- `company.status = customer` fait passer un prospect (ou une société inactive) en client. Un
  client n'est jamais repassé prospect ;
- un contact inconnu (autre e-mail) est ajouté à la société.

Les champs complétés sont listés dans la réponse (`updated`).

### `POST /prospects`

JSON :

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `system` | texte | avec `company.external_id` | Nom court du logiciel appelant : minuscules, chiffres, `-` ou `_`, 50 car. max (ex. `alma`, `radquote`). Sert aussi de source par défaut de la piste |
| `company.external_id` | texte | non | Identifiant du client **dans votre logiciel**, 191 car. max. Retenu par l'ERP : les appels suivants retrouvent la fiche par lui, même si la raison sociale ou l'e-mail ont changé |
| `company.status` | texte | non | `prospect` (défaut) ou `customer` (client) |
| `company.label` | texte | non | Raison sociale, 255 car. max. Absente : nom du contact, sinon son e-mail |
| `company.siren` | texte | non | SIREN (9 chiffres) ou SIRET (14 chiffres), espaces admis |
| `company.naf_code` | texte | non | 10 car. max |
| `company.intra_community_vat` | texte | non | 20 car. max |
| `company.website` | texte | non | 255 car. max |
| `contact.civility` | texte | non | 50 car. max |
| `contact.first_name` | texte | non | 255 car. max |
| `contact.name` | texte | voir note | Nom, 255 car. max |
| `contact.email` | texte | voir note | E-mail valide |
| `contact.phone` | texte | voir note | 50 car. max |
| `contact.mobile` | texte | voir note | 50 car. max |
| `contact.function` | texte | non | Fonction, 255 car. max |
| `address.adress`, `address.zipcode`, `address.city`, `address.country`, `address.label` | texte | non | Adresse de la société créée ; pays par défaut « France ». Absente : adresse « à compléter » |
| `lead.subject` | texte | non | Objet de la demande, 1 000 car. max (255 gardés) |
| `lead.message` | texte | non | Texte de la demande |
| `lead.source` | texte | non | Origine affichée sur la piste (« Site web », « Salon… ») ; défaut : `system`, sinon « API » |
| `lead.campaign` | texte | non | Campagne, 255 car. max |
| `lead.external_id` | texte | non | Identifiant de la demande chez vous, 200 car. max. Sert à ne pas créer deux fois la même piste |
| `lead.received_at` | date | non | Date de la demande ; défaut : maintenant |
| `owner_email` | texte | non | E-mail d'un utilisateur de l'ERP à qui attribuer société et piste ; absent : responsable par défaut de l'instance, sinon aucun |

Le contact doit avoir un **nom ou un e-mail**, et un **moyen d'être joint** (e-mail, téléphone
ou mobile). Sans objet `lead`, aucune piste n'est créée.

**Rapprochement**, dans cet ordre : identifiant de votre logiciel (`system` + `company.external_id`),
puis SIREN, puis contact ayant le même e-mail, puis société dont un contact ou le site porte le
domaine de l'e-mail (jamais pour une messagerie publique : gmail, orange…). Le champ `matched_by`
de la réponse dit lequel a joué (`external_id`, `siren`, `contact`, `domain`, ou `null` pour une
société créée). Une société retrouvée autrement que par son identifiant est rattachée à
celui-ci pour les appels suivants.

**Appel rejoué** : une demande déjà reçue (même `lead.external_id`, sinon même e-mail, objet et
message) renvoie la piste existante, rien n'est recréé.

```bash
curl -X POST "https://erp.exemple.fr/api/prospects" \
  -H "Authorization: Bearer 12|k3Jf9sQe7XyZ0aBcDeFgHiJkLmNoPqRsTuVwXyZ1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company": { "label": "Client Exemple SAS", "siren": "123 456 789", "website": "www.client-exemple.fr" },
    "contact": { "first_name": "Jeanne", "name": "Durand", "email": "j.durand@client-exemple.fr",
                 "phone": "01 02 03 04 05", "function": "Acheteuse" },
    "address": { "adress": "1 rue des Forges", "zipcode": "35000", "city": "Rennes" },
    "lead":    { "subject": "Demande de prix — 40 supports pliés", "message": "Bonjour, ...",
                 "source": "Site web", "external_id": "form-2026-1142" }
  }'
```

Réponse `201` (société, contact ou piste créé) ou `200` (fiche connue, complétée ou non ;
demande rejouée) :

```json
{
  "company": {
    "id": 214,
    "code": "CLI-0214",
    "label": "Client Exemple SAS",
    "created": true,
    "matched_by": null,
    "updated": [],
    "url": "https://erp.exemple.fr/companies/214"
  },
  "contact": { "id": 530, "created": true, "updated": [] },
  "lead": { "id": 88, "created": true, "url": "https://erp.exemple.fr/leads/88" }
}
```

`lead` vaut `null` quand l'appel ne porte pas de demande. `updated` liste les champs complétés
sur une fiche connue : `siren`, `naf_code`, `intra_community_vat`, `website`, `status`, `address`
pour la société ; `civility`, `first_name`, `name`, `function`, `phone`, `mobile` pour le contact.

Exemple de mise à jour depuis un logiciel de chiffrage, quand le client est gagné :

```json
{
  "system": "alma",
  "company": { "external_id": "C-0042", "label": "Client Exemple SAS", "status": "customer",
               "siren": "123456789" },
  "contact": { "name": "Durand", "email": "j.durand@client-exemple.fr" }
}
```

Réponse `200` : `"matched_by": "external_id"`, `"updated": ["siren", "status"]` si le SIREN
était vide.

| Code | Cas |
|---|---|
| `403` | Jeton créé sans la capacité `prospects:write` |
| `422` | `company.external_id` sans `system`, contact sans nom ni e-mail, contact injoignable (ni e-mail ni téléphone), SIREN qui n'a ni 9 ni 14 chiffres, e-mail invalide, champ trop long |
| `429` | Plus de 60 appels par minute |

---

## 8. Stock d'un lot de tôle

Route destinée au MES Nest2Prod, qui reçoit de l'ERP les entrées de tôle sous une référence
`MV-{n}` et peut relire leur état.

### `GET /n2p/sheet-lots/{ref}/stock`

`{ref}` : référence du lot, par exemple `MV-42` (lettres, chiffres, `-` et `_`).

Réponse `200` :

```json
{
  "external_ref": "MV-42",
  "aggregate_qty": 6,
  "initial_qty": 10,
  "received_at": "2026-11-20T10:15:00+01:00",
  "product": {
    "code": "TOLE-S235-3",
    "label": "Tôle S235 3 mm 3000 × 1500",
    "material": "S235",
    "thickness": "3.000",
    "sheet_x": "3000.000",
    "sheet_y": "1500.000",
    "sheet_z": "3.000"
  },
  "location": "Rack tôles A"
}
```

`aggregate_qty` est le stock **total** de l'article à cet emplacement, tous lots confondus, et
non le reste du seul lot `MV-42`. `initial_qty` est la quantité reçue avec ce lot.

| Code | Corps | Cas |
|---|---|---|
| `400` | `{"error": "invalid_ref_format"}` | La référence ne commence pas par `MV-` |
| `400` | `{"error": "invalid_ref_id"}` | Numéro absent ou nul |
| `404` | `{"error": "ref_not_found"}` | Aucun mouvement de ce numéro |
| `404` | `{"error": "ref_not_a_sheet_lot"}` | Le mouvement n'est pas une entrée de tôle |
| `404` | `{"error": "no_slp_attached"}` | Mouvement sans emplacement de stock |

---

## 9. Imbrications et chutes

Pour une FAO / un logiciel d'imbrication : **un appel = un programme découpé** (ou en cours).
L'ERP enregistre l'imbrication et ses pièces par ligne de commande. Quand le programme est
**terminé** (`status = done`), il sort la tôle du stock et y remet les chutes, **une seule
fois**. Le coût matière est ensuite réparti sur les lignes de commande, au prorata de la surface
des pièces (sinon de leur poids, sinon de leur nombre) : écran Stock → Imbrications.

L'utilisateur du jeton doit avoir le droit de gestion des lots et numéros de série (le même que
l'écran Imbrications).

### `GET /remnants`

Chutes de tôle en stock, pour imbriquer dessus. Lecture seule. La découpe d'une chute se déclare
ensuite par [`POST /nests`](#post-nests), avec son format (`sheet.x_size`, `sheet.y_size`) et son
lot (`sheet.batch`).

| Paramètre de requête | Type | Obligatoire | Description |
|---|---|---|---|
| `product_code` | texte | non | Code de l'article tôle |
| `material` | texte | non | Matière (égalité exacte, ex. `S235`) |
| `thickness` | nombre | non | Épaisseur de l'article (mm) |
| `min_x`, `min_y` | nombre | non | Taille minimale (mm) ; une chute est gardée si elle la contient dans un sens ou l'autre |
| `include_sheets` | booléen | non | `1` : liste aussi les tôles entières en stock (`is_remnant: false`) |

Réponse `200`, la plus grande chute d'abord pour chaque article :

```json
{
  "data": [
    {
      "product": { "id": 31, "code": "TOLE-S235-3", "label": "Tôle S235 ép. 3", "material": "S235",
                   "thickness": 3, "sheet_x": 1500, "sheet_y": 3000 },
      "is_remnant": true,
      "x_size": 1500,
      "y_size": 1000,
      "z_size": 3,
      "qty": 1,
      "batch": "LOT-2026-118",
      "heat_number": "C-24518",
      "stock_line": { "id": 12, "code": "SL-0012", "location": "RACK-A" },
      "unit_value": 30,
      "drawings": [ { "id": 904, "name": "chute-77-1.dxf" } ]
    }
  ],
  "meta": { "count": 1, "truncated": false }
}
```

- `qty` : nombre de pièces identiques (même format, même lot) sur la ligne de stock.
- `drawings` : contour réel de la chute (DXF ou SVG, dernière version), quand il a été déposé à la
  remise en stock. Contenu par [`GET /files/json/{id}/download`](#get-filesjsoniddownload). Vide
  pour une chute rectangulaire sans dessin.
- `unit_value` : coût moyen de la ligne de stock × surface de la chute / surface de la tôle ;
  `null` si le coût ou les dimensions manquent.
- 500 articles tôle au plus sont examinés par appel (`meta.truncated` vaut alors `true`) :
  filtrer par matière ou épaisseur.

| Code | Cas |
|---|---|
| `403` | Jeton sans la capacité `remnants:read`, ou utilisateur sans le droit de gestion des lots |

### `POST /nests`

JSON :

| Champ | Type | Obligatoire | Règles et notes |
|---|---|---|---|
| `system` | texte | **oui** | Nom court de votre logiciel : minuscules, chiffres, `-` ou `_`, 50 car. max |
| `external_id` | texte | **oui** | Identifiant du programme **chez vous**, 190 car. max. Un nouvel envoi du même identifiant met à jour la même imbrication |
| `name` | texte | non | Nom du programme ; défaut : `external_id` |
| `status` | texte | non | `planned`, `in_progress`, `done` (défaut), `cancelled` |
| `cut_at` | date | non | Date de découpe ; défaut : maintenant si `done` |
| `machine` | texte | non | Machine, 255 car. max |
| `material` | texte | non | Matière ; défaut : celle de l'article tôle |
| `usage_percent` | nombre | non | Taux d'utilisation de la tôle, 0 à 100 |
| `comment` | texte | non | 5 000 car. max |
| `sheet_qty` | nombre | **oui** | Nombre de tôles découpées (> 0) |
| `consume_stock` | booléen | non | `false` : enregistre l'imbrication sans toucher au stock (ni sortie ni chute). Défaut `true` |
| `sheet.product_code` | texte | **oui** (ou `sheet.product_id`) | Code de l'**article tôle** dans l'ERP |
| `sheet.product_id` | entier | non | Identifiant de l'article tôle, à la place du code |
| `sheet.x_size`, `sheet.y_size` | nombre | non | Format de la tôle découpée (mm), sens indifférent. Sert à choisir la pièce en stock : une tôle entière ou une **chute** de ce format. Défaut : format de l'article |
| `sheet.z_size` | nombre | non | Épaisseur (mm) ; défaut : celle de l'article |
| `sheet.batch` | texte | non | Code du lot ou numéro de coulée de la tôle à sortir |
| `sheet.stock_line_code` | texte | non | Code de la ligne de stock (emplacement) à utiliser ; défaut : la première qui a la pièce en quantité suffisante |
| `parts[]` | liste | non | Pièces découpées, 500 au plus. **Remplacées** à chaque envoi |
| `parts[].order_line_id` | entier | non | Ligne de commande de l'ERP |
| `parts[].of_code` | texte | non | À la place : code d'OF au format `OF<id de ligne>`, tel qu'imprimé sur la fiche suiveuse |
| `parts[].qty` | nombre | **oui** | Quantité découpée (> 0) |
| `parts[].good_qty`, `parts[].bad_qty` | nombre | non | Bonnes / rebutées |
| `parts[].part_ref`, `parts[].external_ref` | texte | non | Nom de la pièce, référence chez vous |
| `parts[].area_mm2`, `parts[].weight_kg` | nombre | non | Surface et poids **d'une** pièce : base de la répartition du coût |
| `remnants[]` | liste | non | Chutes remises en stock, 4 au plus : `width`, `height` (mm, obligatoires), `quantity` (défaut 1) |

Règles :
- **Sortie de stock et chutes une seule fois**, au premier envoi en `done`. Les envois suivants
  mettent à jour le nom, les pièces, le taux… mais ne refont ni ne défont la sortie (avertissement
  `stock_already_recorded`). Un programme envoyé d'abord en `in_progress` ne touche pas au stock.
- **Tout ou rien** : si la tôle ne peut pas être sortie (pas assez de tôles au format ou au lot
  demandé, article inconnu, chute plus grande que la tôle), la réponse est `422` et **rien n'est
  enregistré**, ni l'imbrication ni ses pièces. Corriger et renvoyer.
- La sortie est valorisée au coût moyen de la ligne de stock ; une chute est valorisée au prorata
  de sa surface (le coût moyen ne bouge pas). Elle garde le lot et la coulée de la tôle.
- Une pièce dont la ligne de commande est inconnue est gardée « non rattachée » (avertissement
  `part_unmatched:<pièce>`). Elle est reliée à l'opération de la ligne qui consomme cette tôle,
  quand il y en a une.

```json
{
  "system": "fao-exemple",
  "external_id": "JOB-881",
  "name": "PRG-881",
  "status": "done",
  "machine": "Laser 1",
  "usage_percent": 74.5,
  "sheet_qty": 1,
  "sheet": { "product_code": "TOLE-S235-3", "x_size": 3000, "y_size": 1500, "batch": "C-24518" },
  "parts": [
    { "of_code": "OF412", "part_ref": "SUPPORT-A", "qty": 4, "area_mm2": 250000 },
    { "of_code": "OF415", "part_ref": "PATTE-B", "qty": 10, "area_mm2": 50000 }
  ],
  "remnants": [ { "width": 1500, "height": 1000, "quantity": 1 } ]
}
```

Réponse `201` (imbrication créée) ou `200` (mise à jour) :

```json
{
  "id": 57,
  "external_id": "JOB-881",
  "name": "PRG-881",
  "status": "done",
  "created": true,
  "stock": { "recorded": true, "recorded_now": true, "stock_line_id": 31, "sheet_qty": 1, "remnants": 1 },
  "parts": [
    { "id": 140, "part_ref": "SUPPORT-A", "of_code": "OF412", "order_line_id": 412, "task_id": 2210 },
    { "id": 141, "part_ref": "PATTE-B", "of_code": "OF415", "order_line_id": 415, "task_id": 2231 }
  ],
  "warnings": [],
  "url": "https://erp.exemple.fr/products/nests/57"
}
```

| Code | Cas |
|---|---|
| `403` | Jeton sans la capacité `nests:write`, ou utilisateur sans le droit de gestion des lots |
| `422` | Champ manquant ou invalide ; tôle impossible à sortir (`errors.sheet`, message explicite) : rien n'est enregistré |
| `429` | Plus de 60 appels par minute |

---

## 10. Événements entrants signés (webhooks)

Un système partenaire (MES, automate, autre logiciel) **envoie des événements** à l'ERP. Il
n'utilise pas de jeton d'API : l'entreprise déclare le partenaire dans l'ERP et lui transmet
un **code système**, l'URL d'appel et un secret.

### `POST /integrations/{système}/inbound`

`{système}` : code du partenaire déclaré par l'entreprise (minuscules, chiffres, `-`, `_`), par
exemple `n2p`.

#### Authentification

Selon le réglage choisi par l'entreprise pour ce partenaire :

| Mode | À envoyer |
|---|---|
| Signature HMAC | en-têtes d'horodatage et de signature (ci-dessous) |
| Jeton porteur | `Authorization: Bearer {jeton fourni}` |
| Les deux | les deux |

Signature HMAC :

1. `horodatage` = heure Unix actuelle, en secondes.
2. `chaîne signée` = `{horodatage}.{corps brut de la requête}` (le corps exact envoyé, octet
   pour octet).
3. `signature` = HMAC-SHA256 de la chaîne signée avec le secret, en **hexadécimal minuscule**,
   sans préfixe.
4. En-têtes : horodatage dans l'en-tête d'horodatage, signature dans l'en-tête de signature.
   Leurs noms sont fixés pour chaque partenaire ; par défaut `X-Timestamp` et
   `X-Signature-256`.

L'horodatage doit être à moins de 300 secondes (réglable) de l'heure du serveur.

```bash
BODY='{"event_id":"6f1c2b9e-8a4d-4c3b-9e2f-1a2b3c4d5e6f","event_type":"task.started","occurred_at":"2026-11-27T08:02:11+01:00","source":"mes","data":{"of_code":"OF1234"}}'
TS=$(date +%s)
SIG=$(printf "%s" "${TS}.${BODY}" | openssl dgst -sha256 -hmac "SECRET_FOURNI" -hex | awk '{print $2}')
curl -sS -X POST "https://erp.exemple.fr/api/integrations/mes/inbound" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Timestamp: ${TS}" \
  -H "X-Signature-256: ${SIG}" \
  -d "${BODY}"
```

#### Enveloppe

| Champ | Type | Obligatoire | Notes |
|---|---|---|---|
| `event_id` | UUID | **oui** | Identifiant unique de l'événement : sert au dédoublonnage |
| `event_type` | texte | **oui** | 100 car. max, voir la liste ci-dessous |
| `occurred_at` | date-heure | **oui** | ISO 8601 |
| `source` | texte | non | 100 car. max ; absent = code du système |
| `data` | objet | non | Contenu propre à l'événement |

Types d'événements traités aujourd'hui : `task.started`, `task.paused`, `task.resumed`,
`task.finished`, `task.declared_good`, `task.declared_bad`, `task.commented`, `task.canceled`
(exécution des opérations d'atelier), `stock.consumed` (consommation de tôle), `nest.updated`
(imbrication réalisée), `job.status_changed` (statut d'un ordre de fabrication). Le contenu de
`data` de chacun fait l'objet d'un contrat d'échange remis avec l'intégration.

#### Réponses

| Code | Corps | Signification |
|---|---|---|
| `200` | `{"status": "processed", "delivery_id": 981, "event_id": "...", "processed_at": "2026-11-27T08:02:12+01:00", "error": null}` | Traité |
| `200` | `{"status": "processed", ...}` | Même `event_id` déjà traité : rien n'est refait |
| `200` | `{"status": "ignored", ..., "error": "no_handler"}` | Type d'événement inconnu de l'ERP : accepté, sans effet. Ne pas renvoyer |
| `202` | `{"status": "ignored", "reason": "event_type_not_subscribed", "event_type": "..."}` | Type non retenu pour ce partenaire |
| `401` | `{"error": "hmac_mismatch"}` ou `{"error": "bearer_mismatch"}` | Signature, horodatage ou jeton invalide |
| `403` | `{"error": "endpoint_disabled"}` | Partenaire désactivé par l'entreprise |
| `404` | `{"error": "unknown_endpoint"}` | Code système inconnu |
| `422` | `{"message": ..., "errors": {...}}` | Enveloppe invalide |
| `422` | `{"status": "failed", ..., "error": "handler_failed"}` | Événement reçu mais non appliqué (ex. opération introuvable). Peut être renvoyé avec le même `event_id` une fois la cause corrigée |
| `500` | `{"status": "failed", "event_id": "...", "error": "dispatch_failed", "correlation_id": "..."}` | Erreur du serveur ; communiquer le `correlation_id` au support |
| `429` | | Limite d'appels atteinte (60 par minute et par adresse IP) |

Un événement en échec peut être renvoyé avec le **même** `event_id` : il est rejoué. Un
événement déjà traité ne l'est jamais deux fois.

### Notifications des prestataires

`/integrations/esignature/webhook` et `/integrations/qonto/...` reçoivent les notifications de
prestataires précis (signature électronique, Qonto), qui les signent selon leur propre
protocole. Elles ne sont pas destinées à un intégrateur.

---

## 11. Ce que l'API ne permet pas encore

- **Lire** un devis, une commande, un article, une société ou une opération de production.
  Avec un jeton, seuls se lisent la liste des clients (`GET /clients`), la liste des fichiers
  d'une entité et le stock d'un lot de tôle.
- Créer une commande, une facture, un bon de livraison, un achat.
- Déposer une nouvelle version d'un fichier existant, ou consulter l'historique des versions.
- Créer un fournisseur, ou **remplacer** une donnée déjà saisie d'une société : l'API
  [Prospects](#7-prospects) crée prospects et clients et complète les champs vides ; le reste passe
  par l'application ou l'import de données.

Ces besoins sont à exprimer à l'éditeur.
