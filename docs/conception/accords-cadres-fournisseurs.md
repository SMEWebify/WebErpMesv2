# Accords-cadres fournisseurs — étude et proposition

> Statut : **proposition à valider**. Aucune migration ni aucun fichier applicatif n'a été créé.
> Date : 14 octobre 2026 — branche `WEM-2.0` (v3.0.0).

## Sommaire

0. Synthèse
1. État des lieux
2. Proposition de modèle
3. Écrans et usages
4. Découpage en lots
5. Questions ouvertes
6. Annexe — anomalies relevées en chemin (hors périmètre)

---

## 0. Synthèse

- **Rien n'existe.** Aucune notion de contrat, d'accord-cadre, de commande ouverte ni
  d'appel de livraison dans le code. Le seul « contrat » de l'application est le contrat de
  travail RH.
- **La chaîne achat a deux faiblesses** qu'il faut contourner, pas reproduire :
  - les montants stockés sur `purchase_lines` (`unit_price_after_discount`,
    `total_selling_price`) valaient 0 sur toutes les lignes saisies ou modifiées depuis
    l'écran React. C'est corrigé depuis le 15/10/2026 (A1), mais le calcul de l'accord n'en
    dépend pas ;
  - les factures d'achat ne portent aucun montant.

  Les chiffres d'un accord se recalculent donc toujours à partir de
  `qty × selling_price × (1 − discount)`, jamais à partir des colonnes de total.
- **Proposition retenue :**
  - deux tables nouvelles : l'en-tête de l'accord et ses lignes d'engagement ;
  - une colonne nullable sur `purchase_lines`, le **lien** vers la ligne d'engagement ;
  - le commandé et le facturé sont **calculés à la lecture**.

  Seul le rattachement est stocké, comme `days_count` sur une absence. Il est posé
  automatiquement, et l'utilisateur peut le corriger à la main.
- **Unités** : il n'existe aucune conversion (pas de densité, pas de coefficient d'unité, et
  `products.weight` sans sémantique documentée). De plus, `purchase_lines.qty` est encore un
  **entier**. Un engagement en tonnes est suivable dès le lot 1 si les achats sont saisis en
  kg. Le suivi d'achats saisis à la tôle demande une conversion, prévue au lot 2.
- **Lot 1 (M)** : accords, rattachement automatique, liste avec barres de progression, fiche,
  onglet sur la fiche fournisseur. Aucun changement pour un utilisateur sans accord.

---

## 1. État des lieux

### 1.1 Chaîne achat actuelle

| Étape | Table | Points utiles pour l'accord |
|---|---|---|
| Demande de prix | `purchases_quotations` / `purchase_quotation_lines` | `unit_price`, `qty_to_order`, `canceled_qty`, `lead_time_days`. Statuts 1 à 6 (6 = commande créée). |
| Commande | `purchases` | `companies_id` (fournisseur), `statu` : 1 en cours, 2 commandé, 3 partiellement reçu, 4 reçu, **5 annulé**. **Aucune colonne de date** hormis `created_at`, et aucune devise. |
| Ligne de commande | `purchase_lines` | `product_id` (colonne **texte**), `methods_units_id` (nullable), `qty` **entier**, `selling_price` (= prix unitaire d'achat, decimal 10,3), `discount` %, `receipt_qty` / `invoiced_qty` entiers, `tasks_id` (0 = sans tâche), `delivery_date`. **Pas de statut de ligne** et pas d'annulation partielle. Soft delete. |
| Réception | `purchase_receipts` / `purchase_receipt_lines` | `receipt_qty` decimal(10,3), `accepted_qty` / `rejected_qty` (contrôle). `PurchaseReceiptService` reçoit toujours la quantité commandée entière. |
| Facture d'achat | `purchase_invoices` | Statuts 1 en cours, 2 à comptabiliser, 3 clos. **Aucun montant, aucune date propre, aucune devise.** |
| Ligne de facture | `purchase_invoice_lines` | **Uniquement des clés** : `purchase_invoice_id`, `purchase_receipt_line_id`, `purchase_line_id`, `accounting_allocation_id`. |
| Facture reçue (PDP) | `pdp_incoming_invoices` | `total_ht/total_vat/total_ttc`, `currency`, `purchase_invoice_id`. Seul endroit où un montant facturé par le fournisseur existe. |

**Contrôleurs et services** (`app/Http/Controllers/Purchases/`, routes `routes/web.php` 529-613) :

- `PurchaseLinesController` : `storeLineJson` (refus si `statu != 1`), `updateLineJson` et
  `destroyLineJson` (**sans contrôle de statut**, ni de quantité reçue ou facturée).
- `PurchaseOrderService` : lignes créées depuis une tâche ou depuis une ligne de demande de
  prix.
- Autres créateurs de lignes : `ReorderService` (réappro) et
  `PurchasesReceiptController@...manual` (réception sans commande, qui crée un achat
  `MANUAL-…`).
- `PurchasesInvoiceController` : l'écran `/purchases/waiting/invoice` et `storeInvoiceJson`,
  qui crée une ligne de facture par ligne de réception et incrémente `invoiced_qty`.
- `PdpIncomingInvoiceService::convertToPurchaseInvoice` : bouton « Sans rapprochement », qui
  crée un **en-tête de facture sans aucune ligne**.

**Écrans React** :

- `PurchasesIndex` ;
- `PurchaseLinesPage`, monté dans `purchases/purchases-show.blade.php`, qui est la saisie
  d'une ligne ;
- `PurchasesQuotationShow`, `PurchasesRequest`, `ReorderPage` ;
- `PurchaseReceiptIndex` / `PurchaseReceiptLinesPage` ;
- `PurchasesWaitingInvoice`, `PurchaseInvoicesIndex`, `IncomingInvoicesIndex`.

**Fiche fournisseur** : `companies/companies-show.blade.php`, onglets en pastilles Bootstrap,
une application React montée par onglet. L'onglet « Évaluation » n'apparaît déjà que si
`statu_supplier == 2`, et un onglet « Accords » prendrait la même condition.
`mountCompanyTimeline` (`resources/js/app.js`) est le modèle de montage le plus simple.

### 1.2 Ce qui porte déjà un prix ou une préférence fournisseur

| Table | Contenu | Utilisée par l'achat ? |
|---|---|---|
| `products_preferred_suppliers` | (produit, fournisseur), unique. Ni prix, ni rang, ni date. | Filtre de recherche produit, réappro (1er fournisseur = défaut). |
| `products_quantity_prices` | **Grille d'achat par fournisseur** : `products_id`, `companies_id`, `min_qty`, `max_qty`, `price`. **Ni dates de validité, ni devise, ni contrainte d'unicité.** | **Seulement par le réappro** (`ReorderService`, `ReorderPage.jsx`). |
| `methods_service_suppliers` | (service, fournisseur). Sans prix. | Affichage seulement. |
| `product_price_histories` | Historique du `purchased_price` / `selling_price` **du produit** (observer `ProductsObserver`). **Pas de fournisseur.** | Lecture seule (modale historique). |
| `methods_families` | `code`, `label`, `methods_services_id`, `nest_type`. **Plate** : pas de sous-famille. | — |
| `products` | `methods_families_id`, `methods_services_id`, `purchased_price`, `material` (**texte libre**), `thickness`, `weight`, `x/y/z_size`. | — |

Les ventes ont leur propre grille, `customer_price_lists`, à ne pas confondre avec
`products_quantity_prices`.

**Comment le prix d'une ligne d'achat est proposé aujourd'hui** — il dépend du chemin :

| Chemin | Prix proposé |
|---|---|
| Commande depuis les tâches du planning | `tasks.unit_cost`, lui-même copié de `products.purchased_price` à la gestion de la tâche |
| Demande de prix → commande | Prix saisi dans la conversion, à défaut `unit_cost` de la tâche ou `unit_price` de la ligne de demande de prix |
| **Ligne saisie à la main** (`PurchaseLinesPage`) | **`product.selling_price`, c'est-à-dire le prix de vente** (la recherche ne renvoie même pas `purchased_price`) |
| Réappro | Grille `products_quantity_prices` du fournisseur, sinon `purchased_price` (seul chemin qui lit la grille) |
| Réception sans commande | `purchased_price` |

Conclusion : il n'y a pas aujourd'hui de « prix fournisseur » qui fasse foi. Un prix
contractuel d'accord serait la première source de prix d'achat datée et rattachée à un
fournisseur.

### 1.3 Unités

**Ce qui existe :**

- `methods_units` : `code`, `label`, `type` (1 masse, 2 longueur, 3 surface, 4 volume,
  5 autre), `default`. Le seeder ne crée que KG, MM et UNIT.
- Le produit a une unité obligatoire, la ligne d'achat une unité nullable.
- **Une ligne d'achat peut être saisie dans une unité différente de celle du produit**, et
  rien ne le contrôle. `storeLineJson` retombe sur l'unité par défaut, pas sur celle du
  produit, et les lignes issues d'une demande de prix ont une unité nulle.

**Ce qui manque :**

- **Aucun facteur entre unités** : rien ne dit que 1 t = 1000 kg ni que les deux unités sont
  de même nature.
- **Aucune masse par unité fiable.** `products.weight` existe (decimal 10,3), mais il est
  saisi ou copié depuis la CAO, jamais calculé. Rien ne garantit qu'il soit en kg ni qu'il
  soit le poids **d'une** unité.
- **Aucune densité ni table de matières** : `material` est du texte libre (« S235 »,
  « DC01 »…).
- **`purchase_lines.qty` est un entier** : la migration `2026_04_21_000004` a passé les
  quantités en décimal partout sauf ici. 1,5 t ne se saisit pas, 1 500 kg oui.

**Ce que cela implique pour un engagement en tonnes :**

| Cas d'achat | Suivi en tonnes |
|---|---|
| Ligne saisie en kg (ou t) | Direct, avec un facteur kg ↔ t |
| Ligne saisie à la tôle / à l'unité, produit avec un poids unitaire renseigné | Possible via `products.weight` interprété comme « kg par unité de stock » — **décision métier** |
| Ligne saisie à l'unité, produit sans poids | Impossible sans conversion : la ligne doit apparaître comme **« non convertible »**, pas être ignorée en silence |

Le calcul de la masse depuis les dimensions (x × y × épaisseur × densité) est faisable pour
la tôle (densité de l'acier ≈ 7,85), mais il demande une densité par matière et n'est
**pas** proposé au lot 1.

### 1.4 Points d'attention

| Sujet | Constat | Proposition pour l'accord |
|---|---|---|
| **Devises** | Aucune devise sur les achats. `factory.curency` sert à l'affichage partout. | L'accord est exprimé dans la devise de la société, sans colonne devise. Un fournisseur en USD est hors périmètre et documenté comme tel. |
| **Commande en cours (statu 1)** | Brouillon modifiable | Ne compte **pas** dans le commandé. Affichée à part, en « en préparation ». |
| **Commande annulée (statu 5)** | Seule annulation existante, au niveau de l'en-tête. `PurchaseCalculatorService` ne l'exclut pas, `AffairSummaryService` si. | Exclue du commandé et du facturé. |
| **Ligne supprimée** (soft delete) | Possible même après réception ou facture | Exclue. Une ligne facturée puis supprimée disparaît donc aussi du facturé : à afficher en anomalie plutôt que masquer (voir Q8). |
| **Annulation partielle** | N'existe pas. On baisse `qty` ou on supprime la ligne. | Rien à faire, le calcul suit la quantité. |
| **Facture sans réception** (« Sans rapprochement ») | En-tête seul, sans ligne ni montant | Elle **ne peut pas** entrer dans le facturé (rien ne la relie à une ligne de commande). Elle est affichée comme information sur la fiche de l'accord (« factures fournisseur non rapprochées sur la période : n, total HT PDP »), sans compter dans le taux. |
| **Avoirs fournisseurs** | **N'existent pas** (les `credit_notes` sont côté client, les retours aussi) | Rien à déduire aujourd'hui. Le calcul sera écrit pour qu'un futur avoir fournisseur **réduise le facturé** (et non le commandé), mais pas la quantité engagée (Q7). |
| **Quantité refusée au contrôle** (`rejected_qty`) | Stockée sur la ligne de réception | Ne change ni le commandé ni le facturé (la facture suit la ligne de réception). Elle pourra être montrée en « reçu conforme » plus tard. |

---

## 2. Proposition de modèle

### 2.1 Tables

Toutes les migrations sont **additives** : deux tables nouvelles, une colonne nullable sur
`purchase_lines` au lot 1, une colonne nullable sur `methods_units` au lot 2. Aucune
contrainte nouvelle sur les données existantes.

#### `supplier_agreements` — en-tête de l'accord

| Colonne | Type | Rôle |
|---|---|---|
| `id` | pk | |
| `code` | string, unique | `AC-{id}` ou numérotation (Q11) |
| `label` | string | « Acier 2027 — Fournisseur X » |
| `companies_id` | fk `companies` | Le fournisseur. Restreint à `statu_supplier = 2` dans la validation. |
| `supplier_reference` | string, nullable | Numéro de contrat chez le fournisseur |
| `start_date` / `end_date` | date | Période (bornes incluses) |
| `statu` | tinyint | 1 brouillon, 2 actif, 3 clos (voir 2.2) |
| `alert_days` | smallint, nullable | Préavis d'échéance pour l'alerte (lot 3). Défaut en config. |
| `user_id` | fk `users`, nullable | Responsable de l'accord |
| `comment` | text, nullable | Conditions particulières, pénalités |
| `closed_at` | datetime, nullable | |
| timestamps, `deleted_at` | | Soft delete, comme le reste des achats |

**Index :** (`companies_id`, `statu`, `start_date`, `end_date`), pour trouver les accords
applicables à une commande en une seule requête.

#### `supplier_agreement_lines` — engagements

| Colonne | Type | Rôle |
|---|---|---|
| `id` | pk | |
| `supplier_agreement_id` | fk, cascade | |
| `ordre` | smallint | |
| `scope` | string(16) | `global` \| `family` \| `material` \| `product`, choisi à la saisie (Q1) |
| `methods_families_id` | fk, nullable | Renseigné si `scope = family` |
| `material` | string, nullable | Renseigné si `scope = material`. Comparé à `products.material` sans tenir compte de la casse ni des espaces en bord (« DC01 » = « dc01 »). L'écran propose les matières déjà saisies sur les produits, pour éviter les fautes de frappe. |
| `products_id` | fk, nullable | Renseigné si `scope = product` |
| `committed_qty` | decimal(14,3), nullable | Engagement en quantité |
| `methods_units_id` | fk, nullable | Unité de l'engagement en quantité (t, kg…) |
| `committed_amount` | decimal(14,2), nullable | Engagement en montant HT |
| `contract_price` | decimal(12,4), nullable | Prix contractuel, exprimé dans l'unité `methods_units_id` |
| `contract_discount` | decimal(5,2), nullable | Ou remise contractuelle en % (exclusive du prix) |
| `label` / `comment` | nullable | |
| timestamps | | |

**Règles de validation :**

- au moins un de `committed_qty` / `committed_amount` est renseigné (« en quantité et/ou en
  montant ») ;
- `committed_qty` impose `methods_units_id` ;
- `scope` impose la clé correspondante ;
- une seule ligne `global` par accord.

**Index :** `supplier_agreement_id`, `products_id`, `methods_families_id`.

#### Colonnes ajoutées à `purchase_lines` (nullables)

| Colonne | Rôle |
|---|---|
| `supplier_agreement_line_id` | fk nullable, indexée. La ligne d'engagement qui consomme cet achat. |
| `supplier_agreement_link` | string(8), nullable : `auto` \| `manual` \| `excluded`. Le recalcul automatique ne touche jamais `manual` ni `excluded`. |

Les deux restent `null` sur toutes les lignes existantes tant qu'aucun accord n'est créé.

#### Lot 2 — `methods_units.base_factor` (decimal nullable)

Facteur vers l'unité de référence du même `type` (masse → kg : KG = 1, T = 1000 ; longueur →
mm : MM = 1, M = 1000). Une unité sans facteur n'est convertible avec aucune autre.

#### Pas de table de consommation

Le commandé et le facturé sont calculés à la lecture (voir 2.4). Aucune table de cumul ni
aucune colonne de cumul sur l'accord.

#### Fichier du contrat

Ajouter l'alias `supplier-agreement` à `FileableRegistry` pour joindre le contrat signé
avec la GED existante (`include.file-manager-mount`), sans stockage propre.

### 2.2 Statuts

| Statut | Stocké | Effet |
|---|---|---|
| **Brouillon** (1) | oui | Éditable. Ne rattache aucune ligne d'achat. |
| **Actif** (2) | oui | Rattache automatiquement. L'activation exécute le rattachement **rétroactif** des lignes déjà commandées sur la période, car un accord est souvent saisi après sa date de début. |
| **Expiré** | **non, déduit** | Actif dont `end_date` est passée. Plus de nouveau rattachement, chiffres consultables. |
| **Clos** (3) | oui | Clôture volontaire (bilan fait, éventuellement avant terme). Liens figés. |

L'« expiré » est **calculé à la lecture** plutôt que stocké, pour ne dépendre d'aucune tâche
planifiée. Sur Windows, le planificateur ne doit pas devenir un point de défaillance d'une
fonction de lecture.

### 2.3 Rattachement d'une ligne de commande à un accord

**Recommandation : les deux.** Le lien est automatique par défaut et corrigeable à la main.

**Règle automatique.** Une ligne d'achat est candidate à une ligne d'engagement si :

1. l'achat a pour fournisseur `companies_id` celui de l'accord ;
2. la date de l'achat tombe dans [`start_date`, `end_date`]. La date de référence proposée est
   `purchases.created_at`, faute d'autre date (Q3) ;
3. l'accord est actif et non expiré à cette date ;
4. la ligne correspond au périmètre de l'engagement :
   - `product` : `purchase_lines.product_id` = le produit ;
   - `family` : la famille du produit ;
   - `material` : la matière du produit ;
   - `global` : toute ligne.

**Résolution des recouvrements :**

- **Dans un même accord**, la ligne la plus précise l'emporte : produit, puis famille,
  puis matière, puis global. Famille et matière peuvent se recouper (une famille
  « Tôle » contient plusieurs matières) : la famille passe avant la matière, et ce cas est
  signalé à la saisie de l'engagement. **La ligne `global` d'un accord agrège toutes les lignes rattachées à cet accord**,
  quelle que soit la ligne d'engagement qui les porte. « 60 000 € d'acier » et « 40 t de
  DC01 » vivent donc dans le même accord : chaque kilo de DC01 compte dans les deux, sans
  double lien.
- **Entre deux accords du même fournisseur**, deux accords actifs dont les périodes se
  chevauchent avec un même périmètre (même produit, même famille, ou deux globaux) sont
  **refusés à l'activation**. Des périmètres différents restent autorisés, et le plus précis
  gagne. En cas d'égalité restante, l'accord qui se termine le premier l'emporte (il est le
  plus urgent à consommer).
- Une ligne d'achat ne consomme **qu'une** ligne d'engagement : pas de double comptage entre
  accords.

**Quand le lien est posé :**

- **À la création ou modification d'une ligne d'achat**, par un observer sur `PurchaseLines`.
  C'est le seul moyen de couvrir les cinq chemins de création (saisie manuelle, depuis
  tâche, depuis demande de prix, réappro, réception sans commande) sans les modifier un par
  un. L'observer sort immédiatement s'il n'existe aucun accord actif (une requête mise en
  cache), d'où **aucun effet pour un utilisateur sans accord**.
- **Au changement de fournisseur de l'en-tête d'achat**.
- **À l'activation d'un accord** et via un bouton « Recalculer les rattachements » sur sa
  fiche.
- Un `update()` de masse contourne l'observer, comme pour les reports de date. Aucun chemin
  actuel n'en fait sur `product_id`, mais il faut le noter dans `CLAUDE.md`.

**Correction manuelle**, sur la fiche de l'accord et dans la saisie de commande (lot 2) :

- rattacher une ligne hors règle (lien `manual`) ;
- exclure une ligne (`excluded`), par exemple un achat ponctuel hors contrat.

### 2.4 Commandé et facturé : calculés à la lecture

**Accord avec la préférence exprimée.** Le seul élément stocké est le **lien**, comme
`days_count` sur une absence. Ce n'est pas un cumul, mais une décision qui doit rester
stable, et ce pour trois raisons :

1. **Historique stable** : si un produit change de famille ou si un accord est modifié, un
   recalcul pur à la lecture déplacerait la consommation passée sans trace.
2. **Recouvrements et corrections manuelles** : il faut pouvoir les mémoriser.
3. **Requête simple et indexée** : `WHERE supplier_agreement_line_id IN (…)`, au lieu de
   rejouer la règle de rattachement sur tous les achats du fournisseur.

**Définitions proposées :**

| Chiffre | Calcul |
|---|---|
| **Commandé (montant)** | Σ `qty × selling_price × (1 − discount/100)` des lignes rattachées, achat au statut 2, 3 ou 4, ligne et achat non supprimés |
| **Commandé (quantité)** | Σ `qty` converti dans l'unité de l'engagement. Les lignes non convertibles sont comptées à part et listées. |
| **En préparation** | Même calcul sur les achats au statut 1, affiché sans entrer dans le taux |
| **Reçu** (gratuit, utile) | Σ `purchase_receipt_lines.receipt_qty` des lignes rattachées |
| **Facturé (montant)** | Σ sur `purchase_invoice_lines` non supprimées : `receipt_qty` de la ligne de réception × prix net de la ligne d'achat |
| **Taux de réalisation** | commandé / engagé et facturé / engagé, en % (montant et quantité séparément si les deux sont engagés) |

**Pièges à éviter, constatés dans le code :**

- **Ne jamais lire** `unit_price_after_discount` ni `total_selling_price` : ils valent 0 sur
  les lignes saisies ou modifiées depuis `PurchaseLinesPage` (voir annexe).
- Le facturé reste **rattaché à la ligne**, pas à la date de facture : une commande de
  décembre facturée en janvier compte dans l'accord de l'année de commande.
- Le facturé est notre **valorisation** de ce qui a été facturé, pas le montant écrit par le
  fournisseur. La facture d'achat n'en porte aucun. L'écart avec le document PDP reste
  visible sur l'écran de rapprochement existant.

**Performance.** Un service `SupplierAgreementProgressService`, sur le patron
d'`AffairSummaryService` et de `LeaveBalanceService` :

- lectures en `toBase()` ;
- **nombre de requêtes fixe** pour N accords (en-têtes et lignes, agrégat commandé groupé par
  ligne d'engagement, agrégat facturé groupé, lignes non convertibles) ;
- couvert par un test de comptage.

La liste des accords appelle la version « en masse ».

### 2.5 Prix contractuel

**Recommandation** : l'accord propose son prix sur une nouvelle ligne d'achat, au lot 2.
Il est **proposé et jamais imposé** : l'acheteur garde la main, et un écart est signalé.

**Ordre de priorité du prix proposé** dans `PurchaseLinesPage`, après le choix du produit :

1. engagement de l'accord applicable : `contract_price` converti dans l'unité de la ligne,
   ou `contract_discount` appliquée au prix suivant ;
2. grille `products_quantity_prices` du fournisseur (palier min ≤ qté ≤ max), comme le
   réappro le fait déjà ;
3. `products.purchased_price`.

**Articulation avec `products_quantity_prices` :**

- l'accord **ne l'écrit pas et ne la remplace pas** ;
- la grille n'a pas de dates : c'est un tarif permanent ;
- l'accord est un tarif daté qui prime pendant sa période ;
- à échéance, on retombe naturellement sur la grille.

Le même ordre devrait être appliqué au réappro (`ReorderService::priceFor`) pour que les
deux chemins proposent le même prix (Q9).

**Point de décision.** La saisie manuelle propose aujourd'hui le **prix de vente** du
produit. Introduire l'ordre ci-dessus change donc le prix proposé **même sans accord**
(grille, puis prix d'achat). C'est un correctif, mais il est visible du client : à
décider (Q10). Sans cette décision, le lot 2 n'applique la priorité que lorsqu'un accord
s'applique, et ne touche pas au reste.

### 2.6 Rétro-compatibilité

| Garantie | Comment |
|---|---|
| Migrations | Uniquement `create table` et `add column … nullable` |
| Aucun accord ⇒ aucun changement | L'observer sort sans écrire. Pas d'onglet visible sans accord (ou un onglet vide, voir 3.3). Pas de badge dans la saisie. |
| Pas de nouvelle contrainte | Rien n'empêche de commander, de recevoir ni de facturer ; un dépassement d'accord n'est jamais bloquant |
| Exports comptables / FEC | Non touchés : l'accord ne produit aucune écriture |
| Permissions | Les routes portent `purchases-menu`, comme le reste des achats |

---

## 3. Écrans et usages

### 3.1 Liste des accords — `/purchases/agreements`

Composant React `SupplierAgreementsIndex` sur `DataTable`, avec le même schéma que les
autres index (fetch, tri et pagination serveur).

- **Colonnes** : code, fournisseur, libellé, période, statut (`StatusBadge`, avec « expiré »
  déduit), engagé, barre **commandé**, barre **facturé**, projection à échéance.
- **Barres de progression** : balisage déjà utilisé dans `OrderLinesIndex.jsx` et
  `AffairSummary.jsx`. Couleur par seuil comme `GoalWidget` (en retard sur le rythme, dans le
  rythme, dépassé).
- **Filtres** : statut, fournisseur, « en cours seulement » par défaut.
- Rendu en cartes sous `md` géré par `DataTable`.
- **Entrée de menu** sous Achats.

### 3.2 Fiche d'un accord

Shell Blade et composant React `SupplierAgreementShow`.

- **En-tête** : éditable en brouillon, et pour les dates et le commentaire une fois actif.
  Boutons Activer / Clore / Recalculer les rattachements.
- **Lignes d'engagement** : tableau éditable (périmètre, quantité + unité, montant, prix ou
  remise). Pour chaque ligne :
  - engagé / commandé / en préparation / reçu / facturé ;
  - taux ;
  - nombre de lignes non convertibles, avec lien vers elles.
- **Commandes rattachées** : `DataTable` des lignes d'achat (achat, date, produit, qté,
  unité, prix net, montant, reçu, facturé, mode de lien `auto` / `manual`). Actions :
  exclure, et rattacher une ligne du même fournisseur par recherche.
- **Projection de fin de période au rythme actuel** :
  - formule linéaire : commandé ÷ jours écoulés × durée totale ;
  - affichage « au rythme actuel ≈ 34 t (85 %) au 31/12 » ;
  - et le rythme mensuel nécessaire pour atteindre 100 % ;
  - rien d'affiché avant 30 jours d'accord, la projection n'ayant alors pas de sens.
  - Courbe cumulée mensuelle (Chart.js, déjà présent) au lot 3.
- **Factures fournisseur non rapprochées sur la période** : information seule (voir 1.4).
- **Contrat** : `FileManager` de la GED.

### 3.3 Fiche fournisseur — onglet « Accords »

Nouvel onglet dans `companies-show.blade.php`, sous la même condition que « Évaluation »
(`statu_supplier == 2`).

- Petit composant monté comme `CompanyTimeline` (une seule URL en `data-endpoint`), qui
  liste les accords en cours et récents avec leurs deux barres.
- Le compteur d'onglet n'est affiché que s'il y a des accords.

### 3.4 Dans la saisie d'une commande d'achat (lot 2)

Dans `PurchaseLinesPage`, après le choix du produit (le fournisseur est connu par l'en-tête) :

- **Bandeau discret** : « Accord AC-12 — DC01 2027 : reste 12,4 t / 40 t, prix contractuel
  1,10 €/kg ».
- **Prix proposé** selon 2.5.
- **Écart signalé** si le prix saisi diffère du prix contractuel (texte d'avertissement,
  aucun blocage).
- **Indicateur dans la ligne enregistrée** (icône « sous accord »), avec un menu pour exclure.

Un endpoint `GET …/agreements/applicable?companies_id=&product_id=&date=` sert ces
informations et répond vide sans requête lourde quand aucun accord n'existe.

### 3.5 Alertes

| Alerte | Valeur | Coût | Lot |
|---|---|---|---|
| **Badge visuel** dans la liste et la fiche : en retard sur le rythme, dépassé, échéance < N jours | Forte : c'est l'écran qu'on regarde | Nul (calcul déjà fait) | **1** |
| **Notification « échéance proche et projection < seuil »** (ex. 60 jours, < 80 %) | La plus utile : c'est le moment où l'on peut encore réagir (commander, renégocier) avant de perdre la remise ou de payer une pénalité | S : commande quotidienne sur le patron de `quality:dispatch-calibration-alerts`, notification base | **3** |
| Notification de **dépassement** | Faible : le dépassement n'est pas un risque dans la plupart des contrats (il peut l'être si le prix n'est garanti que jusqu'au volume) | S | 3, si Q6 le justifie |
| Widget tableau de bord « Accords fournisseurs » | Moyenne | S (`WidgetRegistry`, `permission: 'purchases'`) | 3 |
| Alerte d'écart de prix à la saisie | Forte, sans notification | Fait partie de 3.4 | 2 |

**Recommandation pour le premier lot** : badges uniquement. Les notifications dépendent du
planificateur, dont le bon fonctionnement sur l'instance client est encore à confirmer
(dette « Exploitation »).

---

## 4. Découpage en lots

### Lot 1 — Suivre les accords (effort **M**)

**Contenu** :

- les deux tables et les deux colonnes nullables de `purchase_lines` ;
- le service de rattachement (règle, priorités, rattachement rétroactif à l'activation) et
  l'observer `PurchaseLines` ;
- le service de calcul ;
- la liste, la fiche (lignes, commandes rattachées, exclusion et rattachement manuel,
  projection chiffrée) et l'onglet de la fiche fournisseur ;
- les badges ;
- la GED du contrat ;
- les traductions `resources/lang/*/agreements.php` ;
- l'entrée de menu.

**Unités au lot 1** : un engagement en quantité ne compte que les lignes **de même unité**.
Les autres sont listées « non convertibles ». Les engagements en montant fonctionnent sur
toutes les lignes. Le cas « 60 000 € d'acier » est donc couvert, et « 40 t de DC01 » l'est
si les achats sont saisis en kg avec un engagement en kg (affichage en t au lot 2).

**Fichiers touchés** :

- *Nouveaux* :
  - 2 à 3 migrations ;
  - `app/Models/Purchases/SupplierAgreement*.php` ;
  - `app/Services/Purchases/Agreements/{SupplierAgreementResolver, SupplierAgreementProgressService}.php` ;
  - `app/Observers/PurchaseLinesObserver.php` ;
  - `app/Http/Controllers/Purchases/SupplierAgreementController.php` ;
  - vues shell `resources/views/purchases/agreements-*.blade.php` ;
  - `resources/js/components/purchases/agreements/*` ;
  - les traductions.
- *Modifiés* :
  - `routes/web.php` (bloc achats) ;
  - `AppServiceProvider` (enregistrement de l'observer) ;
  - `resources/js/app.js` (montages) ;
  - `companies-show.blade.php` (un onglet) ;
  - `CompaniesController@show` (rien, si l'onglet charge en JSON) ;
  - `config/adminlte.php` (menu) ;
  - `FileableRegistry` (un alias) ;
  - `PurchaseLines` (`$fillable`, une relation).

**Risques de régression sur la chaîne achat** :

| Risque | Parade |
|---|---|
| L'observer ralentit ou fait échouer la création d'une ligne d'achat | Sortie immédiate sans accord actif (existence en cache, vidée à l'activation et à la clôture). Toute exception du rattachement est **journalisée et avalée** : un rattachement raté ne doit jamais empêcher une commande. Test dédié. |
| `$fillable` de `PurchaseLines` modifié | Ajout seulement, aucune suppression |
| Duplication de ligne (`duplicateLineJson`) qui recopie un lien `manual` | Le lien est remis à `null` à la duplication, puis recalculé |
| Soft delete d'un accord | Les liens restent mais ne sont plus lus (jointure sur accord non supprimé) |

**Tests à écrire** (`tests/Feature/SupplierAgreements/`) :

- sans aucun accord, créer, modifier et dupliquer une ligne d'achat donne exactement le même
  résultat qu'avant (colonnes de lien nulles, aucune requête sur les accords au-delà du
  contrôle en cache) ;
- rattachement par produit, par famille, global ; priorité produit > famille > global ;
  lignes hors période ou d'un autre fournisseur ignorées ;
- refus d'activation de deux accords recouvrants de même périmètre ;
- rattachement rétroactif à l'activation ; liens `manual` et `excluded` conservés au
  recalcul ;
- commandé : statut 1 en « en préparation », statut 5 exclu, lignes et achats supprimés
  exclus ; ligne `global` agrégeant toutes les lignes de l'accord ;
- facturé = `receipt_qty × prix net` calculé depuis `selling_price` / `discount`, juste
  **même quand `unit_price_after_discount` vaut 0** ;
- quantité : lignes d'unité différente comptées en « non convertibles » ;
- comptage de requêtes de la liste (constant pour 1 ou 20 accords) ;
- permissions `purchases-menu` sur toutes les routes ;
- **factories manquantes à créer** : `PurchaseLines`, `PurchaseReceipt(Lines)`,
  `PurchaseInvoice(Lines)` (seul `PurchasesFactory` existe).

### Lot 2 — Assister la commande, convertir les unités (effort **M**)

**Contenu** :

- `methods_units.base_factor` (nullable) et sa saisie sur l'écran des unités ;
- conversion d'unités de même type ;
- conversion masse ↔ unité via `products.weight`, si Q4 est validée ;
- bandeau « accord applicable / reste disponible », prix contractuel proposé et écart de prix
  dans `PurchaseLinesPage` ;
- exclusion depuis la ligne ;
- alignement de `ReorderService` sur le même ordre de priorité de prix (si Q9).

**Fichiers touchés** :

- `PurchaseLinesPage.jsx` et `purchases-show.blade.php` (endpoint) ;
- `PurchaseLinesController` (endpoint « applicable », lecture seule) ;
- `SelectDataService::searchProducts` (renvoyer `purchased_price`, ajout de champ) ;
- `ReorderService` / `ReorderPage.jsx` ;
- l'écran des unités ;
- le service de calcul.

**Risques** :

- c'est l'écran de saisie utilisé en production : le bandeau et le prix proposé ne doivent
  pas changer le comportement sans accord (sauf si Q10 en décide autrement) ;
- un `base_factor` mal saisi fausse les quantités : seuls des accords sont concernés, rien
  d'autre ne lit la colonne.

**Tests** :

- conversions (kg ↔ t, unité → kg par poids, produit sans poids ⇒ non convertible) ;
- endpoint « applicable » vide sans accord ;
- priorité accord > grille > prix d'achat ;
- prix proposé inchangé sans accord (si Q10 = non) ;
- Vitest sur l'affichage du bandeau.

### Lot 3 — Alertes et pilotage (effort **S**)

**Contenu** :

- commande `wem:agreements:dispatch-alerts` (échéance proche et projection sous le seuil,
  dépassement si retenu) et `SupplierAgreementNotification` (canal base, mail selon les
  préférences) ;
- planification dans `routes/console.php` et ajout aux tableaux de `CLAUDE.md` ;
- courbe cumulée mensuelle sur la fiche ;
- widget tableau de bord ;
- export XLSX de l'état des accords (patron FEC / paie, `maatwebsite/excel`).

**Risques** : faibles, il n'y a que des ajouts. La dépendance au planificateur est à vérifier
sur l'instance client.

**Tests** :

- sélection des accords à alerter (bornes de dates, seuil, accords clos et brouillons
  exclus) ;
- pas de double notification le même jour.

### Hors lots, à envisager plus tard

- engagement par **matière** (`products.material`, texte libre) ou par **liste d'articles** ;
- calcul de masse par dimensions × densité ;
- avoirs fournisseurs (prérequis : leur existence) ;
- accords **clients** (même modèle, côté vente) ;
- remise de fin d'année (RFA) calculée sur le réalisé.

---

## 5. Questions ouvertes

### 5.0 Décisions par défaut

Toutes les questions sont tranchées : Q1, Q4 et Q6 par vous le 15/10/2026, les onze autres
par une décision par défaut, appliquée sauf avis contraire.

| # | Décision par défaut | Qui tranche |
|---|---|---|
| Q1 | **Tranché (15/10/2026)** : l'organisation des familles ne doit pas compter. Le type de périmètre se choisit à la saisie de chaque engagement : produit, famille, **matière** (champ « Matière » du produit) ou global. Le périmètre « matière » entre au lot 1. | décidé |
| Q2 | Le commandé compte à partir du statut « Commandé » ; « En cours » est affiché à part | par défaut |
| Q3 | Date de création de la commande d'achat | par défaut |
| Q4 | **Tranché (15/10/2026)** : le champ Poids du produit est le poids d'une tôle. La conversion unité → kg par ce poids est retenue au lot 2 ; un produit sans poids reste « non convertible ». | décidé |
| Q5 | Chaque engagement (quantité, montant) a sa propre barre ; l'accord est atteint quand toutes sont à 100 % | par défaut |
| Q6 | **Tranché (15/10/2026)** : le prix négocié tient au-delà du volume. Le dépassement n'est qu'un badge, sans notification, et le prix contractuel reste proposé. | décidé |
| Q7 | Un futur avoir fournisseur réduit le facturé seulement | par défaut |
| Q8 | Ligne facturée puis supprimée : exclue et signalée en anomalie | par défaut |
| Q9 | Oui, le réappro propose le même prix que la saisie | par défaut |
| Q10 | Non dans ce chantier : correctif séparé, annoncé dans la note de version | par défaut |
| Q11 | `AC-0001`, `AC-0002`… | par défaut |
| Q12 | Refus d'activer deux accords qui se recouvrent sur le même périmètre | par défaut |
| Q13 | Pas de changement de colonne : les achats en tonnes se saisissent en kg | par défaut |
| Q14 | Permission achats existante (`purchases-menu`) | par défaut |

Le détail de chaque question suit, pour mémoire.

### Métier

1. **Périmètre « famille »** : chez le client 1, les familles sont-elles organisées par
   matière (« Tôle DC01 », « Tôle S235 »), ou par forme avec la matière dans
   `products.material` ? Dans le second cas, « 40 t de DC01 » ne se décrit ni par une famille
   ni par un produit unique. Il faut alors un périmètre « matière » ou « liste d'articles »,
   qui ne sont pas dans le lot 1.
2. **Le commandé compte-t-il dès la commande envoyée (statut 2)**, ou seulement le reçu ?
   Ma proposition : commandé = statuts 2, 3 et 4, reçu affiché à côté.
3. **Date de rattachement** : date de commande (`purchases.created_at`, la seule disponible),
   date de livraison prévue de la ligne, ou date de réception ? Proposition : la date de
   commande.
4. **`products.weight` vaut-il « kg par unité de stock »** chez le client (tôle entière,
   barre…) ? S'il est fiable, la conversion à la tôle est possible au lot 2. Sinon,
   seules les lignes saisies en kg comptent en tonnes.
5. **Quantité ET montant sur une même ligne** : faut-il atteindre les deux, ou l'un des deux
   suffit-il ? Cela change la couleur de la barre, pas le modèle.
6. **Le dépassement est-il un risque** dans les contrats du client (prix garanti seulement
   jusqu'au volume, par exemple) ? Si oui : alerte de dépassement au lot 3, et prix
   contractuel non proposé au-delà du volume.
7. **Avoirs et retours fournisseurs** : quand ils existeront, doivent-ils réduire le
   commandé, le facturé, ou les deux ? Proposition : le facturé seulement.
8. **Ligne facturée puis supprimée** : l'exclure (calcul actuel) ou la garder dans le
   facturé ? Proposition : l'exclure, et la signaler comme anomalie sur la fiche.

### Produit / technique

9. **Aligner le réappro** sur le même ordre de prix (accord > grille > prix d'achat) ?
   Recommandé, pour qu'un même article ne se voie pas proposer deux prix.
10. **Corriger le prix proposé par défaut** dans la saisie manuelle (prix de vente
    aujourd'hui) même sans accord ? C'est un correctif, mais il est visible du client. À
    faire de préférence en ticket séparé, annoncé dans la note de version.
11. **Numérotation des accords** : `AC-{id}` simple ou un compteur paramétrable comme les
    autres documents ?
12. **Accords recouvrants** : le refus à l'activation (même fournisseur, même périmètre,
    périodes qui se chevauchent) convient-il ? L'alternative est une priorité saisie à la
    main.
13. **`purchase_lines.qty` en décimal** : passer la colonne de `int` à `decimal(10,3)`,
    comme les autres tables en avril, est un élargissement de type et non un ajout. Il ne
    rentre pas dans la règle « migrations additives » sans votre accord. Nécessaire seulement
    si des achats doivent être saisis en tonnes décimales.
14. **Visibilité** : l'accord porte des conditions commerciales. Faut-il une permission
    dédiée (`purchase-agreements`) plutôt que `purchases-menu` ?

---

## 6. Annexe — anomalies relevées en chemin (hors périmètre)

Constatées pendant l'étude, **non corrigées**. Chacune mérite un ticket à part, et les deux
premières touchent l'instance en production.

| # | Constat | Effet visible | Gravité |
|---|---|---|---|
| A1 | `PurchaseLinesController::storeLineJson` / `updateLineJson` ne renseignent ni `unit_price_after_discount` ni `total_selling_price` | L'écran « En attente de facturation » valorise ces lignes à **0 €**. Le total « acheté » du dossier d'affaire et `PurchaseKPIService::...sum('total_selling_price')` les sous-évaluent. | **Corrigé le 15/10/2026** (`PurchaseLines::netPrices`, reprise `wem:purchases:backfill-line-totals`) |
| A2 | `AccountingEntryService::createPurchaseEntry` valorise l'écriture avec `purchase_lines.qty` (commandé), alors qu'une ligne de facture correspond à **une** ligne de réception | Une ligne reçue en deux fois et facturée en deux fois produit deux écritures du montant total : **double comptabilisation** possible | Haute (comptable) |
| A3 | `PurchaseInvoiceService::getPurchasesWaintingInvoiceLines` filtre sur `invoiced_qty < qty` sans exclure les lignes de réception déjà facturées | Une même réception peut être refacturée | Moyenne (à confirmer) |
| A4 | La saisie manuelle d'une ligne d'achat propose `product.selling_price` (prix de **vente**) | Prix d'achat proposé faux | Moyenne |
| A5 | `purchase_lines.qty`, `receipt_qty` et `invoiced_qty` sont des entiers alors que `purchase_receipt_lines.receipt_qty` est décimal | Réception de 12,5 kg ⇒ `invoiced_qty` tronqué | Moyenne |
| A6 | `updateLineJson` / `destroyLineJson` sans contrôle de statut ni de quantité reçue ou facturée | Une ligne facturée peut être modifiée ou supprimée | Moyenne |
| A7 | Graphique mensuel des factures d'achat (`PurchaseKPIService`, ~l. 360) : somme de `order_lines.selling_price` (prix de vente client) | Graphique faux | Basse (à confirmer) |
| A8 | `PurchasesRFQController::storePurchaseApi` : contact et adresse inversés à l'appel de `createPurchaseOrder` | Adresse/contact faux sur les commandes issues des tâches | Basse à vérifier |
| A9 | Relations mal clées : `ProductsPreferredSupplier::QuantityPrice()`, `ProductsQuantityPrice::supplier()` | Code mort, piège pour qui les utiliserait | Basse |
| A10 | `PurchaseOrderService` passe `'statu' => 1` sur des lignes qui n'ont pas de colonne statut | Aucun (ignoré) | Info |
