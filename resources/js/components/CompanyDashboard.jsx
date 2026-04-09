import React from 'react';
import StatCard from './dashboard/primitives/StatCard.jsx';
import DonutChart from './dashboard/primitives/DonutChart.jsx';
import OrdersMonthlyWidget from './dashboard/widgets/OrdersMonthlyWidget.jsx';

// ---------------------------------------------------------------------------
// Small Box wrapper (col + StatCard)
// ---------------------------------------------------------------------------

function SmallBox({ value, label, icon, theme }) {
    return (
        <div className="col-lg-3 col-md-4 col-sm-6 mb-3">
            <StatCard value={value} label={label} icon={icon} theme={theme} />
        </div>
    );
}

function InvoicesCard({ paid, unpaid, trans }) {
    return (
        <div className="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div className="card card-outline card-primary" style={{ minHeight: '88px' }}>
                <div className="card-body d-flex flex-column justify-content-center">
                    <p className="card-text mb-1">{trans.bills_paid} : <strong>{paid}</strong></p>
                    <p className="card-text mb-0">{trans.bills_unpaid} : <strong>{unpaid}</strong></p>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Config statuts devis → DonutChart
// ---------------------------------------------------------------------------

const QUOTE_STATUS_CONFIG = {
    1: { color: '#17a2b8', key: 'open' },
    2: { color: '#ffc107', key: 'send' },
    3: { color: '#28a745', key: 'win' },
    4: { color: '#dc3545', key: 'lost' },
    5: { color: '#6c757d', key: 'closed' },
    6: { color: '#007bff', key: 'obsolete' },
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyDashboard({ kpi, charts, trans }) {
    return (
        <div>
            {/* KPIs */}
            <div className="row">
                <SmallBox value={kpi.orderAverage}           label={trans.order_average}            icon="fa-shopping-cart" theme="orange" />
                <SmallBox value={kpi.remainingInvoiceOrder}  label={trans.remaining_invoice}        icon="fa-file-invoice"  theme="info" />
                <SmallBox value={kpi.pendingOrdersCount}     label={trans.pending_orders}           icon="fa-box-open"      theme="warning" />
                <SmallBox value={kpi.customerProcessingCost} label={trans.customer_processing_cost} icon="fa-cogs"          theme="teal" />
                <SmallBox value={`${kpi.serviceRate ?? 0}%`} label={trans.service_rate}             icon="fa-chart-line"    theme="primary" />
                <InvoicesCard paid={kpi.paidInvoices} unpaid={kpi.unpaidInvoices} trans={trans} />
                <SmallBox value="Since" label={kpi.since}                                           icon="fa-calendar-alt"  theme="warning" />
            </div>

            {/* Charts */}
            <div className="row mt-2">
                <div className="col-lg-3 col-md-12 mb-3">
                    <div className="card card-outline card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar text-teal mr-1" />
                                {trans.quote_transformation}
                            </h3>
                        </div>
                        <div className="card-body">
                            <DonutChart
                                data={(charts.quotesDataRate ?? []).map(row => ({
                                    label: trans[QUOTE_STATUS_CONFIG[row.statu]?.key] ?? `Status ${row.statu}`,
                                    color: QUOTE_STATUS_CONFIG[row.statu]?.color ?? '#adb5bd',
                                    value: parseInt(row.QuoteCountRate, 10) || 0,
                                }))}
                                centerLabel={trans.total ?? 'Total'}
                            />
                        </div>
                    </div>
                </div>

                <div className="col-lg-9 col-md-12 mb-3">
                    <OrdersMonthlyWidget
                        data={{
                            orderMonthlyRecap:    charts.orderMonthlyRecap,
                            deliveryMonthlyRecap: charts.deliveryMonthlyRecap,
                            invoiceMonthlyRecap:  charts.invoiceMonthlyRecap,
                            purchaseMonthlyRecap: charts.purchaseMonthlyRecap,
                            estimatedBudget:      charts.estimatedBudget,
                        }}
                        trans={{
                            ...trans,
                            monthly_recap_title: trans.monthly_recap,
                        }}
                        currency={trans.currency ?? 'EUR'}
                        locale={trans.locale   ?? 'fr-FR'}
                        height={280}
                        showTotals={false}
                    />
                </div>
            </div>
        </div>
    );
}
