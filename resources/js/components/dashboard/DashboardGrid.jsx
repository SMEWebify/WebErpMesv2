import React, { useState, useCallback, useEffect, useRef, useLayoutEffect } from 'react';
import { ResponsiveGridLayout } from 'react-grid-layout';
import 'react-grid-layout/css/styles.css';
import 'react-resizable/css/styles.css';
import REGISTRY, { WIDGET_MAP, DEFAULT_LAYOUT } from './WidgetRegistry.js';


// ─── Helpers ─────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function saveLayout(endpoint, layout) {
    const res = await fetch(endpoint, {
        method:  'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({ layout }),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
}

// ─── Permission check ────────────────────────────────────────────────────────

/**
 * permissions : { canPurchases: bool, ... }
 */
function canSeeWidget(widgetDef, permissions = {}) {
    if (!widgetDef.permission) return true;
    if (widgetDef.permission === 'purchases') return !!permissions.canPurchases;
    return true;
}

// ─── Hook largeur conteneur ───────────────────────────────────────────────────

function useContainerWidth(ref) {
    const [width, setWidth] = useState(1200);
    useLayoutEffect(() => {
        if (!ref.current) return;
        const ro = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
        ro.observe(ref.current);
        return () => ro.disconnect();
    }, [ref]);
    return width;
}

// ─── AddWidgetPanel ───────────────────────────────────────────────────────────

function AddWidgetPanel({ activeIds, onAdd, onClose, permissions }) {
    const available = REGISTRY.filter(w =>
        !activeIds.includes(w.id) && canSeeWidget(w, permissions)
    );
    return (
        <div
            style={{
                position: 'fixed', top: 0, right: 0, bottom: 0, width: 280,
                background: '#fff', boxShadow: '-4px 0 16px rgba(0,0,0,0.15)',
                zIndex: 1050, padding: '1rem', overflowY: 'auto',
            }}
        >
            <div className="d-flex justify-content-between align-items-center mb-3">
                <strong>Ajouter un widget</strong>
                <button className="btn btn-sm btn-light" onClick={onClose}>
                    <i className="fas fa-times" />
                </button>
            </div>
            {available.length === 0 && (
                <p className="text-muted" style={{ fontSize: '0.85rem' }}>Tous les widgets sont déjà affichés.</p>
            )}
            {available.map(w => (
                <div
                    key={w.id}
                    className="card card-body mb-2 p-2"
                    style={{ cursor: 'pointer', fontSize: '0.85rem' }}
                    onClick={() => onAdd(w)}
                >
                    <div className="d-flex align-items-center" style={{ gap: '0.5rem' }}>
                        <i className={`fas ${w.icon} text-primary`} style={{ width: 20 }} />
                        <span>{w.label}</span>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ─── DashboardGrid ────────────────────────────────────────────────────────────

/**
 * DashboardGrid — grille React-Grid-Layout personnalisable
 *
 * Props :
 *   dashProps        object  — toutes les props HomeDashboard (kpi, charts, urls, trans…)
 *   configEndpoint   string  — GET/PUT /dashboard/config
 */
export default function DashboardGrid({ dashProps, configEndpoint }) {
    const permissions = { canPurchases: !!dashProps?.canPurchases };
    const [layout,   setLayout]   = useState(null);   // null = en cours de chargement
    const [editMode, setEditMode] = useState(false);
    const [showAdd,  setShowAdd]  = useState(false);
    const [saving,   setSaving]   = useState(false);
    const saveTimer  = useRef(null);
    const containerRef = useRef(null);
    const containerWidth = useContainerWidth(containerRef);

    // ── Chargement de la config sauvegardée ──────────────────────────────────
    useEffect(() => {
        fetch(configEndpoint, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => setLayout(Array.isArray(data) && data.length ? data : DEFAULT_LAYOUT))
            .catch(() => setLayout(DEFAULT_LAYOUT));
    }, [configEndpoint]);

    // ── Sauvegarde automatique (debounce 1s) ─────────────────────────────────
    const persistLayout = useCallback((newLayout) => {
        clearTimeout(saveTimer.current);
        saveTimer.current = setTimeout(async () => {
            setSaving(true);
            try { await saveLayout(configEndpoint, newLayout); }
            catch (e) { console.error('[DashboardGrid] save error', e); }
            finally { setSaving(false); }
        }, 1000);
    }, [configEndpoint]);

    const handleLayoutChange = useCallback((newLayout) => {
        // react-grid-layout retourne seulement {i,x,y,w,h} — on garde ça
        setLayout(newLayout);
        persistLayout(newLayout);
    }, [persistLayout]);

    // ── Supprimer un widget ───────────────────────────────────────────────────
    const removeWidget = useCallback((id) => {
        setLayout(prev => {
            const next = prev.filter(item => item.i !== id);
            persistLayout(next);
            return next;
        });
    }, [persistLayout]);

    // ── Ajouter un widget ─────────────────────────────────────────────────────
    const addWidget = useCallback((widgetDef) => {
        setLayout(prev => {
            const maxY = prev.reduce((m, item) => Math.max(m, item.y + item.h), 0);
            const next = [...prev, {
                i: widgetDef.id,
                x: 0,
                y: maxY,
                w: widgetDef.defaultW,
                h: widgetDef.defaultH,
            }];
            persistLayout(next);
            return next;
        });
        setShowAdd(false);
    }, [persistLayout]);

    // ── Réinitialiser le layout ───────────────────────────────────────────────
    const resetLayout = useCallback(() => {
        setLayout(DEFAULT_LAYOUT);
        persistLayout(DEFAULT_LAYOUT);
    }, [persistLayout]);

    if (!layout) {
        return (
            <div className="text-center py-5 text-muted">
                <i className="fas fa-spinner fa-spin mr-2" />Chargement…
            </div>
        );
    }

    // ── Rendu ─────────────────────────────────────────────────────────────────
    return (
        <div ref={containerRef}>
            {/* Barre d'outils edit mode */}
            <div className="d-flex justify-content-end align-items-center mb-2" style={{ gap: '0.5rem' }}>
                {saving && (
                    <small className="text-muted">
                        <i className="fas fa-spinner fa-spin mr-1" />Sauvegarde…
                    </small>
                )}
                {editMode && (
                    <>
                        <button className="btn btn-sm btn-outline-primary" onClick={() => setShowAdd(true)}>
                            <i className="fas fa-plus mr-1" />Ajouter
                        </button>
                        <button className="btn btn-sm btn-outline-secondary" onClick={resetLayout}>
                            <i className="fas fa-undo mr-1" />Réinitialiser
                        </button>
                    </>
                )}
                <button
                    className={`btn btn-sm ${editMode ? 'btn-success' : 'btn-outline-secondary'}`}
                    onClick={() => { setEditMode(e => !e); setShowAdd(false); }}
                >
                    <i className={`fas ${editMode ? 'fa-check' : 'fa-edit'} mr-1`} />
                    {editMode ? 'Terminer' : 'Personnaliser'}
                </button>
            </div>

            {/* Grille */}
            <ResponsiveGridLayout
                width={containerWidth}
                layouts={{ lg: layout, md: layout, sm: layout }}
                breakpoints={{ lg: 1200, md: 992, sm: 768, xs: 480 }}
                cols={{ lg: 12, md: 12, sm: 6, xs: 4 }}
                rowHeight={80}
                isDraggable={editMode}
                isResizable={editMode}
                onLayoutChange={(current) => handleLayoutChange(current)}
                draggableHandle=".widget-drag-handle"
                margin={[12, 12]}
            >
                {layout.map(item => {
                    const def = WIDGET_MAP[item.i];
                    if (!def) return null;
                    if (!canSeeWidget(def, permissions)) return null;
                    const WidgetComponent = def.component;
                    const widgetProps = def.getProps(dashProps);
                    return (
                        <div key={item.i} style={{ display: 'flex', flexDirection: 'column' }}>
                            {editMode && (
                                <div
                                    className="d-flex justify-content-between align-items-center px-2 py-1 mb-1"
                                    style={{ background: '#f8f9fa', borderRadius: 4, fontSize: '0.8rem' }}
                                >
                                    <span className="widget-drag-handle" style={{ cursor: 'grab', color: '#6c757d' }}>
                                        <i className="fas fa-grip-vertical mr-1" />{def.label}
                                    </span>
                                    <button
                                        className="btn btn-xs btn-outline-danger"
                                        style={{ padding: '0 6px', fontSize: '0.75rem' }}
                                        onClick={() => removeWidget(item.i)}
                                        title="Supprimer ce widget"
                                    >
                                        <i className="fas fa-times" />
                                    </button>
                                </div>
                            )}
                            <div style={{ flex: 1, minHeight: 0, overflow: 'hidden' }}>
                                <WidgetComponent {...widgetProps} />
                            </div>
                        </div>
                    );
                })}
            </ResponsiveGridLayout>

            {/* Panneau ajout */}
            {showAdd && (
                <AddWidgetPanel
                    activeIds={layout.map(l => l.i)}
                    onAdd={addWidget}
                    onClose={() => setShowAdd(false)}
                    permissions={permissions}
                />
            )}
        </div>
    );
}
