import React from 'react';
import StatCard from '../primitives/StatCard.jsx';

export default function MonthlyStatsWidget({ kpi, trans = {}, urls = {} }) {
    const u = urls;
    const t = trans;

    return (
        <div className="row h-100" style={{ margin: 0 }}>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 0, paddingRight: 6 }}>
                <StatCard
                    value={kpi.delivered_month}
                    label={t.delivered_month}
                    icon="fa-info"
                    theme="yellow"
                    url={u.orders_index}
                    urlText={t.view_details}
                />
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 6 }}>
                <StatCard
                    value={kpi.remaining_delivery}
                    label={t.remaining_delivery}
                    icon="fa-info"
                    theme="danger"
                    url={u.orders_index}
                    urlText={t.view_details}
                />
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 6 }}>
                <StatCard
                    value={kpi.remaining_invoice}
                    label={t.remaining_invoice}
                    icon="fa-file-invoice-dollar"
                    theme="info"
                    url={u.invoices}
                    urlText={t.view_details}
                />
            </div>
            <div className="col-6 col-xl-3 mb-0" style={{ paddingLeft: 6, paddingRight: 0 }}>
                <StatCard
                    value={kpi.forecast_3m}
                    label={t.forecast_3m}
                    icon="fa-chart-line"
                    theme="teal"
                    url={u.orders_index}
                    urlText={t.view_details}
                />
            </div>
        </div>
    );
}
