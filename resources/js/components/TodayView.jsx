import React, { useState, useEffect, useCallback, useRef } from 'react';
import { fmt } from '../lib/i18n.js';

// Textes de l'écran (resources/lang/*/workshop_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

// ---------------------------------------------------------------------------
// Registre des widgets
// ---------------------------------------------------------------------------

export const WIDGET_REGISTRY = [
    {
        id:       'invoices_overdue',
        get label() { return L.invoices_overdue ?? 'Factures échues'; },
        icon:     'fa-file-invoice-dollar',
        color:    '#dc3545',
        section:  'now',
        endpointKey: 'today_invoices_overdue',
        get emptyMsg() { return L.invoices_overdue_empty ?? 'Aucune facture échue.'; },
        get dateLabel() { return L.due_date ?? 'Échéance'; },
        daysSuffix: (d) => d > 0 ? fmt(L.days_late ?? ':daysj de retard', { days: d }) : (L.due_today ?? 'Échue aujourd\'hui'),
        badgeClass: 'text-bg-danger',
    },
    {
        id:       'orders_late',
        get label() { return L.orders_late ?? 'Commandes en retard'; },
        icon:     'fa-shopping-cart',
        color:    '#fd7e14',
        section:  'now',
        endpointKey: 'today_orders_late',
        get emptyMsg() { return L.orders_late_empty ?? 'Aucune commande en retard.'; },
        get dateLabel() { return L.planned_delivery ?? 'Livraison prévue'; },
        daysSuffix: (d) => d > 0 ? fmt(L.days_late ?? ':daysj de retard', { days: d }) : (L.today ?? 'Aujourd\'hui'),
        badgeClass: 'text-bg-warning',
    },
    {
        id:       'quotes_expiring',
        get label() { return L.quotes_expiring ?? 'Devis qui expirent'; },
        icon:     'fa-file-alt',
        color:    '#007bff',
        section:  'now',
        endpointKey: 'today_quotes_expiring',
        get emptyMsg() { return L.quotes_expiring_empty ?? 'Aucun devis proche de l\'expiration.'; },
        get dateLabel() { return L.expires_on ?? 'Expire le'; },
        daysSuffix: (d) => d < 0 ? fmt(L.expired ?? 'Expiré', {  }) : d === 0 ? (L.expires_today ?? 'Expire aujourd\'hui') : fmt(L.in_days ?? 'Dans :daysj', { days: d }),
        badgeClass: 'text-bg-info',
    },
    {
        id:       'leads_pending',
        get label() { return L.leads_pending ?? 'Leads non traités'; },
        icon:     'fa-bullseye',
        color:    '#17a2b8',
        section:  'now',
        endpointKey: 'today_leads_pending',
        get emptyMsg() { return L.leads_pending_empty ?? 'Aucun lead en attente.'; },
        get dateLabel() { return L.received_on ?? 'Reçu le'; },
        daysSuffix: (d) => fmt(L.days_without_follow_up ?? ':daysj sans suivi', { days: d }),
        badgeClass: 'text-bg-info',
    },
    {
        id:       'orders_due_week',
        get label() { return L.orders_due_week ?? 'Livraisons cette semaine'; },
        icon:     'fa-truck',
        color:    '#20c997',
        section:  'week',
        endpointKey: 'today_orders_due_week',
        get emptyMsg() { return L.orders_due_week_empty ?? 'Aucune livraison prévue cette semaine.'; },
        get dateLabel() { return L.delivery ?? 'Livraison'; },
        daysSuffix: (d) => d === 0 ? (L.today ?? 'Aujourd\'hui') : fmt(L.in_days ?? 'Dans :daysj', { days: d }),
        badgeClass: 'text-bg-success',
    },
    {
        id:       'recent_activity',
        get label() { return L.recent_activity ?? 'Activité récente'; },
        icon:     'fa-history',
        color:    'var(--bs-secondary-color)',
        section:  'recent',
        endpointKey: 'today_recent_activity',
        get emptyMsg() { return L.recent_activity_empty ?? 'Aucune activité récente.'; },
        get dateLabel() { return L.created_on ?? 'Créé le'; },
        daysSuffix: null,
        badgeClass: 'text-bg-secondary',
    },
];

const SECTIONS = [
    { id: 'now',    get label() { return L.section_now ?? 'À faire maintenant'; },    icon: 'fa-exclamation-circle', color: '#dc3545' },
    { id: 'week',   get label() { return L.section_week ?? 'Cette semaine'; },          icon: 'fa-calendar-week',      color: '#fd7e14' },
    { id: 'recent', get label() { return L.section_recent ?? 'Récent'; },                 icon: 'fa-history',            color: 'var(--bs-secondary-color)' },
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
        credentials: 'same-origin',
        ...opts,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken(), ...opts.headers },
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
                    {item.code && <span style={{ marginRight: 4, color: 'var(--bs-body-color)' }}>{item.code}</span>}
                    {item.company && <span style={{ color: 'var(--bs-secondary-color)' }}>{item.company}</span>}
                    {item.label && !item.company && <span style={{ color: 'var(--bs-secondary-color)' }}>{item.label}</span>}
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
                className="btn btn-sm btn-outline-secondary"
                style={{ fontSize: 10, padding: '1px 6px', flexShrink: 0 }}
                title={L.open ?? 'Ouvrir'}
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
            background: 'var(--bs-body-bg)',
            border: '1px solid var(--bs-border-color)',
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
                <span style={{ fontWeight: 600, fontSize: 13, color: 'var(--bs-emphasis-color)' }}>
                    <i className={`fas ${def.icon} me-2`} style={{ color: def.color }} />
                    {def.label}
                </span>
                {!loading && (
                    <span className={`badge ${count > 0 ? def.badgeClass : 'text-bg-secondary'}`}>
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
                    <i className="fas fa-check-circle me-1 text-success" />{def.emptyMsg}
                </div>
            )}
            {error && (
                <div style={{ padding: '8px 12px', color: '#dc3545', fontSize: 12 }}>
                    <i className="fas fa-exclamation-triangle me-1" />{L.error_loading ?? 'Erreur de chargement'}
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
            background: 'var(--bs-body-bg)', boxShadow: '-4px 0 16px rgba(0,0,0,0.15)',
            zIndex: 1050, padding: '1rem', overflowY: 'auto',
        }}>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <strong>{L.customize_view ?? 'Personnaliser la vue'}</strong>
                <button className="btn btn-sm btn-light" onClick={onClose}>
                    <i className="fas fa-times" />
                </button>
            </div>
            <p className="text-muted" style={{ fontSize: '0.8rem' }}>
                {L.customize_help ?? 'Activez ou désactivez les sections qui vous concernent.'}
            </p>

            {WIDGET_REGISTRY.map(def => {
                const conf = local.find(w => w.id === def.id);
                const enabled = conf?.enabled ?? true;
                return (
                    <div key={def.id}
                        className="d-flex align-items-center justify-content-between mb-2 p-2"
                        style={{ border: '1px solid var(--bs-border-color)', borderRadius: 4 }}
                    >
                        <span style={{ fontSize: 13 }}>
                            <i className={`fas ${def.icon} me-2`} style={{ color: def.color, width: 16 }} />
                            {def.label}
                        </span>
                        <div className="form-check form-switch">
                            <input
                                type="checkbox"
                                className="form-check-input"
                                id={`widget-toggle-${def.id}`}
                                checked={enabled}
                                onChange={() => toggle(def.id)}
                            />
                            <label className="form-check-label" htmlFor={`widget-toggle-${def.id}`} />
                        </div>
                    </div>
                );
            })}

            <button
                className="btn btn-primary w-100 mt-3"
                onClick={() => { onSave(local); onClose(); }}
            >
                <i className="fas fa-save me-1" />{L.save ?? 'Enregistrer'}
            </button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TodayView — composant principal
// ---------------------------------------------------------------------------
export default function TodayView({ endpoints, trans = {} }) {
    L = trans?.ui ?? {};
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
                <i className="fas fa-spinner fa-spin me-2" />{L.loading ?? 'Chargement…'}
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
                    <h6 className="mb-0" style={{ fontWeight: 600, color: 'var(--bs-emphasis-color)' }}>
                        <i className="fas fa-sun me-2 text-warning" />
                        {today.charAt(0).toUpperCase() + today.slice(1)}
                    </h6>
                </div>
                <div className="d-flex align-items-center" style={{ gap: 8 }}>
                    {saving && (
                        <small className="text-muted">
                            <i className="fas fa-spinner fa-spin me-1" />
                        </small>
                    )}
                    <button
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => setShowConfig(s => !s)}
                        title={L.customize ?? 'Personnaliser'}
                    >
                        <i className="fas fa-sliders-h me-1" />{L.customize ?? 'Personnaliser'}
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
                                textTransform: 'uppercase', color: 'var(--bs-secondary-color)',
                            }}>
                                {section.label}
                            </span>
                            <div style={{ flex: 1, height: 1, background: 'var(--bs-border-color)' }} />
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
                    {L.no_widget ?? 'Aucun widget activé. Cliquez sur "Personnaliser" pour en ajouter.'}
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
