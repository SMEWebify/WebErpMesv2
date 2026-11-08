import React, { useEffect, useState } from 'react';

// Indicateur « coût de non-qualité » de l'index des NC : 12 derniers mois, par mois et
// par fournisseur (NonConformityImpact::nonQualityCosts). Repliée par défaut, état
// mémorisé par navigateur.

const LS_OPEN = 'non_conformities_cost_card_open';

function readOpen() {
    try { return window.localStorage.getItem(LS_OPEN) === '1'; } catch { return false; }
}

export default function NonQualityCostCard({ url, trans, currency = 'EUR', locale = 'fr' }) {
    const t = trans ?? {};
    const [open, setOpen] = useState(readOpen);
    const [data, setData] = useState(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        if (!open || data || !url) return;
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((res) => (res.ok ? res.json() : Promise.reject(res)))
            .then(setData)
            .catch(() => setError(true));
    }, [open, data, url]);

    if (!url) return null;

    const money = (value) => new Intl.NumberFormat(locale, { style: 'currency', currency, maximumFractionDigits: 0 }).format(Number(value ?? 0));
    const monthLabel = (ym) => {
        const [y, m] = ym.split('-').map(Number);
        return new Date(Date.UTC(y, m - 1, 1)).toLocaleDateString(locale, { month: 'short', year: 'numeric', timeZone: 'UTC' });
    };
    const toggle = () => {
        const next = !open;
        setOpen(next);
        try { window.localStorage.setItem(LS_OPEN, next ? '1' : '0'); } catch { /* navigation privée */ }
    };

    return (
        <div className="card card-outline card-danger mb-3">
            <div className="card-header d-flex align-items-center">
                <h3 className="card-title m-0 fs-6">
                    <i className="fas fa-coins me-1" />{t.title ?? 'Coût de non-qualité'}
                    {data && <span className="ms-2 fw-bold text-danger">{money(data.total)}</span>}
                </h3>
                <button type="button" className="btn btn-sm btn-outline-secondary ms-auto" onClick={toggle}>
                    {open ? (t.hide ?? 'Masquer') : (t.show ?? 'Afficher')}
                </button>
            </div>
            {open && (
                <div className="card-body">
                    <p className="small text-body-secondary">{t.intro}</p>
                    {error && <div className="alert alert-danger py-1 small">{t.load_error ?? 'Erreur'}</div>}
                    {!data && !error && <i className="fas fa-spinner fa-spin text-body-secondary" />}
                    {data && (
                        <div className="row g-3">
                            <div className="col-lg-7">
                                <h6>{t.by_month}</h6>
                                <div className="table-responsive">
                                    <table className="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>{t.month}</th>
                                                <th className="text-end">{t.count}</th>
                                                <th className="text-end">{t.internal}</th>
                                                <th className="text-end">{t.external}</th>
                                                <th className="text-end">{t.scrap}</th>
                                                <th className="text-end">{t.total}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.months.map((row) => (
                                                <tr key={row.month} className={row.count ? '' : 'text-body-secondary'}>
                                                    <td>{monthLabel(row.month)}</td>
                                                    <td className="text-end">{row.count}</td>
                                                    <td className="text-end">{money(row.internal)}</td>
                                                    <td className="text-end">{money(row.external)}</td>
                                                    <td className="text-end">{money(row.scrap)}</td>
                                                    <td className="text-end fw-semibold">{money(row.total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="col-lg-5">
                                <h6>{t.by_supplier}</h6>
                                {data.suppliers.length === 0 ? (
                                    <div className="small text-body-secondary">{t.no_supplier}</div>
                                ) : (
                                    <table className="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>{t.supplier}</th>
                                                <th className="text-end">{t.count}</th>
                                                <th className="text-end">{t.total}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.suppliers.map((row) => (
                                                <tr key={row.company_id}>
                                                    <td>{row.label}</td>
                                                    <td className="text-end">{row.count}</td>
                                                    <td className="text-end fw-semibold">{money(row.total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
