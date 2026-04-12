import React, { useState, useEffect, useCallback, useRef } from 'react';

// ---------------------------------------------------------------------------
// Registre des widgets
// ---------------------------------------------------------------------------

export const WIDGET_REGISTRY = [
    {
        id:       'invoices_overdue',
        label:    'Factures échues',
        icon:     'fa-file-invoice-dollar',
        color:    '#dc3545',
        section:  'now',
        endpointKey: 'today_invoices_overdue',
        emptyMsg: 'Aucune facture échue.',
        dateLabel: 'Échéance',
        daysSuffix: (d) => d > 0 ? `${d}j de retard` : 'Échue aujourd\'hui',
        badgeClass: 'badge-danger',
    },
    {
        id:       'orders_late',
        label:    'Commandes en retard',
        icon:     'fa-shopping-cart',
        color:    '#fd7e14',
        section:  'now',
        endpointKey: 'today_orders_late',
        emptyMsg: 'Aucune commande en retard.',
        dateLabel: 'Livraison prévue',
        daysSuffix: (d) => d > 0 ? `${d}j de retard` : 'Aujourd\'hui',
        badgeClass: 'badge-warning',
    },
    {
        id:       'quotes_expiring',
        label:    'Devis qui expirent',
        icon:     'fa-file-alt',
        color:    '#007bff',
        section:  'now',
        endpointKey: 'today_quotes_expiring',
        emptyMsg: 'Aucun devis proche de l\'expiration.',
        dateLabel: 'Expire le',
        daysSuffix: (d) => d < 0 ? `Expiré` : d === 0 ? 'Expire aujourd\'hui' : `Dans ${d}j`,
        badgeClass: 'badge-info',
    },
    {
        id:       'leads_pending',
        label:    'Leads non traités',
        icon:     'fa-bullseye',
        color:    '#17a2b8',
        section:  'now',
        endpointKey: 'today_leads_pending',
        emptyMsg: 'Aucun lead en attente.',
        dateLabel: 'Reçu le',
        daysSuffix: (d) => `${d}j sans suivi`,
        badgeClass: 'badge-info',
    },
    {
        id:       'orders_due_week',
        label:    'Livraisons cette semaine',
        icon:     'fa-truck',
        color:    '#20c997',
        section:  'week',
        endpointKey: 'today_orders_due_week',
        emptyMsg: 'Aucune livraison prévue cette semaine.',
        dateLabel: 'Livraison',
        daysSuffix: (d) => d === 0 ? 'Aujourd\'hui' : `Dans ${d}j`,
        badgeClass: 'badge-success',
    },
    {
        id:       'recent_activity',
        label:    'Activité récente',
        icon:     'fa-history',
        color:    '#6c757d',
        section:  'recent',
        endpointKey: 'today_recent_activity',
        emptyMsg: 'Aucune activité récente.',
        dateLabel: 'Créé le',
        daysSuffix: null,
        badgeClass: 'badge-secondary',
    },
];

const SECTIONS = [
    { id: 'now',    label: 'À faire maintenant',    icon: 'fa-exclamation-circle', color: '#dc3545' },
    { id: 'week',   label: 'Cette semaine',          icon: 'fa-calendar-week',      color: '#fd7e14' },
    { id: 'recent', label: 'Récent',                 icon: 'fa-history',            color: '#6c757d' },
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' }).format(new Date(y, m - 1, d));
    } catch { return dateStr; }
}

async function apiFetch(url, opts = {}) {
    const res = await fetch(url, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken(), ...opts.headers },
        credentials: 'same-origin',
        ...opts,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

const DEFAULT_LAYOUT = WIDGET_REGISTRY.map((w, i) => ({ id: w.id, enabled: true, order: i }));

// ---------------------------------------------------------------------------
// TodayItem — une ligne dans un widget
// ---------------------------------------------------------------------------
function TodayItem({ item, def }) {
    const typeColors = { quote: '#007bff', order: '#fd7e14', delivery: '#20c997', invoice: '#28a745' };
    const color = item.type ? typeColors[item.type] : def.color;

    return (
        <div style={{
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '6px 0', borderBottom: '1px solid #f1f3f4',
        }}>
            {/* Indicateur de retard */}
            {def.daysSuffix && item.days != null && (
                <span className={`badge ${def.badgeClass}`} style={{ fontSize: 10, minWidth: 70, textAlign: 'center' }}>
                    {def.daysSuffix(item.days)}
                </span>
            )}
            {item.type && (
                <span style={{
                    fontSize: 10, fontWeight: 600, color,
                    textTransform: 'uppercase', minWidth: 50,
                }}>
                    {item.type}
                </span>
            )}

            {/* Contenu */}
            <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontSize: 13, fontWeight: 500, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {item.code && <span style={{ marginRight: 4, color: '#495057' }}>{item.code}</span>}
                    {item.company && <span style={{ color: '#6c757d' }}>{item.company}</span>}
                    {item.label && !item.company && <span style={{ color: '#6c757d' }}>{item.label}</span>}
                </div>
                {item.label && item.company && (
                    <div style={{ fontSize: 11, color: '#adb5bd', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                        {item.label}
                    </div>
                )}
            </div>

            {/* Date */}
            <small style={{ color: '#adb5bd', fontSize: 11, flexShrink: 0 }}>
                {formatDate(item.date)}
            </small>

            {/* Lien */}
            <a
                href={item.url}
                className="btn btn-xs btn-outline-secondary"
                style={{ fontSize: 10, padding: '1px 6px', flexShrink: 0 }}
                title="Ouvrir"
            >
                <i className="fas fa-arrow-right" />
            </a>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TodayWidget — un widget avec fetch indépendant
// ---------------------------------------------------------------------------
function TodayWidget({ def, endpoint }) {
    const [data,    setData]    = useState(null);
    const [loading, setLoading] = useState(true);
    const [error,   setError]   = useState(null);

    useEffect(() => {
        if (!endpoint) { setLoading(false); return; }
        apiFetch(endpoint)
            .then(d  => { setData(d); setLoading(false); })
            .catch(e => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    const count = data?.count ?? 0;
    const items = data?.items ?? [];

    return (
        <div style={{
            background: '#fff',
            border: '1px solid #dee2e6',
            borderTop: `3px solid ${def.color}`,
            borderRadius: 4,
            marginBottom: 12,
        }}>
            {/* Header */}
            <div style={{
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                padding: '8px 12px',
                borderBottom: count > 0 ? '1px solid #f1f3f4' : 'none',
            }}>
                <span style={{ fontWeight: 600, fontSize: 13, color: '#343a40' }}>
                    <i className={`fas ${def.icon} mr-2`} style={{ color: def.color }} />
                    {def.label}
                </span>
                {!loading && (
                    <span className={`badge ${count > 0 ? def.badgeClass : 'badge-secondary'}`}>
                        {count}
                    </span>
                )}
                {loading && <i className="fas fa-spinner fa-spin text-muted" style={{ fontSize: 12 }} />}
            </div>

            {/* Body */}
            {!loading && !error && count > 0 && (
                <div style={{ padding: '0 12px' }}>
                    {items.map((item, idx) => (
                        <TodayItem key={idx} item={item} def={def} />
                    ))}
                </div>
            )}
            {!loading && !error && count === 0 && (
                <div style={{ padding: '10px 12px', color: '#adb5bd', fontSize: 12 }}>
                    <i className="fas fa-check-circle mr-1 text-success" />{def.emptyMsg}
                </div>
            )}
            {error && (
                <div style={{ padding: '8px 12px', color: '#dc3545', fontSize: 12 }}>
                    <i className="fas fa-exclamation-triangle mr-1" />Erreur de chargement
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// ConfigPanel — panneau de configuration des widgets
// ---------------------------------------------------------------------------
function ConfigPanel({ layout, onSave, onClose }) {
    const [local, setLocal] = useState([...layout]);

    function toggle(id) {
        setLocal(prev => prev.map(w => w.id === id ? { ...w, enabled: !w.enabled } : w));
    }

    return (
        <div style={{
            position: 'fixed', top: 0, right: 0, bottom: 0, width: 300,
            background: '#fff', boxShadow: '-4px 0 16px rgba(0,0,0,0.15)',
            zIndex: 1050, padding: '1rem', overflowY: 'auto',
        }}>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <strong>Personnaliser la vue</strong>
                <button className="btn btn-sm btn-light" onClick={onClose}>
                    <i className="fas fa-times" />
                </button>
            </div>
            <p className="text-muted" style={{ fontSize: '0.8rem' }}>
                Activez ou désactivez les sections qui vous concernent.
            </p>

            {WIDGET_REGISTRY.map(def => {
                const conf = local.find(w => w.id === def.id);
                const enabled = conf?.enabled ?? true;
                return (
                    <div key={def.id}
                        className="d-flex align-items-center justify-content-between mb-2 p-2"
                        style={{ border: '1px solid #dee2e6', borderRadius: 4 }}
                    >
                        <span style={{ fontSize: 13 }}>
                            <i className={`fas ${def.icon} mr-2`} style={{ color: def.color, width: 16 }} />
                            {def.label}
                        </span>
                        <div className="custom-control custom-switch">
                            <input
                                type="checkbox"
                                className="custom-control-input"
                                id={`widget-toggle-${def.id}`}
                                checked={enabled}
                                onChange={() => toggle(def.id)}
                            />
                            <label className="custom-control-label" htmlFor={`widget-toggle-${def.id}`} />
                        </div>
                    </div>
                );
            })}

            <button
                className="btn btn-primary btn-block mt-3"
                onClick={() => { onSave(local); onClose(); }}
            >
                <i className="fas fa-save mr-1" />Enregistrer
            </button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TodayView — composant principal
// ---------------------------------------------------------------------------
export default function TodayView({ endpoints }) {
    const [layout,      setLayout]      = useState(null);
    const [showConfig,  setShowConfig]  = useState(false);
    const [saving,      setSaving]      = useState(false);
    const saveTimer = useRef(null);

    // Charge la config utilisateur au montage
    useEffect(() => {
        if (!endpoints?.today_config) { setLayout(DEFAULT_LAYOUT); return; }
        apiFetch(endpoints.today_config)
            .then(data => setLayout(data?.today_layout ?? DEFAULT_LAYOUT))
            .catch(() => setLayout(DEFAULT_LAYOUT));
    }, []);

    const saveLayout = useCallback(async (newLayout) => {
        if (!endpoints?.today_config_update) return;
        clearTimeout(saveTimer.current);
        setSaving(true);
        try {
            await apiFetch(endpoints.today_config_update, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ today_layout: newLayout }),
            });
        } catch (e) { console.error('[TodayView] save error', e); }
        finally { setSaving(false); }
    }, [endpoints]);

    function handleSave(newLayout) {
        setLayout(newLayout);
        saveLayout(newLayout);
    }

    if (!layout) {
        return (
            <div className="text-center py-5 text-muted">
                <i className="fas fa-spinner fa-spin mr-2" />Chargement…
            </div>
        );
    }

    // Widgets actifs triés
    const activeWidgets = WIDGET_REGISTRY
        .map(def => {
            const conf = layout.find(w => w.id === def.id) ?? { enabled: true, order: 99 };
            return { def, conf };
        })
        .filter(({ conf }) => conf.enabled)
        .sort((a, b) => (a.conf.order ?? 99) - (b.conf.order ?? 99));

    const today = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

    return (
        <div>
            {/* En-tête */}
            <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 className="mb-0" style={{ fontWeight: 600, color: '#343a40' }}>
                        <i className="fas fa-sun mr-2 text-warning" />
                        {today.charAt(0).toUpperCase() + today.slice(1)}
                    </h6>
                </div>
                <div className="d-flex align-items-center" style={{ gap: 8 }}>
                    {saving && (
                        <small className="text-muted">
                            <i className="fas fa-spinner fa-spin mr-1" />
                        </small>
                    )}
                    <button
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => setShowConfig(s => !s)}
                        title="Personnaliser"
                    >
                        <i className="fas fa-sliders-h mr-1" />Personnaliser
                    </button>
                </div>
            </div>

            {/* Sections */}
            {SECTIONS.map(section => {
                const sectionWidgets = activeWidgets.filter(({ def }) => def.section === section.id);
                if (sectionWidgets.length === 0) return null;

                return (
                    <div key={section.id} style={{ marginBottom: 20 }}>
                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 8,
                            marginBottom: 10,
                        }}>
                            <i className={`fas ${section.icon}`} style={{ color: section.color, fontSize: 13 }} />
                            <span style={{
                                fontSize: 11, fontWeight: 700, letterSpacing: 0.8,
                                textTransform: 'uppercase', color: '#6c757d',
                            }}>
                                {section.label}
                            </span>
                            <div style={{ flex: 1, height: 1, background: '#dee2e6' }} />
                        </div>

                        <div className="row">
                            {sectionWidgets.map(({ def }) => (
                                <div key={def.id} className="col-md-6 col-lg-4">
                                    <TodayWidget
                                        def={def}
                                        endpoint={endpoints?.[def.endpointKey]}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                );
            })}

            {activeWidgets.length === 0 && (
                <div className="text-center text-muted py-5">
                    <i className="fas fa-sliders-h fa-2x mb-2 d-block" />
                    Aucun widget activé. Cliquez sur "Personnaliser" pour en ajouter.
                </div>
            )}

            {/* Panneau config */}
            {showConfig && (
                <ConfigPanel
                    layout={layout}
                    onSave={handleSave}
                    onClose={() => setShowConfig(false)}
                />
            )}
        </div>
    );
}
