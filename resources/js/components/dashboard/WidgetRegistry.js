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

const REGISTRY = [
    {
        id: 'kpi_stats',
        label: 'KPI principaux',
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
        label: 'Suivi mensuel',
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
        label: 'Graphique mensuel',
        icon: 'fa-chart-line',
        defaultW: 8, defaultH: 5,
        minW: 4,     minH: 3,
        component: OrdersMonthlyWidget,
        getProps: (p) => ({
            data:       p.charts,
            trans:      p.trans,
            currency:   p.currency,
            locale:     p.locale,
            year:       p.year,
            showTotals: true,
        }),
    },
    {
        id: 'delivery_board',
        label: 'Planning livraisons',
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
        label: 'Dernières commandes',
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
        label: 'Derniers devis',
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
        label: 'Taux de transformation',
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
        label: 'Objectif annuel',
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
        label: 'Dernières factures',
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
        label: 'Derniers bons de livraison',
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
        label: 'Commandes achat en attente',
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
        label: 'Top clients (CA)',
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
        label: 'Qualité — NC ouvertes',
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
        label: 'Délai moyen fournisseurs',
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
        label: 'Taux de service (OTD)',
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
        label: 'Annonces',
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
        label: 'Niko Niko',
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
