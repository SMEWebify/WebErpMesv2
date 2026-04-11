import React from 'react';
import DonutChart from './dashboard/primitives/DonutChart.jsx';

/**
 * QuoteChartsTab
 *
 * Props:
 *   productTime  object  { label: [label, value, color], ... }
 *   settingTime  object
 *   cost         object
 *   price        object
 *   currency     string  ex: '€'
 *   trans        object  { productTime, settingTime, cost, price }
 */

function toDonutData(raw) {
    return Object.values(raw ?? {}).map(item => ({
        label: item[0],
        value: Math.round((parseFloat(item[1]) || 0) * 100) / 100,
        color: item[2] ?? '#6c757d',
    }));
}

function ChartCard({ title, data, unit }) {
    const items = toDonutData(data);

    return (
        <div className="col-md-6 mb-4">
            <div className="card card-secondary">
                <div className="card-header">
                    <h3 className="card-title">{title}</h3>
                </div>
                <div className="card-body">
                    <DonutChart
                        data={items}
                        centerLabel={unit}
                        size={240}
                        showLegend={true}
                    />
                </div>
            </div>
        </div>
    );
}

export default function QuoteChartsTab({ productTime, settingTime, cost, price, currency, trans }) {
    return (
        <div className="row">
            <ChartCard
                title={trans.productTime ?? 'Temps prod. par service'}
                data={productTime}
                unit="h"
            />
            <ChartCard
                title={trans.settingTime ?? 'Temps réglage par service'}
                data={settingTime}
                unit="h"
            />
            <ChartCard
                title={trans.cost ?? 'Coût par service'}
                data={cost}
                unit={currency}
            />
            <ChartCard
                title={trans.price ?? 'Prix par service'}
                data={price}
                unit={currency}
            />
        </div>
    );
}
