import React, { useState, useEffect } from 'react';
import DashboardGrid from './dashboard/DashboardGrid.jsx';
import TodayView from './TodayView.jsx';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const sideBtn = {
    base: {
        writingMode: 'vertical-rl',
        transform: 'rotate(180deg)',
        padding: '14px 8px',
        border: 'none',
        borderRadius: '6px 0 0 6px',
        cursor: 'pointer',
        fontSize: '0.78rem',
        fontWeight: 600,
        letterSpacing: '0.04em',
        display: 'flex',
        alignItems: 'center',
        gap: 6,
        whiteSpace: 'nowrap',
        boxShadow: '-2px 2px 6px rgba(0,0,0,0.15)',
        transition: 'opacity 0.15s',
    },
    today:       { background: '#17a2b8', color: '#fff' },
    todayActive: { background: '#138496', color: '#fff' },
    kpi:         { background: '#007bff', color: '#fff' },
    kpiActive:   { background: '#0056b3', color: '#fff' },
    customize:   { background: '#6c757d', color: '#fff' },
    customizeOn: { background: '#28a745', color: '#fff' },
};

export default function HomeDashboard({
    year,
    currency,
    locale,
    canPurchases,
    kpi,
    charts,
    delivery,
    recentOrders,
    recentQuotes,
    announcement,
    trans,
    urls,
    endpoints,
}) {
    const [activeView, setActiveView] = useState(null); // null = loading
    const [editMode,   setEditMode]   = useState(false);

    // Charge la préférence sauvegardée
    useEffect(() => {
        if (!endpoints?.today_config) { setActiveView('kpi'); return; }
        fetch(endpoints.today_config, {
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => setActiveView(data?.home_view ?? 'kpi'))
            .catch(() => setActiveView('kpi'));
    }, []);

    function switchView(view) {
        setActiveView(view);
        setEditMode(false);
        if (!endpoints?.today_config_update) return;
        fetch(endpoints.today_config_update, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ home_view: view }),
        }).catch(() => {});
    }

    const dashProps = {
        year, currency, locale, canPurchases,
        kpi, charts, delivery, recentOrders, recentQuotes,
        announcement, trans: trans ?? {}, urls: urls ?? {}, endpoints,
    };

    if (activeView === null) {
        return (
            <div className="text-center py-5 text-muted">
                <i className="fas fa-spinner fa-spin mr-2" />Chargement…
            </div>
        );
    }

    return (
        <div>
            {/* Boutons latéraux fixes à droite */}
            <div style={{
                position: 'fixed',
                right: 0,
                top: '50%',
                transform: 'translateY(-50%)',
                display: 'flex',
                flexDirection: 'column',
                gap: 6,
                zIndex: 1050,
            }}>
                <button
                    style={{ ...sideBtn.base, ...(activeView === 'today' ? sideBtn.todayActive : sideBtn.today) }}
                    onClick={() => switchView('today')}
                    title="Vue du jour"
                >
                    <i className="fas fa-sun" />
                    Vue du jour
                </button>

                <button
                    style={{ ...sideBtn.base, ...(activeView === 'kpi' ? sideBtn.kpiActive : sideBtn.kpi) }}
                    onClick={() => switchView('kpi')}
                    title="Dashboard KPI"
                >
                    <i className="fas fa-chart-bar" />
                    Dashboard KPI
                </button>

                {activeView === 'kpi' && (
                    <button
                        style={{ ...sideBtn.base, ...(editMode ? sideBtn.customizeOn : sideBtn.customize) }}
                        onClick={() => setEditMode(e => !e)}
                        title={editMode ? 'Terminer la personnalisation' : 'Personnaliser'}
                    >
                        <i className={`fas ${editMode ? 'fa-check' : 'fa-edit'}`} />
                        {editMode ? 'Terminer' : 'Personnaliser'}
                    </button>
                )}
            </div>

            {/* Contenu */}
            {activeView === 'today' ? (
                <TodayView endpoints={endpoints} />
            ) : (
                <DashboardGrid
                    dashProps={dashProps}
                    configEndpoint={endpoints?.dashboard_config ?? '/dashboard/config'}
                    editMode={editMode}
                    onEditModeChange={setEditMode}
                />
            )}
        </div>
    );
}
