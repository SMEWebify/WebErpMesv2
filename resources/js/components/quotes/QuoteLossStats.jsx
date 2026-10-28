import React, { useEffect, useState } from 'react';
import { apiFetch } from '../../lib/http';
import { formatCurrencyRounded } from '../../utils';

/*
 * Indicateur « pertes commerciales » du tableau de bord des devis : devis perdus
 * par motif (barres, une seule teinte : c'est une grandeur, pas un statut) puis
 * ventilés par catégorie d'affaire (tableau). Données : quotes.json.loss-stats
 * (App\Services\Quotes\QuoteLossStats).
 */

const PERIODS = ['12m', 'year', 'all'];

/** Largeur relative d'une barre (le motif le plus fréquent occupe toute la piste). */
export function barWidth(count, max) {
    if (!max || max <= 0 || !count) return 0;
    return Math.max(2, Math.round((count / max) * 100));
}

function ReasonBars({ reasons, trans, currency, locale }) {
    const max = Math.max(0, ...reasons.map(r => r.count));

    return (
        <ul className="list-unstyled mb-0">
            {reasons.map(r => (
                <li key={r.key} className="mb-2" title={`${r.label} : ${r.count} — ${formatCurrencyRounded(r.amount, currency, locale)} (${r.share} %)`}>
                    <div className="d-flex justify-content-between small">
                        <span className={r.key === 'none' ? 'text-body-secondary fst-italic' : ''}>{r.label}</span>
                        <span>
                            <strong>{r.count}</strong>
                            <span className="text-body-secondary ms-2">{r.share} %</span>
                            <span className="text-body-secondary ms-2">{formatCurrencyRounded(r.amount, currency, locale)}</span>
                        </span>
                    </div>
                    <div className="progress" style={{ height: 8 }} role="progressbar"
                         aria-label={r.label} aria-valuenow={r.count} aria-valuemin={0} aria-valuemax={max}>
                        <div className={`progress-bar ${r.key === 'none' ? 'bg-secondary' : 'bg-primary'}`}
                             style={{ width: `${barWidth(r.count, max)}%`, borderRadius: 4 }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

function CategoryTable({ categories, reasons, trans, currency, locale }) {
    return (
        <div className="table-responsive">
            <table className="table table-sm table-hover mb-0 small">
                <thead className="table-light">
                    <tr>
                        <th>{trans.by_category}</th>
                        {reasons.map(r => <th key={r.key} className="text-end">{r.label}</th>)}
                        <th className="text-end">{trans.total}</th>
                        <th className="text-end">{trans.amount}</th>
                    </tr>
                </thead>
                <tbody>
                    {categories.map(c => (
                        <tr key={c.key}>
                            <td className={c.key === 'none' ? 'text-body-secondary fst-italic' : ''}>{c.label}</td>
                            {reasons.map(r => (
                                <td key={r.key} className="text-end">
                                    {c.by_reason[r.key] ?? <span className="text-body-secondary">—</span>}
                                </td>
                            ))}
                            <td className="text-end fw-bold">{c.count}</td>
                            <td className="text-end">{formatCurrencyRounded(c.amount, currency, locale)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function QuoteLossStats({ endpoint, trans = {}, currency = 'EUR', locale = 'fr-FR' }) {
    const [period, setPeriod]   = useState('12m');
    const [data, setData]       = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError]     = useState(false);

    useEffect(() => {
        if (!endpoint) return;
        setLoading(true);
        setError(false);
        apiFetch(`${endpoint}?period=${period}`)
            .then(setData)
            .catch(() => setError(true))
            .finally(() => setLoading(false));
    }, [endpoint, period]);

    if (!endpoint) return null;

    const periods = trans.periods ?? {};
    const empty = !data || data.total.count === 0;

    return (
        <div className="card card-danger card-outline mb-3">
            <div className="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 className="card-title me-auto"><i className="fas fa-heart-broken me-1" />{trans.title}</h3>
                <select className="form-select form-select-sm" style={{ width: 'auto' }}
                        aria-label={trans.period} value={period} onChange={e => setPeriod(e.target.value)}>
                    {PERIODS.map(p => <option key={p} value={p}>{periods[p] ?? p}</option>)}
                </select>
            </div>
            <div className="card-body">
                {loading && <div className="text-center py-3"><i className="fas fa-spinner fa-spin" /></div>}
                {!loading && error && <div className="alert alert-danger mb-0">{trans.error ?? 'Erreur'}</div>}
                {!loading && !error && empty && <p className="text-body-secondary mb-0">{trans.empty}</p>}
                {!loading && !error && !empty && (
                    <div className="row">
                        <div className="col-lg-4 mb-3 mb-lg-0">
                            <h6 className="text-body-secondary">{trans.by_reason}</h6>
                            <p className="mb-2">
                                <span className="fs-4 fw-bold">{data.total.count}</span>
                                <span className="text-body-secondary ms-1">{trans.count}</span>
                                <span className="text-body-secondary ms-2">{formatCurrencyRounded(data.total.amount, currency, locale)}</span>
                            </p>
                            <ReasonBars reasons={data.reasons} trans={trans} currency={currency} locale={locale} />
                        </div>
                        <div className="col-lg-8">
                            <CategoryTable categories={data.categories} reasons={data.reasons} trans={trans} currency={currency} locale={locale} />
                        </div>
                    </div>
                )}
                <div className="form-text mt-2">{trans.period_help}</div>
            </div>
        </div>
    );
}
