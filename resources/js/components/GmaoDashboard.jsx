import React, { useState } from 'react';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function fmt(val) {
    if (val === null || val === undefined || val === '') return '—';
    return val;
}

function fmtDate(str) {
    if (!str) return '—';
    try { return new Date(str).toLocaleDateString('fr-FR'); } catch { return str; }
}

// ---------------------------------------------------------------------------
// KPI Card (mirrors AdminLTE info-box style)
// ---------------------------------------------------------------------------

const KPI_ICONS = {
    MTBF:              { icon: 'fas fa-clock',          color: '#17a2b8' },
    MTTR:              { icon: 'fas fa-tools',          color: '#ffc107' },
    'Disponibilité':   { icon: 'fas fa-check-circle',   color: '#28a745' },
    'Coût maintenance':{ icon: 'fas fa-euro-sign',      color: '#dc3545' },
    'Préventif/curatif':{ icon: 'fas fa-balance-scale', color: '#6f42c1' },
};

function KpiCard({ name, description, value }) {
    const meta = KPI_ICONS[name] ?? { icon: 'fas fa-chart-line', color: '#17a2b8' };
    return (
        <div style={{
            display: 'flex', alignItems: 'stretch',
            borderRadius: 6, overflow: 'hidden',
            boxShadow: '0 1px 4px rgba(0,0,0,0.14)',
            background: '#fff', minHeight: 80,
        }}>
            <div style={{
                width: 72, display: 'flex', alignItems: 'center', justifyContent: 'center',
                background: meta.color,
            }}>
                <i className={meta.icon} style={{ fontSize: 26, color: '#fff' }} />
            </div>
            <div style={{ flex: 1, padding: '0.6rem 0.9rem', overflow: 'hidden' }}>
                <span style={{ fontSize: 11, color: '#6c757d', textTransform: 'uppercase', letterSpacing: 0.4 }}>
                    {name}
                </span>
                <div style={{ fontSize: 22, fontWeight: 700, color: '#343a40', lineHeight: 1.2 }}>
                    {fmt(value)}
                </div>
                <div style={{ fontSize: 12, color: '#6c757d', marginTop: 2, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {description}
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Status / priority badges
// ---------------------------------------------------------------------------

const STATUS_COLORS = {
    draft:       { bg: '#6c757d', label: 'Brouillon' },
    planned:     { bg: '#17a2b8', label: 'Planifié' },
    in_progress: { bg: '#007bff', label: 'En cours' },
    completed:   { bg: '#28a745', label: 'Terminé' },
    closed:      { bg: '#343a40', label: 'Clôturé' },
};

const PRIORITY_COLORS = {
    low:      { bg: '#28a745', label: 'Basse' },
    medium:   { bg: '#ffc107', label: 'Moyenne' },
    high:     { bg: '#fd7e14', label: 'Haute' },
    critical: { bg: '#dc3545', label: 'Critique' },
};

const WORK_TYPE_LABELS = {
    preventive:  'Préventif',
    corrective:  'Curatif',
    improvement: 'Amélioration',
    safety:      'Sécurité',
};

function Badge({ value, map }) {
    const meta = map[value] ?? { bg: '#6c757d', label: value ?? '—' };
    return (
        <span style={{
            display: 'inline-block',
            padding: '2px 8px',
            borderRadius: 12,
            background: meta.bg,
            color: '#fff',
            fontSize: 11,
            fontWeight: 600,
        }}>
            {meta.label}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Status breakdown bar
// ---------------------------------------------------------------------------

function StatusBar({ byStatus }) {
    const total = Object.values(byStatus).reduce((s, n) => s + n, 0);
    if (!total) return null;

    return (
        <div style={{ marginBottom: '1.25rem' }}>
            <div style={{ display: 'flex', height: 18, borderRadius: 4, overflow: 'hidden' }}>
                {Object.entries(STATUS_COLORS).map(([key, { bg, label }]) => {
                    const n = byStatus[key] ?? 0;
                    if (!n) return null;
                    const pct = (n / total * 100).toFixed(1);
                    return (
                        <div key={key} title={`${label}: ${n}`} style={{
                            width: `${pct}%`, background: bg, transition: 'width .3s',
                        }} />
                    );
                })}
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem', marginTop: '0.4rem' }}>
                {Object.entries(STATUS_COLORS).map(([key, { bg, label }]) => {
                    const n = byStatus[key] ?? 0;
                    if (!n) return null;
                    return (
                        <span key={key} style={{ fontSize: 12, color: '#495057', display: 'flex', alignItems: 'center', gap: 4 }}>
                            <span style={{ width: 10, height: 10, borderRadius: 2, background: bg, display: 'inline-block' }} />
                            {label} ({n})
                        </span>
                    );
                })}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Card wrapper
// ---------------------------------------------------------------------------

function Card({ title, icon, color = '#007bff', children }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 6,
            boxShadow: '0 1px 4px rgba(0,0,0,0.14)',
            overflow: 'hidden', marginBottom: '1.5rem',
        }}>
            <div style={{
                padding: '0.65rem 1rem',
                background: color,
                color: '#fff',
                fontWeight: 600,
                fontSize: '0.95rem',
                display: 'flex', alignItems: 'center', gap: 8,
            }}>
                {icon && <i className={icon} />}
                {title}
            </div>
            <div style={{ padding: '1rem' }}>
                {children}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Stat counters row
// ---------------------------------------------------------------------------

function StatRow({ label, value, color }) {
    return (
        <div style={{
            display: 'flex', justifyContent: 'space-between',
            alignItems: 'center',
            padding: '0.45rem 0',
            borderBottom: '1px solid #f0f0f0',
        }}>
            <span style={{ fontSize: 13, color: '#495057' }}>{label}</span>
            <span style={{
                fontWeight: 700, fontSize: 15,
                color: color ?? '#343a40',
            }}>{value}</span>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Recent work orders table
// ---------------------------------------------------------------------------

function RecentWorkOrders({ workOrders, endpoints }) {
    const [expanded, setExpanded] = useState(false);
    const rows = expanded ? workOrders : workOrders.slice(0, 5);

    if (!workOrders.length) {
        return <p style={{ color: '#6c757d', margin: 0, fontSize: 13 }}>Aucun bon de travail.</p>;
    }

    return (
        <>
            <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                    <thead>
                        <tr style={{ background: '#f8f9fa' }}>
                            {['Titre', 'Équipement', 'Type', 'Priorité', 'Statut', 'Date prévue', ''].map(h => (
                                <th key={h} style={{ padding: '6px 10px', textAlign: 'left', fontWeight: 600, color: '#495057', whiteSpace: 'nowrap', borderBottom: '2px solid #dee2e6' }}>
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((wo, i) => (
                            <tr key={wo.id} style={{ background: i % 2 ? '#f8f9fa' : '#fff' }}>
                                <td style={{ padding: '6px 10px', maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {wo.title}
                                </td>
                                <td style={{ padding: '6px 10px', whiteSpace: 'nowrap' }}>
                                    {wo.asset ?? '—'}
                                </td>
                                <td style={{ padding: '6px 10px', whiteSpace: 'nowrap' }}>
                                    {WORK_TYPE_LABELS[wo.work_type] ?? wo.work_type ?? '—'}
                                </td>
                                <td style={{ padding: '6px 10px' }}>
                                    <Badge value={wo.priority} map={PRIORITY_COLORS} />
                                </td>
                                <td style={{ padding: '6px 10px' }}>
                                    <Badge value={wo.status} map={STATUS_COLORS} />
                                </td>
                                <td style={{ padding: '6px 10px', whiteSpace: 'nowrap' }}>
                                    {fmtDate(wo.scheduled_at)}
                                </td>
                                <td style={{ padding: '6px 10px' }}>
                                    {endpoints?.work_order_show && (
                                        <a
                                            href={`${endpoints.work_order_show}/${wo.id}`}
                                            style={{ color: '#17a2b8', textDecoration: 'none', fontWeight: 600, fontSize: 12 }}
                                        >
                                            Voir →
                                        </a>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {workOrders.length > 5 && (
                <button
                    onClick={() => setExpanded(e => !e)}
                    style={{
                        marginTop: '0.6rem',
                        background: 'none', border: 'none',
                        color: '#17a2b8', cursor: 'pointer',
                        fontSize: 13, padding: 0,
                    }}
                >
                    {expanded ? '▲ Réduire' : `▼ Voir les ${workOrders.length} bons`}
                </button>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function GmaoDashboard({
    kpis = [],
    workOrdersCount = {},
    recentWorkOrders = [],
    maintenancePlansCount = 0,
    endpoints = {},
    trans = {},
}) {
    const totalWo = Object.values(workOrdersCount).reduce((s, n) => s + n, 0);
    const openWo  = (workOrdersCount.draft ?? 0) + (workOrdersCount.planned ?? 0) + (workOrdersCount.in_progress ?? 0);

    return (
        <div style={{ fontFamily: 'inherit' }}>

            {/* KPI cards row */}
            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
                gap: '1rem',
                marginBottom: '1.5rem',
            }}>
                {kpis.map((kpi) => (
                    <KpiCard key={kpi.name} {...kpi} />
                ))}
            </div>

            {/* Two-column layout */}
            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
                gap: '1.5rem',
                marginBottom: '1.5rem',
            }}>

                {/* Work orders summary */}
                <Card title="Bons de travail" icon="fas fa-wrench" color="#007bff">
                    <StatusBar byStatus={workOrdersCount} />
                    <StatRow label="Total" value={totalWo} />
                    <StatRow label="Ouverts" value={openWo} color="#007bff" />
                    <StatRow label="En cours" value={workOrdersCount.in_progress ?? 0} color="#007bff" />
                    <StatRow label="Terminés" value={workOrdersCount.completed ?? 0} color="#28a745" />
                    <StatRow label="Clôturés" value={workOrdersCount.closed ?? 0} />
                    {endpoints?.work_orders_index && (
                        <a
                            href={endpoints.work_orders_index}
                            style={{ display: 'inline-block', marginTop: '0.75rem', fontSize: 13, color: '#007bff' }}
                        >
                            Voir tous les bons →
                        </a>
                    )}
                </Card>

                {/* Maintenance plans + quick actions */}
                <Card title="Maintenance préventive" icon="fas fa-calendar-check" color="#28a745">
                    <StatRow label="Plans actifs" value={maintenancePlansCount} color="#28a745" />
                    <div style={{ marginTop: '1rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                        {endpoints?.work_orders_create && (
                            <a
                                href={endpoints.work_orders_create}
                                style={{
                                    display: 'block', padding: '0.45rem 0.75rem',
                                    background: '#007bff', color: '#fff',
                                    borderRadius: 4, textDecoration: 'none',
                                    fontSize: 13, fontWeight: 600, textAlign: 'center',
                                }}
                            >
                                <i className="fas fa-plus" style={{ marginRight: 6 }} />
                                Nouveau bon de travail
                            </a>
                        )}
                        {endpoints?.maintenance_plans_index && (
                            <a
                                href={endpoints.maintenance_plans_index}
                                style={{
                                    display: 'block', padding: '0.45rem 0.75rem',
                                    background: '#28a745', color: '#fff',
                                    borderRadius: 4, textDecoration: 'none',
                                    fontSize: 13, fontWeight: 600, textAlign: 'center',
                                }}
                            >
                                <i className="fas fa-list" style={{ marginRight: 6 }} />
                                Plans de maintenance
                            </a>
                        )}
                    </div>
                </Card>

                {/* KPI reference table */}
                <Card title="Indicateurs de performance" icon="fas fa-chart-bar" color="#17a2b8">
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                        <thead>
                            <tr>
                                <th style={{ padding: '5px 8px', textAlign: 'left', color: '#495057', borderBottom: '2px solid #dee2e6' }}>
                                    KPI
                                </th>
                                <th style={{ padding: '5px 8px', textAlign: 'left', color: '#495057', borderBottom: '2px solid #dee2e6' }}>
                                    Description
                                </th>
                                <th style={{ padding: '5px 8px', textAlign: 'right', color: '#495057', borderBottom: '2px solid #dee2e6' }}>
                                    Valeur
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {kpis.map((kpi, i) => (
                                <tr key={kpi.name} style={{ background: i % 2 ? '#f8f9fa' : '#fff' }}>
                                    <td style={{ padding: '5px 8px', fontWeight: 600 }}>{kpi.name}</td>
                                    <td style={{ padding: '5px 8px', color: '#6c757d' }}>{kpi.description}</td>
                                    <td style={{ padding: '5px 8px', textAlign: 'right', fontWeight: 700 }}>{fmt(kpi.value)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </div>

            {/* Recent work orders */}
            <Card title="Bons de travail récents" icon="fas fa-history" color="#6c757d">
                <RecentWorkOrders workOrders={recentWorkOrders} endpoints={endpoints} />
            </Card>
        </div>
    );
}
