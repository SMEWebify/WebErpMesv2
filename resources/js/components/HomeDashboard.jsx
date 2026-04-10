import React from 'react';
import DashboardGrid from './dashboard/DashboardGrid.jsx';

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
    const dashProps = {
        year, currency, locale, canPurchases,
        kpi, charts, delivery, recentOrders, recentQuotes,
        announcement, trans: trans ?? {}, urls: urls ?? {}, endpoints,
    };

    return (
        <DashboardGrid
            dashProps={dashProps}
            configEndpoint={endpoints?.dashboard_config ?? '/dashboard/config'}
        />
    );
}
