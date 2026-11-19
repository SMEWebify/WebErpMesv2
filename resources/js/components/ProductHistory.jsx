import React, { useState, useEffect } from 'react';
import { fmt } from '../lib/i18n.js';

// Textes de l'écran (resources/lang/*/stock_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

// ---------------------------------------------------------------------------
// Config per document type
// ---------------------------------------------------------------------------
const TYPE_CONFIG = {
    quote: {
        get label() { return L.type_quote ?? 'Devis'; },
        color:   '#007bff',
        bg:      '#e8f0fe',
        icon:    'fas fa-file-alt',
        statuses: {
            1: { get label() { return L.draft ?? 'Brouillon'; },   badge: 'text-bg-secondary' },
            2: { get label() { return L.sent_m ?? 'Envoyé'; },      badge: 'text-bg-info' },
            3: { get label() { return L.under_review ?? 'En révision'; }, badge: 'text-bg-warning' },
            4: { get label() { return L.accepted ?? 'Accepté'; },     badge: 'text-bg-success' },
            5: { get label() { return L.refused ?? 'Refusé'; },      badge: 'text-bg-danger' },
            6: { get label() { return L.expired ?? 'Expiré'; },      badge: 'text-bg-dark' },
        },
    },
    order: {
        get label() { return L.order ?? 'Commande'; },
        color:   '#fd7e14',
        bg:      '#fff3e0',
        icon:    'fas fa-shopping-cart',
        statuses: {
            1: { get label() { return L.new_f ?? 'Nouvelle'; },  badge: 'text-bg-info' },
            2: { get label() { return L.in_progress ?? 'En cours'; },  badge: 'text-bg-primary' },
            3: { get label() { return L.finished_f ?? 'Terminée'; }, badge: 'text-bg-success' },
            4: { get label() { return L.canceled_f ?? 'Annulée'; },  badge: 'text-bg-danger' },
        },
    },
    purchase: {
        get label() { return L.type_purchase ?? 'Achat'; },
        color:   '#6f42c1',
        bg:      '#f3eeff',
        icon:    'fas fa-cart-arrow-down',
        statuses: {
            1: { get label() { return L.draft ?? 'Brouillon'; }, badge: 'text-bg-secondary' },
            2: { get label() { return L.sent_m ?? 'Envoyé'; },   badge: 'text-bg-info' },
            3: { get label() { return L.received ?? 'Reçu'; },     badge: 'text-bg-success' },
            4: { get label() { return L.canceled_m ?? 'Annulé'; },   badge: 'text-bg-danger' },
        },
    },
    // Lot reçu : le libellé porte la coulée, le lot fournisseur et le certificat
    batch: {
        get label() { return L.type_batch ?? 'Lot'; },
        color:   '#20c997',
        bg:      'var(--bs-tertiary-bg)',
        icon:    'fas fa-fire',
        statuses: {
            1: { get label() { return L.open ?? 'Ouvert'; },   badge: 'text-bg-success' },
            2: { get label() { return L.closed ?? 'Clôturé'; }, badge: 'text-bg-secondary' },
        },
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

function statusInfo(type, statu, trans = {}) {
    const cfg = TYPE_CONFIG[type]?.statuses?.[statu];
    const label = trans[`${type}_statu_${statu}`];
    if (cfg && label) return { ...cfg, label };
    return cfg ?? { label: fmt(L.status_n ?? 'Statut :status', { status: statu }), badge: 'text-bg-secondary' };
}

// ---------------------------------------------------------------------------
// TimelineItem
// ---------------------------------------------------------------------------
function TimelineItem({ item, trans = {} }) {
    const cfg   = TYPE_CONFIG[item.type] ?? {};
    const statu = statusInfo(item.type, item.statu, trans);

    return (
        <div style={{ display: 'flex', gap: '12px', marginBottom: '12px' }}>
            {/* Left rail */}
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', flexShrink: 0 }}>
                <div style={{
                    width: 36, height: 36, borderRadius: '50%',
                    background: cfg.bg ?? '#f8f9fa',
                    border: `2px solid ${cfg.color ?? '#6c757d'}`,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    color: cfg.color ?? '#6c757d',
                    fontSize: 14,
                }}>
                    <i className={cfg.icon ?? 'fas fa-circle'} />
                </div>
                <div style={{ width: 2, flexGrow: 1, background: 'var(--bs-border-color)', minHeight: 8 }} />
            </div>

            {/* Card */}
            <div style={{
                flex: 1,
                background: 'var(--bs-body-bg)',
                border: '1px solid var(--bs-border-color)',
                borderLeft: `3px solid ${cfg.color ?? '#6c757d'}`,
                borderRadius: 4,
                padding: '8px 12px',
                marginBottom: 4,
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 4 }}>
                    <div>
                        <span style={{
                            display: 'inline-block',
                            fontSize: 10,
                            fontWeight: 600,
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                            color: cfg.color ?? '#6c757d',
                            marginBottom: 2,
                        }}>
                            {trans[`type_${item.type}`] ?? cfg.label ?? item.type}
                        </span>

                        <div style={{ fontWeight: 600, fontSize: 13 }}>
                            {item.code && (
                                <span style={{ marginRight: 6, color: 'var(--bs-body-color)' }}>{item.code}</span>
                            )}
                            {item.label && (
                                <span style={{ color: 'var(--bs-secondary-color)', fontWeight: 400 }}>{item.label}</span>
                            )}
                            {!item.code && !item.label && <span style={{ color: '#adb5bd' }}>—</span>}
                        </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                        <span className={`badge ${statu.badge}`} style={{ fontSize: 10 }}>
                            {statu.label}
                        </span>
                        <small style={{ color: '#adb5bd', fontSize: 11 }}>{formatDate(item.date)}</small>
                        <a href={item.url} className="btn btn-sm btn-outline-secondary" style={{ fontSize: 10, padding: '1px 6px' }}>
                            <i className="fas fa-external-link-alt" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ProductHistory
// ---------------------------------------------------------------------------
export default function ProductHistory({ endpoint, canPurchases = true, trans = {} }) {
    L = trans?.ui ?? {};
    const [items,       setItems]       = useState([]);
    const [loading,     setLoading]     = useState(true);
    const [error,       setError]       = useState(null);

    const visibleTypes = canPurchases
        ? Object.keys(TYPE_CONFIG)
        : Object.keys(TYPE_CONFIG).filter(t => t !== 'purchase');

    const [activeTypes, setActiveTypes] = useState(new Set(visibleTypes));

    useEffect(() => {
        fetch(endpoint, { headers: { Accept: 'application/json' } })
            .then(r => { if (!r.ok) throw new Error(r.statusText); return r.json(); })
            .then(data => { setItems(data); setLoading(false); })
            .catch(e  => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    function toggleType(type) {
        setActiveTypes(prev => {
            const next = new Set(prev);
            next.has(type) ? next.delete(type) : next.add(type);
            return next;
        });
    }

    const visible = items.filter(i => activeTypes.has(i.type));

    // Group by year-month
    const grouped = [];
    let lastMonth = null;
    for (const item of visible) {
        const month = item.date ? item.date.substring(0, 7) : 'unknown';
        if (month !== lastMonth) {
            grouped.push({ type: 'separator', month });
            lastMonth = month;
        }
        grouped.push({ type: 'item', item });
    }

    return (
        <div>
            {/* Filter pills */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginBottom: 16 }}>
                {visibleTypes.map(type => {
                    const cfg    = TYPE_CONFIG[type];
                    const active = activeTypes.has(type);
                    const count  = items.filter(i => i.type === type).length;
                    if (count === 0) return null;
                    return (
                        <button
                            key={type}
                            onClick={() => toggleType(type)}
                            style={{
                                border: `1px solid ${cfg.color}`,
                                borderRadius: 20,
                                padding: '3px 10px',
                                fontSize: 12,
                                cursor: 'pointer',
                                background: active ? cfg.color : '#fff',
                                color:      active ? '#fff'    : cfg.color,
                                transition: 'all .15s',
                            }}
                        >
                            <i className={`${cfg.icon} me-1`} style={{ fontSize: 10 }} />
                            {trans[`type_${type}`] ?? cfg.label} ({count})
                        </button>
                    );
                })}
            </div>

            {/* States */}
            {loading && (
                <div className="text-center text-muted py-4">
                    <i className="fas fa-spinner fa-spin me-2" /> {L.loading ?? 'Chargement…'}
                </div>
            )}
            {error && <div className="alert alert-danger">{error}</div>}
            {!loading && !error && visible.length === 0 && (
                <div className="text-center text-muted py-4">
                    <i className="fas fa-inbox fa-2x mb-2 d-block" />
                    {L.no_document ?? 'Aucun document pour ce produit.'}
                </div>
            )}

            {/* Timeline */}
            {!loading && grouped.map((entry, idx) => {
                if (entry.type === 'separator') {
                    const [y, m] = (entry.month ?? '').split('-');
                    const label  = entry.month === 'unknown' ? (L.unknown_date ?? 'Date inconnue')
                        : new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(new Date(+y, +m - 1));
                    return (
                        <div key={`sep-${idx}`} style={{ display: 'flex', alignItems: 'center', gap: 8, margin: '16px 0 8px' }}>
                            <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--bs-secondary-color)', textTransform: 'uppercase', whiteSpace: 'nowrap' }}>
                                {label}
                            </div>
                            <div style={{ flex: 1, height: 1, background: 'var(--bs-border-color)' }} />
                        </div>
                    );
                }
                return <TimelineItem key={`${entry.item.type}-${entry.item.id}`} item={entry.item} trans={trans} />;
            })}
        </div>
    );
}
