import './bootstrap';
import { createRoot } from 'react-dom/client';
import React from 'react';

// livewire-sortable ne doit être chargé que sur les pages qui embarquent Livewire
document.addEventListener('livewire:init', () => {
    import('livewire-sortable');
});

function parseJsonAttribute(value) {
    if (!value) return null;
    try {
        return JSON.parse(value);
    } catch (error) {
        console.warn('Unable to parse JSON attribute', value, error);
        return null;
    }
}

async function mountKanbanBoard() {
    const element = document.getElementById('card');
    if (!element) return;

    const { default: KanbanBoard } = await import('./components/KanbanBoard.jsx');
    const initialData = element.dataset.initialData
        ? JSON.parse(element.dataset.initialData)
        : [];

    createRoot(element).render(
        React.createElement(KanbanBoard, { initialData })
    );
}

async function mountDocumentTable() {
    const element = document.getElementById('document-table-app');
    if (!element) return;

    const { default: DocumentTable } = await import('./components/DocumentTable.jsx');
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

async function mountWhiteboard() {
    const element = document.getElementById('whiteboard-app');
    if (!element) return;

    const [{ createApp, h }, { default: Whiteboard }] = await Promise.all([
        import('vue'),
        import('./components/Whiteboard.vue'),
    ]);

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

async function mountNestingPage() {
    const element = document.getElementById('nesting-app');
    if (!element) return;

    const { default: NestingPage } = await import('./components/NestingPage.jsx');
    createRoot(element).render(React.createElement(NestingPage));
}

async function mountQuotesIndex() {
    const element = document.getElementById('quotes-index-app');
    if (!element) return;

    const { default: QuotesIndex } = await import('./components/QuotesIndex.jsx');
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

async function mountLeadsIndex() {
    const element = document.getElementById('leads-index-app');
    if (!element) return;

    const { default: LeadsIndex } = await import('./components/LeadsIndex.jsx');
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

async function mountOpportunitiesIndex() {
    const element = document.getElementById('opportunities-index-app');
    if (!element) return;

    const { default: OpportunitiesIndex } = await import('./components/OpportunitiesIndex.jsx');
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

async function mountOrdersIndex() {
    const element = document.getElementById('orders-index-app');
    if (!element) return;

    const { default: OrdersIndex } = await import('./components/OrdersIndex.jsx');
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

async function mountPurchasesIndex() {
    const element = document.getElementById('purchases-index-app');
    if (!element) return;

    const { default: PurchasesIndex } = await import('./components/PurchasesIndex.jsx');
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

async function mountPurchaseInvoicesIndex() {
    const element = document.getElementById('purchase-invoices-index-app');
    if (!element) return;

    const { default: PurchaseInvoicesIndex } = await import('./components/PurchaseInvoicesIndex.jsx');
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

async function mountQuoteLinesIndex() {
    const element = document.getElementById('quote-lines-index-app');
    if (!element) return;

    const { default: QuoteLinesIndex } = await import('./components/QuoteLinesIndex.jsx');
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

async function mountOrderLinesIndex() {
    const element = document.getElementById('order-lines-index-app');
    if (!element) return;

    const { default: OrderLinesIndex } = await import('./components/OrderLinesIndex.jsx');
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

async function mountPurchasesQuotationIndex() {
    const element = document.getElementById('purchases-quotation-index-app');
    if (!element) return;

    const { default: PurchasesQuotationIndex } = await import('./components/PurchasesQuotationIndex.jsx');
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

async function mountPurchasesQuotationShow() {
    const element = document.getElementById('purchases-quotation-show-app');
    if (!element) return;

    const { default: PurchasesQuotationShow } = await import('./components/PurchasesQuotationShow.jsx');
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

async function mountPurchasesRequest() {
    const element = document.getElementById('purchases-request-app');
    if (!element) return;

    const { default: PurchasesRequest } = await import('./components/PurchasesRequest.jsx');
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

async function mountDeliverysRequest() {
    const element = document.getElementById('deliverys-request-app');
    if (!element) return;

    const { default: DeliverysRequest } = await import('./components/DeliverysRequest.jsx');
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

async function mountInvoicesRequest() {
    const element = document.getElementById('invoices-request-app');
    if (!element) return;

    const { default: InvoicesRequest } = await import('./components/InvoicesRequest.jsx');
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

async function mountDeliverysIndex() {
    const element = document.getElementById('deliverys-index-app');
    if (!element) return;

    const { default: DeliverysIndex } = await import('./components/DeliverysIndex.jsx');
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

async function mountCreditNotesIndex() {
    const element = document.getElementById('credit-notes-index-app');
    if (!element) return;

    const { default: CreditNotesIndex } = await import('./components/CreditNotesIndex.jsx');
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

async function mountInvoicesIndex() {
    const element = document.getElementById('invoices-index-app');
    if (!element) return;

    const { default: InvoicesIndex } = await import('./components/InvoicesIndex.jsx');
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

async function mountProductsIndex() {
    const element = document.getElementById('products-index-app');
    if (!element) return;

    const { default: ProductsIndex } = await import('./components/ProductsIndex.jsx');
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

async function mountProductHistory() {
    const element = document.getElementById('product-history-app');
    if (!element) return;

    const { default: ProductHistory } = await import('./components/ProductHistory.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ProductHistory, {
            endpoint:    element.dataset.endpoint    ?? '',
            trans:       parse('trans')              ?? {},
            canPurchases: element.dataset.canPurchases === 'true',
        })
    );
}

async function mountOrderPurchaseHistory() {
    const element = document.getElementById('order-purchase-history-app');
    if (!element) return;

    const { default: ProductHistory } = await import('./components/ProductHistory.jsx');

    createRoot(element).render(
        React.createElement(ProductHistory, {
            endpoint:     element.dataset.endpoint ?? '',
            canPurchases: true,
        })
    );
}

async function mountCompaniesIndex() {
    const element = document.getElementById('companies-index-app');
    if (!element) return;

    const { default: CompaniesIndex } = await import('./components/CompaniesIndex.jsx');
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

async function mountCompanyForm() {
    const element = document.getElementById('company-form-app');
    if (!element) return;

    const { default: CompanyForm } = await import('./components/CompanyForm.jsx');
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

async function mountCompanyDashboard() {
    const element = document.getElementById('company-dashboard-app');
    if (!element) return;

    const { default: CompanyDashboard } = await import('./components/CompanyDashboard.jsx');
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

async function mountCompanyAddresses() {
    const element = document.getElementById('company-addresses-app');
    if (!element) return;

    const { default: CompanyAddresses } = await import('./components/CompanyAddresses.jsx');
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

async function mountCompanyContacts() {
    const element = document.getElementById('company-contacts-app');
    if (!element) return;

    const { default: CompanyContacts } = await import('./components/CompanyContacts.jsx');
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

async function mountCompanyTimeline() {
    const element = document.getElementById('company-timeline-app');
    if (!element) return;

    const { default: CompanyTimeline } = await import('./components/CompanyTimeline.jsx');
    createRoot(element).render(
        React.createElement(CompanyTimeline, {
            endpoint: element.dataset.endpoint ?? '',
        })
    );
}

async function mountSetupWizard() {
    const element = document.getElementById('setup-wizard-app');
    if (!element) return;

    const { default: SetupWizard } = await import('./components/SetupWizard.jsx');
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

async function mountQualityIndex() {
    const element = document.getElementById('quality-index-app');
    if (!element) return;

    const { default: QualityIndex } = await import('./components/QualityIndex.jsx');
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

async function mountQuoteLinesPage() {
    const element = document.getElementById('quote-lines-page-app');
    if (!element) return;

    const { default: QuoteLinesPage } = await import('./components/QuoteLinesPage.jsx');
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

async function mountOrderLinesPage() {
    const element = document.getElementById('order-lines-page-app');
    if (!element) return;

    const { default: OrderLinesPage } = await import('./components/OrderLinesPage.jsx');
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

async function mountPurchaseLinesPage() {
    const element = document.getElementById('purchase-lines-page-app');
    if (!element) return;

    const { default: PurchaseLinesPage } = await import('./components/PurchaseLinesPage.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchaseLinesPage, {
            purchaseId:    parseInt(element.dataset.purchaseId, 10),
            purchaseStatu: parseInt(element.dataset.purchaseStatu, 10),
            endpoints:     parse('endpoints') ?? {},
        })
    );
}

async function mountPurchaseReceiptLinesPage() {
    const element = document.getElementById('purchase-receipt-lines-page-app');
    if (!element) return;

    const { default: PurchaseReceiptLinesPage } = await import('./components/PurchaseReceiptLinesPage.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchaseReceiptLinesPage, {
            receiptId:    parseInt(element.dataset.receiptId, 10),
            receiptCode:  element.dataset.receiptCode ?? '',
            receiptStatu: parseInt(element.dataset.receiptStatu, 10),
            userId:       parseInt(element.dataset.userId, 10),
            endpoints:    parse('endpoints') ?? {},
        })
    );
}

async function mountLoadPlanningIndex() {
    const element = document.getElementById('load-planning-app');
    if (!element) return;

    const { default: LoadPlanningIndex } = await import('./components/LoadPlanningIndex.jsx');
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

async function mountHomeDashboard() {
    const element = document.getElementById('home-dashboard-app');
    if (!element) return;

    const { default: HomeDashboard } = await import('./components/HomeDashboard.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    const props = parse('props') ?? {};
    createRoot(element).render(React.createElement(HomeDashboard, props));
}

async function mountTasksIndex() {
    const element = document.getElementById('tasks-index-app');
    if (!element) return;

    const { default: TasksIndex } = await import('./components/TasksIndex.jsx');
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

async function mountPurchaseReceiptIndex() {
    const element = document.getElementById('purchase-receipt-index-app');
    if (!element) return;

    const { default: PurchaseReceiptIndex } = await import('./components/PurchaseReceiptIndex.jsx');
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

async function mountPurchasesWaitingInvoice() {
    const element = document.getElementById('purchases-waiting-invoice-app');
    if (!element) return;

    const { default: PurchasesWaitingInvoice } = await import('./components/PurchasesWaitingInvoice.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesWaitingInvoice, {
            endpoints:   parse('endpoints')   ?? {},
            trans:       parse('trans')       ?? {},
            initialCode: parse('initialCode') ?? '',
        })
    );
}

async function mountPurchasesWaitingReceipt() {
    const element = document.getElementById('purchases-waiting-receipt-app');
    if (!element) return;

    const { default: PurchasesWaitingReceipt } = await import('./components/PurchasesWaitingReceipt.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(PurchasesWaitingReceipt, {
            endpoints:   parse('endpoints')   ?? {},
            trans:       parse('trans')       ?? {},
            initialCode: parse('initialCode') ?? '',
        })
    );
}

async function mountConstructionSitePage() {
    const element = document.getElementById('construction-site-app');
    if (!element) return;

    const { default: ConstructionSitePage } = await import('./components/ConstructionSitePage.jsx');
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

async function mountSerialNumbersIndex() {
    const element = document.getElementById('serial-numbers-index-app');
    if (!element) return;

    const { default: SerialNumbersIndex } = await import('./components/SerialNumbersIndex.jsx');
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

async function mountSerialNumbersEmbedded() {
    const element = document.getElementById('serial-numbers-embedded-app');
    if (!element) return;

    const { default: SerialNumbersIndex } = await import('./components/SerialNumbersIndex.jsx');
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

async function mountMethodsOverview() {
    const element = document.getElementById('methods-overview-app');
    if (!element) return;

    const { default: MethodsOverview } = await import('./components/MethodsOverview.jsx');
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

async function mountGanttChart() {
    const element = document.getElementById('gantt-chart-app');
    if (!element) return;

    const { default: GanttChart } = await import('./components/GanttChart.jsx');
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

async function mountTaskStatuApp() {
    const element = document.getElementById('task-statu-app');
    if (!element) return;

    const { default: TaskStatuApp } = await import('./components/TaskStatuApp.jsx');
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

async function mountNonConformitiesIndex() {
    const element = document.getElementById('non-conformities-index-app');
    if (!element) return;

    const { default: NonConformitiesIndex } = await import('./components/NonConformitiesIndex.jsx');
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

async function mountGmaoDashboard() {
    const element = document.getElementById('gmao-dashboard-app');
    if (!element) return;

    const { default: GmaoDashboard } = await import('./components/GmaoDashboard.jsx');
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

async function mountInspectionProjectsApp() {
    const element = document.getElementById('inspection-projects-app');
    if (!element) return;

    const { default: InspectionProjectsApp } = await import('./components/InspectionProjectsApp.jsx');
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

async function mountTaskManagePage() {
    const element = document.getElementById('task-manage-app');
    if (!element) return;

    const { default: TaskManagePage } = await import('./components/TaskManagePage.jsx');
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

async function mountProcessDiagramApp() {
    const element = document.getElementById('process-diagram-app');
    if (!element) return;

    const { default: ProcessDiagramApp } = await import('./components/ProcessDiagramApp.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ProcessDiagramApp, {
            endpoints: parse('endpoints') ?? {},
        })
    );
}

async function mountQuoteChartsTab() {
    const element = document.getElementById('quote-charts-tab-app');
    if (!element) return;

    const { default: QuoteChartsTab } = await import('./components/QuoteChartsTab.jsx');
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

async function mountOrderChartsTab() {
    const element = document.getElementById('order-charts-tab-app');
    if (!element) return;

    const { default: QuoteChartsTab } = await import('./components/QuoteChartsTab.jsx');
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

async function mountUserProfilePage() {
    const el = document.getElementById('user-profile-app');
    if (!el) return;

    const { default: UserProfilePage } = await import('./components/UserProfilePage.jsx');
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(UserProfilePage, {
            initial:   parse('initial')   ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

async function mountNotificationLinePage() {
    const el = document.getElementById('notification-line-app');
    if (!el) return;

    const { default: NotificationLinePage } = await import('./components/NotificationLinePage.jsx');
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };
    createRoot(el).render(
        React.createElement(NotificationLinePage, {
            initialNotifications: parse('notifications') ?? [],
            endpoints:            parse('endpoints')     ?? {},
            trans:                parse('trans')         ?? {},
        })
    );
}

async function mountUserAutoEmailReportsPage() {
    const el = document.getElementById('user-auto-email-reports-app');
    if (!el) return;

    const { default: UserAutoEmailReportsPage } = await import('./components/UserAutoEmailReportsPage.jsx');
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

async function mountAuditPlannerApp() {
    const el = document.getElementById('audit-planner-app');
    if (!el) return;

    const { default: AuditPlannerApp } = await import('./components/AuditPlannerApp.jsx');
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

async function mountEstimatedBudgetsIndex() {
    const element = document.getElementById('estimated-budgets-app');
    if (!element) return;

    const { default: EstimatedBudgetsIndex } = await import('./components/EstimatedBudgetsIndex.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(EstimatedBudgetsIndex, {
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

async function mountStockDetailPage() {
    const el = document.getElementById('stock-detail-app');
    if (!el) return;

    const { default: StockDetailPage } = await import('./components/StockDetailPage.jsx');
    const parse = (attr) => { try { return JSON.parse(el.dataset[attr] ?? 'null'); } catch { return null; } };

    createRoot(el).render(
        React.createElement(StockDetailPage, {
            initial:   parse('initial')   ?? {},
            endpoints: parse('endpoints') ?? {},
        })
    );
}

async function mountChatWidget() {
    // Le widget est global : il crée son propre container sur document.body
    // et s'affiche uniquement si l'utilisateur est authentifié (présence du meta csrf-token)
    const hasCsrf = !!document.querySelector('meta[name="csrf-token"]');
    if (!hasCsrf) return;

    // Détecte le locale depuis l'URL (/fr/..., /en/...) — mcamara/laravel-localization
    const localeMatch = window.location.pathname.match(/^\/([a-z]{2})\//);
    const locale = localeMatch ? localeMatch[1] : 'fr';

    // Lit les permissions depuis la meta tag injectée par le backend
    const permMeta = document.querySelector('meta[name="user-permissions"]');
    const permissions = permMeta ? JSON.parse(permMeta.content) : [];

    const { default: ChatWidget } = await import('./components/ai/ChatWidget.jsx');
    const container = document.createElement('div');
    container.id = 'ai-chat-widget-root';
    document.body.appendChild(container);
    createRoot(container).render(React.createElement(ChatWidget, { locale, permissions }));
}

async function mountDeliveryLinesTab() {
    const element = document.getElementById('delivery-lines-tab-app');
    if (!element) return;

    const { default: DeliveryLinesTab } = await import('./components/DeliveryLinesTab.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(DeliveryLinesTab, {
            lines:            parse('lines')           ?? [],
            deliveryId:       Number(element.dataset.deliveryId ?? 0),
            nonConformities:  parse('nonConformities') ?? [],
            endpoints:        parse('endpoints')       ?? {},
            trans:            parse('trans')           ?? {},
        })
    );
}

async function mountReturnsIndex() {
    const element = document.getElementById('returns-index-app');
    if (!element) return;

    const { default: ReturnsIndex } = await import('./components/ReturnsIndex.jsx');
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ReturnsIndex, {
            endpoints: parse('endpoints') ?? {},
            props:     parse('props')     ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

// --- Bootstrap : montage de tous les composants ---
mountSetupWizard();
mountCompanyDashboard();
mountCompanyForm();
mountCompanyAddresses();
mountCompanyContacts();
mountCompanyTimeline();
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
mountPurchaseLinesPage();
mountPurchaseReceiptLinesPage();
mountLoadPlanningIndex();
mountConstructionSitePage();
mountHomeDashboard();
mountTasksIndex();
mountPurchaseReceiptIndex();
mountPurchasesWaitingReceipt();
mountPurchasesWaitingInvoice();
mountSerialNumbersIndex();
mountSerialNumbersEmbedded();
mountMethodsOverview();
mountTaskStatuApp();
mountGanttChart();
mountNonConformitiesIndex();
mountGmaoDashboard();
mountInspectionProjectsApp();
mountTaskManagePage();
mountProcessDiagramApp();
mountQuoteChartsTab();
mountOrderChartsTab();
mountUserProfilePage();
mountNotificationLinePage();
mountUserAutoEmailReportsPage();
mountAuditPlannerApp();
mountEstimatedBudgetsIndex();
mountChatWidget();
mountProductHistory();
mountOrderPurchaseHistory();
mountStockDetailPage();
mountReturnsIndex();
mountDeliveryLinesTab();
