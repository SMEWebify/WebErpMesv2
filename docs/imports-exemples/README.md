# Fichiers d'exemple d'import (Excel)

Classeurs au format d'un export « type ERP » (en-tête en première ligne, données fictives),
importables tels quels. Ils sont rejoués par `tests/Feature/Imports/ImportCsvServiceTest`
(`test_demo_files_import_without_any_rejection`) : ils doivent toujours passer sans rejet.

Ordre conseillé : clients, puis articles, puis gamme.

## clients.xlsx et articles.xlsx — Votre société › Imports/Exports › « Importer des données »

Choisir « Clients » ou « Articles », déposer le fichier : l'assistant reconnaît toutes les
colonnes d'après leur en-tête (Code tiers, Raison sociale, N° TVA intracommunautaire… ;
Référence, Désignation, Épaisseur (mm)…) et l'aperçu marque chaque ligne « À créer ».
Pour les articles, choisir aussi le service, la famille et l'unité donnés à tous.

> Un client importé n'a ni adresse ni contact : ajoutez-les (par défaut) sur sa fiche avant
> d'importer des devis ou des commandes à son nom, sinon ces lignes sont rejetées.

Un article dont la référence existe déjà est mis à jour ; un client dont le code existe déjà
est rejeté (réimporter le fichier ne crée pas de doublon).

## gamme-support-plie.xlsx — écran de gamme d'une ligne › bouton « CSV / Excel »

Les colonnes sont reconnues par leur nom technique (première ligne). `service_code` doit être
le code d'un poste de charge existant (ici `LASER`, `PLIAGE`, `SOUDURE` en opérations,
`MATIERE` en matière) : adaptez-le aux codes de l'instance. `component_code` vide = opération
sans composant ; sinon la référence d'un article existant (`TOLE-S235-3`, importé par
`articles.xlsx`).
