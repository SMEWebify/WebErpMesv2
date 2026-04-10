import React from 'react';
import StatCard from '../primitives/StatCard.jsx';

export default function KpiStatsWidget({ kpi, canPurchases, trans = {}, urls = {} }) {
    const u = urls;
    const t = trans;

    return (
        <div className="row h-100" style={{ margin: 0 }}>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 0, paddingRight: 6 }}>
                <StatCard
                    value={kpi.customers_count}
                    label={`${t.new_client} — ${kpi.latest_customer_since}`}
                    icon="fa-building"
                    theme="info"
                    url={u.orders_index}
                    urlText={t.view_details}
                />
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 6 }}>
                {canPurchases ? (
                    <StatCard
                        value={kpi.suppliers_count}
                        label={t.suppliers}
                        icon="fa-building"
                        theme="success"
                        url={u.orders_index}
                        urlText={t.view_details}
                    />
                ) : (
                    <StatCard
                        value={kpi.delivery_notes_count}
                        label={`${t.delivery_notes} — ${t.current_month}`}
                        icon="fa-truck"
                        theme="success"
                        url={u.deliveries}
                        urlText={t.view_details}
                    />
                )}
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 6 }}>
                <StatCard
                    value={kpi.quotes_count}
                    label={t.quotes_open}
                    icon="fa-calculator"
                    theme="primary"
                    url={u.quotes_index}
                    urlText={t.view_details}
                />
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 0 }}>
                <StatCard
                    value={kpi.orders_count}
                    label={t.orders_open}
                    icon="fa-shopping-cart"
                    theme="yellow"
                    url={u.orders_index}
                    urlText={t.view_details}
                />
            </div>
        </div>
    );
}
