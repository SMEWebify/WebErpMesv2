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

function canSeeWidget(widgetDef, permissions = {}) {
    if (!widgetDef.permission) return true;
    if (widgetDef.permission === 'purchases') return !!permissions.canPurchases;
    return true;
}

// ─── Hook largeur conteneur ───────────────────────────────────────────────────

function useContainerWidth(ref) {
    const [width, setWidth] = useState(() => {
        // Initialiser avec la vraie largeur si disponible dès le départ
        return ref?.current?.offsetWidth || window.innerWidth - 280 || 1200;
    });
    useLayoutEffect(() => {
        if (!ref.current) return;
        // Mesure immédiate avant le premier paint
        setWidth(ref.current.offsetWidth);
        const ro = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
        ro.observe(ref.current);
        return () => ro.disconnect();
    }, [ref]);
    return width;
}

// ─── Placement intelligent ────────────────────────────────────────────────────

/**
 * Trouve la première position libre (x, y) pour un widget de taille w×h.
 * Parcourt de haut en bas, gauche à droite.
 */
function findFreePosition(layout, w, h, cols = 12) {
    const safeW = Math.min(w, cols);
    const maxY = layout.reduce((m, item) => Math.max(m, item.y + item.h), 0);

    for (let y = 0; y <= maxY; y++) {
        for (let x = 0; x <= cols - safeW; x++) {
            const fits = !layout.some(item => {
                const noOverlap =
                    item.x + item.w <= x ||
                    item.x >= x + safeW  ||
                    item.y + item.h <= y  ||
                    item.y >= y + h;
                return !noOverlap;
            });
            if (fits) return { x, y };
        }
    }
    return { x: 0, y: maxY }; // fallback bas de page
}

// ─── AddWidgetPanel ───────────────────────────────────────────────────────────

function AddWidgetPanel({ activeIds, onAdd, onClose, permissions, onDragStart, onDragEnd }) {
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
            <p className="text-muted" style={{ fontSize: '0.78rem', marginBottom: '0.75rem' }}>
                <i className="fas fa-hand-pointer mr-1" />Cliquez ou <i className="fas fa-arrows-alt mr-1" />glissez dans la grille
            </p>
            {available.map(w => (
                <div
                    key={w.id}
                    className="card card-body mb-2 p-2"
                    style={{ cursor: 'grab', fontSize: '0.85rem', userSelect: 'none' }}
                    draggable
                    onDragStart={(e) => {
                        e.dataTransfer.setData('widgetId', w.id);
                        e.dataTransfer.effectAllowed = 'copy';
                        onDragStart(w);
                    }}
                    onDragEnd={onDragEnd}
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

export default function DashboardGrid({ dashProps, configEndpoint, editMode = false, onEditModeChange }) {
    const permissions = { canPurchases: !!dashProps?.canPurchases };
    const [layout,          setLayout]          = useState(null);
    const [showAdd,         setShowAdd]         = useState(false);
    const [saving,          setSaving]          = useState(false);
    const [draggingWidget,  setDraggingWidget]  = useState(null); // widget glissé depuis le panel
    const saveTimer    = useRef(null);
    const containerRef = useRef(null);
    const containerWidth = useContainerWidth(containerRef);

    // ── Fermer le panel Ajouter quand on quitte le mode édition ─────────────
    useEffect(() => {
        if (!editMode) { setShowAdd(false); setDraggingWidget(null); }
    }, [editMode]);

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

    // ── Ajouter un widget (clic depuis le panel) ──────────────────────────────
    const addWidget = useCallback((widgetDef) => {
        setLayout(prev => {
            const { x, y } = findFreePosition(prev, widgetDef.defaultW, widgetDef.defaultH);
            const next = [...prev, {
                i: widgetDef.id,
                x,
                y,
                w: Math.min(widgetDef.defaultW, 12),
                h: widgetDef.defaultH,
            }];
            persistLayout(next);
            return next;
        });
        setShowAdd(false);
    }, [persistLayout]);

    // ── Drop depuis le panel (drag-and-drop) ──────────────────────────────────
    const handleDrop = useCallback((currentLayout, droppedItem, event) => {
        const widgetId = event.dataTransfer?.getData('widgetId') || draggingWidget?.id;
        const def = widgetId ? WIDGET_MAP[widgetId] : null;
        if (!def) { setDraggingWidget(null); return; }

        setLayout(prev => {
            // Retirer l'item fantôme '__dropping-elem__' s'il existe, ajouter le vrai widget
            const next = [
                ...prev.filter(l => l.i !== '__dropping-elem__' && l.i !== def.id),
                {
                    i: def.id,
                    x: droppedItem.x,
                    y: droppedItem.y,
                    w: droppedItem.w,
                    h: droppedItem.h,
                },
            ];
            persistLayout(next);
            return next;
        });
        setDraggingWidget(null);
        setShowAdd(false);
    }, [draggingWidget, persistLayout]);

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
        <div ref={containerRef} style={{ width: '100%' }}>
            {/* Barre d'outils — visible uniquement en mode édition */}
            {editMode && (
                <div className="d-flex justify-content-end align-items-center mb-2" style={{ gap: '0.5rem' }}>
                    {saving && (
                        <small className="text-muted">
                            <i className="fas fa-spinner fa-spin mr-1" />Sauvegarde…
                        </small>
                    )}
                    <button className="btn btn-sm btn-outline-primary" onClick={() => setShowAdd(s => !s)}>
                        <i className="fas fa-plus mr-1" />Ajouter
                    </button>
                    <button className="btn btn-sm btn-outline-secondary" onClick={resetLayout}>
                        <i className="fas fa-undo mr-1" />Réinitialiser
                    </button>
                    <button
                        className="btn btn-sm btn-success"
                        onClick={() => { onEditModeChange?.(false); setShowAdd(false); setDraggingWidget(null); }}
                    >
                        <i className="fas fa-check mr-1" />Terminer
                    </button>
                </div>
            )}

            {/* Zone de drop visuelle en mode édition */}
            {editMode && draggingWidget && (
                <div
                    style={{
                        position: 'absolute', inset: 0, zIndex: 1,
                        pointerEvents: 'none',
                        border: '2px dashed #007bff',
                        borderRadius: 4,
                        background: 'rgba(0, 123, 255, 0.03)',
                    }}
                />
            )}

            {/* Grille */}
            <ResponsiveGridLayout
                width={containerWidth}
                layouts={{ lg: layout, md: layout, sm: layout }}
                breakpoints={{ lg: 1200, md: 992, sm: 768, xs: 480 }}
                cols={{ lg: 12, md: 12, sm: 6, xs: 4 }}
                rowHeight={80}
                isDraggable={editMode}
                isResizable={editMode}
                isDroppable={editMode}
                droppingItem={draggingWidget ? {
                    i:    '__dropping-elem__',
                    w:    Math.min(draggingWidget.defaultW, 12),
                    h:    draggingWidget.defaultH,
                } : undefined}
                onLayoutChange={(current) => handleLayoutChange(current)}
                onDrop={handleDrop}
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
                    onDragStart={(w) => setDraggingWidget(w)}
                    onDragEnd={() => {
                        // Si le drop n'a pas eu lieu sur la grille, reset
                        setTimeout(() => setDraggingWidget(d => d), 200);
                    }}
                />
            )}
        </div>
    );
}
