import React, { useEffect, useRef, useState } from 'react';
import Gantt from 'frappe-gantt';
// CSS chargé via @vite dans la Blade (frappe-gantt/dist/frappe-gantt.css hors exports map)

const VIEW_MODES = ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'];
const DEFAULT_VIEW = 'Week';

function buildTasks(data) {
    const today = new Date().toISOString().slice(0, 10);

    return data.map((task) => {
        // PHP DateTime sérialise en { date: "YYYY-MM-DD HH:mm:ss.000000", ... }
        const start = task.start_date?.date
            ? task.start_date.date.slice(0, 10)
            : null;
        const end = task.end_date?.date
            ? task.end_date.date.slice(0, 10)
            : null;

        const durationDays = task.duration
            ? Math.max(1, Math.ceil(task.duration))
            : 1;

        const resolvedStart = start ?? today;
        const resolvedEnd   = end ?? (() => {
            const d = new Date(resolvedStart);
            d.setDate(d.getDate() + durationDays);
            return d.toISOString().slice(0, 10);
        })();

        let customClass = '';
        if (String(task.id).startsWith('order_')) customClass = 'gantt-order-root';
        else if (String(task.id).startsWith('ol_'))   customClass = 'gantt-order-line';

        return {
            id:           String(task.id),
            name:         task.label,
            start:        resolvedStart,
            end:          resolvedEnd,
            progress:     task.progress ?? 0,
            dependencies: task.dependencies ? String(task.dependencies) : '',
            custom_class: customClass,
        };
    });
}

export default function GanttChart({ orderId, apiBase, trans }) {
    // frappe-gantt a besoin d'un <div> conteneur, pas d'un <svg>
    const containerRef = useRef(null);
    const ganttRef     = useRef(null);

    const [tasks,    setTasks]    = useState([]);
    const [loading,  setLoading]  = useState(false);
    const [error,    setError]    = useState(null);
    const [viewMode, setViewMode] = useState(DEFAULT_VIEW);

    const t = (key) => trans?.[key] ?? key;

    // Fetch quand orderId change
    useEffect(() => {
        if (!orderId) return;

        setLoading(true);
        setError(null);
        setTasks([]);

        fetch(`${apiBase}/${orderId}`)
            .then((r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((json) => setTasks(buildTasks(json.data ?? [])))
            .catch((err) => setError(err.message))
            .finally(() => setLoading(false));
    }, [orderId, apiBase]);

    // Mount / update frappe-gantt quand les tâches ou le zoom changent
    useEffect(() => {
        if (!containerRef.current || tasks.length === 0) return;

        // Vider le conteneur pour éviter les doublons
        containerRef.current.innerHTML = '';
        ganttRef.current = null;

        try {
            ganttRef.current = new Gantt(containerRef.current, tasks, {
                view_mode:  viewMode,
                date_format: 'YYYY-MM-DD',
                language:   'fr',
                popup_on:   'click',
                popup: (task) => `
                    <div style="font-size:0.85rem;min-width:180px;padding:8px">
                        <strong>${task.name}</strong><br/>
                        <small>${task.start} → ${task.end}</small><br/>
                        <div style="background:#e9ecef;border-radius:3px;height:6px;margin-top:4px">
                            <div style="background:#20c997;width:${task.progress}%;height:100%;border-radius:3px"></div>
                        </div>
                        <small>${task.progress}%</small>
                    </div>
                `,
            });
        } catch (e) {
            console.error('frappe-gantt error:', e);
        }
    }, [tasks, viewMode]);

    return (
        <div>
            {/* Boutons de zoom */}
            <div className="mb-3 d-flex align-items-center" style={{ gap: '0.4rem' }}>
                <span className="text-muted small mr-2">{t('view_trans_key')} :</span>
                {VIEW_MODES.map((mode) => (
                    <button
                        key={mode}
                        type="button"
                        className={`btn btn-sm ${viewMode === mode ? 'btn-danger' : 'btn-outline-secondary'}`}
                        onClick={() => setViewMode(mode)}
                    >
                        {mode}
                    </button>
                ))}
            </div>

            {!orderId && (
                <p className="text-muted">{t('select_order_trans_key')}</p>
            )}
            {loading && (
                <div className="text-center py-4">
                    <i className="fas fa-spinner fa-spin fa-2x text-secondary" />
                </div>
            )}
            {error && (
                <div className="alert alert-danger">{error}</div>
            )}

            <div ref={containerRef} style={{ overflowX: 'auto' }} />
        </div>
    );
}
