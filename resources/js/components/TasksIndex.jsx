import React, { useState, useMemo, useRef } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const today = new Date().toISOString().split('T')[0];

const STATUS_COLORS = {
    'Open':        'info',
    'In Progress': 'warning',
    'Pending':     'secondary',
    'Supplied':    'primary',
    'Finished':    'success',
};

function statusColor(title) {
    return STATUS_COLORS[title] ?? 'secondary';
}

function endDateClass(task) {
    if (task.type != 1 && task.type != 7) return 'bg-info';
    const date = task.formatted_end_date;
    if (!date || date === 'NULL') return '';
    if (today > date) return 'bg-danger';
    if (today === date) return 'bg-orange';
    return 'bg-primary';
}

const LS_COL_ORDER   = 'tasks_table_col_order';
const LS_HIDDEN_COLS = 'tasks_table_hidden_cols';

const DEFAULT_COL_ORDER = [
    'order', 'qty', 'order_label', 'label',
    'product', 'service', 'resources',
    'quality_required', 'seting_time', 'unit_time', 'total_time',
    'progress', 'status', 'end_date',
];

const TEXT_FILTER_COLS = new Set(['label', 'order_label']);
const DATE_RANGE_COLS  = new Set(['end_date']);

// ---------------------------------------------------------------------------
// Column definitions
// ---------------------------------------------------------------------------

function colDefs(t) {
    return {
        order: {
            label:     t('order_trans_key'),
            sortField: 'order_lines_id',
            align:     '',
            render:    (task) => {
                if (task.sub_assembly?.order_lines_id) {
                    const id   = task.sub_assembly.order_lines?.orders_id;
                    const code = task.sub_assembly.order_lines?.order?.code;
                    return <a href={`/orders/${id}`} className="btn btn-xs btn-default">{code}</a>;
                }
                if (task.order_lines) {
                    const id   = task.order_lines.orders_id;
                    const code = task.order_lines.order?.code;
                    return <a href={`/orders/${id}`} className="btn btn-xs btn-default">{code}</a>;
                }
                return <span className="badge badge-secondary">{t('generic_trans_key')}</span>;
            },
        },
        qty: {
            label:     t('qty_trans_key'),
            sortField: null,
            align:     '',
            render:    (task) => {
                if (task.sub_assembly) return `${task.sub_assembly.qty} ×`;
                if (task.order_lines)  return `${task.order_lines.qty} × ${task.qty}`;
                return '';
            },
        },
        order_label: {
            label:     `${t('order_trans_key')} ${t('label_trans_key')}`,
            sortField: null,
            align:     '',
            render:    (task) => {
                if (task.sub_assembly) return <><i className="fas fa-code-branch mr-1" />{task.sub_assembly.child?.label}</>;
                if (task.order_lines)  return task.order_lines.label;
                return '';
            },
        },
        label: {
            label:     t('label_trans_key'),
            sortField: 'label',
            align:     '',
            render:    (task) => (
                <>
                    <a href={`/production/Task/Statu/Id/${task.id}`} className="btn btn-xs btn-success mr-1">
                        {t('view_trans_key')}
                    </a>
                    <span className="text-muted small">#{task.id}</span>
                    {' — '}
                    {task.label}
                    {task.component_id && <span className="text-muted"> — {task.component?.label}</span>}
                </>
            ),
        },
        product: {
            label:     t('product_trans_key'),
            sortField: null,
            align:     'center',
            render:    (task) =>
                task.component_id
                    ? <a href={`/products/${task.component_id}`} className="btn btn-xs btn-default"><i className="fas fa-eye" /></a>
                    : null,
            cellClass: (task) => task.component_id ? `bg-${task.component?.stock_color} color-palette` : '',
        },
        service: {
            label:     t('service_trans_key'),
            sortField: 'methods_services_id',
            align:     '',
            render:    (task) => {
                if (!task.service) return null;
                const style = task.service.color ? { backgroundColor: task.service.color } : {};
                return (
                    <span style={style}>
                        {task.service.picture ? (
                            <span
                                data-toggle="tooltip"
                                data-html="true"
                                title={`<img alt='Service' class='profile-user-img img-fluid img-circle' src='/images/methods/${task.service.picture}'>`}
                            >
                                {task.service.label}
                            </span>
                        ) : task.service.label}
                    </span>
                );
            },
            cellStyle: (task) => task.service?.color ? { backgroundColor: task.service.color } : {},
        },
        resources: {
            label:     t('ressource_trans_key'),
            sortField: null,
            align:     '',
            render:    (task) => (
                <ul className="list-unstyled mb-0">
                    {task.resources?.map((r) => (
                        <li key={r.id}>
                            {r.picture ? (
                                <span
                                    data-toggle="tooltip"
                                    data-html="true"
                                    title={`<img alt='Ressource' class='profile-user-img' src='/images/ressources/${r.picture}'>`}
                                >
                                    {r.label}
                                </span>
                            ) : r.label}
                        </li>
                    ))}
                </ul>
            ),
        },
        quality_required: {
            label:     t('qty_trans_key'),
            sortField: 'quality_required',
            align:     'right',
            render:    (task) =>
                Number(task.quality_required ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 }),
        },
        seting_time: {
            label:     t('setting_time_trans_key'),
            sortField: 'seting_time',
            align:     'right',
            render:    (task) => `${parseFloat((task.seting_time ?? 0).toFixed(2))} h`,
        },
        unit_time: {
            label:     t('unit_time_trans_key'),
            sortField: 'unit_time',
            align:     'right',
            render:    (task) => `${parseFloat((task.unit_time ?? 0).toFixed(2))} h`,
        },
        total_time: {
            label:     t('total_time_trans_key'),
            sortField: 'total_time',
            align:     'right',
            render:    (task) => `${parseFloat((task.total_time ?? 0).toFixed(2))} h`,
        },
        progress: {
            label:     t('progress_trans_key'),
            sortField: 'progress',
            align:     '',
            render:    (task) => <ProgressBar value={task.progress} />,
        },
        status: {
            label:     t('status_trans_key'),
            sortField: 'status_id',
            align:     '',
            render:    (task) => (
                <span className={`badge badge-${statusColor(task.status?.title ?? '')}`}>
                    {task.status?.title}
                </span>
            ),
        },
        end_date: {
            label:     t('end_date_trans_key'),
            sortField: 'formatted_end_date',
            align:     '',
            render:    (task) => {
                const display = task.type != 1 && task.type != 7
                    ? task.service?.label
                    : task.formatted_end_date === 'NULL' ? '' : task.formatted_end_date;
                return display;
            },
            cellClass: (task) => `${endDateClass(task)} color-palette`,
        },
    };
}

// ---------------------------------------------------------------------------
// Per-column filter matching
// ---------------------------------------------------------------------------

function matchesColFilter(task, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = task.formatted_end_date;
        if (!iso || iso === 'NULL') return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'label':
            return (task.label ?? '').toLowerCase().includes(v);
        case 'order_label': {
            const lbl = task.sub_assembly?.child?.label ?? task.order_lines?.label ?? '';
            return lbl.toLowerCase().includes(v);
        }
        default:
            return true;
    }
}

// ---------------------------------------------------------------------------
// localStorage helpers
// ---------------------------------------------------------------------------

function readSavedColOrder() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_COL_ORDER));
        if (Array.isArray(saved) && saved.every((c) => DEFAULT_COL_ORDER.includes(c))) return saved;
    } catch {}
    return DEFAULT_COL_ORDER;
}

function readSavedHiddenCols() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_HIDDEN_COLS));
        if (Array.isArray(saved)) return new Set(saved.filter((c) => DEFAULT_COL_ORDER.includes(c)));
    } catch {}
    return new Set();
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function ProgressBar({ value }) {
    const pct = Math.min(Math.max(value ?? 0, 0), 100);
    return (
        <div className="progress progress-sm" style={{ minWidth: 60 }}>
            <div
                className="progress-bar bg-teal progress-bar-striped progress-bar-animated"
                role="progressbar"
                style={{ width: `${pct}%` }}
            >
                <span>{pct}%</span>
            </div>
        </div>
    );
}

function SortIcon({ field, sortField, sortAsc }) {
    if (sortField !== field) return <i className="fas fa-sort text-muted ml-1" style={{ fontSize: '0.7rem', opacity: 0.5 }} />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} style={{ fontSize: '0.7rem' }} />;
}

function StatusFilter({ statuses, selected, onChange }) {
    const toggle = (id) => {
        const next = selected.includes(id)
            ? selected.filter((s) => s !== id)
            : [...selected, id];
        onChange(next.length ? next : [id]);
    };
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.2rem' }}>
            {statuses.map((s) => {
                const color  = statusColor(s.title);
                const active = selected.includes(s.id);
                return (
                    <button
                        key={s.id}
                        type="button"
                        className={`btn btn-sm ${active ? `btn-${color}` : 'btn-outline-secondary'}`}
                        onClick={() => toggle(s.id)}
                    >
                        {s.title}
                    </button>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// TasksTable — drag-to-reorder + per-column hide + column filters
// ---------------------------------------------------------------------------

function TasksTable({ tasks, sortField, sortAsc, onSort, t }) {
    const [colOrder,    setColOrder]    = useState(readSavedColOrder);
    const [hiddenCols,  setHiddenCols]  = useState(readSavedHiddenCols);
    const [colFilters,  setColFilters]  = useState({});
    const [dragOver,    setDragOver]    = useState(null);
    const dragCol = useRef(null);

    const COLS        = colDefs(t);
    const visibleCols = colOrder.filter((c) => !hiddenCols.has(c));

    const hideCol = (colId) => {
        const next = new Set(hiddenCols);
        next.add(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };

    const showCol = (colId) => {
        const next = new Set(hiddenCols);
        next.delete(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };

    // Apply per-column text/date filters on top of the already-filtered task list
    const filtered = tasks.filter((task) =>
        visibleCols.every((colId) => matchesColFilter(task, colId, colFilters[colId] ?? ''))
    );

    // ---- drag handlers ----
    const onDragStart = (colId) => { dragCol.current = colId; };
    const onDragOver  = (e, colId) => { e.preventDefault(); setDragOver(colId); };
    const onDragLeave = () => setDragOver(null);
    const onDrop      = (targetId) => {
        const src = dragCol.current;
        if (!src || src === targetId) { setDragOver(null); return; }
        const next = [...colOrder];
        next.splice(next.indexOf(targetId), 0, next.splice(next.indexOf(src), 1)[0]);
        setColOrder(next);
        localStorage.setItem(LS_COL_ORDER, JSON.stringify(next));
        setDragOver(null);
        dragCol.current = null;
    };

    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    return (
        <div>
            {/* ── hidden-column restore chips ── */}
            {hiddenCols.size > 0 && (
                <div className="mb-2 d-flex flex-wrap px-3" style={{ gap: '4px' }}>
                    {colOrder.filter((c) => hiddenCols.has(c)).map((colId) => (
                        <button
                            key={colId}
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => showCol(colId)}
                            title="Réafficher la colonne"
                        >
                            + {COLS[colId].label}
                        </button>
                    ))}
                </div>
            )}

            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        {/* ── Row 1 : column headers (draggable + sort + hide) ── */}
                        <tr>
                            {visibleCols.map((colId) => {
                                const col      = COLS[colId];
                                const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                const dropping = dragOver === colId;
                                return (
                                    <th
                                        key={colId}
                                        className={alignCls}
                                        draggable
                                        style={{
                                            cursor:     col.sortField ? 'pointer' : 'grab',
                                            whiteSpace: 'nowrap',
                                            userSelect: 'none',
                                            borderLeft: dropping ? '3px solid #007bff' : undefined,
                                            background: dropping ? '#e8f0fe' : undefined,
                                        }}
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={(e) => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => col.sortField && onSort(col.sortField)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        {col.sortField && (
                                            <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />
                                        )}
                                        <span
                                            role="button"
                                            aria-label="Masquer la colonne"
                                            style={{ marginLeft: '6px', opacity: 0.4, fontSize: '0.8rem', lineHeight: 1 }}
                                            className="text-danger"
                                            onClick={(e) => { e.stopPropagation(); hideCol(colId); }}
                                            onMouseEnter={(e) => (e.currentTarget.style.opacity = 1)}
                                            onMouseLeave={(e) => (e.currentTarget.style.opacity = 0.4)}
                                        >
                                            ×
                                        </span>
                                    </th>
                                );
                            })}
                        </tr>
                        {/* ── Row 2 : per-column filter inputs ── */}
                        <tr>
                            {visibleCols.map((colId) => (
                                <th key={colId} style={{ padding: '2px 4px', fontWeight: 'normal' }}>
                                    {TEXT_FILTER_COLS.has(colId) && (
                                        <input
                                            type="text"
                                            className="form-control form-control-sm"
                                            style={inputStyle}
                                            placeholder="⌕"
                                            value={colFilters[colId] ?? ''}
                                            onChange={(e) =>
                                                setColFilters((prev) => ({ ...prev, [colId]: e.target.value }))
                                            }
                                        />
                                    )}
                                    {DATE_RANGE_COLS.has(colId) && (
                                        <div style={{ display: 'flex', gap: '2px', minWidth: '200px' }}>
                                            <input
                                                type="date"
                                                className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Du"
                                                value={(colFilters[colId] ?? {}).from ?? ''}
                                                onChange={(e) =>
                                                    setColFilters((prev) => ({
                                                        ...prev,
                                                        [colId]: { ...(prev[colId] ?? {}), from: e.target.value },
                                                    }))
                                                }
                                            />
                                            <input
                                                type="date"
                                                className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Au"
                                                value={(colFilters[colId] ?? {}).to ?? ''}
                                                onChange={(e) =>
                                                    setColFilters((prev) => ({
                                                        ...prev,
                                                        [colId]: { ...(prev[colId] ?? {}), to: e.target.value },
                                                    }))
                                                }
                                            />
                                        </div>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 && (
                            <tr>
                                <td colSpan={visibleCols.length} className="text-center text-muted py-3">
                                    {t('no_data_trans_key')}
                                </td>
                            </tr>
                        )}
                        {filtered.map((task) => (
                            <tr key={task.id}>
                                {visibleCols.map((colId) => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    const extraCls = col.cellClass ? col.cellClass(task) : '';
                                    const extraSty = col.cellStyle ? col.cellStyle(task) : {};
                                    return (
                                        <td
                                            key={colId}
                                            className={`${alignCls} ${extraCls}`.trim()}
                                            style={{ whiteSpace: col.align === 'right' ? 'nowrap' : undefined, ...extraSty }}
                                        >
                                            {col.render(task)}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TasksIndex — toolbar + table
// ---------------------------------------------------------------------------

export default function TasksIndex({
    tasks: initialTasks,
    services,
    statuses,
    resources,
    defaultStatusIds,
    trans,
}) {
    const [serviceId,        setServiceId]        = useState('');
    const [selectedStatuses, setSelectedStatuses] = useState(defaultStatusIds ?? []);
    const [resourceId,       setResourceId]       = useState('');
    const [showGeneric,      setShowGeneric]       = useState(false);
    const [sortField,        setSortField]         = useState('formatted_end_date');
    const [sortAsc,          setSortAsc]           = useState(true);

    const t = (key) => trans?.[key] ?? key;

    function handleSort(field) {
        if (sortField === field) {
            setSortAsc(!sortAsc);
        } else {
            setSortField(field);
            setSortAsc(true);
        }
    }

    const filtered = useMemo(() => {
        let list = initialTasks;

        // Hide tasks not linked to any order unless "show generic" is on
        if (!showGeneric) {
            list = list.filter(
                (task) =>
                    (task.sub_assembly_id && task.sub_assembly?.order_lines_id) ||
                    task.order_lines_id
            );
        }

        if (serviceId) {
            list = list.filter((task) => String(task.methods_services_id) === String(serviceId));
        }

        if (selectedStatuses.length > 0) {
            list = list.filter((task) => selectedStatuses.includes(task.status_id));
        }

        if (resourceId) {
            list = list.filter((task) =>
                task.resources?.some((r) => String(r.id) === String(resourceId))
            );
        }

        list = [...list].sort((a, b) => {
            const aVal = a[sortField] ?? '';
            const bVal = b[sortField] ?? '';
            if (aVal < bVal) return sortAsc ? -1 : 1;
            if (aVal > bVal) return sortAsc ? 1 : -1;
            return 0;
        });

        return list;
    }, [initialTasks, serviceId, selectedStatuses, resourceId, showGeneric, sortField, sortAsc]);

    return (
        <div className="card">
            {/* ── Toolbar ── */}
            <div className="card-body pb-2 pt-3">
                <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>

                    {/* Service */}
                    <select
                        className="form-control form-control-sm flex-shrink-0"
                        style={{ width: 160 }}
                        value={serviceId}
                        onChange={(e) => setServiceId(e.target.value)}
                    >
                        <option value="">— {t('service_trans_key')} —</option>
                        {services.map((s) => (
                            <option key={s.id} value={s.id}>{s.label}</option>
                        ))}
                    </select>

                    {/* Resource */}
                    <select
                        className="form-control form-control-sm flex-shrink-0"
                        style={{ width: 160 }}
                        value={resourceId}
                        onChange={(e) => setResourceId(e.target.value)}
                    >
                        <option value="">— {t('ressource_trans_key')} —</option>
                        {resources.map((r) => (
                            <option key={r.id} value={r.id}>{r.label}</option>
                        ))}
                    </select>

                    {/* Status buttons */}
                    <StatusFilter
                        statuses={statuses}
                        selected={selectedStatuses}
                        onChange={setSelectedStatuses}
                    />

                    {/* Spacer */}
                    <div className="flex-grow-1" />

                    {/* Show generic toggle */}
                    <button
                        type="button"
                        className={`btn btn-sm flex-shrink-0 ${showGeneric ? 'btn-warning' : 'btn-outline-secondary'}`}
                        onClick={() => setShowGeneric(!showGeneric)}
                    >
                        <i className="fas fa-layer-group mr-1" />
                        {t('show_generic_task_trans_key')}
                    </button>

                    {/* Result count */}
                    <span className="badge badge-secondary flex-shrink-0">{filtered.length}</span>
                </div>
            </div>

            {/* ── Table ── */}
            <TasksTable
                tasks={filtered}
                sortField={sortField}
                sortAsc={sortAsc}
                onSort={handleSort}
                t={t}
            />
        </div>
    );
}
