import React, { useState, useEffect } from 'react';
import LineChart from '../primitives/LineChart.jsx';
import WidgetCard from '../WidgetCard.jsx';

// ─── Helpers ──────────────────────────────────────────────────────────────────

const MONTH_KEYS = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

/**
 * Convertit [{month, orderSum}] → tableau de 12 valeurs
 * commençant au mois fiscal (startMonth 1=jan…12=dec).
 */
function toMonthlyArray(rows, valueKey = 'orderSum', startMonth = 1) {
    const byMonth = {};
    (rows ?? []).forEach(r => { byMonth[r.month] = parseFloat(r[valueKey]) || 0; });
    return Array.from({ length: 12 }, (_, i) => {
        const calMonth = ((startMonth - 1 + i) % 12) + 1;
        return byMonth[calMonth] ?? 0;
    });
}

/**
 * Convertit {amount1..amount12} → tableau de 12 valeurs
 * commençant au mois fiscal.
 */
function targetToArray(target, startMonth = 1) {
    if (!target) return null;
    return Array.from({ length: 12 }, (_, i) => {
        const calMonth = ((startMonth - 1 + i) % 12) + 1;
        return parseFloat(target[`amount${calMonth}`]) || 0;
    });
}

/**
 * Normalise les données brutes (Blade ou API) en séries LineChart.
 *
 * raw peut être :
 *   - { orders, deliveries, invoices, purchases, target }   (réponse API)
 *   - { orderMonthlyRecap, deliveryMonthlyRecap,
 *       invoiceMonthlyRecap, purchaseMonthlyRecap,
 *       estimatedBudget }                                   (prop Blade)
 */
function normalize(raw, trans, startMonth = 1) {
    if (!raw) return [];

    // Détecter le format : API vs Blade
    const isApi = 'orders' in raw;

    const ordersRows   = isApi ? raw.orders    : raw.orderMonthlyRecap;
    const deliveryRows = isApi ? raw.deliveries : raw.deliveryMonthlyRecap;
    const invoiceRows  = isApi ? raw.invoices   : raw.invoiceMonthlyRecap;
    const purchaseRows = isApi ? raw.purchases  : raw.purchaseMonthlyRecap;
    const fiscalMonth  = isApi ? (raw.fiscalYearStartMonth ?? startMonth) : startMonth;

    const targetRaw = isApi
        ? raw.target
        : (Array.isArray(raw.estimatedBudget) ? raw.estimatedBudget[0] : raw.estimatedBudget);

    const series = [
        {
            label:  trans.orders_forecast    ?? 'Prévision commandes',
            color:  'rgba(60,141,188,0.9)',
            area:   true,
            values: toMonthlyArray(ordersRows, 'orderSum', fiscalMonth),
        },
        {
            label:  trans.delivered_revenues ?? 'CA livré',
            color:  'rgba(240,173,78,0.85)',
            values: toMonthlyArray(deliveryRows, 'orderSum', fiscalMonth),
        },
        {
            label:  trans.invoiced_revenues  ?? 'CA facturé',
            color:  'rgba(217,83,79,0.85)',
            values: toMonthlyArray(invoiceRows, 'orderSum', fiscalMonth),
        },
    ];

    if (purchaseRows && purchaseRows.length > 0) {
        series.push({
            label:  trans.purchase_revenues ?? 'Achats',
            color:  'rgba(21,83,79,0.8)',
            values: toMonthlyArray(purchaseRows, 'purchaseSum', fiscalMonth).map(v => -v),
        });
    }

    const targetValues = targetToArray(targetRaw, fiscalMonth);
    if (targetValues) {
        series.push({
            label:  trans.order_targets ?? 'Objectif',
            color:  'rgba(40,167,69,1)',
            dashed: true,
            values: targetValues,
        });
    }

    return series;
}

// ─── Totaux récap (pied de card) ─────────────────────────────────────────────

function formatCurrency(value, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR', maximumFractionDigits: 0,
        }).format(value);
    } catch {
        return value.toFixed(0);
    }
}

function TotalsContent({ series, trans, currency, locale }) {
    if (!series.length) return null;

    const sum = (idx) => (series[idx]?.values ?? []).reduce((s, v) => s + v, 0);

    const forecastTotal  = sum(0);
    const deliveredTotal = sum(1);
    const invoicedTotal  = sum(2);

    // Objectif = dernière série si dashed
    const targetSeries = series.find(s => s.dashed);
    const targetTotal  = targetSeries
        ? targetSeries.values.reduce((s, v) => s + v, 0)
        : null;

    const goalPct = targetTotal > 0
        ? Math.round((invoicedTotal / targetTotal) * 100)
        : null;

    return (
        <div className="row text-center">
                <div className="col-sm-3 col-6">
                    <div className="description-block border-right">
                        <h5 className="description-header" style={{ fontSize: '0.85rem' }}>
                            {formatCurrency(forecastTotal, currency, locale)}
                        </h5>
                        <span className="description-text" style={{ fontSize: '0.7rem' }}>
                            {trans.total_order_forecasted ?? 'Prévision'}
                        </span>
                    </div>
                </div>
                <div className="col-sm-3 col-6">
                    <div className="description-block border-right">
                        <h5 className="description-header" style={{ fontSize: '0.85rem' }}>
                            {formatCurrency(deliveredTotal, currency, locale)}
                        </h5>
                        <span className="description-text" style={{ fontSize: '0.7rem' }}>
                            {trans.total_order_delivered ?? 'CA livré'}
                        </span>
                    </div>
                </div>
                <div className="col-sm-3 col-6">
                    <div className="description-block border-right">
                        <h5 className="description-header" style={{ fontSize: '0.85rem' }}>
                            {formatCurrency(invoicedTotal, currency, locale)}
                        </h5>
                        <span className="description-text" style={{ fontSize: '0.7rem' }}>
                            {trans.total_invoiced ?? 'CA facturé'}
                        </span>
                    </div>
                </div>
                <div className="col-sm-3 col-6">
                    <div className="description-block">
                        <h5 className="description-header" style={{ fontSize: '0.85rem' }}>
                            {targetTotal !== null
                                ? `${formatCurrency(invoicedTotal, currency, locale)} / ${formatCurrency(targetTotal, currency, locale)} (${goalPct}%)`
                                : '—'}
                        </h5>
                        <span className="description-text" style={{ fontSize: '0.7rem' }}>
                            {trans.goal ?? 'Objectif'}
                        </span>
                    </div>
                </div>
            </div>
    );
}

// ─── OrdersMonthlyWidget ──────────────────────────────────────────────────────

/**
 * OrdersMonthlyWidget — évolution mensuelle commandes / livraisons / factures / objectif
 *
 * Deux modes :
 *
 *   Mode 1 — données pré-chargées (Blade → props, aucun fetch)
 *     <OrdersMonthlyWidget
 *       data={{ orderMonthlyRecap, deliveryMonthlyRecap, invoiceMonthlyRecap,
 *               purchaseMonthlyRecap, estimatedBudget }}
 *       trans={trans}
 *     />
 *
 *   Mode 2 — fetch autonome (dashboard personnalisable)
 *     <OrdersMonthlyWidget
 *       endpoint="/fr/kpi/orders/monthly"
 *       year={2026}
 *       trans={trans}
 *     />
 *
 * Props :
 *   data       object   — données pré-chargées (format Blade)
 *   endpoint   string   — URL de l'API (si data absent)
 *   year       number   — filtre année (mode fetch, défaut: année courante)
 *   trans      object   — traductions
 *   currency   string   — code devise (défaut 'EUR')
 *   locale     string   — locale Intl (défaut 'fr-FR')
 *   showTotals bool     — afficher le récap en pied de card (défaut true)
 *   height     number   — hauteur du graphique en px (défaut 300)
 *   // WidgetCard props
 *   editMode   bool
 *   onRemove   func
 *   onTypeChange func
 */
export default function OrdersMonthlyWidget({
    data: initialData,
    endpoint,
    year,
    fiscalYearStartMonth = 1,
    trans = {},
    currency = 'EUR',
    locale   = 'fr-FR',
    showTotals = true,
    editMode   = false,
    onRemove,
    onTypeChange,
}) {
    const [series, setSeries]   = useState(() => initialData ? normalize(initialData, trans, fiscalYearStartMonth) : null);
    const [loading, setLoading] = useState(!initialData && !!endpoint);
    const [error, setError]     = useState(null);

    // Fetch autonome
    useEffect(() => {
        if (initialData || !endpoint) return;

        const params = new URLSearchParams();
        if (year) params.set('year', year);
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
                setSeries(normalize(json, trans, json.fiscalYearStartMonth ?? fiscalYearStartMonth));
                setLoading(false);
            })
            .catch(err => {
                setError(err.message ?? 'Erreur de chargement');
                setLoading(false);
            });
    }, [endpoint, year]);

    // Ré-normaliser si les données Blade changent
    useEffect(() => {
        if (initialData) setSeries(normalize(initialData, trans, fiscalYearStartMonth));
    }, [initialData, fiscalYearStartMonth]);

    const currentYear = year ?? new Date().getFullYear();
    // Labels pivotés selon le mois de début d'exercice
    const labels = Array.from({ length: 12 }, (_, i) => {
        const key = MONTH_KEYS[((fiscalYearStartMonth - 1) + i) % 12];
        return trans[key] ?? key;
    });
    const isEmpty     = !loading && !error && (!series || series.length === 0);

    const totalsFooter = showTotals && series && series.length > 0 && !loading && !error
        ? <TotalsContent series={series} trans={trans} currency={currency} locale={locale} />
        : null;

    return (
        <WidgetCard
            title={trans.monthly_recap_title ?? `CA mensuel ${currentYear}`}
            icon="fa-chart-bar"
            theme="purple"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_data ?? 'Aucune donnée pour cette période'}
            fillHeight
            footer={totalsFooter}
            editMode={editMode}
            onRemove={onRemove}
            onTypeChange={onTypeChange}
            availableTypes={['line', 'area', 'bar']}
            currentType="line"
        >
            <LineChart
                labels={labels}
                series={series ?? []}
                currency={currency}
                locale={locale}
                showLegend
            />
        </WidgetCard>
    );
}
