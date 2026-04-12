import React, { useState, useEffect } from 'react';
import DashboardGrid from './dashboard/DashboardGrid.jsx';
import TodayView from './TodayView.jsx';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

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
            {/* Toggle KPI / Vue du jour */}
            <div className="d-flex justify-content-end mb-3">
                <div className="btn-group btn-group-sm" role="group">
                    <button
                        type="button"
                        className={`btn ${activeView === 'today' ? 'btn-primary' : 'btn-outline-secondary'}`}
                        onClick={() => switchView('today')}
                    >
                        <i className="fas fa-sun mr-1" />Vue du jour
                    </button>
                    <button
                        type="button"
                        className={`btn ${activeView === 'kpi' ? 'btn-primary' : 'btn-outline-secondary'}`}
                        onClick={() => switchView('kpi')}
                    >
                        <i className="fas fa-chart-bar mr-1" />Dashboard KPI
                    </button>
                </div>
            </div>

            {/* Contenu */}
            {activeView === 'today' ? (
                <TodayView endpoints={endpoints} />
            ) : (
                <DashboardGrid
                    dashProps={dashProps}
                    configEndpoint={endpoints?.dashboard_config ?? '/dashboard/config'}
                />
            )}
        </div>
    );
}
