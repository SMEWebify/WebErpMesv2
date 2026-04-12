import './bootstrap';

import { createApp, h } from 'vue';
import Whiteboard from './components/Whiteboard.vue';
import { createRoot } from 'react-dom/client';
import React from 'react';
import KanbanBoard from './components/KanbanBoard.jsx';
import DocumentTable from './components/DocumentTable.jsx';
import NestingPage from './components/NestingPage.jsx';
import QuotesIndex from './components/QuotesIndex.jsx';
import OpportunitiesIndex from './components/OpportunitiesIndex.jsx';
import LeadsIndex from './components/LeadsIndex.jsx';
import CompaniesIndex from './components/CompaniesIndex.jsx';
import OrdersIndex from './components/OrdersIndex.jsx';
import PurchasesIndex from './components/PurchasesIndex.jsx';
import QualityIndex from './components/QualityIndex.jsx';
import DeliverysIndex from './components/DeliverysIndex.jsx';
import CreditNotesIndex from './components/CreditNotesIndex.jsx';
import DeliverysRequest from './components/DeliverysRequest.jsx';
import InvoicesRequest from './components/InvoicesRequest.jsx';
import PurchasesRequest from './components/PurchasesRequest.jsx';
import PurchasesQuotationIndex from './components/PurchasesQuotationIndex.jsx';
import PurchasesQuotationShow from './components/PurchasesQuotationShow.jsx';
import InvoicesIndex from './components/InvoicesIndex.jsx';
import ProductsIndex from './components/ProductsIndex.jsx';
import QuoteLinesIndex from './components/QuoteLinesIndex.jsx';
import OrderLinesIndex from './components/OrderLinesIndex.jsx';
import SetupWizard from './components/SetupWizard.jsx';
import CompanyDashboard from './components/CompanyDashboard.jsx';
import CompanyForm from './components/CompanyForm.jsx';
import CompanyAddresses from './components/CompanyAddresses.jsx';
import CompanyContacts from './components/CompanyContacts.jsx';
import LoadPlanningIndex from './components/LoadPlanningIndex.jsx';
import QuoteLinesPage from './components/QuoteLinesPage.jsx';
import OrderLinesPage from './components/OrderLinesPage.jsx';
import ConstructionSitePage from './components/ConstructionSitePage.jsx';
import HomeDashboard from './components/HomeDashboard.jsx';
import PurchaseReceiptIndex from './components/PurchaseReceiptIndex.jsx';
import PurchaseInvoicesIndex from './components/PurchaseInvoicesIndex.jsx';
import TasksIndex from './components/TasksIndex.jsx';
import TaskStatuApp from './components/TaskStatuApp.jsx';
import SerialNumbersIndex from './components/SerialNumbersIndex.jsx';
import MethodsOverview from './components/MethodsOverview.jsx';
import GanttChart from './components/GanttChart.jsx';
import NonConformitiesIndex from './components/NonConformitiesIndex.jsx';
import GmaoDashboard from './components/GmaoDashboard.jsx';
import InspectionProjectsApp from './components/InspectionProjectsApp.jsx';
import ProcessDiagramApp from './components/ProcessDiagramApp.jsx';
import TaskManagePage from './components/TaskManagePage.jsx';
import QuoteChartsTab from './components/QuoteChartsTab.jsx';
import UserProfilePage from './components/UserProfilePage.jsx';
import NotificationLinePage from './components/NotificationLinePage.jsx';
import UserAutoEmailReportsPage from './components/UserAutoEmailReportsPage.jsx';
import AuditPlannerApp from './components/AuditPlannerApp.jsx';
// livewire-sortable ne doit être chargé que sur les pages qui embarquent Livewire
document.addEventListener('livewire:init', () => {
    import('livewire-sortable');
});

function mountKanbanBoard() {
    const element = document.getElementById('card');
    if (!element) return;

    const initialData = element.dataset.initialData
        ? JSON.parse(element.dataset.initialData)
        : [];

    createRoot(element).render(
        React.createElement(KanbanBoard, { initialData })
    );
}

function mountDocumentTable() {
    const element = document.getElementById('document-table-app');
    if (!element) return;

    const initialDocuments = element.dataset.documents
        ? JSON.parse(element.dataset.documents)
        : [];
    const translations = element.dataset.translations
        ? JSON.parse(element.dataset.translations)
        : {};

    createRoot(element).render(
        React.createElement(DocumentTable, { initialDocuments, translations })
    );
}

function parseJsonAttribute(value) {
    if (!value) return null;
    try {
        return JSON.parse(value);
    } catch (error) {
        console.warn('Unable to parse JSON attribute', value, error);
        return null;
    }
}

function mountWhiteboard() {
    const element = document.getElementById('whiteboard-app');
    if (!element) return;

    const props = {};

    if (element.hasAttribute('data-initial-whiteboard-id')) {
        const id = element.getAttribute('data-initial-whiteboard-id');
        if (id !== null && id !== '' && id !== 'null' && id !== 'undefined') {
            const numericId = Number(id);
            if (!Number.isNaN(numericId)) {
                props.initialWhiteboardId = numericId;
            }
        }
    }

    if (element.dataset.initialWhiteboard) {
        props.initialWhiteboard = parseJsonAttribute(element.dataset.initialWhiteboard);
    }
    if (element.dataset.initialSnapshots) {
        props.initialSnapshots = parseJsonAttribute(element.dataset.initialSnapshots) || [];
    }
    if (element.dataset.initialFiles) {
        props.initialFiles = parseJsonAttribute(element.dataset.initialFiles) || [];
    }
    if (element.dataset.endpoints) {
        props.endpoints = parseJsonAttribute(element.dataset.endpoints) || {};
    }

    if (element.children.length === 0) {
        createApp(Whiteboard, props).mount(element);
        return;
    }

    const whiteboardApp = createApp({
        render() {
            return h('whiteboard', props);
        },
    });
    whiteboardApp.component('whiteboard', Whiteboard);
    whiteboardApp.mount(element);
}

function mountNestingPage() {
    const element = document.getElementById('nesting-app');
    if (!element) return;

    createRoot(element).render(React.createElement(NestingPage));
}

function mountQuotesIndex() {
    const element = document.getElementById('quotes-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QuotesIndex, {
            kpi:           parse('kpi')          ?? {},
            chartData:     parse('chart')        ?? {},
            topCustomers:  parse('topCustomers') ?? [],
            quotesByUser:  parse('quotesByUser') ?? {},
            endpoints:     parse('endpoints')    ?? {},
            trans:         parse('trans')        ?? {},
            companieId:    parse('companieId')   ?? null,
        })
    );
}

function mountLeadsIndex() {
    const element = document.getElementById('leads-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(LeadsIndex, {
            kpi:        parse('kpi')        ?? {},
            chart:      parse('chart')      ?? [],
            byCompany:  parse('byCompany')  ?? [],
            byPriority: parse('byPriority') ?? [],
            byUser:     parse('byUser')     ?? [],
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: parse('companieId') ?? null,
        })
    );
}

function mountOpportunitiesIndex() {
    const element = document.getElementById('opportunities-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(OpportunitiesIndex, {
            kpi:        parse('kpi')        ?? {},
            chart:      parse('chart')      ?? [],
            activities: parse('activities') ?? [],
            byCompany:  parse('byCompany')  ?? [],
            byAmount:   parse('byAmount')   ?? [],
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: parse('companieId') ?? null,
        })
    );
}

function mountOrdersIndex() {
    const element = document.getElementById('orders-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(OrdersIndex, {
            kpi:          parse('kpi')          ?? {},
            chartData:    parse('chart')        ?? {},
            topCustomers: parse('topCustomers') ?? [],
            endpoints:    parse('endpoints')    ?? {},
            trans:        parse('trans')        ?? {},
            companieId:   parse('companieId')   ?? null,
        })
    );
}

function mountPurchasesIndex() {
    const element = document.getElementById('purchases-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesIndex, {
            kpi:                   parse('kpi')                   ?? {},
            chartData:             parse('chart')                 ?? {},
            topSuppliers:          parse('topSuppliers')          ?? [],
            fastestSuppliers:      parse('fastestSuppliers')      ?? [],
            slowestSuppliers:      parse('slowestSuppliers')      ?? [],
            compositeIndicators:   parse('compositeIndicators')   ?? [],
            suppliersToRequalify:  parse('suppliersToRequalify')  ?? [],
            topProducts:           parse('topProducts')           ?? [],
            endpoints:             parse('endpoints')             ?? {},
            trans:                 parse('trans')                 ?? {},
            companieId:            parse('companieId')            ?? null,
        })
    );
}

function mountPurchaseInvoicesIndex() {
    const element = document.getElementById('purchase-invoices-index-app');
    if (!element) return;
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };
    createRoot(element).render(
        React.createElement(PurchaseInvoicesIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountQuoteLinesIndex() {
    const element = document.getElementById('quote-lines-index-app');
    if (!element) return;
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };
    createRoot(element).render(
        React.createElement(QuoteLinesIndex, {
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountOrderLinesIndex() {
    const element = document.getElementById('order-lines-index-app');
    if (!element) return;
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };
    createRoot(element).render(
        React.createElement(OrderLinesIndex, {
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountPurchasesQuotationIndex() {
    const element = document.getElementById('purchases-quotation-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesQuotationIndex, {
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            initialKpi: parse('kpi')        ?? null,
        })
    );
}

function mountPurchasesQuotationShow() {
    const element = document.getElementById('purchases-quotation-show-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesQuotationShow, {
            initialQuotation:  parse('quotation')  ?? {},
            suppliers:         parse('suppliers')  ?? [],
            initialAddresses:  parse('addresses')  ?? [],
            initialContacts:   parse('contacts')   ?? [],
            currency:          element.dataset.currency ?? 'EUR',
            endpoints:         parse('endpoints')  ?? {},
            trans:             parse('trans')       ?? {},
        })
    );
}

function mountPurchasesRequest() {
    const element = document.getElementById('purchases-request-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesRequest, {
            lastPurchaseCode:  element.dataset.lastPurchaseCode  ?? '',
            lastQuotationCode: element.dataset.lastQuotationCode ?? '',
            suppliers:         parse('suppliers')  ?? [],
            endpoints:         parse('endpoints')  ?? {},
            trans:             parse('trans')       ?? {},
        })
    );
}

function mountDeliverysRequest() {
    const element = document.getElementById('deliverys-request-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(DeliverysRequest, {
            initialCode:    element.dataset.code        ?? '',
            userId:         element.dataset.userId      ? parseInt(element.dataset.userId, 10) : null,
            users:          parse('users')              ?? [],
            companies:      parse('companies')          ?? [],
            canManageStock: element.dataset.canManageStock === 'true',
            endpoints:      parse('endpoints')          ?? {},
            trans:          parse('trans')              ?? {},
        })
    );
}

function mountInvoicesRequest() {
    const element = document.getElementById('invoices-request-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(InvoicesRequest, {
            initialCode: element.dataset.code   ?? '',
            userId:      element.dataset.userId ? parseInt(element.dataset.userId, 10) : null,
            users:       parse('users')         ?? [],
            companies:   parse('companies')     ?? [],
            endpoints:   parse('endpoints')     ?? {},
            trans:       parse('trans')         ?? {},
        })
    );
}

function mountDeliverysIndex() {
    const element = document.getElementById('deliverys-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(DeliverysIndex, {
            kpi:        parse('kpi')        ?? {},
            chartData:  parse('chart')      ?? {},
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: parse('companieId') ?? null,
        })
    );
}

function mountCreditNotesIndex() {
    const element = document.getElementById('credit-notes-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CreditNotesIndex, {
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountInvoicesIndex() {
    const element = document.getElementById('invoices-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(InvoicesIndex, {
            kpi:        parse('kpi')        ?? {},
            chartData:  parse('chart')      ?? {},
            topClients: parse('topClients') ?? [],
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
        })
    );
}

function mountProductsIndex() {
    const element = document.getElementById('products-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ProductsIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountCompaniesIndex() {
    const element = document.getElementById('companies-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompaniesIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountCompanyForm() {
    const element = document.getElementById('company-form-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyForm, {
            company:  parse('company')  ?? {},
            users:    parse('users')    ?? [],
            endpoint: element.dataset.endpoint ?? '',
            trans:    parse('trans')    ?? {},
        })
    );
}

function mountCompanyDashboard() {
    const element = document.getElementById('company-dashboard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyDashboard, {
            kpi:    parse('kpi')    ?? {},
            charts: parse('charts') ?? {},
            trans:  parse('trans')  ?? {},
        })
    );
}

function mountCompanyAddresses() {
    const element = document.getElementById('company-addresses-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyAddresses, {
            initialAddresses:     parse('addresses') ?? [],
            storeUrl:             element.dataset.storeUrl          ?? '',
            updateBaseUrl:        element.dataset.updateBaseUrl      ?? '',
            companieId:           element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
            trans:                parse('trans') ?? {},
            initialDocDefaults:   parse('documentDefaults') ?? {},
            syncAddressBaseUrl:   element.dataset.syncAddressBaseUrl ?? '',
        })
    );
}

function mountCompanyContacts() {
    const element = document.getElementById('company-contacts-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyContacts, {
            initialContacts:     parse('contacts') ?? [],
            storeUrl:            element.dataset.storeUrl           ?? '',
            updateBaseUrl:       element.dataset.updateBaseUrl       ?? '',
            companieId:          element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
            trans:               parse('trans') ?? {},
            initialDocDefaults:  parse('documentDefaults') ?? {},
            syncContactBaseUrl:  element.dataset.syncContactBaseUrl ?? '',
        })
    );
}

function mountSetupWizard() {
    const element = document.getElementById('setup-wizard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(SetupWizard, {
            endpoints: parse('endpoints') ?? {},
            initial:   parse('initial')   ?? {},
        })
    );
}

function mountQualityIndex() {
    const element = document.getElementById('quality-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QualityIndex, {
            kpi:                  parse('kpi')                 ?? {},
            rates:                parse('rates')               ?? {},
            statusCounts:         parse('statusCounts')        ?? {},
            topGenerators:        parse('topGenerators')       ?? {},
            calibrationAlerts:    parse('calibrationAlerts')   ?? {},
            initialFailures:      parse('initialFailures')     ?? [],
            initialCauses:        parse('initialCauses')       ?? [],
            initialCorrections:   parse('initialCorrections')  ?? [],
            endpoints:            parse('endpoints')           ?? {},
            trans:                parse('trans')               ?? {},
        })
    );
}

function mountQuoteLinesPage() {
    const element = document.getElementById('quote-lines-page-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QuoteLinesPage, {
            quoteId:    parseInt(element.dataset.quoteId, 10),
            quoteStatu: parseInt(element.dataset.quoteStatu, 10),
            endpoints:  parse('endpoints') ?? {},
        })
    );
}

function mountOrderLinesPage() {
    const element = document.getElementById('order-lines-page-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(OrderLinesPage, {
            orderId:        parseInt(element.dataset.orderId, 10),
            orderStatu:     parseInt(element.dataset.orderStatu, 10),
            orderType:      parseInt(element.dataset.orderType, 10),
            canManageStock: element.dataset.canManageStock === 'true',
            endpoints:      parse('endpoints') ?? {},
        })
    );
}

function mountLoadPlanningIndex() {
    const element = document.getElementById('load-planning-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(LoadPlanningIndex, {
            initial:          parse('initial')         ?? null,
            startDate:        element.dataset.startDate ?? '',
            endDate:          element.dataset.endDate   ?? '',
            displayHoursDiff: element.dataset.displayHoursDiff === 'true',
            endpoints:        parse('endpoints')        ?? {},
            trans:            parse('trans')            ?? {},
        })
    );
}

function mountHomeDashboard() {
    const element = document.getElementById('home-dashboard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    const props = parse('props') ?? {};
    createRoot(element).render(React.createElement(HomeDashboard, props));
}

function mountTasksIndex() {
    const element = document.getElementById('tasks-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(TasksIndex, {
            tasks:            parse('tasks')           ?? [],
            services:         parse('services')        ?? [],
            statuses:         parse('statuses')        ?? [],
            resources:        parse('resources')       ?? [],
            defaultStatusIds: parse('defaultStatusIds') ?? [],
            trans:            parse('trans')            ?? {},
        })
    );
}

function mountPurchaseReceiptIndex() {
    const element = document.getElementById('purchase-receipt-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchaseReceiptIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountConstructionSitePage() {
    const element = document.getElementById('construction-site-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ConstructionSitePage, {
            quoteId:     parseInt(element.dataset.quoteId, 10),
            saveUrl:     element.dataset.saveUrl ?? '',
            currency:    element.dataset.currency ?? 'EUR',
            initialData: parse('initialData') ?? null,
        })
    );
}

function mountSerialNumbersIndex() {
    const element = document.getElementById('serial-numbers-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(SerialNumbersIndex, {
            kpi:       parse('kpi')       ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountSerialNumbersEmbedded() {
    const element = document.getElementById('serial-numbers-embedded-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(SerialNumbersIndex, {
            kpi:       {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
            productId: element.dataset.productId ?? null,
        })
    );
}

function mountMethodsOverview() {
    const element = document.getElementById('methods-overview-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(MethodsOverview, {
            sections: parse('sections') ?? [],
            services: parse('services') ?? [],
            trans:    parse('trans')    ?? {},
        })
    );
}

mountSetupWizard();
mountCompanyDashboard();
mountCompanyForm();
mountCompanyAddresses();
mountCompanyContacts();
mountKanbanBoard();
mountDocumentTable();
mountCompaniesIndex();
mountWhiteboard();
mountNestingPage();
mountQuotesIndex();
mountLeadsIndex();
mountOpportunitiesIndex();
mountOrdersIndex();
mountPurchasesIndex();
mountPurchaseInvoicesIndex();
mountQuoteLinesIndex();
mountOrderLinesIndex();
mountDeliverysIndex();
mountCreditNotesIndex();
mountDeliverysRequest();
mountInvoicesRequest();
mountPurchasesQuotationIndex();
mountPurchasesQuotationShow();
mountPurchasesRequest();
mountInvoicesIndex();
mountProductsIndex();
mountQualityIndex();
mountQuoteLinesPage();
mountOrderLinesPage();
mountLoadPlanningIndex();
mountConstructionSitePage();
mountHomeDashboard();
mountTasksIndex();
mountPurchaseReceiptIndex();
mountSerialNumbersIndex();
mountSerialNumbersEmbedded();
mountMethodsOverview();

function mountGanttChart() {
    const element = document.getElementById('gantt-chart-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(GanttChart, {
            orderId:  element.dataset.orderId ? parseInt(element.dataset.orderId, 10) : null,
            apiBase:  element.dataset.apiBase ?? '/production/gantt/order',
            trans:    parse('trans') ?? {},
        })
    );
}

function mountTaskStatuApp() {
    const element = document.getElementById('task-statu-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    const taskId = element.dataset.taskId ? parseInt(element.dataset.taskId, 10) : null;

    createRoot(element).render(
        React.createElement(TaskStatuApp, {
            kpi:                  parse('kpi')              ?? {},
            userProductivity:     parse('userProductivity') ?? [],
            resourceHours:        parse('resourceHours')    ?? [],
            initialTaskId:        Number.isNaN(taskId) ? null : taskId,
            baseStatuUrl:         element.dataset.baseStatuUrl         ?? '',
            apiBaseUrl:           element.dataset.apiBaseUrl           ?? '',
            andonStoreUrl:        element.dataset.andonStoreUrl        ?? '',
            purchasesRequestUrl:  element.dataset.purchasesRequestUrl  ?? '',
            trans:                parse('trans')            ?? {},
        })
    );
}

mountTaskStatuApp();
mountGanttChart();

function mountNonConformitiesIndex() {
    const element = document.getElementById('non-conformities-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(NonConformitiesIndex, {
            endpoints:   parse('endpoints')   ?? {},
            nextCode:    element.dataset.nextCode ?? '',
            users:       parse('users')       ?? [],
            services:    parse('services')    ?? [],
            companies:   parse('companies')   ?? [],
            failures:    parse('failures')    ?? [],
            causes:      parse('causes')      ?? [],
            corrections: parse('corrections') ?? [],
            trans:       parse('trans')       ?? {},
        })
    );
}

mountNonConformitiesIndex();

function mountGmaoDashboard() {
    const element = document.getElementById('gmao-dashboard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(GmaoDashboard, {
            kpis:                  parse('kpis')               ?? [],
            workOrdersCount:       parse('workOrdersCount')    ?? {},
            recentWorkOrders:      parse('recentWorkOrders')   ?? [],
            maintenancePlansCount: element.dataset.maintenancePlansCount
                                   ? parseInt(element.dataset.maintenancePlansCount, 10)
                                   : 0,
            endpoints:             parse('endpoints')          ?? {},
        })
    );
}

mountGmaoDashboard();

function mountInspectionProjectsApp() {
    const element = document.getElementById('inspection-projects-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(InspectionProjectsApp, {
            endpoints:  parse('endpoints') ?? {},
            canApprove: parse('canApprove') ?? false,
        })
    );
}

mountInspectionProjectsApp();

function mountTaskManagePage() {
    const element = document.getElementById('task-manage-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(TaskManagePage, {
            context: {
                idType:   element.dataset.idType   ?? '',
                idPage:   element.dataset.idPage   ?? '',
                idLine:   element.dataset.idLine   ?? '',
                statu:    parseInt(element.dataset.statu ?? '0', 10),
                currency: element.dataset.currency ?? '€',
            },
            endpoints: parse('endpoints') ?? {},
        })
    );
}

mountTaskManagePage();

function mountProcessDiagramApp() {
    const element = document.getElementById('process-diagram-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ProcessDiagramApp, {
            endpoints: parse('endpoints') ?? {},
        })
    );
}

mountProcessDiagramApp();

function mountQuoteChartsTab() {
    const element = document.getElementById('quote-charts-tab-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QuoteChartsTab, {
            productTime: parse('productTime') ?? {},
            settingTime: parse('settingTime') ?? {},
            cost:        parse('cost')        ?? {},
            price:       parse('price')       ?? {},
            currency:    element.dataset.currency ?? '€',
            trans:       parse('trans')       ?? {},
        })
    );
}

function mountOrderChartsTab() {
    const element = document.getElementById('order-charts-tab-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QuoteChartsTab, {
            productTime: parse('productTime') ?? {},
            settingTime: parse('settingTime') ?? {},
            cost:        parse('cost')        ?? {},
            price:       parse('price')       ?? {},
            currency:    element.dataset.currency ?? '€',
            trans:       parse('trans')       ?? {},
        })
    );
}

mountQuoteChartsTab();
mountOrderChartsTab();

function mountUserProfilePage() {
    const el = document.getElementById('user-profile-app');
    if (!el) return;
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(UserProfilePage, {
            initial:   parse('initial')   ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountNotificationLinePage() {
    const el = document.getElementById('notification-line-app');
    if (!el) return;
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(NotificationLinePage, {
            initialNotifications: parse('notifications') ?? [],
            endpoints:            parse('endpoints')     ?? {},
            trans:                parse('trans')         ?? {},
        })
    );
}

function mountUserAutoEmailReportsPage() {
    const el = document.getElementById('user-auto-email-reports-app');
    if (!el) return;
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(UserAutoEmailReportsPage, {
            initialReports: parse('reports')     ?? {},
            reportTypes:    parse('reportTypes') ?? {},
            endpoints:      parse('endpoints')   ?? {},
            trans:          parse('trans')       ?? {},
        })
    );
}

mountUserProfilePage();
mountNotificationLinePage();
mountUserAutoEmailReportsPage();

function mountAuditPlannerApp() {
    const el = document.getElementById('audit-planner-app');
    if (!el) return;
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(AuditPlannerApp, {
            endpoints:  parse('endpoints')  ?? {},
            users:      parse('users')      ?? [],
            processes:  parse('processes')  ?? [],
            checklists: parse('checklists') ?? [],
            kpi:        parse('kpi')        ?? {},
            canAdmin:   parse('canAdmin')   ?? false,
        })
    );
}

mountAuditPlannerApp();
