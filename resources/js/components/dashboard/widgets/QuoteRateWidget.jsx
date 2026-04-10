import React, { useState, useEffect } from 'react';
import DonutChart from '../primitives/DonutChart.jsx';
import WidgetCard from '../WidgetCard.jsx';

// ─── Config statuts devis ─────────────────────────────────────────────────────

const STATUS_CONFIG = {
    1: { color: '#17a2b8', labelKey: 'open'     },
    2: { color: '#ffc107', labelKey: 'send'     },
    3: { color: '#28a745', labelKey: 'win'      },
    4: { color: '#dc3545', labelKey: 'lost'     },
    5: { color: '#6c757d', labelKey: 'closed'   },
    6: { color: '#007bff', labelKey: 'obsolete' },
};

/**
 * Transforme la réponse brute du service QuoteKPIService en items DonutChart.
 * Accepte les deux formes :
 *   - { data: [...] }          (réponse JSON de l'endpoint /kpi/quotes/rate)
 *   - [...]                    (données passées directement en prop depuis Blade)
 */
function normalize(raw, trans) {
    const rows = Array.isArray(raw) ? raw : (raw?.data ?? []);
    return rows
        .map(row => ({
            label: trans[STATUS_CONFIG[row.statu]?.labelKey] ?? `Statut ${row.statu}`,
            color: STATUS_CONFIG[row.statu]?.color ?? '#adb5bd',
            value: parseInt(row.QuoteCountRate, 10) || 0,
        }))
        .filter(i => i.value > 0);
}

// ─── QuoteRateWidget ──────────────────────────────────────────────────────────

/**
 * QuoteRateWidget — taux de transformation des devis par statut
 *
 * Deux modes d'utilisation :
 *
 *   Mode 1 — données pré-chargées (Blade → data-* → React, aucun fetch)
 *     <QuoteRateWidget data={quotesDataRate} trans={trans} />
 *
 *   Mode 2 — fetch autonome (dashboard personnalisable, page devis)
 *     <QuoteRateWidget endpoint="/fr/kpi/quotes/rate" trans={trans} year={2026} companyId={42} />
 *
 * Props :
 *   data        array           — données pré-chargées [{statu, QuoteCountRate}]
 *   endpoint    string          — URL de l'API (si data absent)
 *   year        number          — filtre année (mode fetch, défaut: année courante)
 *   companyId   number|null     — filtre entreprise optionnel
 *   trans       object          — traductions : { open, send, win, lost, closed, obsolete, total, quote_rate_title }
 *   // WidgetCard props
 *   editMode    bool
 *   onRemove    func
 *   onTypeChange func
 */
export default function QuoteRateWidget({
    data: initialData,
    endpoint,
    year,
    companyId,
    trans = {},
    editMode = false,
    onRemove,
    onTypeChange,
}) {
    const [items, setItems]     = useState(() => initialData ? normalize(initialData, trans) : null);
    const [loading, setLoading] = useState(!initialData && !!endpoint);
    const [error, setError]     = useState(null);

    // Fetch uniquement si pas de données pré-chargées
    useEffect(() => {
        if (initialData || !endpoint) return;

        const params = new URLSearchParams();
        if (year)      params.set('year',       year);
        if (companyId) params.set('company_id', companyId);

        const url = params.toString() ? `${endpoint}?${params}` : endpoint;

        setLoading(true);
        setError(null);

        fetch(url, {
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(json => {
                setItems(normalize(json, trans));
                setLoading(false);
            })
            .catch(err => {
                setError(err.message ?? 'Erreur lors du chargement');
                setLoading(false);
            });
    }, [endpoint, year, companyId]);

    // Ré-normaliser si les données pré-chargées changent (ex: changement de filtre parent)
    useEffect(() => {
        if (initialData) setItems(normalize(initialData, trans));
    }, [initialData]);

    const isEmpty = !loading && !error && (!items || items.length === 0);

    return (
        <WidgetCard
            title={trans.quote_rate_title ?? 'Taux de transformation devis'}
            icon="fa-chart-pie"
            theme="teal"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_quotes ?? 'Aucun devis pour cette période'}
            editMode={editMode}
            onRemove={onRemove}
            onTypeChange={onTypeChange}
            availableTypes={['donut', 'bar']}
            currentType="donut"
        >
            <DonutChart
                data={items ?? []}
                centerLabel={trans.total ?? 'Total'}
                showLegend
            />
        </WidgetCard>
    );
}
