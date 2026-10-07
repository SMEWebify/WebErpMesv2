import React, { useCallback, useEffect, useState } from 'react';
import {
    request, fmt, formatDate, formatAmount, formatNumber, todayIso, StatusBadge,
} from './affairShared.jsx';

/**
 * Synthèse d'une affaire (onglet par défaut de la fiche opportunité) :
 * étape du cycle, points bloquants, prochaine action, totaux et détail des
 * pièces liées. Pensée d'abord pour le téléphone : la prochaine action passe
 * en tête sur petit écran, les détails sont repliés et ouverts à la demande.
 */

const LS_OPEN_SECTIONS = 'affairSummary.openSections';
const SECTION_ORDER = ['quotes', 'orders', 'purchases', 'invoices', 'visits'];

const SEVERITY = {
    danger:  'fas fa-exclamation-circle text-danger',
    warning: 'fas fa-exclamation-triangle text-warning',
    info:    'fas fa-info-circle text-info',
};

function readOpenSections() {
    try {
        const value = JSON.parse(localStorage.getItem(LS_OPEN_SECTIONS) ?? 'null');
        return Array.isArray(value) ? value : [];
    } catch { return []; }
}

function writeOpenSections(value) {
    try { localStorage.setItem(LS_OPEN_SECTIONS, JSON.stringify(value)); } catch { /* stockage indisponible */ }
}

// ── Étape ──────────────────────────────────────────────────────────────────

function StageStepper({ stage, path, trans }) {
    const steps = path ?? [];
    const isClosed = stage === 'closed';
    const isLost = stage === 'lost';
    const currentIndex = steps.indexOf(stage);

    return (
        <div className="mb-3">
            <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span>
                    {trans.stage_label ?? 'Étape'} : <strong>{trans.stages?.[stage] ?? stage}</strong>
                </span>
                {isClosed && <span className="badge text-bg-success">{trans.stages?.closed ?? 'Terminée'}</span>}
                {isLost && <span className="badge text-bg-danger">{trans.stages?.lost ?? 'Perdue'}</span>}
            </div>

            <ol className={`list-unstyled d-flex m-0 p-0 ${isLost ? 'opacity-50' : ''}`}>
                {steps.map((key, index) => {
                    const done = isClosed || (currentIndex >= 0 && index < currentIndex);
                    const current = !isClosed && !isLost && index === currentIndex;
                    let dot = 'border border-2 border-secondary bg-body';
                    if (done) dot = 'bg-success border border-2 border-success';
                    if (current) dot = 'bg-primary border border-2 border-primary';
                    if (isLost) dot = 'border border-2 border-secondary bg-body';

                    return (
                        <li key={key} className="flex-fill text-center position-relative" style={{ minWidth: 0 }}>
                            {index > 0 && (
                                // Trait de liaison vers l'étape précédente
                                <span
                                    className={`position-absolute ${done || current ? 'bg-success' : 'bg-secondary-subtle'}`}
                                    style={{ height: 2, top: 6, left: 0, right: '50%' }}
                                    aria-hidden="true"
                                />
                            )}
                            {index < steps.length - 1 && (
                                <span
                                    className={`position-absolute ${done ? 'bg-success' : 'bg-secondary-subtle'}`}
                                    style={{ height: 2, top: 6, left: '50%', right: 0 }}
                                    aria-hidden="true"
                                />
                            )}
                            <span
                                className={`d-inline-block rounded-circle position-relative ${dot}`}
                                style={{ width: 14, height: 14 }}
                                aria-current={current ? 'step' : undefined}
                            />
                            <div className={`small lh-sm mt-1 text-truncate ${current ? 'fw-bold text-primary' : (done ? '' : 'text-body-secondary')}`}>
                                <span className="d-md-none">{trans.stages_short?.[key] ?? key}</span>
                                <span className="d-none d-md-inline">{trans.stages?.[key] ?? key}</span>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

// ── Points bloquants ───────────────────────────────────────────────────────

function BlockersCard({ blockers, trans, locale, currency }) {
    const list = blockers ?? [];

    return (
        <div className="card h-100 mb-0">
            <div className="card-header d-flex align-items-center gap-2">
                <h3 className="card-title m-0 fs-6">{trans.blockers_title ?? 'Points bloquants'}</h3>
                <span className={`badge ${list.length ? 'text-bg-danger' : 'text-bg-success'}`}>{list.length}</span>
            </div>
            {list.length === 0 ? (
                <div className="card-body">
                    <span className="small text-success">
                        <i className="fas fa-check-circle me-1" />{trans.no_blocker ?? 'Rien ne bloque.'}
                    </span>
                </div>
            ) : (
                <div className="list-group list-group-flush">
                    {list.map((blocker, index) => {
                        const params = blocker.params ?? {};
                        const text = fmt(trans.blockers?.[blocker.code] ?? blocker.code, {
                            ...params,
                            ref: blocker.ref ?? params.ref,
                            date: formatDate(blocker.date, locale),
                            amount: params.amount !== undefined && params.amount !== null
                                ? formatAmount(params.amount, currency, locale) : '',
                        });
                        const content = (
                            <span className="d-flex align-items-start gap-2">
                                <i className={`${SEVERITY[blocker.severity] ?? SEVERITY.info} mt-1`} />
                                <span className="text-break">{text}</span>
                            </span>
                        );
                        const key = `${blocker.code}-${blocker.ref ?? ''}-${index}`;
                        return blocker.url ? (
                            <a key={key} href={blocker.url} className="list-group-item list-group-item-action">{content}</a>
                        ) : (
                            <div key={key} className="list-group-item">{content}</div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

// ── Prochaine action ───────────────────────────────────────────────────────

function NextActionCard({ action, trans }) {
    let body;
    if (!action) {
        body = <span className="text-body-secondary">{trans.no_action ?? 'Aucune action à mener.'}</span>;
    } else {
        const label = fmt(trans.actions?.[action.code] ?? action.code, { ref: action.ref ?? '' });
        body = action.url ? (
            <a href={action.url} className="btn btn-primary btn-lg w-100 text-break">
                <i className="fas fa-arrow-right me-2" />{label}
            </a>
        ) : (
            <p className="fs-5 fw-semibold mb-0 text-break">{label}</p>
        );
    }

    return (
        <div className="card card-outline card-primary border-primary h-100 mb-0">
            <div className="card-header">
                <h3 className="card-title m-0 fs-6">{trans.next_action_title ?? 'Prochaine action'}</h3>
            </div>
            <div className="card-body d-flex align-items-center">{body}</div>
        </div>
    );
}

// ── Totaux ─────────────────────────────────────────────────────────────────

function Tile({ label, children }) {
    return (
        <div className="col-6 col-md-4 col-xl">
            <div className="border rounded p-2 h-100 bg-body-tertiary">
                <div className="small text-body-secondary text-truncate">{label}</div>
                <div className="fw-bold text-break">{children}</div>
            </div>
        </div>
    );
}

function TotalsTiles({ totals, trans, locale, currency }) {
    if (!totals) return null;
    const t = trans.totals ?? {};
    const money = (value) => formatAmount(value, currency, locale);
    const isSet = (value) => value !== null && value !== undefined;
    const hasHours = isSet(totals.hours_actual) || isSet(totals.hours_planned);
    const overrun = Number(totals.hours_actual ?? 0) > Number(totals.hours_planned ?? 0);

    return (
        <div className="row g-2 mb-3">
            {isSet(totals.quoted) && <Tile label={t.quoted ?? 'Devisé HT'}>{money(totals.quoted)}</Tile>}
            {isSet(totals.ordered) && <Tile label={t.ordered ?? 'Commandé HT'}>{money(totals.ordered)}</Tile>}
            {isSet(totals.purchased) && <Tile label={t.purchased ?? 'Acheté HT'}>{money(totals.purchased)}</Tile>}
            {hasHours && (
                <Tile label={t.hours ?? 'Heures réel / prévu'}>
                    <span className={overrun ? 'text-danger' : ''}>
                        {formatNumber(totals.hours_actual ?? 0, locale)} / {formatNumber(totals.hours_planned ?? 0, locale)} h
                    </span>
                </Tile>
            )}
            {isSet(totals.invoiced) && <Tile label={t.invoiced ?? 'Facturé HT'}>{money(totals.invoiced)}</Tile>}
            {isSet(totals.collected) && (
                <Tile label={t.collected ?? 'Encaissé TTC'}>
                    {money(totals.collected)}
                    {Number(totals.outstanding ?? 0) > 0 && (
                        <div className="small fw-normal text-danger">
                            {t.outstanding ?? 'Reste à encaisser'} {money(totals.outstanding)}
                        </div>
                    )}
                </Tile>
            )}
        </div>
    );
}

// ── Détail des pièces ──────────────────────────────────────────────────────

function Progress({ label, value, color }) {
    const pct = Math.max(0, Math.min(100, Number(value ?? 0)));
    return (
        <div className="small">
            <div className="d-flex justify-content-between">
                <span className="text-body-secondary">{label}</span>
                <span>{formatNumber(pct, undefined, 0)} %</span>
            </div>
            <div className="progress" style={{ height: 6 }} role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                <div className={`progress-bar bg-${color}`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

/** En-tête commun d'une ligne : code (lien), libellé, statut, montant. */
function RowHead({ row, label, badge, amount }) {
    return (
        <div className="d-flex flex-wrap align-items-start gap-2">
            <div className="flex-grow-1" style={{ minWidth: 0 }}>
                {row.url ? <a href={row.url} className="fw-bold">{row.code}</a> : <span className="fw-bold">{row.code}</span>}
                {' '}{badge}
                {label && <div className="small text-body-secondary text-break">{label}</div>}
            </div>
            {amount !== null && amount !== undefined && <div className="text-end fw-semibold text-nowrap">{amount}</div>}
        </div>
    );
}

function Meta({ items }) {
    const visible = items.filter(Boolean);
    if (!visible.length) return null;
    return (
        <div className="d-flex flex-wrap column-gap-3 row-gap-1 small mt-1">
            {visible.map(([label, value], i) => (
                <span key={i}><span className="text-body-secondary">{label} :</span> {value}</span>
            ))}
        </div>
    );
}

function SectionRows({ type, rows, trans, locale, currency }) {
    const c = trans.columns ?? {};
    const money = (value) => formatAmount(value, currency, locale);
    const today = todayIso();

    if (!rows?.length) {
        return <div className="list-group-item small text-body-secondary">{trans.none ?? 'Aucun'}</div>;
    }

    return rows.map((row) => {
        let body = null;
        switch (type) {
            case 'quotes':
                body = (
                    <>
                        <RowHead row={row} label={row.label} amount={money(row.amount)}
                            badge={<StatusBadge trans={trans} family="quote_status" statu={row.statu} />} />
                        <Meta items={[
                            row.validity_date && [c.validity ?? 'Validité', formatDate(row.validity_date, locale)],
                            row.sent_at && [c.sent ?? 'Envoyé le', formatDate(row.sent_at, locale)],
                        ]} />
                    </>
                );
                break;
            case 'orders': {
                const overrun = Number(row.hours_actual ?? 0) > Number(row.hours_planned ?? 0);
                body = (
                    <>
                        <RowHead row={row} label={row.label} amount={money(row.amount)}
                            badge={<StatusBadge trans={trans} family="order_status" statu={row.statu} />} />
                        <Meta items={[
                            row.next_delivery_date && [c.delivery ?? 'Livraison', formatDate(row.next_delivery_date, locale)],
                            (row.hours_planned || row.hours_actual) && [
                                c.hours ?? 'Heures',
                                <span className={overrun ? 'text-danger' : ''}>
                                    {formatNumber(row.hours_actual ?? 0, locale)} / {formatNumber(row.hours_planned ?? 0, locale)} h
                                </span>,
                            ],
                        ]} />
                        <div className="row g-2 mt-1">
                            <div className="col-6"><Progress label={c.delivered ?? 'Livré'} value={row.delivered_pct} color="info" /></div>
                            <div className="col-6"><Progress label={c.invoiced ?? 'Facturé'} value={row.invoiced_pct} color="success" /></div>
                        </div>
                    </>
                );
                break;
            }
            case 'purchases':
                body = (
                    <>
                        <RowHead row={row} amount={money(row.amount)}
                            badge={<StatusBadge trans={trans} family="purchase_status" statu={row.statu} />} />
                        <Meta items={[
                            [c.received ?? 'Reçu', `${row.received_lines ?? 0} / ${row.lines ?? 0}`],
                            row.next_delivery_date && [c.delivery ?? 'Livraison', formatDate(row.next_delivery_date, locale)],
                        ]} />
                    </>
                );
                break;
            case 'invoices': {
                const remaining = Number(row.remaining ?? 0);
                const overdue = remaining > 0 && row.due_date && row.due_date < today;
                body = (
                    <>
                        <RowHead row={row} label={row.label} amount={money(row.amount_ttc ?? row.amount_ht)}
                            badge={<>
                                <StatusBadge trans={trans} family="invoice_status" statu={row.statu} />
                                {row.invoice_type !== null && row.invoice_type !== undefined && (
                                    <span className="badge text-bg-light border ms-1">
                                        {trans.invoice_type?.[row.invoice_type] ?? row.invoice_type}
                                    </span>
                                )}
                            </>} />
                        <Meta items={[
                            row.due_date && [c.due ?? 'Échéance', formatDate(row.due_date, locale)],
                            [c.paid ?? 'Réglé', money(row.paid ?? 0)],
                            [c.remaining ?? 'Reste', <span className={overdue ? 'text-danger fw-bold' : ''}>{money(remaining)}</span>],
                        ]} />
                    </>
                );
                break;
            }
            case 'visits':
                body = (
                    <div className="d-flex flex-wrap align-items-center gap-2">
                        {row.url
                            ? <a href={row.url} className="fw-bold">{formatDate(row.visited_at, locale)}</a>
                            : <span className="fw-bold">{formatDate(row.visited_at, locale)}</span>}
                        <StatusBadge trans={trans} family="visit_status" statu={row.statu} />
                    </div>
                );
                break;
            default:
                body = <RowHead row={row} label={row.label} />;
        }
        return <div key={row.id} className="list-group-item">{body}</div>;
    });
}

function DetailSections({ sections, counts, trans, locale, currency }) {
    const [open, setOpen] = useState(readOpenSections);
    const keys = SECTION_ORDER.filter((key) => sections && Array.isArray(sections[key]));

    const toggle = (key) => {
        setOpen((prev) => {
            const next = prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key];
            writeOpenSections(next);
            return next;
        });
    };

    if (!keys.length) return null;

    return (
        <div className="card mb-0">
            {keys.map((key, index) => {
                const isOpen = open.includes(key);
                const count = counts?.[key] ?? sections[key].length;
                return (
                    <div key={key} className={index > 0 ? 'border-top' : ''}>
                        <button
                            type="button"
                            className="btn w-100 text-start d-flex align-items-center gap-2 px-3 py-2 rounded-0"
                            aria-expanded={isOpen}
                            onClick={() => toggle(key)}
                        >
                            <i className={`fas fa-chevron-${isOpen ? 'down' : 'right'} small text-body-secondary`} />
                            <span className="fw-semibold">{trans.sections?.[key] ?? key}</span>
                            <span className="badge text-bg-secondary ms-auto">{count}</span>
                        </button>
                        {isOpen && (
                            <div className="list-group list-group-flush border-top">
                                <SectionRows type={key} rows={sections[key]} trans={trans} locale={locale} currency={currency} />
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

// ── Composant principal ────────────────────────────────────────────────────

export default function AffairSummary({ endpoints = {}, trans = {}, locale = 'fr', currency = 'EUR' }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const load = useCallback(async () => {
        if (!endpoints.summary) { setLoading(false); return; }
        setLoading(true);
        setError(null);
        try {
            setData(await request(endpoints.summary));
        } catch (e) {
            setError(e);
        } finally {
            setLoading(false);
        }
    }, [endpoints.summary]);

    // Onglet actif par défaut : on charge dès le montage
    useEffect(() => { load(); }, [load]);

    return (
        <div className="affair-summary">
            <div className="d-flex justify-content-end mb-1">
                <button
                    type="button"
                    className="btn btn-tool"
                    onClick={load}
                    disabled={loading}
                    title={trans.refresh ?? 'Actualiser'}
                    aria-label={trans.refresh ?? 'Actualiser'}
                >
                    <i className={`fas fa-sync ${loading ? 'fa-spin' : ''}`} />
                </button>
            </div>

            {error && (
                <div className="alert alert-danger d-flex flex-wrap align-items-center gap-2">
                    <span className="flex-grow-1">{trans.load_error ?? 'Impossible de charger la synthèse.'}</span>
                    <button type="button" className="btn btn-sm btn-outline-danger" onClick={load}>
                        {trans.retry ?? 'Réessayer'}
                    </button>
                </div>
            )}

            {loading && !data && (
                <div className="text-center text-body-secondary py-4">
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                    {trans.loading ?? 'Chargement…'}
                </div>
            )}

            {data && (
                <>
                    <StageStepper stage={data.stage} path={data.path} trans={trans} />

                    <div className="row g-3 mb-3">
                        {/* Sur téléphone la prochaine action passe en tête */}
                        <div className="col-12 col-lg-5 order-first order-lg-last">
                            <NextActionCard action={data.next_action} trans={trans} />
                        </div>
                        <div className="col-12 col-lg-7">
                            <BlockersCard blockers={data.blockers} trans={trans} locale={locale} currency={currency} />
                        </div>
                    </div>

                    <TotalsTiles totals={data.totals} trans={trans} locale={locale} currency={currency} />

                    <DetailSections
                        sections={data.sections}
                        counts={data.counts}
                        trans={trans}
                        locale={locale}
                        currency={currency}
                    />
                </>
            )}
        </div>
    );
}
