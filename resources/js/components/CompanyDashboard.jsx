import React from 'react';
import StatCard from './dashboard/primitives/StatCard.jsx';
import DonutChart from './dashboard/primitives/DonutChart.jsx';
import OrdersMonthlyWidget from './dashboard/widgets/OrdersMonthlyWidget.jsx';
import { creditTheme } from './receivables/CreditBadge.jsx';
import { formatCurrency } from '../utils';

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
            <div className="card card-outline card-primary mb-3" style={{ minHeight: '88px' }}>
                <div className="card-body d-flex flex-column justify-content-center">
                    <p className="card-text mb-1">{trans.bills_paid} : <strong>{paid}</strong></p>
                    <p className="card-text mb-0">{trans.bills_unpaid} : <strong>{unpaid}</strong></p>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Encours client / plafond
// ---------------------------------------------------------------------------

function CreditCard({ credit, overrides = [], trans, currency, locale }) {
    if (!credit) return null;
    const t     = trans ?? {};
    const money = v => formatCurrency(v ?? 0, currency, locale);
    const theme = creditTheme(credit.ratio);
    const pct   = credit.ratio === null ? null : Math.round(credit.ratio * 100);

    return (
        <div className="col-12 mb-3">
            <div className={`card card-outline card-${credit.ratio === null ? 'secondary' : theme} mb-0`}>
                <div className="card-header">
                    <h3 className="card-title">
                        <i className="fas fa-hand-holding-usd me-1" />
                        {t.card_title}
                    </h3>
                </div>
                <div className="card-body">
                    <div className="row align-items-center">
                        <div className="col-md-3">
                            <div className="small text-body-secondary">{t.outstanding}</div>
                            <div className="fs-4 fw-bold">{money(credit.total)}</div>
                        </div>
                        <div className="col-md-3 small">
                            <div>{t.invoices_due} : <strong>{money(credit.invoices_due)}</strong></div>
                            <div>{t.open_orders} : <strong>{money(credit.open_orders)}</strong></div>
                        </div>
                        <div className="col-md-6">
                            {credit.limit === null ? (
                                <span className="text-body-secondary">{t.no_limit}</span>
                            ) : (
                                <>
                                    <div className="d-flex justify-content-between small mb-1">
                                        <span>{t.limit} : <strong>{money(credit.limit)}</strong></span>
                                        <span className={`text-${theme} fw-bold`}>
                                            {credit.level === 'block' && <i className="fas fa-lock me-1" />}
                                            {pct} % {t.of_limit}
                                        </span>
                                    </div>
                                    <div className="progress" role="progressbar" aria-valuenow={pct} aria-valuemin="0" aria-valuemax="100">
                                        <div className={`progress-bar bg-${theme}`} style={{ width: `${Math.min(pct, 100)}%` }} />
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                    {overrides.length > 0 && (
                        <div className="mt-3">
                            <div className="small fw-bold mb-1">{t.overrides_title}</div>
                            <ul className="list-unstyled small mb-0">
                                {overrides.map((o, i) => (
                                    <li key={i} className="border-top py-1">
                                        <span className="text-body-secondary">{o.date} {t.override_by} {o.user ?? '—'}</span>
                                        {' — '}{money(o.projected)} / {money(o.limit)}{' — '}
                                        <em>{o.reason}</em>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
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
    5: { color: 'var(--bs-secondary-color)', key: 'closed' },
    6: { color: '#007bff', key: 'obsolete' },
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyDashboard({ kpi, charts, trans, fiscalYearStartMonth = 1 }) {
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
                <CreditCard credit={kpi.credit} overrides={kpi.creditOverrides} trans={trans.credit}
                            currency={trans.currency} locale={trans.locale} />
            </div>

            {/* Charts */}
            <div className="row mt-2">
                <div className="col-lg-3 col-md-12 mb-3">
                    <div className="card card-outline card-teal mb-3">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar text-teal me-1" />
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
                        fiscalYearStartMonth={charts.fiscalYearStartMonth ?? fiscalYearStartMonth}
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
