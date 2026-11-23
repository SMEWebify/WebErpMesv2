<?php

return [
    'status' => [
        'qualified'      => 'Qualifiziert',
        'unqualified'    => 'Nicht qualifiziert',
        'review_overdue' => 'Überprüfung überfällig',
    ],

    'detail' => [
        'no_evaluation'  => 'keine Lieferantenbewertung.',
        'not_approved'   => 'letzte Bewertung im Status „:status“.',
        'review_overdue' => 'Qualifizierungsüberprüfung überfällig seit dem :date.',
    ],

    'blocked_title'           => 'Lieferant nicht qualifiziert',
    'blocked_create'          => 'Bestellung abgelehnt: Lieferant nicht qualifiziert.',
    'blocked_validate'        => 'Freigabe der Bestellung abgelehnt: Lieferant nicht qualifiziert.',
    'blocked_supplier_change' => 'Lieferantenwechsel bei einer freigegebenen Bestellung abgelehnt: Lieferant nicht qualifiziert. Setzen Sie die Bestellung auf „In Bearbeitung“ zurück, um den Lieferanten zu wechseln.',
    'no_permission'           => 'Sie sind nicht berechtigt, bei einem nicht qualifizierten Lieferanten zu bestellen. Eine Preisanfrage bleibt möglich.',
    'override_reason'         => 'Grund der Ausnahmegenehmigung',
    'override_reason_help'    => 'Wird an der Bestellung protokolliert und ist in der Lieferantenakte sichtbar.',
    'override_reason_required'=> 'Geben Sie den Grund der Ausnahmegenehmigung an.',
    'override'                => 'Trotzdem bestellen',

    // Fiche société et fiche commande
    'label'           => 'Qualifizierung',
    'overrides_title' => 'Ausnahmegenehmigungen „Lieferant nicht qualifiziert“',
    'override_on'     => 'Ausnahmegenehmigung für :document',
    'context'         => [
        'create'          => 'Anlage',
        'validate'        => 'Freigabe',
        'supplier_change' => 'Lieferantenwechsel',
    ],

    // Export
    'export'          => 'Qualifizierte Lieferanten (CSV)',
    'export_columns'  => [
        'code'              => 'Code',
        'label'             => 'Firmenname',
        'qualification'     => 'Qualifizierung',
        'evaluation_status' => 'Status der letzten Bewertung',
        'evaluated_at'      => 'Bewertet am',
        'approved_at'       => 'Genehmigt am',
        'next_review_at'    => 'Nächste Überprüfung',
    ],
];
