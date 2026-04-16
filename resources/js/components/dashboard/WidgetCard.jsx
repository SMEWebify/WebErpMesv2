import React from 'react';

// ─── Skeleton loader ──────────────────────────────────────────────────────────

function Skeleton({ lines = 3 }) {
    return (
        <div style={{ padding: '0.5rem 0' }}>
            {Array.from({ length: lines }, (_, i) => (
                <div
                    key={i}
                    style={{
                        height: i === 0 ? 36 : 14,
                        borderRadius: 4,
                        background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
                        backgroundSize: '400% 100%',
                        animation: 'wem-shimmer 1.4s infinite',
                        marginBottom: 10,
                        width: i === 0 ? '50%' : `${70 - i * 10}%`,
                    }}
                />
            ))}
            <style>{`
                @keyframes wem-shimmer {
                    0%   { background-position: 100% 0; }
                    100% { background-position: -100% 0; }
                }
            `}</style>
        </div>
    );
}

// ─── WidgetCard ───────────────────────────────────────────────────────────────

/**
 * WidgetCard — enveloppe commune pour tous les widgets du dashboard
 *
 * Props:
 *   title      string        — titre de la card
 *   icon       string        — classe FontAwesome (ex: "fa-chart-bar")
 *   theme      string        — couleur AdminLTE (info|primary|success|warning|danger|purple|teal|orange|lime)
 *   loading    bool          — affiche le skeleton
 *   error      string|null   — affiche un message d'erreur
 *   empty      bool          — affiche un état vide
 *   emptyText  string        — message état vide (défaut "Aucune donnée")
 *   children   ReactNode
 *
 *   // mode édition (dashboard personnalisable)
 *   editMode   bool          — active les handles d'édition
 *   onRemove   () => void    — callback suppression
 *   onTypeChange (type) => void  — callback changement de type de graphique
 *   availableTypes string[] — types disponibles pour ce widget (ex: ['bar', 'line', 'pie'])
 *   currentType  string     — type actif
 */
export default function WidgetCard({
    title,
    icon,
    theme = 'primary',
    loading = false,
    error = null,
    empty = false,
    emptyText = 'Aucune donnée',
    children,
    footer,
    fillHeight = false,
    // edit mode
    editMode = false,
    onRemove,
    onTypeChange,
    availableTypes = [],
    currentType,
}) {
    return (
        <div className={`card card-outline card-${theme}`} style={{ height: '100%', marginBottom: 0, display: 'flex', flexDirection: 'column' }}>
            {/* Header */}
            <div className="card-header d-flex align-items-center" style={{ minHeight: 42, padding: '0.4rem 0.75rem' }}>
                <h3 className="card-title" style={{ fontSize: '0.9rem', flex: 1, margin: 0 }}>
                    {icon && <i className={`fas ${icon} mr-1`} style={{ opacity: 0.7 }} />}
                    {title}
                </h3>

                {/* Edit mode actions */}
                {editMode && (
                    <div className="card-tools d-flex align-items-center" style={{ gap: '0.4rem' }}>
                        {/* Type switcher */}
                        {availableTypes.length > 1 && onTypeChange && (
                            <select
                                className="form-control form-control-sm"
                                style={{ height: 26, padding: '0 0.4rem', fontSize: '0.75rem', width: 'auto' }}
                                value={currentType}
                                onChange={e => onTypeChange(e.target.value)}
                                title="Changer le type de graphique"
                            >
                                {availableTypes.map(t => (
                                    <option key={t} value={t}>{TYPE_LABELS[t] ?? t}</option>
                                ))}
                            </select>
                        )}
                        {/* Remove */}
                        {onRemove && (
                            <button
                                type="button"
                                className="btn btn-sm btn-outline-danger"
                                style={{ padding: '0 0.4rem', height: 26, lineHeight: 1 }}
                                title="Supprimer ce widget"
                                onClick={onRemove}
                            >
                                <i className="fas fa-times" style={{ fontSize: '0.7rem' }} />
                            </button>
                        )}
                        {/* Drag handle */}
                        <span
                            className="wem-drag-handle"
                            title="Déplacer"
                            style={{ cursor: 'grab', color: '#aaa', padding: '0 2px' }}
                        >
                            <i className="fas fa-grip-vertical" style={{ fontSize: '0.75rem' }} />
                        </span>
                    </div>
                )}
            </div>

            {/* Body */}
            <div
                className="card-body"
                style={{
                    padding: '0.75rem',
                    overflow: fillHeight ? 'hidden' : 'auto',
                    ...(fillHeight ? { flex: '1 1 0', minHeight: 0, display: 'flex', flexDirection: 'column' } : {}),
                }}
            >
                {loading && <Skeleton />}
                {!loading && error && (
                    <div className="alert alert-danger mb-0 py-2" style={{ fontSize: '0.82rem' }}>
                        <i className="fas fa-exclamation-triangle mr-1" />
                        {error}
                    </div>
                )}
                {!loading && !error && empty && (
                    <p className="text-muted text-center small my-3">
                        <i className="fas fa-inbox mr-1" />
                        {emptyText}
                    </p>
                )}
                {!loading && !error && !empty && children}
            </div>
            {footer && (
                <div className="card-footer" style={{ padding: '0.5rem 0.75rem', flexShrink: 0 }}>
                    {footer}
                </div>
            )}
        </div>
    );
}

// Labels lisibles pour les types de graphiques
const TYPE_LABELS = {
    'stat-card': 'Chiffre',
    line:        'Courbe',
    area:        'Aire',
    bar:         'Barres',
    pie:         'Camembert',
    donut:       'Donut',
    table:       'Tableau',
    goal:        'Objectif',
    kanban:      'Kanban',
};
