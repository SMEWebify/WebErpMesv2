<?php

return [
    'title' => 'Marge (intern)',
    'internal' => 'Interne Information: wird nie auf Kundendokumenten gedruckt.',
    'cost_total' => 'Selbstkosten',
    'sale_total' => 'Verkaufspreis netto',
    'margin' => 'Marge',
    'margin_rate' => 'Marge / Kosten',
    'markup_rate' => 'Marge / Verkaufspreis',
    'column' => 'Marge',
    'column_toggle' => 'Margenspalte anzeigen',
    'unit_cost' => 'Stückkosten',
    'no_cost' => 'Keine Selbstkosten (kein kalkulierter Arbeitsgang, keine Stückliste und keine Baugruppe)',
    'below_threshold' => 'Marge unter dem Schwellenwert von :threshold %',
    'lines_without_cost' => '{1} :count Position ohne Selbstkosten: zum Verkaufspreis gezählt, ohne Kosten.|[2,*] :count Positionen ohne Selbstkosten: zum Verkaufspreis gezählt, ohne Kosten.',
    'cost_help' => 'Kosten = Arbeitsgänge (einschließlich Fremdfertigung) + Stückliste + Baugruppen. Abschnitts-, Zwischensummen- und Textpositionen werden ignoriert.',

    'apply_title' => 'Globale Marge anwenden',
    'apply_rate' => 'Margensatz auf die Kosten (%)',
    'apply_button' => 'Preise neu berechnen',
    'apply_help' => 'Verkaufspreis jeder Position = Kosten × (1 + Satz), Positionsrabatt ausgeglichen. Der aus dem Arbeitsplan berechnete Preis wird deaktiviert. Positionen ohne Kosten bleiben unverändert.',
    'apply_confirm' => 'Den Verkaufspreis aller Positionen aus ihren Kosten neu berechnen?',
    'applied' => '{0} Kein Preis neu berechnet.|{1} :count Preis neu berechnet.|[2,*] :count Preise neu berechnet.',
    'applied_skipped' => ':count Position(en) ohne Kosten zu ihrem Preis belassen.',
    'refused_converted' => 'Dieses Angebot ist in einen Auftrag umgewandelt: seine Preise sind festgeschrieben, die globale Marge kann nicht mehr angewendet werden.',
    'refused_status' => 'Nur ein offenes Angebot kann neu berechnet werden.',
    'locked_converted' => 'Angebot in Auftrag umgewandelt: Preise festgeschrieben.',
];
