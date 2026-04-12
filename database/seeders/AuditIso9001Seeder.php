<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit\AuditChecklist;
use App\Models\Audit\AuditChecklistItem;

class AuditIso9001Seeder extends Seeder
{
    public function run(): void
    {
        $checklists = [
            [
                'title'      => 'Contexte de l\'organisme',
                'iso_clause' => '4',
                'items' => [
                    ['question' => 'L\'organisme a-t-il déterminé les enjeux internes et externes pertinents pour son orientation stratégique et son SMQ ?', 'iso_clause' => '4.1'],
                    ['question' => 'Les parties intéressées pertinentes sont-elles identifiées ? Leurs exigences sont-elles déterminées et surveillées ?', 'iso_clause' => '4.2'],
                    ['question' => 'Le domaine d\'application du SMQ est-il documenté et disponible ?', 'iso_clause' => '4.3'],
                    ['question' => 'Les processus nécessaires au SMQ sont-ils déterminés, leurs interactions et séquences établies ?', 'iso_clause' => '4.4'],
                    ['question' => 'Des indicateurs de performance des processus sont-ils définis et mesurés ?', 'iso_clause' => '4.4'],
                ],
            ],
            [
                'title'      => 'Leadership et engagement',
                'iso_clause' => '5',
                'items' => [
                    ['question' => 'La direction démontre-t-elle son leadership et son engagement envers le SMQ (revues, ressources, politique) ?', 'iso_clause' => '5.1'],
                    ['question' => 'La politique qualité est-elle documentée, communiquée et comprise par le personnel ?', 'iso_clause' => '5.2'],
                    ['question' => 'Les rôles, responsabilités et autorités sont-ils définis, attribués et communiqués dans l\'organisme ?', 'iso_clause' => '5.3'],
                    ['question' => 'L\'orientation client est-elle promue par la direction (satisfaction, risques, conformité) ?', 'iso_clause' => '5.1.2'],
                ],
            ],
            [
                'title'      => 'Planification',
                'iso_clause' => '6',
                'items' => [
                    ['question' => 'Les risques et opportunités ont-ils été déterminés et des actions planifiées pour y répondre ?', 'iso_clause' => '6.1'],
                    ['question' => 'Des objectifs qualité mesurables sont-ils établis et déployés aux fonctions et niveaux pertinents ?', 'iso_clause' => '6.2'],
                    ['question' => 'Les plans d\'actions pour atteindre les objectifs qualité sont-ils documentés (qui, quoi, quand, comment, ressources) ?', 'iso_clause' => '6.2.2'],
                    ['question' => 'La planification des modifications du SMQ est-elle maîtrisée (conséquences, intégrité, ressources) ?', 'iso_clause' => '6.3'],
                ],
            ],
            [
                'title'      => 'Supports et ressources',
                'iso_clause' => '7',
                'items' => [
                    ['question' => 'Les ressources humaines nécessaires au SMQ sont-elles disponibles et compétentes ?', 'iso_clause' => '7.1.2'],
                    ['question' => 'L\'infrastructure nécessaire à la réalisation des produits/services est-elle maintenue ?', 'iso_clause' => '7.1.3'],
                    ['question' => 'L\'environnement de travail est-il maîtrisé (facteurs humains et physiques) ?', 'iso_clause' => '7.1.4'],
                    ['question' => 'Les ressources pour la surveillance et la mesure sont-elles adaptées ? L\'étalonnage/vérification est-il réalisé ?', 'iso_clause' => '7.1.5'],
                    ['question' => 'Les compétences requises pour les postes impactant la qualité sont-elles définies et vérifiées ?', 'iso_clause' => '7.2'],
                    ['question' => 'Le personnel est-il sensibilisé à la politique qualité, aux objectifs et à sa contribution au SMQ ?', 'iso_clause' => '7.3'],
                    ['question' => 'La communication interne et externe relative au SMQ est-elle maîtrisée ?', 'iso_clause' => '7.4'],
                    ['question' => 'Les informations documentées requises par la norme et jugées nécessaires sont-elles créées, à jour et maîtrisées ?', 'iso_clause' => '7.5'],
                    ['question' => 'La maîtrise des informations documentées externes est-elle assurée (identification, distribution) ?', 'iso_clause' => '7.5.3'],
                ],
            ],
            [
                'title'      => 'Réalisation des activités opérationnelles',
                'iso_clause' => '8',
                'items' => [
                    ['question' => 'La planification et la maîtrise opérationnelles sont-elles définies pour les produits/services (critères, ressources, maîtrise) ?', 'iso_clause' => '8.1'],
                    ['question' => 'Les exigences liées aux produits et services sont-elles déterminées (réglementaires, client, implicites) ?', 'iso_clause' => '8.2.2'],
                    ['question' => 'La revue des exigences relatives aux produits/services est-elle réalisée avant engagement (offre, contrat) ?', 'iso_clause' => '8.2.3'],
                    ['question' => 'La communication avec les clients est-elle maîtrisée (informations, devis, réclamations, retours) ?', 'iso_clause' => '8.2.1'],
                    ['question' => 'La conception et développement est-elle planifiée et maîtrisée (si applicable) ?', 'iso_clause' => '8.3'],
                    ['question' => 'Les prestataires externes sont-ils évalués, sélectionnés et leur performance surveillée ?', 'iso_clause' => '8.4'],
                    ['question' => 'La production/prestation de service est-elle réalisée dans des conditions maîtrisées (procédures, équipements, surveillance) ?', 'iso_clause' => '8.5.1'],
                    ['question' => 'L\'identification et la traçabilité des produits sont-elles assurées tout au long de la réalisation ?', 'iso_clause' => '8.5.2'],
                    ['question' => 'Les propriétés des clients/prestataires externes sont-elles identifiées, protégées et sauvegardées ?', 'iso_clause' => '8.5.3'],
                    ['question' => 'La préservation des sorties (manutention, stockage, conditionnement, transport) est-elle assurée ?', 'iso_clause' => '8.5.4'],
                    ['question' => 'Les activités après livraison sont-elles maîtrisées (garantie, maintenance, recyclage) ?', 'iso_clause' => '8.5.5'],
                    ['question' => 'La maîtrise des modifications non planifiées est-elle assurée et documentée ?', 'iso_clause' => '8.5.6'],
                    ['question' => 'La libération des produits/services est-elle réalisée selon les dispositions planifiées (contrôles, approbations) ?', 'iso_clause' => '8.6'],
                    ['question' => 'Les sorties non conformes sont-elles identifiées, maîtrisées et traitées ?', 'iso_clause' => '8.7'],
                ],
            ],
            [
                'title'      => 'Évaluation des performances',
                'iso_clause' => '9',
                'items' => [
                    ['question' => 'La surveillance, la mesure, l\'analyse et l\'évaluation des processus et produits sont-elles planifiées et réalisées ?', 'iso_clause' => '9.1.1'],
                    ['question' => 'La satisfaction du client est-elle surveillée et des méthodes de collecte définies ?', 'iso_clause' => '9.1.2'],
                    ['question' => 'L\'analyse et l\'évaluation des données du SMQ sont-elles réalisées (conformité, satisfaction, fournisseurs, risques) ?', 'iso_clause' => '9.1.3'],
                    ['question' => 'Des audits internes sont-ils planifiés et réalisés à intervalles réguliers ?', 'iso_clause' => '9.2'],
                    ['question' => 'Les auditeurs internes sont-ils indépendants des activités auditées ?', 'iso_clause' => '9.2.2'],
                    ['question' => 'Les résultats des audits internes sont-ils communiqués à la direction concernée ?', 'iso_clause' => '9.2.2'],
                    ['question' => 'La revue de direction est-elle réalisée à intervalles planifiés avec toutes les entrées requises ?', 'iso_clause' => '9.3'],
                    ['question' => 'Les sorties de la revue de direction incluent-elles des décisions et actions relatives aux objectifs et ressources ?', 'iso_clause' => '9.3.3'],
                ],
            ],
            [
                'title'      => 'Amélioration',
                'iso_clause' => '10',
                'items' => [
                    ['question' => 'Des actions d\'amélioration continue sont-elles déterminées et mises en œuvre ?', 'iso_clause' => '10.1'],
                    ['question' => 'Les non-conformités sont-elles traitées (correction, analyse des causes, actions correctives) ?', 'iso_clause' => '10.2'],
                    ['question' => 'L\'efficacité des actions correctives est-elle évaluée ?', 'iso_clause' => '10.2.1'],
                    ['question' => 'Les actions correctives sont-elles documentées et les informations documentées mises à jour ?', 'iso_clause' => '10.2.2'],
                    ['question' => 'L\'amélioration continue de l\'adéquation, de la pertinence et de l\'efficacité du SMQ est-elle démontrée ?', 'iso_clause' => '10.3'],
                ],
            ],
        ];

        foreach ($checklists as $checklistData) {
            $items = $checklistData['items'];
            unset($checklistData['items']);
            $checklistData['is_default'] = true;

            $checklist = AuditChecklist::firstOrCreate(
                ['title' => $checklistData['title'], 'iso_clause' => $checklistData['iso_clause']],
                $checklistData
            );

            foreach ($items as $index => $item) {
                AuditChecklistItem::firstOrCreate(
                    [
                        'audit_checklist_id' => $checklist->id,
                        'question'           => $item['question'],
                    ],
                    [
                        'iso_clause'  => $item['iso_clause'],
                        'order_index' => $index,
                    ]
                );
            }
        }

        // Default processes ISO 9001
        $defaultProcesses = [
            ['name' => 'Direction / Management', 'description' => 'Pilotage stratégique, revue de direction, politique qualité'],
            ['name' => 'Commerce / Devis', 'description' => 'Gestion des offres, revue des exigences clients, contrats'],
            ['name' => 'Achats / Approvisionnements', 'description' => 'Sélection fournisseurs, commandes, réception, évaluation'],
            ['name' => 'Production / Réalisation', 'description' => 'Fabrication, contrôles en cours, libération produits'],
            ['name' => 'Qualité / Contrôle', 'description' => 'Inspections, non-conformités, actions correctives, audits'],
            ['name' => 'Logistique / Expéditions', 'description' => 'Préparation, expédition, livraison, SAV'],
            ['name' => 'Ressources Humaines', 'description' => 'Compétences, formations, sensibilisation du personnel'],
            ['name' => 'Méthodes / Bureau d\'études', 'description' => 'Conception, gammes, nomenclatures, documentation technique'],
            ['name' => 'Maintenance / Infrastructure', 'description' => 'Entretien équipements, étalonnage, GMAO'],
        ];

        foreach ($defaultProcesses as $processData) {
            \App\Models\Audit\AuditProcess::firstOrCreate(
                ['name' => $processData['name']],
                $processData
            );
        }
    }
}
