import MoodTrackerWidget   from './widgets/MoodTrackerWidget.jsx';
import QuoteRateWidget     from './widgets/QuoteRateWidget.jsx';
import OrdersMonthlyWidget from './widgets/OrdersMonthlyWidget.jsx';
import DeliveryBoardWidget from './widgets/DeliveryBoardWidget.jsx';
import RecentItemsWidget   from './widgets/RecentItemsWidget.jsx';
import GoalWidget          from './widgets/GoalWidget.jsx';
import KpiStatsWidget      from './widgets/KpiStatsWidget.jsx';
import MonthlyStatsWidget  from './widgets/MonthlyStatsWidget.jsx';
import AnnouncementWidget  from './widgets/AnnouncementWidget.jsx';
import TopClientsWidget    from './widgets/TopClientsWidget.jsx';
import NcStatsWidget       from './widgets/NcStatsWidget.jsx';
import SupplierDelayWidget from './widgets/SupplierDelayWidget.jsx';
import OtdWidget           from './widgets/OtdWidget.jsx';
import { dashboardTexts as L } from './texts.js';

const REGISTRY = [
    {
        id: 'kpi_stats',
        get label() { return L.widget_kpi_stats ?? 'KPI principaux'; },
        icon: 'fa-tachometer-alt',
        defaultW: 12, defaultH: 2,
        minW: 6,      minH: 2,
        component: KpiStatsWidget,
        getProps: (p) => ({
            kpi:         p.kpi,
            canPurchases: p.canPurchases,
            trans:       p.trans,
            urls:        p.urls,
        }),
    },
    {
        id: 'monthly_stats',
        get label() { return L.widget_monthly_stats ?? 'Suivi mensuel'; },
        icon: 'fa-calendar-alt',
        defaultW: 12, defaultH: 2,
        minW: 6,      minH: 2,
        component: MonthlyStatsWidget,
        getProps: (p) => ({
            kpi:   p.kpi,
            trans: p.trans,
            urls:  p.urls,
        }),
    },
    {
        id: 'orders_monthly',
        get label() { return L.widget_orders_monthly ?? 'Graphique mensuel'; },
        icon: 'fa-chart-line',
        defaultW: 8, defaultH: 5,
        minW: 4,     minH: 3,
        // Le graphique occupe la hauteur de la case : il la garde sur téléphone
        fillsHeight: true,
        component: OrdersMonthlyWidget,
        getProps: (p) => ({
            data:                 p.charts,
            trans:                p.trans,
            currency:             p.currency,
            locale:               p.locale,
            year:                 p.year,
            fiscalYearStartMonth: p.fiscalYearStartMonth ?? 1,
            showTotals:           true,
        }),
    },
    {
        id: 'delivery_board',
        get label() { return L.widget_delivery_board ?? 'Planning livraisons'; },
        icon: 'fa-truck',
        defaultW: 12, defaultH: 4,
        minW: 6,      minH: 3,
        component: DeliveryBoardWidget,
        getProps: (p) => ({
            data:  p.delivery,
            urls:  { orders: p.urls.orders_show, ordersAll: p.urls.orders_index, calendar: p.urls.calendar },
            trans: p.trans,
        }),
    },
    {
        id: 'recent_orders',
        get label() { return L.widget_recent_orders ?? 'Dernières commandes'; },
        icon: 'fa-shopping-cart',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: RecentItemsWidget,
        getProps: (p) => ({
            mode:  'orders',
            data:  p.recentOrders,
            trans: p.trans,
            urls:  { show: p.urls.orders_show, company: p.urls.companies_show, all: p.urls.orders_index },
        }),
    },
    {
        id: 'recent_quotes',
        get label() { return L.widget_recent_quotes ?? 'Derniers devis'; },
        icon: 'fa-calculator',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: RecentItemsWidget,
        getProps: (p) => ({
            mode:  'quotes',
            data:  p.recentQuotes,
            trans: p.trans,
            urls:  { show: p.urls.quotes_show, company: p.urls.companies_show, all: p.urls.quotes_index },
        }),
    },
    {
        id: 'quote_rate',
        get label() { return L.widget_quote_rate ?? 'Taux de transformation'; },
        icon: 'fa-percent',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: QuoteRateWidget,
        getProps: (p) => ({
            data:  p.charts.quotesDataRate,
            trans: p.trans,
        }),
    },
    {
        id: 'goal',
        get label() { return L.widget_goal ?? 'Objectif annuel'; },
        icon: 'fa-bullseye',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: GoalWidget,
        getProps: (p) => ({
            invoiced: p.kpi.invoiced_raw,
            target:   p.kpi.target_raw,
            currency: p.currency,
            locale:   p.locale,
            trans:    p.trans,
        }),
    },
    {
        id: 'recent_invoices',
        get label() { return L.widget_recent_invoices ?? 'Dernières factures'; },
        icon: 'fa-file-invoice',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: RecentItemsWidget,
        getProps: (p) => ({
            mode:     'invoices',
            endpoint: p.endpoints?.recent_invoices,
            trans:    p.trans,
            urls:     { show: p.urls.invoices_show, company: p.urls.companies_show, all: p.urls.invoices },
        }),
    },
    {
        id: 'recent_deliveries',
        get label() { return L.widget_recent_deliveries ?? 'Derniers bons de livraison'; },
        icon: 'fa-truck',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: RecentItemsWidget,
        getProps: (p) => ({
            mode:     'deliveries',
            endpoint: p.endpoints?.recent_deliveries,
            trans:    p.trans,
            urls:     { show: p.urls.deliveries_show, company: p.urls.companies_show, all: p.urls.deliveries },
        }),
    },
    {
        id: 'recent_purchases',
        get label() { return L.widget_pending_purchases ?? 'Commandes achat en attente'; },
        icon: 'fa-box',
        permission: 'purchases',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: RecentItemsWidget,
        getProps: (p) => ({
            mode:     'purchases',
            endpoint: p.endpoints?.recent_purchases,
            trans:    p.trans,
            urls:     { show: p.urls.purchases_show, company: p.urls.companies_show, all: p.urls.purchases },
        }),
    },
    {
        id: 'top_clients',
        get label() { return L.widget_top_clients ?? 'Top clients (CA)'; },
        icon: 'fa-trophy',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: TopClientsWidget,
        getProps: (p) => ({
            endpoint: p.endpoints?.top_clients,
            currency: p.currency,
            locale:   p.locale,
            trans:    p.trans,
            urls:     { companies_show: p.urls.companies_show },
        }),
    },
    {
        id: 'nc_stats',
        get label() { return L.widget_nc_stats ?? 'Qualité — NC ouvertes'; },
        icon: 'fa-exclamation-triangle',
        defaultW: 4, defaultH: 4,
        minW: 3,     minH: 3,
        component: NcStatsWidget,
        getProps: (p) => ({
            endpoint: p.endpoints?.nc_stats,
            trans:    p.trans,
            urls:     { quality: p.urls.quality },
        }),
    },
    {
        id: 'supplier_delays',
        get label() { return L.widget_supplier_delay ?? 'Délai moyen fournisseurs'; },
        icon: 'fa-shipping-fast',
        permission: 'purchases',
        defaultW: 4, defaultH: 5,
        minW: 3,     minH: 3,
        component: SupplierDelayWidget,
        getProps: (p) => ({
            endpoint: p.endpoints?.supplier_delays,
            trans:    p.trans,
        }),
    },
    {
        id: 'otd',
        get label() { return L.widget_otd ?? 'Taux de service (OTD)'; },
        icon: 'fa-clock',
        defaultW: 4, defaultH: 4,
        minW: 3,     minH: 3,
        component: OtdWidget,
        getProps: (p) => ({
            endpoint: p.endpoints?.otd,
            trans:    p.trans,
        }),
    },
    {
        id: 'announcement',
        get label() { return L.widget_announcement ?? 'Annonces'; },
        icon: 'fa-envelope',
        defaultW: 4, defaultH: 3,
        minW: 3,     minH: 2,
        component: AnnouncementWidget,
        getProps: (p) => ({
            announcement: p.announcement,
            trans:        p.trans,
        }),
    },
    {
        id: 'mood_tracker',
        get label() { return L.widget_mood ?? 'Niko Niko'; },
        icon: 'fa-smile',
        defaultW: 4, defaultH: 4,
        minW: 3,     minH: 3,
        component: MoodTrackerWidget,
        getProps: (p) => ({
            endpoint: p.endpoints?.mood,
            trans:    p.trans,
        }),
    },
];

export const WIDGET_MAP = Object.fromEntries(REGISTRY.map(w => [w.id, w]));

export const DEFAULT_LAYOUT = [
    { i: 'kpi_stats',      x: 0, y: 0,  w: 12, h: 2 },
    { i: 'delivery_board', x: 0, y: 2,  w: 12, h: 4 },
    { i: 'monthly_stats',  x: 0, y: 6,  w: 12, h: 2 },
    { i: 'recent_orders',  x: 0, y: 8,  w: 4,  h: 5 },
    { i: 'orders_monthly', x: 4, y: 8,  w: 8,  h: 5 },
    { i: 'goal',           x: 0, y: 13, w: 4,  h: 5 },
    { i: 'quote_rate',     x: 4, y: 13, w: 4,  h: 5 },
    { i: 'recent_quotes',  x: 8, y: 13, w: 4,  h: 5 },
    { i: 'announcement',   x: 0, y: 18, w: 12, h: 3 },
];

export default REGISTRY;
