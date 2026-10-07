import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    request, fmt, formatDateTime, formatAmount, parseDate, statusBadge,
} from './affairShared.jsx';

/**
 * Historique d'une affaire : tous les événements des pièces liées (devis,
 * commandes, achats, livraisons, factures, règlements, e-mails…) sur un fil
 * unique, le plus récent en tête. L'onglet est masqué au chargement de la
 * page : la requête ne part que lorsque le composant devient visible.
 */

const PAGE_SIZE = 30;
const SECTION_ORDER = ['opportunity', 'quotes', 'orders', 'purchases', 'deliveries', 'invoices'];

const TYPE_ICONS = {
    opportunity:  { icon: 'fas fa-handshake',             bg: 'text-bg-primary' },
    event:        { icon: 'fas fa-calendar-alt',          bg: 'text-bg-info' },
    visit:        { icon: 'fas fa-mobile-alt',            bg: 'text-bg-info' },
    quote:        { icon: 'fas fa-file-alt',              bg: 'text-bg-secondary' },
    quote_viewed: { icon: 'fas fa-eye',                   bg: 'text-bg-light border' },
    order:        { icon: 'fas fa-shopping-cart',         bg: 'text-bg-primary' },
    purchase:     { icon: 'fas fa-truck-loading',         bg: 'text-bg-warning' },
    receipt:      { icon: 'fas fa-dolly',                 bg: 'text-bg-warning' },
    delivery:     { icon: 'fas fa-truck',                 bg: 'text-bg-info' },
    invoice:      { icon: 'fas fa-file-invoice-dollar',   bg: 'text-bg-success' },
    payment:      { icon: 'fas fa-euro-sign',             bg: 'text-bg-success' },
    email:        { icon: 'fas fa-envelope',              bg: 'text-bg-secondary' },
};
const DEFAULT_ICON = { icon: 'fas fa-circle', bg: 'text-bg-secondary' };

// Famille de statut à utiliser pour chaque type d'événement
const STATUS_FAMILY = {
    quote:   'quote_status',
    order:   'order_status',
    purchase: 'purchase_status',
    invoice: 'invoice_status',
    visit:   'visit_status',
    event:   'event_type',
};

function monthKey(value) {
    const date = parseDate(value);
    return date ? `${date.getFullYear()}-${date.getMonth()}` : 'none';
}

function monthLabel(value, locale) {
    const date = parseDate(value);
    if (!date) return '—';
    try {
        return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(date);
    } catch { return `${date.getMonth() + 1}/${date.getFullYear()}`; }
}

function TimelineItem({ item, trans, locale, currency }) {
    const tl = trans.timeline ?? {};
    const family = STATUS_FAMILY[item.type];
    const badge = family ? statusBadge(trans, family, item.statu) : null;
    const icon = TYPE_ICONS[item.type] ?? DEFAULT_ICON;

    let extra = null;
    if (item.type === 'payment' && item.amount !== null && item.amount !== undefined) {
        extra = <span className="fw-semibold">{formatAmount(item.amount, currency, locale)}</span>;
    } else if (item.type === 'quote_viewed' && item.amount !== null && item.amount !== undefined) {
        extra = <span className="small text-body-secondary">{fmt(tl.views ?? ':count', { count: item.amount })}</span>;
    } else if (item.type === 'receipt' && item.amount !== null && item.amount !== undefined) {
        extra = <span className="small text-body-secondary">{fmt(tl.lines ?? ':count', { count: item.amount })}</span>;
    }

    return (
        <li className="d-flex gap-2 mb-3 position-relative">
            <span
                className={`${icon.bg} rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 small`}
                style={{ width: 32, height: 32, zIndex: 1 }}
                aria-hidden="true"
            >
                <i className={icon.icon} />
            </span>
            <div className="flex-grow-1 border rounded p-2 bg-body" style={{ minWidth: 0 }}>
                <div className="d-flex flex-wrap align-items-center column-gap-2 small text-body-secondary">
                    <span className="fw-semibold">{tl.types?.[item.type] ?? item.type}</span>
                    {item.date && <span className="ms-auto text-nowrap">{formatDateTime(item.date, locale)}</span>}
                </div>
                <div className="d-flex flex-wrap align-items-center gap-2 mt-1">
                    {item.ref && (item.url
                        ? <a href={item.url} className="fw-bold text-break">{item.ref}</a>
                        : <span className="fw-bold text-break">{item.ref}</span>)}
                    {badge && <span className={badge.className}>{badge.label}</span>}
                    {extra}
                </div>
                {item.label && <div className="small text-break mt-1">{item.label}</div>}
                {item.type === 'email' && item.to && (
                    <div className="small text-body-secondary text-break">{tl.to ?? 'à'} {item.to}</div>
                )}
            </div>
        </li>
    );
}

export default function AffairTimeline({ endpoint = '', trans = {}, locale = 'fr', currency = 'EUR' }) {
    const tl = trans.timeline ?? {};
    const rootRef = useRef(null);
    const [visible, setVisible] = useState(false);
    const [items, setItems] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');
    const [limit, setLimit] = useState(PAGE_SIZE);

    const load = useCallback(async () => {
        if (!endpoint) { setItems([]); return; }
        setLoading(true);
        setError(null);
        try {
            const response = await request(endpoint);
            setItems(Array.isArray(response?.data) ? response.data : []);
        } catch (e) {
            setError(e);
        } finally {
            setLoading(false);
        }
    }, [endpoint]);

    // Onglet masqué au chargement : on attend que le composant soit visible
    useEffect(() => {
        const el = rootRef.current;
        if (!el || typeof IntersectionObserver === 'undefined') {
            setVisible(true);
            return undefined;
        }
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                setVisible(true);
                observer.disconnect();
            }
        });
        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (visible && items === null && !loading && !error) load();
    }, [visible, items, loading, error, load]);

    const sections = useMemo(() => {
        const present = new Set((items ?? []).map((item) => item.section));
        return SECTION_ORDER.filter((key) => present.has(key));
    }, [items]);

    const filtered = useMemo(
        () => (items ?? []).filter((item) => filter === 'all' || item.section === filter),
        [items, filter],
    );
    const shown = filtered.slice(0, limit);

    const changeFilter = (key) => {
        setFilter(key);
        setLimit(PAGE_SIZE);
    };

    // Construit la liste avec un séparateur à chaque changement de mois
    const rows = [];
    let previousMonth = null;
    shown.forEach((item, index) => {
        const key = monthKey(item.date);
        if (key !== previousMonth) {
            rows.push(
                <li key={`month-${key}-${index}`} className="mb-2 position-relative">
                    <span className="badge text-bg-secondary text-capitalize position-relative" style={{ zIndex: 1 }}>
                        {monthLabel(item.date, locale)}
                    </span>
                </li>
            );
            previousMonth = key;
        }
        rows.push(
            <TimelineItem
                key={`${item.type}-${item.ref ?? ''}-${item.date ?? ''}-${index}`}
                item={item}
                trans={trans}
                locale={locale}
                currency={currency}
            />
        );
    });

    return (
        <div ref={rootRef} className="affair-timeline" style={{ minHeight: 1 }}>
            <div className="d-flex justify-content-end mb-1">
                {items !== null && (
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
                )}
            </div>

            {error && (
                <div className="alert alert-danger d-flex flex-wrap align-items-center gap-2">
                    <span className="flex-grow-1">{trans.timeline?.load_error ?? trans.load_error}</span>
                    <button type="button" className="btn btn-sm btn-outline-danger" onClick={load}>
                        {trans.retry ?? 'Réessayer'}
                    </button>
                </div>
            )}

            {items === null && !error && (
                <div className="text-center text-body-secondary py-4">
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                    {trans.loading ?? 'Chargement…'}
                </div>
            )}

            {items !== null && (
                <>
                    {sections.length > 1 && (
                        <ul className="nav nav-pills flex-wrap gap-1 mb-3">
                            {['all', ...sections].map((key) => (
                                <li key={key} className="nav-item">
                                    <button
                                        type="button"
                                        className={`nav-link py-1 px-2 small ${filter === key ? 'active' : ''}`}
                                        onClick={() => changeFilter(key)}
                                    >
                                        {key === 'all' ? (tl.all ?? 'Tout') : (tl.filters?.[key] ?? key)}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}

                    {filtered.length === 0 ? (
                        <p className="text-body-secondary mb-0">{tl.empty ?? 'Rien à afficher.'}</p>
                    ) : (
                        <div className="position-relative">
                            {/* Rail vertical, centré sous les pastilles de 32 px */}
                            <span
                                className="position-absolute bg-secondary-subtle"
                                style={{ width: 2, top: 0, bottom: 0, left: 15 }}
                                aria-hidden="true"
                            />
                            <ul className="list-unstyled mb-0 position-relative">{rows}</ul>
                        </div>
                    )}

                    {filtered.length > limit && (
                        <div className="text-center mt-2">
                            <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setLimit((l) => l + PAGE_SIZE)}>
                                {tl.show_more ?? 'Afficher plus'} ({filtered.length - limit})
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
